CREATE OR REPLACE VIEW view_laporan_penjualan AS
SELECT
    p.id_pesanan,
    COALESCE(
        p.no_invoice,
        'ORD-' || p.id_pesanan::text
    ) AS kode,
    p.tanggal_pesan,
    p.status_pesanan::text AS status_pesanan,

    u.nama AS nama_pelanggan,
    u.no_hp AS telepon,

    COALESCE(
        p.tagihan_pokok,
        detail.subtotal,
        0
    ) + p.ongkir - p.diskon AS total,

    COALESCE(
        detail.daftar_produk,
        'Produk tidak tersedia'
    ) AS daftar_produk,

    COALESCE(
        detail.daftar_spesifikasi,
        'Spesifikasi tidak tersedia'
    ) AS daftar_spesifikasi,

    pembayaran_terbaru.status_pembayaran::text AS status_pembayaran,
    pembayaran_terbaru.metode_pembayaran::text AS metode_pembayaran,
    pembayaran_terbaru.nominal_transfer,
    pembayaran_terbaru.bukti_transfer,
    pembayaran_terbaru.catatan_admin

FROM pesanan p

JOIN "user" u
    ON u.id_user = p.id_user

LEFT JOIN (
    SELECT
        d.id_pesanan,

        SUM(
            d.jumlah * COALESCE(d.harga_satuan, produk.harga_dasar)
        ) AS subtotal,

        STRING_AGG(
            produk.nama_produk || ' × ' || d.jumlah::text,
            ', ' ORDER BY d.id_detail
        ) AS daftar_produk,

        STRING_AGG(
            produk.nama_produk || CHR(10) ||
            CASE d.tipe_ukuran::text
                WHEN 'CUSTOM' THEN 'Custom: '
                ELSE 'Standar: '
            END ||
            d.panjang::text || ' × ' ||
            d.lebar::text || ' × ' ||
            d.tinggi::text || ' cm' ||
            CASE
                WHEN d.tambah_sarban > 0 OR d.tambah_sargul > 0 THEN
                    CHR(10) || '+' ||
                    CASE
                        WHEN d.tambah_sarban > 0
                        THEN d.tambah_sarban::text || ' Sarung Bantal'
                        ELSE ''
                    END ||
                    CASE
                        WHEN d.tambah_sarban > 0 AND d.tambah_sargul > 0
                        THEN ', '
                        ELSE ''
                    END ||
                    CASE
                        WHEN d.tambah_sargul > 0
                        THEN d.tambah_sargul::text || ' Sarung Guling'
                        ELSE ''
                    END
                ELSE ''
            END,
            CHR(10) || CHR(10) ORDER BY d.id_detail
        ) AS daftar_spesifikasi

    FROM detail_pesanan d
    JOIN produk
        ON produk.id_produk = d.id_produk
    GROUP BY d.id_pesanan
) detail
    ON detail.id_pesanan = p.id_pesanan

LEFT JOIN LATERAL (
    SELECT
        status_pembayaran,
        metode_pembayaran,
        nominal_transfer,
        bukti_transfer,
        catatan_admin
    FROM pembayaran
    WHERE id_pesanan = p.id_pesanan
    ORDER BY tanggal_upload DESC, id_pembayaran DESC
    LIMIT 1
) pembayaran_terbaru
    ON TRUE;