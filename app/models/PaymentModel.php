<?php

class PaymentModel
{
    // Business rule: a fully paid order moves from waiting payment to processing.
    private const ORDER_STATUS_BEFORE_FULL_PAYMENT = 'MENUNGGU_PEMBAYARAN';
    private const ORDER_STATUS_AFTER_FULL_PAYMENT = 'DIPROSES';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCustomerOrder(int $orderId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id_pesanan, p.no_invoice, p.tanggal_pesan,
                   p.status_pesanan::text AS status_pesanan,
                   p.alamat_pengiriman, p.catatan_alamat,
                   p.metode_pengiriman::text AS metode_pengiriman,
                   p.catatan_pengiriman, p.tagihan_pokok, p.ongkir, p.diskon,
                   u.nama AS nama_pelanggan, u.no_hp AS telepon_pelanggan
            FROM pesanan p
            JOIN "user" u ON u.id_user = p.id_user
            WHERE p.id_pesanan = :id_pesanan AND p.id_user = :id_user'
        );
        $statement->execute([':id_pesanan' => $orderId, ':id_user' => $userId]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }

        $itemStatement = $this->pdo->prepare(
            'SELECT d.jumlah, COALESCE(d.harga_satuan, produk.harga_dasar) AS harga_satuan,
                    d.panjang, d.lebar, d.tinggi, d.tambah_sarban, d.tambah_sargul,
                    d.tipe_ukuran::text AS tipe_ukuran, produk.nama_produk
             FROM detail_pesanan d
             JOIN produk ON produk.id_produk = d.id_produk
             WHERE d.id_pesanan = :id_pesanan
             ORDER BY d.id_detail'
        );
        $itemStatement->execute([':id_pesanan' => $orderId]);
        $order['items'] = $itemStatement->fetchAll();

        $paymentStatement = $this->pdo->prepare(
            'SELECT id_pembayaran, metode_pembayaran::text AS metode_pembayaran,
                    nominal_transfer, bukti_transfer,
                    status_pembayaran::text AS status_pembayaran,
                    bukti_nama_asli, bukti_ukuran, bukti_mime
             FROM pembayaran
             WHERE id_pesanan = :id_pesanan
             ORDER BY tanggal_upload DESC, id_pembayaran DESC
             LIMIT 1'
        );
        $paymentStatement->execute([':id_pesanan' => $orderId]);
        $order['payment'] = $paymentStatement->fetch() ?: null;

