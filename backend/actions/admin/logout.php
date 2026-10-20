<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method tidak diizinkan.');
}

// Hapus seluruh data session
$_SESSION = [];

// Hapus cookie session jika digunakan
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session
session_destroy();

// Arahkan kembali ke login admin
header('Location: /frontend/pages/user/index.php');
exit;