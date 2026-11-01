<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: /?page=admin-login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductModel.php';

$contentPath = __DIR__ . '/../../../storage/homepage.json';
$uploadDirectory = __DIR__ . '/../../../public/uploads/beranda/';
$uploadUrl = '/uploads/beranda/';
$defaults = [
    'hero_pill' => 'Griya Sprei Malang · Homemade sejak 2010',
    'hero_title' => 'Sprei dan bed cover pas di kasur Anda, ukuran standar maupun custom.',
    'hero_description' => 'Pilih model kain, tentukan ukuran kasur presisi, dan nikmati tidur nyaman.',
    'hero_image' => '',
    'explore_title' => 'Jelajahi katalog',
    'sprei_title' => 'Sprei',
    'sprei_description' => 'Standar atau custom sesuai kasur.',
    'sprei_image' => '',
    'bedcover_title' => 'Bed Cover',
    'bedcover_description' => 'Pilih model dan ukuran.',
    'bedcover_image' => '',
    'featured_title' => 'Produk Pilihan & Best Seller',
    'featured' => [],
];
$content = $defaults;
if (is_file($contentPath)) {
    $savedContent = json_decode((string) file_get_contents($contentPath), true);
    if (is_array($savedContent)) {
        $content = array_replace($defaults, $savedContent);
    }
}

