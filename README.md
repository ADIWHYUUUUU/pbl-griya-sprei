# Reset Password Customer

Fitur reset password customer memakai OTP 6 digit melalui Brevo. Seluruh informasi OTP/reset disimpan sebagai kolom tambahan pada tabel `"user"`; tidak ada tabel baru. Pembatasan IP disimpan di file lokal `storage/password-reset-<hash>.json` (tanpa alamat IP mentah).

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

Jalankan dari root proyek, sesuaikan koneksi agar sama dengan `.env`:

```powershell
psql -h localhost -p 5432 -U postgres -d pbl_siprei -f database/migrations/15_user_password_reset_columns.sql
```

Atau buka file `database/migrations/15_user_password_reset_columns.sql` di pgAdmin Query Tool pada database aplikasi dan jalankan. Migration menambahkan kolom OTP, masa berlaku, jumlah percobaan, waktu pengiriman, izin reset, dan penghitung batas akun ke tabel `"user"` yang sudah ada.

## Uji di Laragon

1. Jalankan Apache dan PostgreSQL di Laragon, isi konfigurasi Brevo, jalankan migration, lalu buka `http://localhost/?page=customer-login`.
2. Klik **Lupa password?**. Masukkan email customer, lalu **Kirim kode**. Halaman OTP menampilkan pesan yang sama untuk email terdaftar/tidak terdaftar: “Jika email terdaftar, kode verifikasi akan dikirim.”
3. Pada akun terdaftar, periksa inbox, masukkan kode 6 digit, lalu klik **Verifikasi**. Kode salah ditolak; setelah 5 percobaan kode gugur. Kode berlaku 10 menit.
4. Untuk menguji kedaluwarsa tanpa menunggu, setelah meminta kode jalankan di pgAdmin:

   ```sql
   UPDATE "user"
   SET password_reset_otp_expires_at = CURRENT_TIMESTAMP - INTERVAL '1 minute'
   WHERE email = 'email-customer-anda';
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
