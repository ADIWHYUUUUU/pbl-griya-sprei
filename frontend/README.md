# Frontend

Antarmuka aplikasi Griya Sprei. Halaman menggunakan Bootstrap 5 melalui CDN;
akses internet diperlukan agar stylesheet dan komponen Bootstrap dimuat.

## Menjalankan

Jalankan server lokal dari root repository agar halaman frontend dan endpoint backend berada pada origin yang sama:

```sh
php -S localhost:8080 -t .
```

Kemudian buka `http://localhost:8080/frontend/pages/user/index.php`.

## Struktur

- `assets/css/` - stylesheet aplikasi
- `assets/js/` - JavaScript aplikasi
- `assets/images/` - gambar dan aset visual
- `pages/` - halaman tambahan
