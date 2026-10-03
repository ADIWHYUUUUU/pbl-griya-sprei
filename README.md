# pbl-griya-sprei
Sistem informasi katalog dan pemesanan online toko sprei berbasis web untuk memudahkan transaksi pelanggan dan pengelolaan produk.

## Struktur proyek

```text
frontend/                   Antarmuka web (HTML, CSS, JavaScript, Bootstrap 5)
  assets/
    css/
    images/
    js/
  pages/
backend/                    Backend PHP native
  app/
    config/
    controllers/
    helpers/
    models/
    views/
  public/                   Document root dan entry point
  routes/
  storage/
    logs/
```

Frontend dan backend dipisahkan agar aset publik dan kode server memiliki
batas yang jelas. Petunjuk menjalankan masing-masing bagian tersedia di
`frontend/README.md` dan `backend/README.md`.
