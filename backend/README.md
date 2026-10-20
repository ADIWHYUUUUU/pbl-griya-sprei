# Backend

Backend aplikasi menggunakan PHP native tanpa framework.

## Menjalankan

Dari root repository, jalankan server dengan document root repositori agar endpoint di `backend/actions/` dapat diakses bersama frontend:

```sh
php -S localhost:8000 -t .
```

Kemudian buka `http://localhost:8000/frontend/pages/user/index.php`.

## Struktur

- `public/` - document root dan entry point aplikasi
- `app/config/` - konfigurasi aplikasi dan database
- `app/controllers/` - pengendali permintaan
- `app/models/` - akses dan logika data
- `app/views/` - template tampilan yang dirender oleh PHP
- `app/helpers/` - fungsi bantuan yang dipakai bersama
- `routes/` - definisi rute aplikasi
- `storage/logs/` - log aplikasi; jangan letakkan di dalam `public/`

Konfigurasi kredensial database nantinya disimpan di luar kontrol versi, bukan
ditulis langsung ke repository.
