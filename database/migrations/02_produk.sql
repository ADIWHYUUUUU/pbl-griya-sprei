CREATE TYPE kategori_produk_enum AS ENUM (
    'SPREI', 'BED_COVER'
);

CREATE TABLE produk (
    id_produk       SERIAL PRIMARY KEY,
    nama_produk     VARCHAR(150)         NOT NULL,
    kategori_produk kategori_produk_enum NOT NULL,
    panjang_standar INTEGER              NOT NULL CHECK (panjang_standar > 0),
    lebar_standar   INTEGER              NOT NULL CHECK (lebar_standar > 0),
    tinggi_standar  INTEGER              NOT NULL CHECK (tinggi_standar > 0),
    harga_dasar     NUMERIC(12,2)        NOT NULL CHECK (harga_dasar > 0),
    stok            INTEGER              NOT NULL DEFAULT 0 CHECK (stok >= 0),
    deskripsi       TEXT,
    gambar_produk   VARCHAR(255)         NOT NULL,
    created_at      TIMESTAMP            NOT NULL DEFAULT now()
);