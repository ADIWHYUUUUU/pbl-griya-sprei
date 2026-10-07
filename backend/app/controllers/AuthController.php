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


    // =========================================
    // LOGIN
    // =========================================
    public function login()
    {
        header('Content-Type: application/json; charset=utf-8');

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi input
        if ($email === '' || $password === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Email dan password wajib diisi.'
            ]);
            return;
        }


        // =====================================
        // LOGIN ADMIN
        // =====================================
        if ($email === $this->admin['email']) {

            if (!password_verify(
                $password,
                $this->admin['password_hash']
            )) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email atau password salah.'
                ]);
                return;
            }

            // Simpan session admin
            $_SESSION['login'] = true;
            $_SESSION['nama'] = 'Admin';
            $_SESSION['email'] = $this->admin['email'];
            $_SESSION['role'] = 'ADMIN';

            echo json_encode([
                'success' => true,
                'message' => 'Login admin berhasil.',
                'role' => 'ADMIN',
                'redirect' => 'admin/index.php'
            ]);

            return;
        }


        // =====================================
        // LOGIN CUSTOMER DARI DATABASE
        // =====================================
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


    // =========================================
    // REGISTER CUSTOMER
    // =========================================
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