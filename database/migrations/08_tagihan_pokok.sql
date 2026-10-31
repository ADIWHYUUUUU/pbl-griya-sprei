ALTER TABLE pesanan
ADD COLUMN tagihan_pokok NUMERIC(12,2) CHECK (tagihan_pokok >= 0);
