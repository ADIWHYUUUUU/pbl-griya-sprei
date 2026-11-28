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
                    FROM pembayaran
                    WHERE status_pembayaran::text = 'MENUNGGU_VERIFIKASI'
                ) AS menunggu_verifikasi,
                (
                    SELECT COUNT(*)
                    FROM pesanan
                    WHERE status_pesanan::text IN ('DIPROSES', 'DIKIRIM')
                      AND (
                        (
                            metode_pengiriman::text = 'INSTANT_GOJEK'
                            AND NULLIF(BTRIM(nomor_telepon_kurir), '') IS NULL
                        )
                        OR (
                            COALESCE(metode_pengiriman::text, '') <> 'INSTANT_GOJEK'
                            AND NULLIF(BTRIM(nomor_resi), '') IS NULL
                        )
                      )
                ) AS perlu_resi"
        );

        return $stmt->fetch();
    }

    public function getRecentOrders(int $limit = 10): array
    {
        return $this->fetchOrders('', [], $limit, true);
    }

    public function getOrderStatusCounts(): array
    {
        $counts = [
            'MENUNGGU_VERIFIKASI' => 0,
            'MENUNGGU_PEMBAYARAN' => 0,
            'DIPROSES' => 0,
            'DIKIRIM' => 0,
            'SELESAI' => 0,
            'DIBATALKAN' => 0,
        ];
        $statement = $this->pdo->query(
            'SELECT status_pesanan::text AS status_pesanan, COUNT(*) AS jumlah
             FROM pesanan
             GROUP BY status_pesanan'
        );

        foreach ($statement->fetchAll() as $row) {
            if (array_key_exists($row['status_pesanan'], $counts)) {
                $counts[$row['status_pesanan']] = (int) $row['jumlah'];
            }
        }

        return $counts;
    }

    public function getOrdersForShipping(int $limit = 100): array
    {
        return $this->fetchOrders(
            "(p.status_pesanan::text IN ('DIPROSES', 'DIKIRIM') OR
              (p.status_pesanan::text = 'SELESAI'
               AND (p.nomor_resi IS NOT NULL OR p.nomor_telepon_kurir IS NOT NULL)))",
            [],
            $limit
        );
    }

    public function getWaitingVerificationPayments(int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                pembayaran.id_pembayaran,
                pembayaran.id_pesanan,
                pembayaran.metode_pembayaran::text AS metode_pembayaran,
                pembayaran.nominal_transfer,
                pembayaran.tanggal_upload,
                COALESCE(pesanan.no_invoice, 'ORD-' || pesanan.id_pesanan::text) AS kode,
                pengguna.nama AS nama_pelanggan
             FROM pembayaran
             JOIN pesanan ON pesanan.id_pesanan = pembayaran.id_pesanan
             JOIN \"user\" pengguna ON pengguna.id_user = pesanan.id_user
             WHERE pembayaran.status_pembayaran::text = 'MENUNGGU_VERIFIKASI'
             ORDER BY pembayaran.tanggal_upload DESC, pembayaran.id_pembayaran DESC
             LIMIT :limit"
        );
        $statement->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getEarliestOrderYear(): ?int
    {
        $statement = $this->pdo->query(
            'SELECT MIN(EXTRACT(YEAR FROM tanggal_pesan))::int FROM pesanan'
        );
        $year = $statement->fetchColumn();

        return $year === false || $year === null ? null : (int) $year;
    }

    public function getEarliestOrderDate(): ?DateTimeImmutable
    {
        $statement = $this->pdo->query('SELECT MIN(tanggal_pesan)::date FROM pesanan');
        $date = $statement->fetchColumn();

        return $date === false || $date === null ? null : new DateTimeImmutable((string) $date);
    }

    public function getOrdersForMonth(int $month, int $year): array
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('+1 month')->modify('-1 day');

        return $this->getOrdersForDateRange($start->format('Y-m-d'), $end->format('Y-m-d'));
    }

  public function getOrdersForDateRange(string $startDate, string $endDate): array
{
    $start = new DateTimeImmutable($startDate);
    $end = (new DateTimeImmutable($endDate))->modify('+1 day');

    $sql = "
        SELECT *
        FROM view_laporan_penjualan
        WHERE tanggal_pesan >= :start_date
          AND tanggal_pesan < :end_date
        ORDER BY tanggal_pesan DESC, id_pesanan DESC
    ";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([
        ':start_date' => $start->format('Y-m-d H:i:s'),
        ':end_date' => $end->format('Y-m-d H:i:s'),
    ]);

    return $stmt->fetchAll();
}

    private function fetchOrders(
        string $condition,
        array $parameters,
        ?int $limit = null,
        bool $includeModalDetails = false
    ): array {
        $modalOrderFields = $includeModalDetails
            ? "
                    p.diskon,
                    detail.items AS produk_detail,
                    pembayaran_terbaru.bukti_nama_asli,
                    pembayaran_terbaru.tanggal_upload,
                    pembayaran_terbaru.nama_pemilik_pengirim,
                    pembayaran_terbaru.nomor_rekening_pengirim,"
            : '';
        $modalItemsField = $includeModalDetails
            ? ",
                        JSON_AGG(
                            JSON_BUILD_OBJECT(
                                'nama_produk', produk.nama_produk,
                                'kategori_produk', produk.kategori_produk::text,
                                'gambar_produk', produk.gambar_produk,
                                'tipe_ukuran', d.tipe_ukuran::text,
                                'jumlah', d.jumlah,
                                'panjang', d.panjang,
                                'lebar', d.lebar,
                                'tinggi', d.tinggi,
                                'tambah_sarban', d.tambah_sarban,
                                'tambah_sargul', d.tambah_sargul,
                                'catatan_pesanan', d.catatan_pesanan
                            ) ORDER BY d.id_detail
                        ) AS items"
            : '';
        $modalPaymentFields = $includeModalDetails
            ? "
                        bukti_nama_asli,
                        tanggal_upload,
                        nama_pemilik_pengirim,
                        nomor_rekening_pengirim,"
            : '';

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
                    p.nomor_telepon_kurir,
                    p.tagihan_pokok,
                    p.ongkir,
                    COALESCE(p.tagihan_pokok, detail.subtotal, 0) + p.ongkir - p.diskon AS total,
                    COALESCE(detail.daftar_produk, 'Produk tidak tersedia') AS daftar_produk,
                    COALESCE(detail.daftar_spesifikasi, 'Spesifikasi tidak tersedia') AS daftar_spesifikasi,
                    pembayaran_terbaru.status_pembayaran::text AS status_pembayaran,
                    pembayaran_terbaru.metode_pembayaran::text AS metode_pembayaran,
                    pembayaran_terbaru.nominal_transfer,
                    pembayaran_terbaru.bukti_transfer,
                    {$modalOrderFields}
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
                        ) AS daftar_spesifikasi{$modalItemsField}
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
                        {$modalPaymentFields}
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