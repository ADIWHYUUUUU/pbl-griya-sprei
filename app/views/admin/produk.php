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
$categories = [
    '' => 'Semua',
    'SPREI' => 'Sprei',
    'BED_COVER' => 'Bed Cover',
    'SARUNG_BANTAL_GULING' => 'Sarung Bantal & Guling',
];
$categoryAliases = [
    'BEDCOVER' => 'BED_COVER',
    'SARUNG_BANTAL' => 'SARUNG_BANTAL_GULING',
    'SARUNG_GULING' => 'SARUNG_BANTAL_GULING',
];
$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$category = is_string($_GET['kategori'] ?? null) ? $_GET['kategori'] : '';
$category = isset($categories[$category]) ? $category : '';
$filteredProducts = array_values(array_filter($products, static function ($product) use ($category, $categoryAliases, $query) {
    $key = $product['kategori_produk'];
    return ($category === '' || ($categoryAliases[$key] ?? $key) === $category)
        && ($query === '' || stripos($product['nama_produk'], $query) !== false);
}));
$pageSize = 10;
$total = count($filteredProducts);
$lastPage = max(1, (int) ceil($total / $pageSize));
$currentPage = filter_var($_GET['halaman'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
$currentPage = max(1, min($lastPage, $currentPage));
$offset = ($currentPage - 1) * $pageSize;
$products = array_slice($filteredProducts, $offset, $pageSize);
$catalogUrl = static function (string $category, int $number = 1) use ($query): string {
    return '/?' . http_build_query(array_filter([
        'page' => 'admin-products',
        'q' => $query,
        'kategori' => $category,
        'halaman' => $number > 1 ? $number : '',
    ], static fn ($value) => $value !== ''));
};
$successMessages = [
    'created' => 'Produk berhasil ditambahkan.',
    'updated' => 'Produk berhasil diperbarui.',
    'deleted' => 'Produk berhasil dihapus.',
];
$successKey = $_GET['success'] ?? '';
$successMessage = is_string($successKey) ? ($successMessages[$successKey] ?? '') : '';
$deleteError = $_SESSION['product_delete_error'] ?? '';
unset($_SESSION['product_delete_error']);

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
    <title>Katalog Produk - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/crud.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/admin/crud.css') ?>">
</head>
<body class="admin-dashboard admin-products">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="product-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <?php if ($successMessage !== ''): ?>
            <div class="product-toast" id="productToast" role="status" aria-live="polite">
                <span class="product-toast__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="m5 12 4 4L19 6" />
                    </svg>
                </span>
                <span class="product-toast__message"><?= e($successMessage) ?></span>
                <button class="product-toast__close" type="button" aria-label="Tutup notifikasi">&times;</button>
            </div>
        <?php endif; ?>
        <nav class="product-breadcrumb" aria-label="Breadcrumb">
            <a href="/?page=admin-dashboard">Admin</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Katalog Produk</span>
        </nav>
        <div class="product-content">
        <header class="product-header">
            <div>
                <h1>Katalog Produk</h1>
                <p>Kelola produk yang tampil di katalog pelanggan.</p>
            </div>
            <a href="/?page=admin-add-product" class="product-add-button">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Produk
            </a>
        </header>
        <?php if ($deleteError !== ''): ?>
            <p role="alert"><?= e($deleteError) ?></p>
        <?php endif; ?>

        <div class="product-toolbar">
            <form method="get" action="/" class="product-search">
                <input type="hidden" name="page" value="admin-products">
                <input type="hidden" name="kategori" value="<?= e($category) ?>">
                <input type="search" name="q" aria-label="Cari nama produk" placeholder="Cari nama produk" value="<?= e($query) ?>">
                <button type="submit" aria-label="Cari produk"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></button>
            </form>
            <nav class="product-filters" aria-label="Kategori produk">
                <?php foreach ($categories as $key => $label): ?>
                    <a href="<?= e($catalogUrl($key)) ?>" <?= $category === $key ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <section class="product-table-card" aria-label="Daftar produk">
            <div class="product-table-scroll" tabindex="0" role="region" aria-label="Tabel produk, dapat digeser ke samping">
                <table class="product-table">
                    <colgroup>
                        <col class="product-col-image">
                        <col class="product-col-name">
                        <col class="product-col-category">
                        <col class="product-col-size">
                        <col class="product-col-price">
                        <col class="product-col-stock">
                        <col class="product-col-actions">
                    </colgroup>
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
                            <td colspan="7" class="product-empty"><?= $query !== '' || $category !== '' ? 'Tidak ada produk yang cocok.' : 'Belum ada produk yang ditambahkan.' ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                            <td>
                                <?php if (!empty($product['gambar_produk'])): ?>
                                    <img
                                        src="<?= e(product_image_url($product['gambar_produk'])) ?>"
                                        class="product-image"
                                        alt="<?= e($product['nama_produk']) ?>"
                                    >
                                <?php else: ?>
                                    <span class="product-image-empty" role="img" aria-label="Belum ada gambar">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="product-name-cell">
                                <strong><?= e($product['nama_produk']) ?></strong>
                            </td>
                            <td>
                                <?php $key = $product['kategori_produk']; ?>
                                <span class="product-category"><?= e($categories[$categoryAliases[$key] ?? $key] ?? $key) ?></span>
                            </td>
                            <td class="product-nowrap">
                                <?= e($product['panjang_standar']) ?> ×
                                <?= e($product['lebar_standar']) ?><?= $product['tinggi_standar'] !== null ? ' × ' . e($product['tinggi_standar']) : '' ?> cm
                            </td>
                            <td class="product-nowrap product-price">Rp <?= number_format((float) $product['harga_dasar'], 0, ',', '.') ?></td>
                            <td><?= (int) $product['stok'] ?></td>
                            <td>
                                <div class="product-actions">
                                    <a
                                        class="product-action product-action-edit"
                                        href="/?page=admin-edit-product&amp;id=<?= (int) $product['id_produk'] ?>"
                                    ><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16zM13 7l4 4"/></svg>Edit</a>
                                    <form
                                        action="/?action=admin-product-delete"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                    >
                                        <input type="hidden" name="id_produk" value="<?= (int) $product['id_produk'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <button type="submit" class="product-action product-action-delete" aria-label="Hapus <?= e($product['nama_produk']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>Hapus</button>
                                    </form>
                                </div>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <footer class="product-pagination">
                <span>Menampilkan <?= $total ? $offset + 1 : 0 ?>–<?= $offset + count($products) ?> dari <?= $total ?> produk</span>
                <nav aria-label="Halaman">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= e($catalogUrl($category, $currentPage - 1)) ?>">‹ Sebelumnya</a>
                    <?php else: ?>
                        <span aria-disabled="true">‹ Sebelumnya</span>
                    <?php endif; ?>
                    <?php for ($number = max(1, min($currentPage - 1, $lastPage - 2)); $number <= min($lastPage, max(3, $currentPage + 1)); $number++): ?>
                        <a href="<?= e($catalogUrl($category, $number)) ?>" <?= $number === $currentPage ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                    <?php endfor; ?>
                    <?php if ($currentPage < $lastPage): ?>
                        <a href="<?= e($catalogUrl($category, $currentPage + 1)) ?>">Berikutnya ›</a>
                    <?php else: ?>
                        <span aria-disabled="true">Berikutnya ›</span>
                    <?php endif; ?>
                </nav>
            </footer>
        </section>
        </div>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/product-toast.js"></script>
</body>
</html>
