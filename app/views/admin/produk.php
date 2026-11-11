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

$controller = new ProductController();
$products = $controller->getAllProducts();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/crud.css">
</head>
<body class="admin-dashboard admin-products">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="dashboard-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <nav class="product-breadcrumb" aria-label="Breadcrumb">
            <a href="/?page=admin-dashboard">Admin</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Katalog Produk</span>
        </nav>
        <header class="dashboard-header product-header">
            <h1>Katalog Produk</h1>
            <a href="/?page=admin-add-product" class="product-add-button"><span aria-hidden="true">+</span> Tambah Produk</a>
        </header>

        <section class="product-table-card" aria-label="Daftar produk">
            <div class="product-table-scroll">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Ukuran Standar</th>
                            <th>Harga Dasar</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="product-empty">Belum ada produk yang ditambahkan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                            <td>
                                <?php if (!empty($product['gambar_produk'])): ?>
                                    <img
                                        src="/uploads/produk/<?= rawurlencode(basename($product['gambar_produk'])) ?>"
                                        class="product-image"
                                        alt="<?= e($product['nama_produk']) ?>"
                                    >
                                <?php else: ?>
                                    <span class="product-image-empty">Belum ada gambar</span>
                                <?php endif; ?>
                            </td>
                            <td class="product-name-cell">
                                <strong><?= e($product['nama_produk']) ?></strong>
                            </td>
                            <td>
                                <span class="product-category"><?= e([
                                'SPREI' => 'Sprei',
                                'BED_COVER' => 'Bed Cover',
                                'BEDCOVER' => 'Bed Cover',
                                'SARUNG_BANTAL' => 'Sarung Bantal',
                                'SARUNG_GULING' => 'Sarung Guling',
                                ][$product['kategori_produk']] ?? $product['kategori_produk']) ?></span>
                            </td>
                            <td class="product-nowrap">
                                <?= e($product['panjang_standar']) ?> ×
                                <?= e($product['lebar_standar']) ?> ×
                                <?= e($product['tinggi_standar']) ?> cm
                            </td>
                            <td class="product-nowrap">Rp <?= number_format((float) $product['harga_dasar'], 0, ',', '.') ?></td>
                            <td><?= (int) $product['stok'] ?></td>
                            <td>
                                <div class="product-actions">
                                    <a
                                        class="product-action product-action-edit"
                                        href="/?page=admin-edit-product&amp;id=<?= (int) $product['id_produk'] ?>"
                                    >Edit</a>
                                    <form
                                        action="/?action=admin-product-delete"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                    >
                                        <input type="hidden" name="id_produk" value="<?= (int) $product['id_produk'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <button type="submit" class="product-action product-action-delete">Hapus</button>
                                    </form>
                                </div>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
</body>
</html>