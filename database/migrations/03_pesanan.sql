CREATE TABLE pesanan (
    id_pesanan               SERIAL PRIMARY KEY,
    id_user                  INTEGER             NOT NULL REFERENCES "user"(id_user) ON DELETE RESTRICT,
    no_invoice               VARCHAR(30)         UNIQUE,
    tanggal_pesan            TIMESTAMP           NOT NULL DEFAULT now(),
    status_pesanan           status_pesanan_enum NOT NULL DEFAULT 'MENUNGGU_VERIFIKASI',
    tanggal_verifikasi_admin TIMESTAMP           NOT NULL DEFAULT now(),
    alamat_pengiriman        TEXT                NOT NULL,
    catatan_alamat           TEXT,
    metode_pengiriman        metode_pengiriman_enum NOT NULL,
    catatan_pengiriman       TEXT,
    nomor_resi               VARCHAR(50),
    diskon                   NUMERIC(12,2)       NOT NULL DEFAULT 0 CHECK (diskon >= 0),
    ongkir                   NUMERIC(12,2)       NOT NULL DEFAULT 0 CHECK (ongkir >= 0)
);

CREATE TYPE status_pesanan_enum AS ENUM (
    'MENUNGGU_VERIFIKASI', 'MENUNGGU_PEMBAYARAN',
    'DIPROSES', 'DIKIRIM', 'SELESAI', 'DIBATALKAN'
);

CREATE TYPE metode_pengiriman_enum AS ENUM (
    'INSTANT_GOJEK', 'EKSPEDISI_JNE', 'EKSPEDISI_JNT'
);