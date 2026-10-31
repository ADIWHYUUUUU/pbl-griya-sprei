<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/OrderModel.php';

class AdminOrderController
{
    public function verify(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'ADMIN') {
            http_response_code(403);
            exit('Akses ditolak.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Metode request tidak diizinkan.');
        }

        $orderId = $this->readInteger($_POST['id_pesanan'] ?? null);
        if ($orderId === null || $orderId < 1) {
            http_response_code(400);
            exit('ID pesanan tidak valid.');
        }

        $redirect = '/?page=admin-order-verification&id=' . $orderId;
        $token = $_POST['csrf_token'] ?? '';
        if (
            !is_string($token)
            || !isset($_SESSION['csrf_token'])
            || !hash_equals((string) $_SESSION['csrf_token'], $token)
        ) {
            $_SESSION['admin_order_message'] = [
                'type' => 'error',
                'text' => 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.',
            ];
            header('Location: ' . $redirect, true, 303);
            exit;
        }

        $principal = $this->readAmount($_POST['tagihan_pokok'] ?? null);
        $shipping = $this->readAmount($_POST['ongkir'] ?? null);
        $discount = $this->readAmount($_POST['diskon'] ?? null);
        if ($principal === null || $principal < 1 || $shipping === null || $discount === null) {
            $_SESSION['admin_order_message'] = [
                'type' => 'error',
                'text' => 'Periksa kembali tagihan pokok, ongkos kirim, dan diskon.',
            ];
            header('Location: ' . $redirect, true, 303);
            exit;
        }

        if ($principal + $shipping > 9999999999 || $discount > $principal + $shipping) {
            $_SESSION['admin_order_message'] = [
                'type' => 'error',
                'text' => 'Total tagihan terlalu besar atau diskon melebihi jumlah tagihan.',
            ];
            header('Location: ' . $redirect, true, 303);
            exit;
        }

        $updated = (new OrderModel($GLOBALS['pdo']))->verifyOrder(
            $orderId,
            $principal,
            $shipping,
            $discount
        );
        $_SESSION['admin_order_message'] = $updated
            ? ['type' => 'success', 'text' => 'Pesanan berhasil diverifikasi dan tagihan telah disimpan.']
            : ['type' => 'error', 'text' => 'Pesanan sudah tidak menunggu verifikasi atau tidak ditemukan.'];

        header(
            'Location: ' . ($updated ? '/?page=admin-orders' : $redirect),
            true,
            303
        );
        exit;
    }

    private function readInteger($value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer === false ? null : $integer;
    }

    private function readAmount($value): ?int
    {
        $amount = $this->readInteger($value);
        return $amount !== null && $amount >= 0 && $amount <= 9999999999
            ? $amount
            : null;
    }
}
