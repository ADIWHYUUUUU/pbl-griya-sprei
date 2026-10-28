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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$kategori = $product['kategori_produk'] === 'BEDCOVER'
    ? 'BED_COVER'
    : $product['kategori_produk'];
$gambar = !empty($product['gambar_produk'])
    ? '/uploads/produk/' . rawurlencode(basename($product['gambar_produk']))
    : null;
$namaGambar = $product['gambar_produk'] ?? '';
$presets = [
    ['Single', 120, 200],
    ['Queen', 160, 200],
    ['King', 180, 200],
    ['Super King', 200, 200],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/edit.css">
</head>
<body class="admin-dashboard admin-edit-product">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="dashboard-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

        <header class="dashboard-header edit-header">
            <div>
                <p class="eyebrow">PANEL ADMIN / PRODUK</p>
                <h1>Edit Produk</h1>
                <p class="dashboard-intro">Perbarui informasi produk di katalog Griya Sprei.</p>
            </div>
            <a href="/?page=admin-products" class="edit-back-link">Kembali ke Katalog</a>
        </header>

        <form
            class="edit-form-card"
            action="/?action=admin-product-update"
            method="POST"
            enctype="multipart/form-data"
        >
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id_produk" value="<?= (int) $product['id_produk'] ?>">

            <div class="edit-card-heading">
                <span class="edit-product-label">PRODUK #<?= (int) $product['id_produk'] ?></span>
                <h2><?= $escape($product['nama_produk']) ?></h2>
                <p>Periksa kembali perubahan sebelum menyimpannya.</p>
            </div>

            <div class="edit-form-body">
                <section class="edit-section" aria-labelledby="editInfoTitle">
                    <h2 class="edit-section-title" id="editInfoTitle">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                        Informasi Utama Produk
                    </h2>

                    <div class="edit-field">
                        <label for="nama_produk">Nama Produk / Sprei <span class="edit-required">*</span></label>
                        <input
                            class="edit-input"
                            type="text"
                            id="nama_produk"
                            name="nama_produk"
                            maxlength="150"
                            value="<?= $escape($product['nama_produk']) ?>"
                            required
                        >
                    </div>

                    <div class="edit-field">
                        <label for="kategori_produk">Kategori Produk <span class="edit-required">*</span></label>
                        <select class="edit-input" id="kategori_produk" name="kategori_produk" required>
                            <option value="SPREI" <?= $kategori === 'SPREI' ? 'selected' : '' ?>>Sprei</option>
                            <option value="BED_COVER" <?= $kategori === 'BED_COVER' ? 'selected' : '' ?>>Bed Cover</option>
                        </select>
                    </div>
                </section>

                <section class="edit-section" aria-labelledby="editSizeTitle">
                    <h2 class="edit-section-title" id="editSizeTitle">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 16-16M7 17l-2-2M11 13l-2-2M15 9l-2-2M19 5l-2-2"/></svg>
                        Ukuran Standar Kasur (cm)
                    </h2>

                    <div class="edit-grid edit-grid-3">
                        <div class="edit-field">
                            <label for="panjang_standar">Panjang Kasur</label>
                            <div class="edit-input-group">
                                <input class="edit-input" type="number" min="1" step="0.01" id="panjang_standar" name="panjang_standar" value="<?= $escape($product['panjang_standar']) ?>" required>
                                <span>cm</span>
                            </div>
                        </div>
                        <div class="edit-field">
                            <label for="lebar_standar">Lebar Kasur</label>
                            <div class="edit-input-group">
                                <input class="edit-input" type="number" min="1" step="0.01" id="lebar_standar" name="lebar_standar" value="<?= $escape($product['lebar_standar']) ?>" required>
                                <span>cm</span>
                            </div>
                        </div>
                        <div class="edit-field">
                            <label for="tinggi_standar">Tinggi Karet</label>
                            <div class="edit-input-group">
                                <input class="edit-input" type="number" min="1" step="0.01" id="tinggi_standar" name="tinggi_standar" value="<?= $escape($product['tinggi_standar']) ?>" required>
                                <span>cm</span>
                            </div>
                        </div>
                    </div>

                    <div class="edit-size-presets" role="group" aria-label="Pilihan ukuran cepat">
                        <?php foreach ($presets as [$label, $width, $length]): ?>
                            <button type="button" class="edit-chip" data-width="<?= $width ?>" data-length="<?= $length ?>">
                                <?= $escape($label) ?> (<?= $width ?>×<?= $length ?>)
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="edit-grid edit-grid-2 edit-price-stock">
                        <div class="edit-field">
                            <label for="harga_dasar">Harga Dasar</label>
                            <div class="edit-input-group">
                                <span>Rp</span>
                                <input class="edit-input" type="number" min="0" step="1" id="harga_dasar" name="harga_dasar" value="<?= $escape($product['harga_dasar']) ?>" required>
                            </div>
                        </div>
                        <div class="edit-field">
                            <label for="stok">Stok</label>
                            <div class="edit-input-group">
                                <input class="edit-input" type="number" min="0" step="1" id="stok" name="stok" value="<?= (int) $product['stok'] ?>" required>
                                <span>unit</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="edit-section" aria-labelledby="editPhotoTitle">
                    <h2 class="edit-section-title" id="editPhotoTitle">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                        Foto Produk Katalog
                    </h2>

                    <div class="edit-photo-box">
                        <span class="edit-photo-thumb" id="editPhotoThumb">
                            <?php if ($gambar): ?>
                                <img src="<?= $escape($gambar) ?>" alt="Foto <?= $escape($product['nama_produk']) ?>">
                            <?php else: ?>
                                <span>Belum ada foto</span>
                            <?php endif; ?>
                        </span>
                        <div class="edit-photo-info">
                            <strong id="editPhotoName"><?= $escape($namaGambar !== '' ? $namaGambar : 'Belum ada foto') ?></strong>
                            <small id="editPhotoHint">Format JPG, PNG, atau WebP. Maksimal 2 MB. Kosongkan jika tidak ingin mengganti foto.</small>
                        </div>
                        <input type="file" id="gambar_produk" name="gambar_produk" accept="image/jpeg,image/png,image/webp" hidden>
                        <button type="button" class="edit-photo-button" id="editPhotoPick">Pilih Foto</button>
                    </div>
                </section>

                <section class="edit-section edit-description-section" aria-labelledby="editDescriptionTitle">
                    <div class="edit-description-heading">
                        <label class="edit-section-title" id="editDescriptionTitle" for="deskripsi">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14M5 10h14M5 15h9M5 20h9"/></svg>
                            Deskripsi Produk
                        </label>
                        <span id="editDescriptionCount" aria-live="polite"></span>
                    </div>
                    <textarea class="edit-input edit-textarea" id="deskripsi" name="deskripsi" rows="4" required><?= $escape($product['deskripsi']) ?></textarea>
                </section>
            </div>

            <footer class="edit-form-footer">
                <a href="/?page=admin-products" class="edit-cancel-button">Batal</a>
                <button type="submit" class="edit-save-button">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                    Simpan Perubahan
                </button>
            </footer>
        </form>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script>
    (function () {
        var photoInput = document.getElementById('gambar_produk');
        var photoPick = document.getElementById('editPhotoPick');
        var photoThumb = document.getElementById('editPhotoThumb');
        var photoName = document.getElementById('editPhotoName');
        var description = document.getElementById('deskripsi');
        var descriptionCount = document.getElementById('editDescriptionCount');

        photoPick.addEventListener('click', function () {
            photoInput.click();
        });

        photoInput.addEventListener('change', function () {
            var file = photoInput.files && photoInput.files[0];
            if (!file) return;

            photoName.textContent = file.name;
            photoThumb.replaceChildren();
            var image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = 'Pratinjau foto produk';
            photoThumb.appendChild(image);
        });

        function updateDescriptionCount() {
            descriptionCount.textContent = description.value.length + ' karakter';
        }

        description.addEventListener('input', updateDescriptionCount);
        updateDescriptionCount();

        document.querySelectorAll('.edit-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                document.getElementById('lebar_standar').value = chip.dataset.width;
                document.getElementById('panjang_standar').value = chip.dataset.length;
            });
        });
    })();
</script>
</body>
</html>
