<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{
    private $model;
    private $admin;

    public function __construct()
    {
        global $pdo;

        $this->model = new UserModel($pdo);

        // Akun admin dari config/admin.php
        $this->admin = require __DIR__ . '/../config/admin.php';

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
                'message' => 'Email dan password wajib diisi.'
            ]);
            return;
        }


       
        $user = $this->model->findByEmail($email);

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'Email atau password salah.'
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
                'message' => 'Email atau password salah.'
            ]);
            return;
        }


        // Pastikan akun adalah CUSTOMER
        if ($user['role'] !== 'CUSTOMER') {
            echo json_encode([
                'success' => false,
                'message' => 'Akun tidak memiliki akses customer.'
            ]);
            return;
        }


        // Simpan session customer
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['login'] = true;
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'CUSTOMER';


        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil.',
            'role' => 'CUSTOMER',
            'redirect' => 'user/index.php'
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
            'redirect' => '/frontend/pages/admin/index.php'
        ]);
    }


    public function registerAdmin()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
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
            $_SESSION['login'] = true;
            $_SESSION['id_user'] = $adminId;
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'ADMIN';
        }

        echo json_encode([
            'success' => true,
            'message' => 'Akun admin berhasil didaftarkan.',
            'redirect' => '/frontend/pages/admin/index.php'
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


        // Password minimal 6 karakter
        if (strlen($password) < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'Password minimal 6 karakter.'
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


    // =========================================
    // LOGOUT
    // =========================================
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        session_destroy();

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'message' => 'Logout berhasil.'
        ]);

        return;
    }
}