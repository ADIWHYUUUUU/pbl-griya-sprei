ALTER TABLE keranjang_pelanggan
    ALTER COLUMN item_data TYPE TEXT
    USING item_data::text;
