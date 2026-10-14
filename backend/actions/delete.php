<?php

require_once __DIR__ . '/../app/controllers/ProductController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method tidak diizinkan.');
}

$id = filter_var(
    $_POST['id_produk'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

$controller = new ProductController();
$controller->delete($id);