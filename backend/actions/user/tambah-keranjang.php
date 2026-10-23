<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/cart_controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method tidak diizinkan.');
}

if (!CartController::isCustomer()) {
    header('Location: /frontend/pages/login.php');
    exit;
}

if (!CartController::validateCsrf()) {
    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

$productId = filter_var(
    $_POST['id_produk'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$detailUrl = $productId !== false
    ? '/frontend/pages/user/detail_produk.php?id=' . $productId
    : '/frontend/pages/user/katalog.php';

try {
    $cart = new CartController($pdo);
    $cart->add($_POST);

    header('Location: /frontend/pages/user/keranjang.php?added=1');
    exit;
} catch (InvalidArgumentException $e) {
    $_SESSION['cart_error'] = $e->getMessage();
} catch (Throwable $e) {
    error_log('[CART ADD] ' . $e->getMessage());
    $_SESSION['cart_error'] =
        'Terjadi kesalahan saat menambahkan produk ke keranjang.';
}

header('Location: ' . $detailUrl);
exit;
