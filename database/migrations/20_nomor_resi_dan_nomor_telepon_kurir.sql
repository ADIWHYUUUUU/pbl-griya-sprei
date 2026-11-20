ALTER TABLE pesanan
    ADD COLUMN IF NOT EXISTS nomor_telepon_kurir VARCHAR(50);

UPDATE pesanan
SET nomor_telepon_kurir = nomor_resi,
    nomor_resi = NULL
WHERE metode_pengiriman::text = 'INSTANT_GOJEK'
  AND nomor_resi IS NOT NULL
  AND BTRIM(nomor_resi) <> '';
