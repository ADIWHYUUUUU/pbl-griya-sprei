<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaymentModel.php';

class AdminPaymentController
{
    private PaymentModel $model;

    public function __construct()
    {
        global $pdo;

        $this->model = new PaymentModel($pdo);
    }

    public function index(int $page): array
    {
        return $this->model->getAdminPayments($page, 10);
    }

    public function verifyForm(?int $paymentId): ?array
    {
        if ($paymentId === null || $paymentId < 1) {
            return null;
        }

        return $this->model->getAdminPayment($paymentId);
    }

    public function verifySave(): void
    {
        $this->authorizeAdminPost();

        $paymentId = filter_var($_POST['id_pembayaran'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($paymentId === false) {
            $this->redirectWithMessage('ID pembayaran tidak valid.', 'error');
        }

        $decision = $_POST['decision'] ?? '';
        try {
            if ($decision === 'verify') {
                $this->model->verifyAdminPayment((int) $paymentId);
                $this->redirectWithMessage('Pembayaran berhasil diverifikasi.', 'success');
            }

            if ($decision === 'reject') {
                $reason = $_POST['catatan_admin'] ?? '';
                if (!is_string($reason)) {
                    $reason = '';
                }
                $this->model->rejectAdminPayment((int) $paymentId, $reason);
                $this->redirectWithMessage('Bukti pembayaran ditolak.', 'success');
            }

            $this->redirectWithMessage('Aksi verifikasi pembayaran tidak valid.', 'error');
        } catch (DomainException $error) {
            $this->redirectWithMessage($error->getMessage(), 'error');
        }
    }

    private function authorizeAdminPost(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            ($_SESSION['login'] ?? false) !== true ||
            ($_SESSION['role'] ?? '') !== 'ADMIN'
        ) {
            http_response_code(403);
            exit('Akses ditolak.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method tidak diizinkan.');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (
            !is_string($token) ||
            !isset($_SESSION['csrf_token']) ||
            !hash_equals((string) $_SESSION['csrf_token'], $token)
        ) {
            http_response_code(403);
            exit('Token CSRF tidak valid.');
        }
    }

    private function redirectWithMessage(string $message, string $type): never
    {
        $_SESSION['admin_payment_message'] = $message;
        $_SESSION['admin_payment_message_type'] = $type;
        header('Location: /?page=admin-payments', true, 303);
        exit;
    }
}
