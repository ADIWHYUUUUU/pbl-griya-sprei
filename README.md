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

## Auto-cancel pesanan kedaluwarsa (Supabase Cron)

Auto-cancel dijalankan langsung oleh PostgreSQL Supabase melalui `pg_cron`, bukan oleh Vercel atau GitHub Actions. Job berjalan setiap lima menit dan memanggil `public.auto_cancel_expired_orders()`.

Aturan pembatalan:

- `MENUNGGU_VERIFIKASI`: dibatalkan setelah 48 jam sejak `tanggal_pesan`.
- `MENUNGGU_PEMBAYARAN`: dibatalkan setelah 48 jam sejak `tanggal_verifikasi_admin`; bila pembayaran terakhir ditolak, hitungan 48 jam dimulai ulang dari `tanggal_verifikasi` pembayaran yang ditolak.
- Pesanan dengan pembayaran berstatus `MENUNGGU_VERIFIKASI` atau `TERVERIFIKASI` tidak dibatalkan.
- Status selain `MENUNGGU_VERIFIKASI` dan `MENUNGGU_PEMBAYARAN` tidak disentuh.
- Data pesanan dan riwayat pembayaran tidak dihapus.

### Instalasi satu kali

1. Buka **Supabase Dashboard → SQL Editor** pada project database yang dipakai aplikasi.
2. Jalankan migration `database/migrations/25_auto_cancel_expired_orders.sql`.
3. Pastikan ekstensi **pg_cron** aktif. Buka **Integrations → Cron** atau **Database → Extensions**, lalu aktifkan `pg_cron` bila belum aktif.
4. Kembali ke SQL Editor, jalankan `database/jobs/schedule_auto_cancel_expired_orders.sql`.
5. Buka **Integrations → Cron → Jobs** dan pastikan job `auto-cancel-expired-orders` aktif dengan jadwal `*/5 * * * *`.

### Memantau job

Periksa 20 eksekusi terakhir:

```sql
SELECT
    d.runid,
    d.status,
    d.start_time,
    d.end_time,
    d.return_message
FROM cron.job_run_details AS d
JOIN cron.job AS j ON j.jobid = d.jobid
WHERE j.jobname = 'auto-cancel-expired-orders'
ORDER BY d.start_time DESC
LIMIT 20;
```

Pastikan job terdaftar dan aktif:

```sql
SELECT jobid, jobname, schedule, active, command
FROM cron.job
WHERE jobname = 'auto-cancel-expired-orders';
```

Untuk menjalankan fungsi secara manual dari SQL Editor:

```sql
SELECT public.auto_cancel_expired_orders();
```

**Perhatian:** perintah manual di atas akan benar-benar membatalkan semua pesanan yang sudah kedaluwarsa. Gunakan hanya setelah memeriksa data produksi.

Script `scripts/auto_cancel_orders.php` tetap tersedia untuk eksekusi manual melalui PHP CLI. Script tersebut memanggil fungsi database yang sama agar aturan bisnis tidak memiliki dua versi. Scheduler GitHub Actions lama sudah dihapus agar tidak ada dua scheduler yang berjalan bersamaan.

### Konsistensi waktu

Kolom waktu pesanan dan pembayaran memakai `TIMESTAMP WITHOUT TIME ZONE` dan aplikasi menganggap nilainya UTC. `app/config/database.php` sekarang mengatur zona waktu setiap koneksi PostgreSQL ke UTC. Fungsi database juga membandingkan batas waktu dengan UTC secara eksplisit.

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
