<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../models/PaymentModel.php';

class PaymentController
{
    private PaymentModel $model;

    public function __construct()
    {
        $this->model = new PaymentModel($GLOBALS['pdo']);
    }

    public function getOrder(?int $orderId): ?array
    {
        $userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($orderId === null || $orderId < 1 || $userId === false || $userId === null) {
            return null;
        }

        return $this->model->getCustomerOrder($orderId, $userId);
    }

    public function submit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'CUSTOMER') {
            http_response_code(403);
            exit('Akses ditolak.');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Metode request tidak diizinkan.');
        }

        $orderId = filter_var($_POST['id_pesanan'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($orderId === false || $orderId === null) {
            $this->setError(null, 'Pesanan tidak valid.');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (
            !is_string($token)
            || !isset($_SESSION['csrf_token'])
            || !hash_equals((string) $_SESSION['csrf_token'], $token)
        ) {
            $this->setError($orderId, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }

        $order = $this->getOrder($orderId);
        if (!$order) {
            http_response_code(404);
            exit('Pesanan tidak ditemukan.');
        }
        if ($order['status_pesanan'] !== 'MENUNGGU_PEMBAYARAN') {
            $this->setError($orderId, 'Pesanan ini belum dapat menerima pembayaran.');
        }
        if ($order['payment'] && $order['payment']['status_pembayaran'] !== 'DITOLAK') {
            $this->setError($orderId, 'Pembayaran sebelumnya masih diproses atau sudah diverifikasi.');
        }

        $config = require __DIR__ . '/../config/payment.php';
        $method = $_POST['metode_pembayaran'] ?? '';
        if (!is_string($method) || !isset($config['accounts'][$method])) {
            $this->setError($orderId, 'Pilih metode pembayaran yang valid.');
        }

        $rawAmount = $_POST['nominal_transfer'] ?? '';
        if (!is_string($rawAmount) || !is_numeric($rawAmount)) {
            $this->setError($orderId, 'Nominal transfer harus berupa angka yang valid.');
        }
        $amount = (float) $rawAmount;
        $subtotal = array_sum(array_map(
            static fn (array $item): float => (float) $item['jumlah'] * (float) $item['harga_satuan'],
            $order['items']
        ));
        $principal = (float) ($order['tagihan_pokok'] ?: $subtotal);
        $total = max(0, $principal + (float) $order['ongkir'] - (float) $order['diskon']);
        if ($amount <= 0 || $amount > 9999999999.99 || abs($amount - $total) >= 0.01) {
            $this->setError($orderId, 'Nominal transfer harus sama dengan total tagihan.');
        }

        $file = $_FILES['bukti_transfer'] ?? null;
        if (!is_array($file)) {
            $this->setError($orderId, 'Bukti transfer wajib diunggah.');
        }

        try {
            $upload = save_upload(
                $file,
                'bukti-transfer',
                2 * 1024 * 1024,
                [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'application/pdf' => 'pdf',
                ]
            );
        } catch (InvalidArgumentException | RuntimeException $error) {
            $this->setError($orderId, $error->getMessage());
        }

        try {
            $this->model->create($orderId, $method, $amount, $upload);
        } catch (DomainException $error) {
            $this->removeUpload($upload['path']);
            $this->setError($orderId, $error->getMessage());
        } catch (Throwable $error) {
            $this->removeUpload($upload['path']);
            throw $error;
        }

        $_SESSION['payment_message'] = 'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi Admin.';
        $_SESSION['payment_message_type'] = 'success';
        $this->redirect($orderId);
    }

    private function setError(?int $orderId, string $message): never
    {
        $_SESSION['payment_message'] = $message;
        $_SESSION['payment_message_type'] = 'error';
        $_SESSION['payment_form'] = [
            'metode_pembayaran' => is_string($_POST['metode_pembayaran'] ?? null)
                ? $_POST['metode_pembayaran']
                : '',
            'nominal_transfer' => is_string($_POST['nominal_transfer'] ?? null)
                ? $_POST['nominal_transfer']
                : '',
        ];
        $this->redirect($orderId);
    }

    private function removeUpload(string $relativePath): void
    {
        $path = dirname(__DIR__, 2) . '/public/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        if (is_file($path) && !unlink($path)) {
            error_log('Gagal menghapus bukti transfer setelah transaksi database gagal.');
        }
    }

    private function redirect(?int $orderId): never
    {
        if ($orderId === null) {
            header('Location: /?page=customer-orders', true, 303);
            exit;
        }

        header('Location: /?page=customer-payment&id=' . $orderId, true, 303);
        exit;
    }
}