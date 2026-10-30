<?php

class OrderModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createOrder(array $order, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
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
            return $orderId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}