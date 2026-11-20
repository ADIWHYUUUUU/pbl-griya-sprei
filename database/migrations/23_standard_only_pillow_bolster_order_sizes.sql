BEGIN;

ALTER TABLE detail_pesanan
    ALTER COLUMN tinggi DROP NOT NULL;

ALTER TABLE detail_pesanan
    DROP CONSTRAINT IF EXISTS detail_pesanan_tinggi_check,
    DROP CONSTRAINT IF EXISTS detail_pesanan_tinggi_positive_or_null;

ALTER TABLE detail_pesanan
    ADD CONSTRAINT detail_pesanan_tinggi_positive_or_null
    CHECK (tinggi IS NULL OR tinggi > 0);

UPDATE detail_pesanan AS d
SET tinggi = NULL,
    tipe_ukuran = 'STANDAR'
FROM produk AS p
WHERE p.id_produk = d.id_produk
  AND p.kategori_produk::text IN (
      'SARUNG_BANTAL',
      'SARUNG_GULING',
      'SARUNG_BANTAL_GULING'
  );

COMMIT;
