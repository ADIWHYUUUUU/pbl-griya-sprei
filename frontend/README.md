# Frontend

Antarmuka aplikasi Griya Sprei. Halaman HTML dan PHP berbagi session PHP untuk
alur keranjang, checkout, dan pesanan, sehingga aplikasi harus dijalankan lewat
server PHP. Akses internet diperlukan untuk memuat font dari Google Fonts.

## Menjalankan

Jalankan server lokal dari root repository:

```sh
php -S localhost:8080 -t frontend
```

Buka `http://localhost:8080` di browser. Jangan membuka berkas HTML langsung
dengan `file://`, karena halaman PHP dan session keranjang tidak akan berjalan.
Untuk melihat panel admin, buka `http://localhost:8080/produk/Katalog.php`
atau halaman pesanan admin di `http://localhost:8080/produk/CekPesanan.php`.
Antrean bukti transfer dan verifikasinya tersedia di
`http://localhost:8080/produk/CekPembayaran.php`.
Editor beranda dan unggah foto tersedia di
`http://localhost:8080/produk/EditBeranda.php`. Form kalkulasi pembayaran dapat
dibuka dari tombol `Form Pembayaran` pada daftar pesanan atau pembayaran.

## Struktur

- `assets/css/` - stylesheet aplikasi
- `assets/js/` - JavaScript aplikasi
- `assets/images/` - gambar dan aset visual
- `pages/` - halaman tambahan
