# Reset Password Customer

Fitur reset password customer memakai OTP 6 digit melalui Brevo. Informasi OTP/reset disimpan pada tabel `password_reset_state`, terpisah dari tabel `"user"`. Pembatasan IP disimpan di file lokal `storage/password-reset-<hash>.json` (tanpa alamat IP mentah).

## Konfigurasi Brevo

Aplikasi memuat `.env` di root proyek melalui `app/config/database.php`. Isi nilai berikut di file `.env` lokal, yang diabaikan Git:

```dotenv
BREVO_API_KEY=
BREVO_SENDER_EMAIL=
BREVO_SENDER_NAME=
PASSWORD_RESET_OTP_SECRET=
```

`BREVO_SENDER_EMAIL` harus merupakan alamat pengirim terverifikasi di Brevo. Buat secret acak minimal 32 karakter dengan:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Salin hasilnya ke `PASSWORD_RESET_OTP_SECRET`. Aktifkan ekstensi PHP cURL. Aplikasi mengirim melalui `https://api.brevo.com/v3/smtp/email`, timeout 10 detik. Diagnostik pengiriman ditulis ke error log tanpa API key, OTP, atau password.

## Migration PostgreSQL

Untuk database baru, jalankan migration 15 terlebih dahulu, lalu migration 21. Untuk database yang sebelumnya sudah menjalankan migration 15, jalankan migration 21. Migration 21 memindahkan kolom OTP/reset yang ditambahkan migration 15 ke tabel `password_reset_state`, lalu menghapus kolom-kolom tersebut dari `"user"`.

```powershell
psql -h localhost -p 5432 -U postgres -d pbl_siprei -f database/migrations/21_password_reset_state_table.sql
```

Atau buka file `database/migrations/21_password_reset_state_table.sql` di pgAdmin Query Tool pada database aplikasi dan jalankan.

## Uji di Laragon

1. Jalankan Apache dan PostgreSQL di Laragon, isi konfigurasi Brevo, jalankan migration, lalu buka `http://localhost/?page=customer-login`.
2. Klik **Lupa password?**. Masukkan email customer, lalu **Kirim kode**. Halaman OTP menampilkan pesan yang sama untuk email terdaftar/tidak terdaftar: “Jika email terdaftar, kode verifikasi akan dikirim.”
3. Pada akun terdaftar, periksa inbox, masukkan kode 6 digit, lalu klik **Verifikasi**. Kode salah ditolak; setelah 5 percobaan kode gugur. Kode berlaku 10 menit.
4. Untuk menguji kedaluwarsa tanpa menunggu, setelah meminta kode jalankan di pgAdmin:

   ```sql
   UPDATE password_reset_state AS reset
   SET password_reset_otp_expires_at = CURRENT_TIMESTAMP - INTERVAL '1 minute'
   FROM "user" AS account
   WHERE account.id_user = reset.id_user
     AND account.email = 'email-customer-anda';
   ```

5. Untuk menguji kode lama, tunggu minimal 60 detik hingga tombol **Kirim ulang kode** aktif, minta kode baru, lalu pastikan kode lama ditolak dan kode terbaru diterima.
6. Akses langsung `http://localhost/?page=customer-password-reset` tanpa verifikasi harus dialihkan kembali ke form lupa password. Setelah OTP benar, buat password baru dan konfirmasi.
7. Login kembali dari halaman login menggunakan password baru. Hash yang disimpan kompatibel dengan `password_verify()` yang sudah dipakai.

Pengiriman email nyata belum teruji sampai API key Brevo dan alamat pengirim terverifikasi diisi pada `.env`.

## Auto-cancel pesanan kedaluwarsa

Job `scripts/auto_cancel_orders.php` membatalkan pesanan yang berumur **lebih dari 48 jam** dan masih berstatus `MENUNGGU_VERIFIKASI` atau `MENUNGGU_PEMBAYARAN`. Waktu dihitung sejak `tanggal_pesan`. Pesanan dengan pembayaran yang sedang menunggu verifikasi atau sudah terverifikasi, serta pesanan yang sudah diproses/dikirim/selesai, tidak dibatalkan. Data pesanan dan riwayat pembayaran tidak dihapus.

Atur cron job pada hosting untuk menjalankan script setiap lima menit. Contoh untuk cPanel (sesuaikan lokasi PHP, direktori aplikasi, dan lokasi log):

```cron
*/5 * * * * /usr/bin/php /home/USER/path-to-app/scripts/auto_cancel_orders.php >> /home/USER/auto-cancel-orders.log 2>&1
```

Pastikan job memakai konfigurasi database aplikasi (`.env` atau environment variables hosting) dan PHP CLI memiliki ekstensi PDO PostgreSQL. Karena job berjalan berkala, pembatalan dapat terjadi beberapa menit setelah batas 48 jam.

## Supabase Storage

Upload baru untuk gambar produk dan bukti pembayaran disimpan di Supabase Storage. Buat bucket `produk` dengan akses **public** dan bucket `bukti-pembayaran` dengan akses **private** di dashboard Supabase. Konfigurasikan URL project dan secret key server-side di `.env`:

```dotenv
SUPABASE_URL=https://<project-ref>.supabase.co
SUPABASE_SECRET_KEY=
SUPABASE_PRODUCT_BUCKET=produk
SUPABASE_PAYMENT_BUCKET=bukti-pembayaran
```

Jangan masukkan secret key ke kode browser atau commit ke Git. Bukti pembayaran private ditampilkan ke Admin melalui signed URL yang berlaku lima menit. Nilai gambar yang sudah tersimpan lokal tetap didukung; upload baru memakai Supabase.

## Alamat Default Checkout

Alamat pengiriman pada checkout disimpan sebagai alamat default akun setelah pesanan berhasil dibuat. Untuk database yang sudah ada, jalankan `database/migrations/18_user_shipping_address.sql` satu kali melalui pgAdmin atau `psql`.

## Harga Kain dan Ukuran

Harga ukuran standar untuk Sprei dan Bed Cover ditampilkan berdasarkan tabel harga kain di `app/helpers/product_pricing.php`. Katalog menampilkan harga paling rendah dari semua kombinasi kain dan ukuran. Ukuran custom tetap dikonfirmasi Admin.

Untuk database yang sudah ada, jalankan `database/migrations/19_enum_jenis_kain_dan_tipe_ukuran.sql` satu kali. Migration ini memastikan `produk.jenis_kain` dan `detail_pesanan.tipe_ukuran` memakai enum yang sudah dipakai aplikasi; tidak menambah kolom dan tidak mengubah dimensi angka.

## Deploy ke Vercel

Aplikasi dijalankan sebagai Container Vercel menggunakan FrankenPHP. `Dockerfile` dan `Caddyfile` berada di root proyek; `vercel.json` tidak diperlukan. Pilih preset **Container** pada pengaturan proyek Vercel jika preset tidak terdeteksi otomatis.

Tambahkan environment variables yang diperlukan aplikasi di **Vercel → Project Settings → Environment Variables**. Masukkan nilai `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, dan konfigurasi Brevo/Supabase yang digunakan. Jangan commit `.env` atau secret ke Git. Setelah itu deploy ulang branch `main`.

Filesystem container tidak persisten dan dapat berbeda antar-instance. Upload baru harus menggunakan Supabase Storage, bukan direktori lokal. Sesi PHP bawaan saat ini tersimpan sebagai file lokal; untuk penggunaan produksi yang dapat diskalakan, konfigurasi session store bersama (misalnya Redis) agar login dan keranjang tetap konsisten antar-instance.
