<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PasswordResetService.php';

class PasswordResetController
{
    private $service;

    public function __construct()
    {
        global $pdo;
        $this->service = new PasswordResetService($pdo);
    }

    public function requestCode()
    {
        if (!$this->csrfValid()) {
            $this->respond(['success' => false, 'message' => 'Token keamanan tidak valid. Muat ulang halaman.'], 403);
            return;
        }
        $email = trim($this->post('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(['success' => false, 'message' => 'Masukkan email yang valid.'], 422);
            return;
        }
        $flow = bin2hex(random_bytes(32));
        $userId = $this->service->requestCode($email, $this->ip(), $flow);
        $_SESSION['password_reset_user_id'] = $userId;
        $_SESSION['password_reset_flow'] = $flow;
        unset($_SESSION['password_reset_grant']);
        $this->respond([
            'success' => true,
            'message' => 'Jika email terdaftar, kode verifikasi akan dikirim.',
            'redirect' => '/?page=customer-password-otp',
        ]);
    }

    public function resendCode()
    {
        if (!$this->csrfValid() || !$this->hasFlow()) {
            $this->respond(['success' => false, 'message' => 'Sesi verifikasi berakhir. Silakan mulai kembali.'], 403);
            return;
        }
        $this->service->resendCode(
            (int) $_SESSION['password_reset_user_id'],
            $this->ip(),
            $_SESSION['password_reset_flow']
        );
        $this->respond(['success' => true, 'message' => 'Jika email terdaftar, kode verifikasi akan dikirim.']);
    }

    public function verifyCode()
    {
        if (!$this->csrfValid() || !$this->hasFlow()) {
            $this->respond(['success' => false, 'message' => 'Sesi verifikasi berakhir. Silakan mulai kembali.'], 403);
            return;
        }
        $grant = $this->service->verifyCode(
            (int) $_SESSION['password_reset_user_id'],
            $_SESSION['password_reset_flow'],
            $this->post('otp')
        );
        if ($grant === null) {
            $this->respond(['success' => false, 'message' => 'Kode verifikasi tidak valid atau kedaluwarsa.']);
            return;
        }
        $_SESSION['password_reset_grant'] = $grant;
        $this->respond(['success' => true, 'redirect' => '/?page=customer-password-reset']);
    }

    public function savePassword()
    {
        if (!$this->csrfValid()) {
            $this->respond(['success' => false, 'message' => 'Token keamanan tidak valid. Muat ulang halaman.'], 403);
            return;
        }
        $password = $this->post('password');
        if (strlen($password) < 8) {
            $this->respond(['success' => false, 'message' => 'Kata sandi minimal 8 karakter.'], 422);
            return;
        }
        if ($password !== $this->post('konfirmasi_password')) {
            $this->respond(['success' => false, 'message' => 'Konfirmasi kata sandi tidak sama.'], 422);
            return;
        }
        $saved = $this->service->updatePassword(
            (int) ($_SESSION['password_reset_user_id'] ?? 0),
            $_SESSION['password_reset_grant'] ?? '',
            password_hash($password, PASSWORD_DEFAULT)
        );
        if (!$saved) {
            $this->clearFlow();
            $this->respond([
                'success' => false,
                'message' => 'Izin reset berakhir atau tidak valid. Silakan minta kode baru.',
                'redirect' => '/?page=customer-password-forgot',
            ], 403);
            return;
        }
        $this->clearFlow();
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['cart_message'] = 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru.';
        $_SESSION['cart_message_type'] = 'success';
        $this->respond(['success' => true, 'redirect' => '/?page=customer-login']);
    }

    public function canVerify()
    {
        return $this->hasFlow();
    }

    public function canReset()
    {
        return $this->hasFlow() && $this->service->hasValidGrant(
            (int) $_SESSION['password_reset_user_id'],
            $_SESSION['password_reset_grant'] ?? ''
        );
    }

    private function hasFlow()
    {
        return isset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_flow'])
            && is_string($_SESSION['password_reset_flow'])
            && preg_match('/^[a-f0-9]{64}$/D', $_SESSION['password_reset_flow']) === 1;
    }

    private function csrfValid()
    {
        $token = $_SESSION['csrf_token'] ?? '';
        $submitted = $this->post('csrf_token');
        return is_string($token) && $token !== '' && $submitted !== '' && hash_equals($token, $submitted);
    }

    private function post($name)
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : '';
    }

    private function ip()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }

    private function clearFlow()
    {
        unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_flow'], $_SESSION['password_reset_grant']);
    }

    private function respond($data, $status = 200)
    {
        http_response_code($status);
        echo json_encode($data);
    }
}
