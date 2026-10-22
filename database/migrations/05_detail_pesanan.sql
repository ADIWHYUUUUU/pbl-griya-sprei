CREATE TABLE detail_pesanan (
    id_detail       SERIAL PRIMARY KEY,
    id_pesanan      INTEGER           NOT NULL REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    id_produk       INTEGER           NOT NULL REFERENCES produk(id_produk) ON DELETE RESTRICT,
    tipe_ukuran     tipe_ukuran_enum  NOT NULL,
    jumlah          INTEGER           NOT NULL CHECK (jumlah > 0),
    harga_satuan    NUMERIC(12,2)              CHECK (harga_satuan >= 0),
    panjang         INTEGER           NOT NULL CHECK (panjang > 0),
    lebar           INTEGER           NOT NULL CHECK (lebar > 0),
    tinggi          INTEGER           NOT NULL CHECK (tinggi > 0),
    tambah_sarban   INTEGER           NOT NULL DEFAULT 0 CHECK (tambah_sarban >= 0),
    tambah_sargul   INTEGER           NOT NULL DEFAULT 0 CHECK (tambah_sargul >= 0),
    catatan_pesanan TEXT
);

CREATE TYPE tipe_ukuran_enum AS ENUM ('STANDAR', 'CUSTOM');