CREATE TYPE jenis_kain_enum AS ENUM (
    'TENCEL', 'KATUN_JEPANG', 'KATUN_LOKAL', 'MICROTENCEL', 'MICROTEX'
);

ALTER TABLE produk
    ADD COLUMN jenis_kain jenis_kain_enum;