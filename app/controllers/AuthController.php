<?php

class AuthController
{
    private $model;
    private $admin;

    public function __construct($connectDatabase = true)
    {
        if ($connectDatabase) {
            global $pdo;

            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/../models/UserModel.php';
            require_once __DIR__ . '/../models/CartModel.php';
            require_once __DIR__ . '/../helpers/SmtpMailer.php';

            $this->model = new UserModel($pdo);
        }
        $this->admin = [
            'email' => getenv('ADMIN_EMAIL') ?: '',
            'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '',
        ];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login()
    {
        header('Content-Type: application/json; charset=utf-8');

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Email dan kata sandi wajib diisi.'
            ]);
            return;
        }
        $user = $this->model->findByEmail($email);

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'Email atau kata sandi salah.'
            ]);
            return;
        }


        // Cek password
        if (!password_verify(
            $password,
            $user['password']
        )) {
            echo json_encode([
                'success' => false,
                'message' => 'Email atau kata sandi salah.'
            ]);
            return;
        }

        if ($user['role'] !== 'CUSTOMER') {
            echo json_encode([
                'success' => false,
                'message' => 'Akun tidak memiliki akses customer.'
            ]);
            return;
        }

        $pendingCart = $_SESSION['pending_cart'] ?? [];
        $pendingCart = is_array($pendingCart) ? $pendingCart : [];
        $sessionCart = $_SESSION['cart'] ?? [];
        $sessionCart = is_array($sessionCart) ? $sessionCart : [];

        global $pdo;
        $cartModel = new CartModel($pdo);
        $cart = array_replace(
            $cartModel->getCart((int) $user['id_user']),
            $sessionCart,
            $pendingCart
        );
        foreach ($cart as $itemId => $item) {
            if (is_string($itemId) && is_array($item)) {
                $cartModel->saveItem((int) $user['id_user'], $itemId, $item);
            }
        }

        // Simpan session customer
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login'] = true;
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['no_hp'] = $user['no_hp'];
        $_SESSION['role'] = 'CUSTOMER';

        $redirect = '/?page=customer-home';
        if ($cart !== []) {
            $_SESSION['cart'] = $cart;
        }
        if ($pendingCart !== []) {
            $_SESSION['cart_message'] = 'Pesanan Anda berhasil ditambahkan ke keranjang.';
            $_SESSION['cart_message_type'] = 'success';
        }
        if ($cart !== []) {
            $redirect = '/?page=customer-cart';
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil.',
            'role' => 'CUSTOMER',
            'redirect' => $redirect
        ]);

        return;
    }


    public function loginAdmin()
    {
        header('Content-Type: application/json; charset=utf-8');

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Email dan kata sandi wajib diisi.']);
            return;
        }

        $isConfiguredAdmin = $email === $this->admin['email']
            && password_verify($password, $this->admin['password_hash']);
        $user = $isConfiguredAdmin ? false : $this->model->findByEmail($email);
        $isRegisteredAdmin = $user
            && $user['role'] === 'ADMIN'
            && password_verify($password, $user['password']);

        if (!$isConfiguredAdmin && !$isRegisteredAdmin) {
            echo json_encode(['success' => false, 'message' => 'Email atau kata sandi admin salah.']);
            return;
        }

        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login'] = true;
        $_SESSION['nama'] = $isConfiguredAdmin ? 'Admin' : $user['nama'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'ADMIN';
        if ($isRegisteredAdmin) {
            $_SESSION['id_user'] = $user['id_user'];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login admin berhasil.',
            'redirect' => '/?page=admin-dashboard'
        ]);
    }


    public function registerAdmin()
    {
        header('Content-Type: application/json; charset=utf-8');

        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $submittedToken = (string) ($_POST['csrf_token'] ?? '');
        if ($sessionToken === '' || $submittedToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.']);
            return;
        }

        $hasAdminSession = ($_SESSION['login'] ?? false) === true
            && ($_SESSION['role'] ?? '') === 'ADMIN';
        if (!$hasAdminSession) {
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPassword = $_POST['admin_password'] ?? '';
            $isConfiguredAdmin = $adminEmail === $this->admin['email']
                && password_verify($adminPassword, $this->admin['password_hash']);
            $adminUser = $isConfiguredAdmin ? false : $this->model->findByEmail($adminEmail);
            $isRegisteredAdmin = $adminUser
                && $adminUser['role'] === 'ADMIN'
                && password_verify($adminPassword, $adminUser['password']);

            if (!$isConfiguredAdmin && !$isRegisteredAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Verifikasi gagal. Masukkan kredensial admin yang sudah aktif.']);
                return;
            }
        }

        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $noHp = trim($_POST['no_hp'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';
        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $noHp === '') {
            echo json_encode(['success' => false, 'message' => 'Nama, email yang valid, dan nomor HP wajib diisi.']);
            return;
        }
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Kata sandi admin minimal 8 karakter.']);
            return;
        }
        if ($password !== $konfirmasi) {
            echo json_encode(['success' => false, 'message' => 'Konfirmasi kata sandi tidak sama.']);
            return;
        }
        if ($email === $this->admin['email'] || $this->model->emailExists($email)) {
            echo json_encode(['success' => false, 'message' => 'Email sudah digunakan.']);
            return;
        }

        $adminId = $this->model->createAdmin([
            'nama' => $nama,
            'email' => $email,
            'no_hp' => $noHp,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);

        if (!$hasAdminSession) {
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['login'] = true;
            $_SESSION['id_user'] = $adminId;
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'ADMIN';
        }

        echo json_encode([
            'success' => true,
            'message' => 'Akun admin berhasil didaftarkan.',
            'redirect' => '/?page=admin-dashboard'
        ]);
    }


    public function register()
    {
        header('Content-Type: application/json; charset=utf-8');

        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';
        $no_hp = trim($_POST['no_hp'] ?? '');


        // Nama wajib
        if ($nama === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Nama wajib diisi.'
            ]);
            return;
        }


        // Email valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Format email tidak valid.'
            ]);
            return;
        }


        // Email admin tidak boleh dipakai register
        if ($email === $this->admin['email']) {
            echo json_encode([
                'success' => false,
                'message' => 'Email sudah digunakan.'
            ]);
            return;
        }


        // Password minimal 8 karakter
        if (strlen($password) < 8) {
            echo json_encode([
                'success' => false,
                'message' => 'Kata sandi minimal 8 karakter.'
            ]);
            return;
        }


        // Konfirmasi password
        if ($password !== $konfirmasi) {
            echo json_encode([
                'success' => false,
                'message' => 'Konfirmasi password tidak sama.'
            ]);
            return;
        }


        // Nomor HP wajib
        if ($no_hp === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Nomor HP wajib diisi.'
            ]);
            return;
        }


        // Cek email database
        if ($this->model->emailExists($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email sudah terdaftar.'
            ]);
            return;
        }


        // Hash password
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        // Simpan customer
        // Role CUSTOMER sudah ditentukan di UserModel
        $idUser = $this->model->createUser([
            'nama' => $nama,
            'email' => $email,
            'password' => $passwordHash,
            'no_hp' => $no_hp
        ]);


        echo json_encode([
            'success' => true,
            'message' => 'Registrasi berhasil. Silakan login.',
            'id_user' => $idUser
        ]);

        return;
    }

    public function requestPasswordReset()
    {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['password_reset_message'] = 'Format email tidak valid.';
            $_SESSION['password_reset_message_type'] = 'error';
            header('Location: /?page=customer-forgot-password', true, 303);
            return;
        }

        $genericMessage = 'Jika email terdaftar, kami akan mengirim link reset kata sandi.';
        $now = time();
        $lastRequest = (int) ($_SESSION['password_reset_last_request'] ?? 0);
        $_SESSION['password_reset_last_request'] = $now;

        if ($now - $lastRequest >= 60) {
            $user = $this->model->findByEmail($email);
            if ($user && $user['role'] === 'CUSTOMER') {
                $token = bin2hex(random_bytes(32));
                if ($this->model->createPasswordResetToken(
                    (int) $user['id_user'],
                    hash('sha256', $token),
                    60
                )) {
                    $baseUrl = rtrim((string) (getenv('APP_BASE_URL') ?: ''), '/');
                    if ($baseUrl === '') {
                        throw new RuntimeException('APP_BASE_URL belum dikonfigurasi.');
                    }

                    $resetUrl = $baseUrl . '/?page=customer-reset-password&token=' . rawurlencode($token);
                    $mailer = new SmtpMailer();
                    $mailer->send(
                        $user['email'],
                        $user['nama'],
                        'Reset kata sandi Griya Sprei',
                        "Halo " . $user['nama'] . ",\n\n"
                            . "Gunakan link berikut untuk membuat kata sandi baru. Link berlaku selama 10 menit dan hanya dapat digunakan sekali:\n"
                            . $resetUrl . "\n\n"
                            . "Jika Anda tidak meminta reset kata sandi, abaikan email ini."
                    );
                }
            }
        }

        $_SESSION['password_reset_message'] = $genericMessage;
        $_SESSION['password_reset_message_type'] = 'success';
        header('Location: /?page=customer-forgot-password', true, 303);
    }

    public function resetPassword()
    {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['konfirmasi_password'] ?? '';

        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            $_SESSION['password_reset_message'] = 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru.';
            $_SESSION['password_reset_message_type'] = 'error';
            header('Location: /?page=customer-forgot-password', true, 303);
            return;
        }
        if (strlen($password) < 8) {
            $this->redirectToResetForm($token, 'Kata sandi minimal 8 karakter.');
            return;
        }
        if ($password !== $confirmation) {
            $this->redirectToResetForm($token, 'Konfirmasi kata sandi tidak sama.');
            return;
        }

        if ($this->model->resetCustomerPassword(hash('sha256', $token), password_hash($password, PASSWORD_DEFAULT))) {
            $_SESSION['cart_message'] = 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.';
            $_SESSION['cart_message_type'] = 'success';
            header('Location: /?page=customer-login', true, 303);
            return;
        }

        $_SESSION['password_reset_message'] = 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru.';
        $_SESSION['password_reset_message_type'] = 'error';
        header('Location: /?page=customer-forgot-password', true, 303);
    }

    private function redirectToResetForm($token, $message)
    {
        $_SESSION['password_reset_message'] = $message;
        $_SESSION['password_reset_message_type'] = 'error';
        header('Location: /?page=customer-reset-password&token=' . rawurlencode($token), true, 303);
    }

    // =========================================
    // LOGOUT
    // =========================================
    public function logoutCustomer()
    {
        $this->logoutRole('CUSTOMER', '/?page=customer-login');
    }

    public function logoutAdmin()
    {
        $this->logoutRole('ADMIN', '/?page=admin-login');
    }

    private function logoutRole($role, $redirect)
    {
        if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== $role) {
            header('Location: ' . $redirect);
            exit;
        }

        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            echo 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.';
            return;
        }

        if ($role === 'CUSTOMER') {
            $anonymousSession = array_intersect_key($_SESSION, ['pending_cart' => true]);
            session_regenerate_id(true);
            $_SESSION = $anonymousSession;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            }
            session_destroy();
        }

        header('Location: ' . $redirect);
        exit;
    }
}
