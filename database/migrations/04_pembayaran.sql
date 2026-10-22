CREATE TABLE pembayaran (
    id_pembayaran        SERIAL PRIMARY KEY,
    id_pesanan           INTEGER                 NOT NULL REFERENCES pesanan(id_pesanan) ON DELETE RESTRICT,
    metode_pembayaran    metode_pembayaran_enum  NOT NULL,
    nominal_transfer     NUMERIC(12,2)           NOT NULL CHECK (nominal_transfer > 0),
    bukti_transfer       VARCHAR(255)            NOT NULL,
    status_pembayaran    status_pembayaran_enum  NOT NULL DEFAULT 'MENUNGGU_VERIFIKASI',
    tanggal_upload       TIMESTAMP               NOT NULL DEFAULT now(),
    tanggal_verifikasi   TIMESTAMP               NOT NULL DEFAULT now(),
    catatan_admin        TEXT
);

CREATE TYPE metode_pembayaran_enum AS ENUM (
    'TRANSFER_BCA', 'TRANSFER_BRI', 'TRANSFER_BSI', 'TRANSFER_MANDIRI'
);

CREATE TYPE status_pembayaran_enum AS ENUM (
    'MENUNGGU_VERIFIKASI', 'TERVERIFIKASI', 'DITOLAK'
);