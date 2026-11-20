CREATE TABLE IF NOT EXISTS public.produk_pilihan (
    urutan      SMALLINT PRIMARY KEY CHECK (urutan BETWEEN 1 AND 4),
    id_produk   INTEGER UNIQUE REFERENCES public.produk(id_produk) ON DELETE SET NULL,
    badge       VARCHAR(60) NOT NULL DEFAULT '',
    tampil      BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE OR REPLACE VIEW public.v_produk_pilihan AS
SELECT
    pp.urutan,
    pp.badge AS tag,
    p.id_produk,
    p.nama_produk AS name,
    p.harga_dasar AS price,
    p.gambar_produk AS image,
    p.deskripsi AS description
FROM public.produk_pilihan AS pp
JOIN public.produk AS p ON p.id_produk = pp.id_produk
WHERE pp.tampil IS TRUE;
