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
    <link rel="stylesheet" href="/assets/css/admin/dashboard.css">
</head>
<body>
<main class="container">
    <div class="topbar">
        <div>
            <h1>Manajemen Produk</h1>
            <p>Kelola produk Griya Sprei melalui dashboard Admin.</p>
        </div>
        <div class="admin-actions">
            <a href="/?page=admin-dashboard" class="btn btn-edit">Dashboard</a>
            <a href="/?page=admin-edit-home" class="btn btn-edit">Edit Beranda</a>
            <a href="/?page=admin-register" class="btn btn-add">Tambah Admin</a>
            <a href="/?page=admin-add-product" class="btn btn-add">+ Tambah Produk</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
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
                    <td colspan="8" class="empty">Belum ada produk yang ditambahkan.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= (int) $product['id_produk'] ?></td>
                        <td>
                            <?php if (!empty($product['gambar_produk'])): ?>
                                <img
                                    src="/uploads/produk/<?= rawurlencode(basename($product['gambar_produk'])) ?>"
                                    class="product-image"
                                    alt="<?= e($product['nama_produk']) ?>"
                                >
                            <?php else: ?>
                                <span>Belum ada gambar</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($product['nama_produk']) ?></td>
                        <td><?= e($product['kategori_produk']) ?></td>
                        <td>
                            <?= e($product['panjang_standar']) ?> ×
                            <?= e($product['lebar_standar']) ?> ×
                            <?= e($product['tinggi_standar']) ?> cm
                        </td>
                        <td>Rp <?= number_format((float) $product['harga_dasar'], 0, ',', '.') ?></td>
                        <td><?= (int) $product['stok'] ?></td>
                        <td>
                            <div class="actions">
                                <a
                                    class="btn btn-edit"
                                    href="/?page=admin-edit-product&amp;id=<?= (int) $product['id_produk'] ?>"
                                >Edit</a>
                                <form
                                    action="/?action=admin-product-delete"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                >
                                    <input type="hidden" name="id_produk" value="<?= (int) $product['id_produk'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <button type="submit" class="btn btn-delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>