$products = (new ProductModel($pdo))->getAllProducts();
$productsById = [];
foreach ($products as $product) {
    $productsById[(int) $product['id_produk']] = $product;
}
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$notice = isset($_GET['saved']) ? 'Perubahan beranda berhasil disimpan.' : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $updated = [];
        foreach (array_keys($defaults) as $key) {
            if ($key !== 'featured') {
                $updated[$key] = trim((string) ($_POST[$key] ?? ''));
            }
        }

        try {
            foreach (['hero_title' => 240, 'hero_description' => 600, 'sprei_title' => 80, 'sprei_description' => 180, 'bedcover_title' => 80, 'bedcover_description' => 180] as $key => $maxLength) {
                if (mb_strlen($updated[$key]) > $maxLength) {
                    throw new RuntimeException('Teks ' . $key . ' terlalu panjang.');
                }
            }
            if ($updated['hero_title'] === '' || $updated['hero_description'] === '') {
                throw new RuntimeException('Judul dan deskripsi hero wajib diisi.');
            }
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }

            $saveUpload = static function (string $field, string $previous = '') use ($uploadDirectory, $uploadUrl): string {
                if (!isset($_FILES[$field]) || (int) $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                    return $previous;
                }
                if ((int) $_FILES[$field]['error'] !== UPLOAD_ERR_OK || (int) $_FILES[$field]['size'] > 5 * 1024 * 1024) {
                    throw new RuntimeException('Gambar harus berukuran maksimal 5 MB.');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!is_string($mime) || !isset($extensions[$mime])) {
                    throw new RuntimeException('Format gambar yang didukung: JPG, PNG, atau WebP.');
                }
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDirectory . $filename)) {
                    throw new RuntimeException('Gambar gagal diunggah. Periksa izin folder uploads.');
                }
                if ($previous !== '' && str_starts_with($previous, $uploadUrl)) {
                    $oldPath = __DIR__ . '/../../../public' . $previous;
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                return $uploadUrl . $filename;
            };

            foreach (['hero_image', 'sprei_image', 'bedcover_image'] as $imageKey) {
                $updated[$imageKey] = $saveUpload($imageKey, (string) ($content[$imageKey] ?? ''));
            }

            $submittedSlots = $_POST['featured'] ?? [];
            if (!is_array($submittedSlots) || count($submittedSlots) !== 4) {
                throw new RuntimeException('Produk pilihan harus terdiri dari tepat 4 slot.');
            }
            $selectedIds = [];
            $updated['featured'] = [];
            foreach (array_values($submittedSlots) as $slot) {
                if (!is_array($slot)) {
                    throw new RuntimeException('Format slot produk tidak valid.');
                }
                $rawId = trim((string) ($slot['id_produk'] ?? ''));
                $id = $rawId === '' ? null : filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id !== null && $id === false) {
                    throw new RuntimeException('Produk pilihan tidak valid.');
                }
                if ($id !== null && isset($selectedIds[(int) $id])) {
                    throw new RuntimeException('Produk pilihan tidak boleh dipilih lebih dari sekali.');
                }
                if ($id !== null && !isset($productsById[(int) $id])) {
                    throw new RuntimeException('Produk pilihan tidak ditemukan di katalog.');
                }
                if ($id !== null) {
                    $selectedIds[(int) $id] = true;
                }
                $badge = trim((string) ($slot['badge'] ?? ''));
                if (mb_strlen($badge) > 60) {
                    throw new RuntimeException('Badge produk maksimal 60 karakter.');
                }
                $updated['featured'][] = [
                    'id_produk' => $id === null ? null : (int) $id,
                    'badge' => $badge,
                    'tampil' => isset($slot['tampil']),
                ];
            }

            $json = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false || file_put_contents($contentPath, $json . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Konten gagal disimpan. Periksa izin folder storage.');
            }
            header('Location: /?page=admin-edit-home&saved=1');
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $content = array_replace($content, $updated);
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$legacyFeatured = is_array($content['featured'] ?? null) ? $content['featured'] : [];
$slots = [];
for ($index = 0; $index < 4; $index++) {
    $savedSlot = $legacyFeatured[$index] ?? [];
    $productId = isset($savedSlot['id_produk']) ? (int) $savedSlot['id_produk'] : null;
    if (!$productId && isset($savedSlot['name'])) {
        foreach ($products as $product) {
            if ((string) $product['nama_produk'] === (string) $savedSlot['name']) {
                $productId = (int) $product['id_produk'];
                break;
            }
        }
    }
    $slots[] = [
        'id_produk' => $productId ?: null,
        'badge' => (string) ($savedSlot['badge'] ?? $savedSlot['tag'] ?? ''),
        'tampil' => array_key_exists('tampil', $savedSlot) ? (bool) $savedSlot['tampil'] : !empty($savedSlot),
    ];
}
$productCatalog = [];
foreach ($products as $product) {
    $productCatalog[(int) $product['id_produk']] = [
        'name' => (string) $product['nama_produk'],
        'price' => (float) $product['harga_dasar'],
        'category' => (string) $product['kategori_produk'],
        'image' => !empty($product['gambar_produk']) ? '/uploads/produk/' . rawurlencode(basename((string) $product['gambar_produk'])) : '',
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Beranda - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/edit_beranda.css">
</head>
<body class="admin-home-editor">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="editor-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="editor-breadcrumb"><span>Admin</span><span aria-hidden="true">›</span><strong>Edit Beranda</strong></div>
        <header class="editor-header"><h1>Edit Beranda</h1><a class="preview-link" href="/?page=customer-home" target="_blank" rel="noopener">&#9673; Pratinjau</a></header>
        <?php if ($notice !== ''): ?><p class="alert alert--success" role="status"><?= $escape($notice) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="alert alert--error" role="alert"><?= $escape($error) ?></p><?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="editor-form">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
            <section class="editor-section">
                <div class="section-heading"><span class="section-number">1</span><div><h2>Foto &amp; Tulisan Utama Depan Toko (Hero)</h2></div></div>
                <div class="form-grid">
                    <?php foreach (['hero_image' => 'Foto Banner Utama Kasur & Sprei', 'sprei_image' => 'Foto kategori Sprei', 'bedcover_image' => 'Foto kategori Bed Cover'] as $imageKey => $imageLabel): ?>
                        <?php if ($imageKey === 'hero_image'): ?>
                            <div class="image-field image-field--hero"><span class="field-label"><?= $escape($imageLabel) ?></span><img class="image-preview" src="<?= $escape($content[$imageKey]) ?>" alt="" data-empty="<?= $content[$imageKey] === '' ? 'true' : 'false' ?>"><span class="file-name"><?= $escape($content[$imageKey] !== '' ? basename($content[$imageKey]) : 'Belum ada foto') ?></span><button type="button" class="button button--small" data-open-upload="<?= $escape($imageKey) ?>">Ganti Foto</button><input type="file" name="<?= $escape($imageKey) ?>" accept="image/jpeg,image/png,image/webp" hidden></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <label class="field field--wide">Label Badge di Atas Judul<input name="hero_pill" maxlength="120" value="<?= $escape($content['hero_pill']) ?>"></label>
                    <label class="field field--wide">Tulisan Besar di Depan (Judul)<textarea name="hero_title" rows="2" maxlength="240" required><?= $escape($content['hero_title']) ?></textarea></label>
                    <label class="field field--wide">Subjudul / Penjelasan Singkat<textarea name="hero_description" rows="3" maxlength="600" required><?= $escape($content['hero_description']) ?></textarea></label>
                </div>
            </section>

            <section class="editor-section">
                <div class="section-heading"><span class="section-number">2</span><div><h2>Kategori di Beranda (Jelajahi Katalog)</h2><p>Atur 2 kartu kategori yang tampil di bawah hero banner.</p></div><span class="section-chip">2 Kartu</span></div>
                <input type="hidden" name="explore_title" value="<?= $escape($content['explore_title']) ?>">
                <div class="category-grid">
                    <?php foreach (['sprei' => 'Sprei', 'bedcover' => 'Bed Cover'] as $category => $label): ?>
                        <article class="category-card">
                            <div class="category-card__top"><span>KATEGORI <?= $category === 'sprei' ? '1' : '2' ?></span><span>● Tampil</span></div>
                            <div class="category-image"><img class="image-preview" src="<?= $escape($content[$category . '_image']) ?>" alt="" data-empty="<?= $content[$category . '_image'] === '' ? 'true' : 'false' ?>"><button type="button" class="button button--small" data-open-upload="<?= $escape($category . '_image') ?>">Ubah Foto</button><input type="file" name="<?= $escape($category . '_image') ?>" accept="image/jpeg,image/png,image/webp" hidden></div>
                            <label class="field">Nama Kategori<input name="<?= $escape($category . '_title') ?>" maxlength="80" value="<?= $escape($content[$category . '_title']) ?>"></label>
                            <label class="field">Deskripsi<textarea name="<?= $escape($category . '_description') ?>" rows="2" maxlength="180"><?= $escape($content[$category . '_description']) ?></textarea></label>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="editor-section">
                <div class="section-heading"><span class="section-number">3</span><div><h2>Produk Pilihan &amp; Best Seller di Beranda</h2></div><span class="section-chip">4 Slot</span></div>
                <label class="field field--wide">Judul Bagian<input name="featured_title" maxlength="100" value="<?= $escape($content['featured_title']) ?>"></label>
                <div class="product-slots">
                    <?php foreach ($slots as $index => $slot): ?>
                        <article class="product-slot" data-slot>
                            <div class="slot-heading"><strong>SLOT <?= $index + 1 ?><?= $index === 0 ? ' (UTAMA)' : '' ?></strong><label class="toggle-label"><input type="checkbox" name="featured[<?= $index ?>][tampil]"<?= $slot['tampil'] ? ' checked' : '' ?>> Tampil</label></div>
                            <label class="field">Pilih Produk dari Katalog<select name="featured[<?= $index ?>][id_produk]" data-product-select><option value="">Pilih produk</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id_produk'] ?>"<?= (int) $slot['id_produk'] === (int) $product['id_produk'] ? ' selected' : '' ?>><?= $escape($product['nama_produk'] . ' - Rp ' . number_format((float) $product['harga_dasar'], 0, ',', '.')) ?></option><?php endforeach; ?></select></label>
                            <label class="field">Badge Khusus / Label Promo<input name="featured[<?= $index ?>][badge]" maxlength="60" value="<?= $escape($slot['badge']) ?>"></label>
                            <div class="product-preview" data-product-preview></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <div class="save-bar"><a href="/?page=admin-dashboard" class="cancel-link">&larr; Kembali ke Dashboard</a><button type="submit" class="button button--primary">&#10003; Simpan Perubahan Beranda</button></div>
        </form>
    </main>
</div>

<div class="upload-modal" id="uploadModal" hidden>
    <div class="upload-modal__backdrop" data-close-upload></div>
    <section class="upload-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="uploadTitle">
        <button type="button" class="upload-modal__close" data-close-upload aria-label="Tutup">&times;</button>
        <h2 id="uploadTitle">Area Unggah Berkas Foto</h2>
        <div class="drop-zone" id="dropZone" tabindex="0"><div class="camera-icon">&#128247;</div><strong>Tarik &amp; letakkan foto di sini, atau klik tombol di bawah</strong><small>Mendukung format JPG, PNG, atau WebP (Maksimal ukuran file 5 MB)</small><button type="button" class="button button--secondary" id="choosePhoto">&#128444; Pilih File Foto dari Perangkat</button><p class="upload-file-name" id="uploadFileName"></p></div>
        <input type="file" id="uploadPicker" accept="image/jpeg,image/png,image/webp" hidden>
        <img class="upload-preview" id="uploadPreview" alt="Pratinjau foto" hidden>
        <button type="button" class="button button--primary" id="usePhoto" disabled>Gunakan Foto</button>
    </section>
</div>
<script>
window.homepageProducts = <?= json_encode($productCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}'; ?>;
</script>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/edit_beranda.js"></script>
</body>
</html>
