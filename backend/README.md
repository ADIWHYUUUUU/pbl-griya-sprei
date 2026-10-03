# Backend

Backend aplikasi menggunakan PHP native tanpa framework.

## Menjalankan

Dari root repository, jalankan:

```sh
php -S localhost:8000 -t backend/public
```

Kemudian buka `http://localhost:8000`.

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