        return $order;
    }

    public function create(
        int $orderId,
        string $method,
        float $amount,
        array $upload,
        string $senderName,
        string $senderAccount
    ): void
    {
        $this->pdo->beginTransaction();
        try {
            $orderStatement = $this->pdo->prepare(
                'SELECT status_pesanan::text FROM pesanan WHERE id_pesanan = :id_pesanan FOR UPDATE'
            );
            $orderStatement->execute([':id_pesanan' => $orderId]);
            if ($orderStatement->fetchColumn() !== self::ORDER_STATUS_BEFORE_FULL_PAYMENT) {
                throw new DomainException('Pesanan belum dapat menerima pembayaran.');
            }

            $activeStatement = $this->pdo->prepare(
                'SELECT status_pembayaran::text FROM pembayaran
                 WHERE id_pesanan = :id_pesanan
                 ORDER BY tanggal_upload DESC, id_pembayaran DESC
                 LIMIT 1'
            );
            $activeStatement->execute([':id_pesanan' => $orderId]);
            $latestStatus = $activeStatement->fetchColumn();
            if ($latestStatus !== false && $latestStatus !== 'DITOLAK') {
                throw new DomainException('Pembayaran aktif untuk pesanan ini sudah ada.');
            }

            $statement = $this->pdo->prepare(
                'INSERT INTO pembayaran (
                    id_pesanan, metode_pembayaran, nominal_transfer, bukti_transfer,
                    status_pembayaran, bukti_nama_asli, bukti_ukuran, bukti_mime,
                    nama_pemilik_pengirim, nomor_rekening_pengirim
                 ) VALUES (
                    :id_pesanan, CAST(:metode_pembayaran AS metode_pembayaran_enum),
                    :nominal_transfer, :bukti_transfer,
                    CAST(:status_pembayaran AS status_pembayaran_enum),
                    :bukti_nama_asli, :bukti_ukuran, :bukti_mime,
                    :nama_pemilik_pengirim, :nomor_rekening_pengirim
                 )'
            );
            $statement->execute([
                ':id_pesanan' => $orderId,
                ':metode_pembayaran' => $method,
                ':nominal_transfer' => $amount,
                ':bukti_transfer' => $upload['path'],
                ':status_pembayaran' => 'MENUNGGU_VERIFIKASI',
                ':bukti_nama_asli' => $upload['original_name'],
                ':bukti_ukuran' => $upload['size'],
                ':bukti_mime' => $upload['mime'],
                ':nama_pemilik_pengirim' => $senderName !== '' ? $senderName : null,
                ':nomor_rekening_pengirim' => $senderAccount !== '' ? $senderAccount : null,
            ]);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function getAdminPayments(int $page, int $perPage = 5): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM pembayaran');
        $countStatement->execute();
        $totalRows = (int) $countStatement->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min($page, $totalPages);

        $statusStatement = $this->pdo->prepare(
            'SELECT status_pembayaran::text AS status_pembayaran, COUNT(*) AS total
             FROM pembayaran
             GROUP BY status_pembayaran'
        );
        $statusStatement->execute();
        $statusCounts = [
            'MENUNGGU_VERIFIKASI' => 0,
            'TERVERIFIKASI' => 0,
            'DITOLAK' => 0,
        ];
        foreach ($statusStatement->fetchAll() as $statusRow) {
            $statusCounts[$statusRow['status_pembayaran']] = (int) $statusRow['total'];
        }

        $oldestWaitingStatement = $this->pdo->prepare(
            'SELECT id_pembayaran, tanggal_upload
             FROM pembayaran
             WHERE status_pembayaran::text = :status
             ORDER BY tanggal_upload ASC, id_pembayaran ASC
             LIMIT 1'
        );
        $oldestWaitingStatement->execute([':status' => 'MENUNGGU_VERIFIKASI']);

        $statement = $this->pdo->prepare(
            'SELECT
                b.id_pembayaran,
                b.id_pesanan,
                b.metode_pembayaran::text AS metode_pembayaran,
                b.nominal_transfer,
                b.status_pembayaran::text AS status_pembayaran,
                b.tanggal_upload,
                COALESCE(p.no_invoice, \'ORD-\' || p.id_pesanan::text) AS no_invoice,
                p.alamat_pengiriman,
                COALESCE(NULLIF(p.tagihan_pokok, 0), detail.subtotal, 0) + p.ongkir - p.diskon AS total_tagihan,
                u.nama AS nama_pelanggan,
                u.no_hp AS telepon
             FROM pembayaran b
             JOIN pesanan p ON p.id_pesanan = b.id_pesanan
             JOIN "user" u ON u.id_user = p.id_user
             LEFT JOIN LATERAL (
                 SELECT SUM(d.jumlah * COALESCE(d.harga_satuan, produk.harga_dasar)) AS subtotal
                 FROM detail_pesanan d
                 JOIN produk ON produk.id_produk = d.id_produk
                 WHERE d.id_pesanan = p.id_pesanan
             ) detail ON TRUE
             ORDER BY
                CASE WHEN b.status_pembayaran::text = \'MENUNGGU_VERIFIKASI\' THEN 0 ELSE 1 END,
                b.tanggal_upload DESC,
                b.id_pembayaran DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'page' => $page,
            'total_pages' => $totalPages,
            'total_rows' => $totalRows,
            'status_counts' => $statusCounts,
            'oldest_waiting' => $oldestWaitingStatement->fetch() ?: null,
        ];
    }

    public function getAdminPayment(int $paymentId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                b.id_pembayaran,
                b.id_pesanan,
                b.metode_pembayaran::text AS metode_pembayaran,
                b.nominal_transfer,
                b.bukti_transfer,
                b.status_pembayaran::text AS status_pembayaran,
                b.tanggal_upload,
                b.tanggal_verifikasi,
                b.catatan_admin,
                b.bukti_nama_asli,
                b.bukti_ukuran,
                b.bukti_mime,
                b.nama_pemilik_pengirim,
                b.nomor_rekening_pengirim,
                p.no_invoice,
                p.status_pesanan::text AS status_pesanan,
                p.alamat_pengiriman,
                p.catatan_alamat,
                p.tagihan_pokok,
                p.ongkir,
                p.diskon,
                u.nama AS nama_pelanggan,
                u.no_hp AS telepon,
                COALESCE(NULLIF(p.tagihan_pokok, 0), detail.subtotal, 0) + p.ongkir - p.diskon AS total_tagihan
             FROM pembayaran b
             JOIN pesanan p ON p.id_pesanan = b.id_pesanan
             JOIN "user" u ON u.id_user = p.id_user
             LEFT JOIN LATERAL (
                 SELECT SUM(d.jumlah * COALESCE(d.harga_satuan, produk.harga_dasar)) AS subtotal
                 FROM detail_pesanan d
                 JOIN produk ON produk.id_produk = d.id_produk
                 WHERE d.id_pesanan = p.id_pesanan
             ) detail ON TRUE
             WHERE b.id_pembayaran = :id_pembayaran'
        );
        $statement->execute([':id_pembayaran' => $paymentId]);
        $payment = $statement->fetch();
        if (!$payment) {
            return null;
        }

        $itemStatement = $this->pdo->prepare(
            'SELECT
                d.tipe_ukuran::text AS tipe_ukuran,
                d.jumlah,
                d.panjang,
                d.lebar,
                d.tinggi,
                d.tambah_sarban,
                d.tambah_sargul,
                d.catatan_pesanan,
                produk.nama_produk
             FROM detail_pesanan d
             JOIN produk ON produk.id_produk = d.id_produk
             WHERE d.id_pesanan = :id_pesanan
             ORDER BY d.id_detail'
        );
        $itemStatement->execute([':id_pesanan' => $payment['id_pesanan']]);
        $payment['items'] = $itemStatement->fetchAll();

        return $payment;
    }

    public function verifyAdminPayment(int $paymentId): void
    {
        $this->pdo->beginTransaction();

        try {
            $paymentStatement = $this->pdo->prepare(
                'SELECT id_pesanan, nominal_transfer, status_pembayaran::text AS status_pembayaran
                 FROM pembayaran
                 WHERE id_pembayaran = :id_pembayaran
                 FOR UPDATE'
            );
            $paymentStatement->execute([':id_pembayaran' => $paymentId]);
            $payment = $paymentStatement->fetch();
            if (!$payment) {
                throw new DomainException('Pembayaran tidak ditemukan.');
            }
            if ($payment['status_pembayaran'] !== 'MENUNGGU_VERIFIKASI') {
                throw new DomainException('Pembayaran ini sudah diproses sebelumnya.');
            }

            $updatePayment = $this->pdo->prepare(
                'UPDATE pembayaran
                 SET status_pembayaran = CAST(:status AS status_pembayaran_enum),
                     tanggal_verifikasi = now(),
                     catatan_admin = NULL
                 WHERE id_pembayaran = :id_pembayaran'
            );
            $updatePayment->execute([
                ':status' => 'TERVERIFIKASI',
                ':id_pembayaran' => $paymentId,
            ]);

            $sumStatement = $this->pdo->prepare(
                'SELECT COALESCE(SUM(nominal_transfer), 0)
                 FROM pembayaran
                 WHERE id_pesanan = :id_pesanan
                   AND status_pembayaran::text = :status'
            );
            $sumStatement->execute([
                ':id_pesanan' => $payment['id_pesanan'],
                ':status' => 'TERVERIFIKASI',
            ]);
            $paidAmount = (float) $sumStatement->fetchColumn();

            $orderStatement = $this->pdo->prepare(
                'SELECT
                    p.id_pesanan,
                    p.tagihan_pokok,
                    p.ongkir,
                    p.diskon,
                    p.status_pesanan::text AS status_pesanan
                 FROM pesanan p
                 WHERE p.id_pesanan = :id_pesanan
                 FOR UPDATE'
            );
            $orderStatement->execute([':id_pesanan' => $payment['id_pesanan']]);
            $order = $orderStatement->fetch();
            if (!$order) {
                throw new DomainException('Pesanan pembayaran tidak ditemukan.');
            }

            $subtotalStatement = $this->pdo->prepare(
                'SELECT COALESCE(SUM(d.jumlah * COALESCE(d.harga_satuan, produk.harga_dasar)), 0)
                 FROM detail_pesanan d
                 JOIN produk ON produk.id_produk = d.id_produk
                 WHERE d.id_pesanan = :id_pesanan'
            );
            $subtotalStatement->execute([':id_pesanan' => $payment['id_pesanan']]);
            $subtotal = (float) $subtotalStatement->fetchColumn();
            $principal = (float) $order['tagihan_pokok'] !== 0.0
                ? (float) $order['tagihan_pokok']
                : $subtotal;
            $total = max(0, $principal + (float) $order['ongkir'] - (float) $order['diskon']);
            if (abs($paidAmount - $total) >= 0.01) {
                throw new DomainException('Nominal pembayaran belum sama dengan total tagihan.');
            }

            if ($order['status_pesanan'] === self::ORDER_STATUS_BEFORE_FULL_PAYMENT) {
                $updateOrder = $this->pdo->prepare(
                    'UPDATE pesanan
                     SET status_pesanan = CAST(:processing AS status_pesanan_enum)
                     WHERE id_pesanan = :id_pesanan'
                );
                $updateOrder->execute([
                    ':processing' => self::ORDER_STATUS_AFTER_FULL_PAYMENT,
                    ':id_pesanan' => $payment['id_pesanan'],
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function rejectAdminPayment(int $paymentId, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'UPDATE pembayaran
                 SET status_pembayaran = CAST(:status AS status_pembayaran_enum),
                     tanggal_verifikasi = now(),
                     catatan_admin = :catatan_admin
                 WHERE id_pembayaran = :id_pembayaran
                   AND status_pembayaran::text = :waiting_status'
            );
            $statement->execute([
                ':status' => 'DITOLAK',
                ':catatan_admin' => $reason,
                ':id_pembayaran' => $paymentId,
                ':waiting_status' => 'MENUNGGU_VERIFIKASI',
            ]);
            if ($statement->rowCount() === 0) {
                throw new DomainException('Pembayaran ini sudah diproses sebelumnya atau tidak ditemukan.');
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}