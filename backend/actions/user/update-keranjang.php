
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/cart_controller.php';

$cart = new CartController($pdo);

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

try {
    $cart->updateQuantity(
        (string) ($_POST['item_id'] ?? ''),
        $_POST['qtySprei'] ?? null
    );

    $_SESSION['cart_success'] =
        'Jumlah produk berhasil diperbarui.';

} catch (InvalidArgumentException $e) {
    $_SESSION['cart_error'] = $e->getMessage();

} catch (Throwable $e) {
    error_log('[CART UPDATE] ' . $e->getMessage());

    $_SESSION['cart_error'] =
        'Terjadi kesalahan saat memperbarui jumlah.';
}

header('Location: /frontend/pages/user/keranjang.php');
exit;
