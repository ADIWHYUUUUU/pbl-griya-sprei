<?php

class DashboardModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getStats(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM pesanan WHERE status_pesanan::text = 'MENUNGGU_PEMBAYARAN') AS pesanan_baru,
                (
                    SELECT COUNT(*)
                    FROM pesanan p
                    JOIN LATERAL (
                        SELECT status_pembayaran
                        FROM pembayaran
                        WHERE id_pesanan = p.id_pesanan
                        ORDER BY tanggal_upload DESC, id_pembayaran DESC
                        LIMIT 1
                    ) pembayaran_terbaru ON TRUE
                    WHERE pembayaran_terbaru.status_pembayaran::text = 'MENUNGGU_VERIFIKASI'
                ) AS menunggu_verifikasi,
                (SELECT COALESCE(SUM(stok), 0) FROM produk) AS total_stok"
        );

        return $stmt->fetch();
    }

    public function getRecentOrders(int $limit = 10): array
    {
        return $this->fetchOrders('', [], $limit);
    }

    public function getWaitingVerificationOrders(int $limit = 5): array
    {
        return $this->fetchOrders(
            "pembayaran_terbaru.status_pembayaran::text = 'MENUNGGU_VERIFIKASI'",
            [],
            $limit
        );
    }

    public function getOrdersForMonth(int $month, int $year): array
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('+1 month');

        return $this->fetchOrders(
            'p.tanggal_pesan >= :start_date AND p.tanggal_pesan < :end_date',
            [
                ':start_date' => $start->format('Y-m-d H:i:s'),
                ':end_date' => $end->format('Y-m-d H:i:s'),
            ]
        );
    }

    private function fetchOrders(string $condition, array $parameters, ?int $limit = null): array
    {
        $sql = "SELECT
                    p.id_pesanan,
                    COALESCE(p.no_invoice, 'ORD-' || p.id_pesanan::text) AS kode,
                    p.tanggal_pesan,
                    p.status_pesanan::text AS status_pesanan,
                    u.nama AS nama_pelanggan,
                    u.no_hp AS telepon,
                    p.alamat_pengiriman,
                    p.catatan_alamat,
                    p.metode_pengiriman::text AS metode_pengiriman,
                    p.catatan_pengiriman,
                    p.nomor_resi,
                    p.ongkir,
                    COALESCE(detail.subtotal, 0) + p.ongkir - p.diskon AS total,
                    COALESCE(detail.daftar_produk, 'Produk tidak tersedia') AS daftar_produk,
                    COALESCE(detail.daftar_spesifikasi, 'Spesifikasi tidak tersedia') AS daftar_spesifikasi,
                    pembayaran_terbaru.status_pembayaran::text AS status_pembayaran,
                    pembayaran_terbaru.metode_pembayaran::text AS metode_pembayaran,
                    pembayaran_terbaru.nominal_transfer,
                    pembayaran_terbaru.bukti_transfer,
                    pembayaran_terbaru.catatan_admin
                FROM pesanan p
                JOIN \"user\" u ON u.id_user = p.id_user
                LEFT JOIN (
                    SELECT
                        d.id_pesanan,
                        SUM(d.jumlah * COALESCE(d.harga_satuan, produk.harga_dasar)) AS subtotal,
                        STRING_AGG(
                            produk.nama_produk || ' × ' || d.jumlah::text,
                            ', ' ORDER BY d.id_detail
                        ) AS daftar_produk,
                        STRING_AGG(
                            produk.nama_produk || CHR(10) ||
                            CASE d.tipe_ukuran::text WHEN 'CUSTOM' THEN 'Custom: ' ELSE 'Standar: ' END ||
                            d.panjang::text || ' × ' || d.lebar::text || ' × ' || d.tinggi::text || ' cm' ||
                            CASE
                                WHEN d.tambah_sarban > 0 OR d.tambah_sargul > 0 THEN
                                    CHR(10) || '+' ||
                                    CASE WHEN d.tambah_sarban > 0 THEN d.tambah_sarban::text || ' Sarung Bantal' ELSE '' END ||
                                    CASE WHEN d.tambah_sarban > 0 AND d.tambah_sargul > 0 THEN ', ' ELSE '' END ||
                                    CASE WHEN d.tambah_sargul > 0 THEN d.tambah_sargul::text || ' Sarung Guling' ELSE '' END
                                ELSE ''
                            END,
                            CHR(10) || CHR(10) ORDER BY d.id_detail
                        ) AS daftar_spesifikasi
                    FROM detail_pesanan d
                    JOIN produk ON produk.id_produk = d.id_produk
                    GROUP BY d.id_pesanan
                ) detail ON detail.id_pesanan = p.id_pesanan
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
                ) pembayaran_terbaru ON TRUE";

        if ($condition !== '') {
            $sql .= ' WHERE ' . $condition;
        }
        $sql .= ' ORDER BY p.tanggal_pesan DESC, p.id_pesanan DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min(100, $limit));
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        return $stmt->fetchAll();
    }
}
