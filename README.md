# Pemulihan Kata Sandi Customer

Migrasi kolom token reset pada tabel `"user"`:

```powershell
psql -h localhost -p 5432 -U postgres -d pbl_siprei -f database/migrations/15_password_reset_columns.sql
```

Isi konfigurasi `APP_BASE_URL`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`,
`SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM_EMAIL`, dan `SMTP_FROM_NAME` di
`.env`. Link reset berlaku 10 menit dan tiap akun dibatasi satu permintaan per
menit. Email tetap memberikan pesan yang sama untuk alamat yang terdaftar dan
tidak terdaftar.

PHPMailer dipasang melalui Composer. Untuk mengirim email melalui Gmail, isi
konfigurasi SMTP berikut di `.env`:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=starttls
SMTP_USERNAME=alamat-gmail
SMTP_PASSWORD=google-app-password
SMTP_FROM_EMAIL=alamat-gmail
SMTP_FROM_NAME=Griya Sprei
```

Gunakan alamat Gmail yang sama untuk username dan pengirim, serta App Password
Google (bukan password login Gmail) sebagai password SMTP. App Password
memerlukan Verifikasi 2 Langkah pada akun Google.

Untuk menguji email secara lokal dengan Mailpit, jalankan:

```powershell
docker run --rm -p 1025:1025 -p 8025:8025 axllent/mailpit
```

Atur `SMTP_HOST=127.0.0.1`, `SMTP_PORT=1025`, `SMTP_ENCRYPTION=none`,
kosongkan `SMTP_USERNAME` dan `SMTP_PASSWORD`, dan sesuaikan `APP_BASE_URL`
dengan alamat aplikasi. Buka inbox Mailpit di `http://localhost:8025`, lalu
kirim permintaan reset dari halaman lupa kata sandi customer.