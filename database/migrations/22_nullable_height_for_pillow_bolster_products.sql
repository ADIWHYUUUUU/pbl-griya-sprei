ALTER TABLE produk
    ALTER COLUMN tinggi_standar DROP NOT NULL;

ALTER TABLE produk
    DROP CONSTRAINT IF EXISTS produk_tinggi_standar_check,
    DROP CONSTRAINT IF EXISTS produk_tinggi_standar_positive_or_null;

ALTER TABLE produk
    ADD CONSTRAINT produk_tinggi_standar_positive_or_null
    CHECK (tinggi_standar IS NULL OR tinggi_standar > 0);

UPDATE produk
SET tinggi_standar = NULL
WHERE kategori_produk::text IN (
    'SARUNG_BANTAL',
    'SARUNG_GULING',
    'SARUNG_BANTAL_GULING'
);
