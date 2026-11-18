ALTER TABLE "user"
    ADD COLUMN IF NOT EXISTS alamat_pengiriman TEXT;

UPDATE "user" AS u
SET alamat_pengiriman = (
    SELECT p.alamat_pengiriman
    FROM pesanan AS p
    WHERE p.id_user = u.id_user
    ORDER BY p.tanggal_pesan DESC, p.id_pesanan DESC
    LIMIT 1
)
WHERE u.alamat_pengiriman IS NULL
  AND EXISTS (
      SELECT 1
      FROM pesanan AS p
      WHERE p.id_user = u.id_user
  );
