ALTER TABLE pembayaran
    ADD COLUMN bukti_nama_asli VARCHAR(255),
    ADD COLUMN bukti_ukuran BIGINT CHECK (bukti_ukuran IS NULL OR bukti_ukuran >= 0),
    ADD COLUMN bukti_mime VARCHAR(100);
