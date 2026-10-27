<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /?page=admin-login');
    exit;
}

require_once __DIR__ . '/../../controllers/ProductController.php';

$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

$controller = new ProductController();
$product = $controller->getProductById($id);

if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

require __DIR__ . '/tambah.php';
