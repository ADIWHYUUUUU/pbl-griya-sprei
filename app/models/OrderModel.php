<?php

class OrderModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCustomerOrders(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                p.id_pesanan,
                COALESCE(p.no_invoice, 'ORD-' || p.id_pesanan::text) AS kode,
                p.tanggal_pesan,
                p.tanggal_verifikasi_admin,
                p.status_pesanan::text AS status_pesanan,
                p.alamat_pengiriman,
                p.catatan_alamat,
                p.metode_pengiriman::text AS metode_pengiriman,
                p.catatan_pengiriman,
                p.nomor_resi,
                p.tagihan_pokok,
                p.ongkir,
                p.diskon,
                pembayaran_terbaru.status_pembayaran::text AS status_pembayaran,
                pembayaran_terbaru.metode_pembayaran::text AS metode_pembayaran,
                pembayaran_terbaru.nominal_transfer,
                pembayaran_terbaru.tanggal_upload,
                pembayaran_terbaru.catatan_admin,
                d.id_detail,
                d.tipe_ukuran::text AS tipe_ukuran,
                d.jumlah,
                d.panjang,
                d.lebar,
                d.tinggi,
                d.tambah_sarban,
                d.tambah_sargul,
                d.catatan_pesanan,
                COALESCE(d.harga_satuan, produk.harga_dasar) AS harga_satuan,
                produk.nama_produk,
                produk.gambar_produk
            FROM pesanan p
            LEFT JOIN detail_pesanan d ON d.id_pesanan = p.id_pesanan
            LEFT JOIN produk ON produk.id_produk = d.id_produk
            LEFT JOIN LATERAL (
                SELECT
                    status_pembayaran,
                    metode_pembayaran,
                    nominal_transfer,
                    tanggal_upload,
                    catatan_admin
                FROM pembayaran
                WHERE id_pesanan = p.id_pesanan
                ORDER BY tanggal_upload DESC, id_pembayaran DESC
                LIMIT 1
            ) pembayaran_terbaru ON TRUE
            WHERE p.id_user = :customer_id
            ORDER BY p.tanggal_pesan DESC, p.id_pesanan DESC, d.id_detail"
        );
        $statement->execute([':customer_id' => $customerId]);

        $orders = [];
        foreach ($statement->fetchAll() as $row) {
            $orderId = (int) $row['id_pesanan'];
            if (!isset($orders[$orderId])) {
                $orders[$orderId] = [
                    'id_pesanan' => $orderId,
                    'kode' => (string) $row['kode'],
                    'tanggal_pesan' => $row['tanggal_pesan'],
                    'tanggal_verifikasi_admin' => $row['tanggal_verifikasi_admin'],
                    'status_pesanan' => (string) $row['status_pesanan'],
                    'alamat_pengiriman' => (string) $row['alamat_pengiriman'],
                    'catatan_alamat' => (string) ($row['catatan_alamat'] ?? ''),
                    'metode_pengiriman' => (string) $row['metode_pengiriman'],
                    'catatan_pengiriman' => (string) ($row['catatan_pengiriman'] ?? ''),
                    'nomor_resi' => (string) ($row['nomor_resi'] ?? ''),
                    'tagihan_pokok' => $row['tagihan_pokok'] === null
                        ? null
                        : (float) $row['tagihan_pokok'],
                    'ongkir' => (float) $row['ongkir'],
                    'diskon' => (float) $row['diskon'],
                    'status_pembayaran' => (string) ($row['status_pembayaran'] ?? ''),
                    'metode_pembayaran' => (string) ($row['metode_pembayaran'] ?? ''),
                    'nominal_transfer' => $row['nominal_transfer'] === null
                        ? null
                        : (float) $row['nominal_transfer'],
                    'tanggal_upload' => $row['tanggal_upload'],
                    'catatan_admin' => (string) ($row['catatan_admin'] ?? ''),
                    'produk' => [],
                    'subtotal' => 0.0,
                ];
            }

            if ($row['id_detail'] !== null) {
                $quantity = (int) $row['jumlah'];
                $price = (float) $row['harga_satuan'];
                $orders[$orderId]['produk'][] = [
                    'nama' => (string) $row['nama_produk'],
                    'gambar' => (string) ($row['gambar_produk'] ?? ''),
                    'tipe_ukuran' => (string) $row['tipe_ukuran'],
                    'jumlah' => $quantity,
                    'panjang' => (int) $row['panjang'],
                    'lebar' => (int) $row['lebar'],
                    'tinggi' => (int) $row['tinggi'],
                    'tambah_sarban' => (int) $row['tambah_sarban'],
                    'tambah_sargul' => (int) $row['tambah_sargul'],
                    'catatan' => (string) ($row['catatan_pesanan'] ?? ''),
                ];
                $orders[$orderId]['subtotal'] += $price * $quantity;
            }
        }

        foreach ($orders as &$order) {
            $principal = $order['tagihan_pokok'] ?? $order['subtotal'];
            $order['total'] = max(0, $principal + $order['ongkir'] - $order['diskon']);
        }
        unset($order);

        return array_values($orders);
    }

    public function getOrderForVerification(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                p.id_pesanan,
                COALESCE(p.no_invoice, 'ORD-' || p.id_pesanan::text) AS kode,
                p.status_pesanan::text AS status_pesanan,
                p.alamat_pengiriman,
                p.catatan_alamat,
                p.metode_pengiriman::text AS metode_pengiriman,
                p.catatan_pengiriman,
                p.tagihan_pokok,
                p.ongkir,
                p.diskon,
                u.nama AS nama_pelanggan,
                u.email AS email_pelanggan,
                u.no_hp AS telepon_pelanggan,
                d.id_detail,
                d.tipe_ukuran::text AS tipe_ukuran,
                d.jumlah,
                d.panjang,
                d.lebar,
                d.tinggi,
                d.tambah_sarban,
                d.tambah_sargul,
                d.catatan_pesanan,
                COALESCE(d.harga_satuan, produk.harga_dasar) AS harga_satuan,
                produk.nama_produk,
                produk.gambar_produk
            FROM pesanan p
            JOIN \"user\" u ON u.id_user = p.id_user
            LEFT JOIN detail_pesanan d ON d.id_pesanan = p.id_pesanan
            LEFT JOIN produk ON produk.id_produk = d.id_produk
            WHERE p.id_pesanan = :order_id
            ORDER BY d.id_detail"
        );
        $statement->execute([':order_id' => $orderId]);
        $rows = $statement->fetchAll();
        if ($rows === []) {
            return null;
        }

        $first = $rows[0];
        $order = [
            'id_pesanan' => (int) $first['id_pesanan'],
            'kode' => (string) $first['kode'],
            'status_pesanan' => (string) $first['status_pesanan'],
            'alamat_pengiriman' => (string) $first['alamat_pengiriman'],
            'catatan_alamat' => (string) ($first['catatan_alamat'] ?? ''),
            'metode_pengiriman' => (string) $first['metode_pengiriman'],
            'catatan_pengiriman' => (string) ($first['catatan_pengiriman'] ?? ''),
            'tagihan_pokok' => $first['tagihan_pokok'] === null ? null : (float) $first['tagihan_pokok'],
            'ongkir' => (float) $first['ongkir'],
            'diskon' => (float) $first['diskon'],
            'nama_pelanggan' => (string) $first['nama_pelanggan'],
            'email_pelanggan' => (string) $first['email_pelanggan'],
            'telepon_pelanggan' => (string) $first['telepon_pelanggan'],
            'produk' => [],
            'subtotal' => 0.0,
        ];

        foreach ($rows as $row) {
            if ($row['id_detail'] === null) {
                continue;
            }

            $quantity = (int) $row['jumlah'];
            $price = (float) $row['harga_satuan'];
            $order['produk'][] = [
                'nama' => (string) $row['nama_produk'],
                'gambar' => (string) ($row['gambar_produk'] ?? ''),
                'tipe_ukuran' => (string) $row['tipe_ukuran'],
                'jumlah' => $quantity,
                'panjang' => (int) $row['panjang'],
                'lebar' => (int) $row['lebar'],
                'tinggi' => (int) $row['tinggi'],
                'tambah_sarban' => (int) $row['tambah_sarban'],
                'tambah_sargul' => (int) $row['tambah_sargul'],
                'catatan' => (string) ($row['catatan_pesanan'] ?? ''),
                'subtotal' => $price * $quantity,
            ];
            $order['subtotal'] += $price * $quantity;
        }

        return $order;
    }

    public function getAdminOrderDetail(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                p.id_pesanan,
                p.no_invoice,
                p.tanggal_pesan,
                p.status_pesanan::text AS status_pesanan,
                p.alamat_pengiriman,
                p.catatan_alamat,
                p.metode_pengiriman::text AS metode_pengiriman,
                p.catatan_pengiriman,
                p.nomor_resi,
                u.nama AS nama_pelanggan,
                u.no_hp AS telepon
             FROM pesanan p
             JOIN "user" u ON u.id_user = p.id_user
             WHERE p.id_pesanan = :order_id'
        );
        $statement->execute([':order_id' => $orderId]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT
                d.tipe_ukuran::text AS tipe_ukuran,
                d.jumlah,
                d.harga_satuan,
                d.panjang,
                d.lebar,
                d.tinggi,
                d.tambah_sarban,
                d.tambah_sargul,
                d.catatan_pesanan,
                produk.nama_produk,
                produk.gambar_produk
             FROM detail_pesanan d
             JOIN produk ON produk.id_produk = d.id_produk
             WHERE d.id_pesanan = :order_id
             ORDER BY d.id_detail'
        );
        $items->execute([':order_id' => $orderId]);
        $order['items'] = $items->fetchAll();

        return $order;
    }

    public function saveShipmentDetails(int $orderId, string $tracking, string $shippingNote): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE pesanan
             SET nomor_resi = :nomor_resi,
                 catatan_pengiriman = :catatan_pengiriman,
                 status_pesanan = 'DIKIRIM'
             WHERE id_pesanan = :order_id
               AND status_pesanan::text IN ('DIPROSES', 'DIKIRIM')"
        );
        $statement->execute([
            ':nomor_resi' => $tracking,
            ':catatan_pengiriman' => $shippingNote,
            ':order_id' => $orderId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function verifyOrder(int $orderId, int $principal, int $shipping, int $discount): bool
    {
        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare(
                'SELECT status_pesanan::text FROM pesanan WHERE id_pesanan = :order_id FOR UPDATE'
            );
            $lock->execute([':order_id' => $orderId]);
            if ($lock->fetchColumn() !== 'MENUNGGU_VERIFIKASI') {
                $this->pdo->rollBack();
                return false;
            }

            $hasItems = $this->pdo->prepare(
                'SELECT EXISTS (SELECT 1 FROM detail_pesanan WHERE id_pesanan = :order_id)'
            );
            $hasItems->execute([':order_id' => $orderId]);
            if (!$hasItems->fetchColumn()) {
                throw new UnexpectedValueException('Pesanan tidak memiliki detail produk.');
            }

            $update = $this->pdo->prepare(
                "UPDATE pesanan
                SET tagihan_pokok = :tagihan_pokok,
                    ongkir = :ongkir,
                    diskon = :diskon,
                    status_pesanan = 'MENUNGGU_PEMBAYARAN',
                    tanggal_verifikasi_admin = CURRENT_TIMESTAMP
                WHERE id_pesanan = :order_id"
            );
            $update->execute([
                ':tagihan_pokok' => $principal,
                ':ongkir' => $shipping,
                ':diskon' => $discount,
                ':order_id' => $orderId,
            ]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function createOrder(array $order, array $items): array
    {
        $this->pdo->beginTransaction();

        try {
            $order['no_invoice'] = $this->createInvoice($items);
            $orderStatement = $this->pdo->prepare(
                'INSERT INTO pesanan (
                    id_user,
                    no_invoice,
                    alamat_pengiriman,
                    catatan_alamat,
                    metode_pengiriman,
                    catatan_pengiriman
                ) VALUES (
                    :id_user,
                    :no_invoice,
                    :alamat_pengiriman,
                    :catatan_alamat,
                    CAST(:metode_pengiriman AS metode_pengiriman_enum),
                    :catatan_pengiriman
                )
                RETURNING id_pesanan'
            );
            $orderStatement->execute([
                ':id_user' => $order['id_user'],
                ':no_invoice' => $order['no_invoice'],
                ':alamat_pengiriman' => $order['alamat_pengiriman'],
                ':catatan_alamat' => $order['catatan_alamat'],
                ':metode_pengiriman' => $order['metode_pengiriman'],
                ':catatan_pengiriman' => $order['catatan_pengiriman'],
            ]);
            $orderId = (int) $orderStatement->fetchColumn();

            $itemStatement = $this->pdo->prepare(
                'INSERT INTO detail_pesanan (
                    id_pesanan,
                    id_produk,
                    tipe_ukuran,
                    jumlah,
                    harga_satuan,
                    panjang,
                    lebar,
                    tinggi,
                    tambah_sarban,
                    tambah_sargul,
                    catatan_pesanan
                ) VALUES (
                    :id_pesanan,
                    :id_produk,
                    CAST(:tipe_ukuran AS tipe_ukuran_enum),
                    :jumlah,
                    :harga_satuan,
                    :panjang,
                    :lebar,
                    :tinggi,
                    :tambah_sarban,
                    :tambah_sargul,
                    :catatan_pesanan
                )'
            );
            foreach ($items as $item) {
                $itemStatement->execute([
                    ':id_pesanan' => $orderId,
                    ':id_produk' => $item['id_produk'],
                    ':tipe_ukuran' => $item['tipe_ukuran'],
                    ':jumlah' => $item['jumlah'],
                    ':harga_satuan' => $item['harga_satuan'],
                    ':panjang' => $item['panjang'],
                    ':lebar' => $item['lebar'],
                    ':tinggi' => $item['tinggi'],
                    ':tambah_sarban' => $item['tambah_sarban'],
                    ':tambah_sargul' => $item['tambah_sargul'],
                    ':catatan_pesanan' => $item['catatan_pesanan'],
                ]);
            }

            $cartStatement = $this->pdo->prepare(
                'DELETE FROM keranjang_pelanggan WHERE id_user = :id_user AND item_id = :item_id'
            );
            foreach ($order['cart_item_ids'] as $itemId) {
                $cartStatement->execute([
                    ':id_user' => $order['id_user'],
                    ':item_id' => $itemId,
                ]);
            }

            $this->pdo->commit();
            return [
                'id_pesanan' => $orderId,
                'no_invoice' => $order['no_invoice'],
            ];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function createInvoice(array $items): string
    {
        $firstItem = $items[0] ?? null;
        if (!is_array($firstItem)) {
            throw new InvalidArgumentException('Pesanan harus memiliki setidaknya satu produk.');
        }

        $categoryCode = match ($firstItem['kategori_produk'] ?? '') {
            'SPREI' => 'SP',
            'BED_COVER' => 'BC',
            default => throw new UnexpectedValueException('Kategori produk tidak valid untuk nomor invoice.'),
        };
        $productId = filter_var($firstItem['id_produk'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($productId === false || $productId === null) {
            throw new UnexpectedValueException('ID produk tidak valid untuk nomor invoice.');
        }

        $businessTimezone = new DateTimeZone('Asia/Jakarta');
        $today = new DateTimeImmutable('now', $businessTimezone);
        $invoiceDate = $today->format('Ymd');
        $dayStart = $today->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
        $nextDay = $dayStart->modify('+1 day');
        $lockStatement = $this->pdo->prepare('SELECT pg_advisory_xact_lock(hashtext(:lock_key))');
        $lockStatement->execute([':lock_key' => 'invoice-' . $invoiceDate]);

        $sequenceStatement = $this->pdo->prepare(
            "SELECT GREATEST(
                (
                    SELECT COUNT(*)
                    FROM pesanan
                    WHERE tanggal_pesan >= :day_start AND tanggal_pesan < :next_day
                ),
                (
                    SELECT COALESCE(MAX(substring(no_invoice FROM '([0-9]+)$')::integer), 0)
                    FROM pesanan
                    WHERE no_invoice LIKE :invoice_prefix
                )
            )"
        );
        $sequenceStatement->execute([
            ':day_start' => $dayStart->format('Y-m-d H:i:s'),
            ':next_day' => $nextDay->format('Y-m-d H:i:s'),
            ':invoice_prefix' => 'INV-' . $invoiceDate . '-%',
        ]);
        $sequence = (int) $sequenceStatement->fetchColumn() + 1;

        return sprintf(
            'INV-%s-%s-%d-%04d',
            $invoiceDate,
            $categoryCode,
            $productId,
            $sequence
        );
    }
}