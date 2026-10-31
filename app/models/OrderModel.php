<?php

class OrderModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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