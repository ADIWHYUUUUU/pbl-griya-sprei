<?php

// =========================================
// ACTION - UPDATE PRODUK
// =========================================

// Memanggil ProductController
require_once __DIR__ . '/../../app/controllers/product_controller.php';

// =========================================
// VALIDASI REQUEST METHOD
// =========================================

// Update produk hanya boleh menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method tidak diizinkan.');
}

// =========================================
// VALIDASI ID PRODUK
// =========================================

// Mengambil id_produk dari form edit.php
$id = filter_var(
    $_POST['id_produk'] ?? null,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);

// Jika ID kosong atau tidak valid
if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

// =========================================
// MEMANGGIL CONTROLLER
// =========================================

$controller = new ProductController();

// Controller menangani:
// 1. Validasi session ADMIN
// 2. Validasi CSRF token
// 3. Validasi seluruh input produk
// 4. Upload gambar baru jika dipilih
// 5. Mempertahankan gambar lama jika tidak diganti
// 6. Memanggil ProductModel untuk UPDATE database
// 7. Redirect setelah berhasil

$controller->update($id);