<?php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$product = is_array($product ?? null) ? $product : [];
$category = match ($product['kategori_produk'] ?? 'SPREI') {
    'BEDCOVER' => 'BED_COVER',
    'SARUNG_BANTAL', 'SARUNG_GULING' => 'SARUNG_BANTAL_GULING',
    default => $product['kategori_produk'] ?? 'SPREI',
};
$hasHeight = $category === 'SPREI';
$photoUrl = $isEdit && !empty($product['gambar_produk']) ? product_image_url($product['gambar_produk']) : null;
$title = $isEdit ? 'Edit Produk' : 'Tambah Katalog Produk';
$assetUrl = static function (string $path): string {
    return $path . '?v=' . filemtime(__DIR__ . '/../../../public' . $path);
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($title) ?> - Griya Sprei</title>
    <link rel="stylesheet" href="<?= $escape($assetUrl('/assets/css/admin/sidebar.css')) ?>">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="<?= $escape($assetUrl('/assets/css/admin/product-form.css')) ?>">
</head>
<body class="admin-dashboard admin-product-form-page<?= $isEdit ? ' admin-edit-product' : '' ?>">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="dashboard-main product-form-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <nav class="product-form-breadcrumb" aria-label="Breadcrumb">
            <a href="/?page=admin-dashboard">Admin</a>
            <span aria-hidden="true">›</span>
            <a href="/?page=admin-products">Katalog Produk</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= $isEdit ? 'Edit Produk' : 'Tambah Produk Baru' ?></span>
        </nav>

        <header class="product-form-heading">
            <h1><?= $escape($title) ?></h1>
            <p><?= $isEdit ? 'Perbarui informasi produk di katalog Griya Sprei.' : 'Isi data di bawah, lalu cek pratinjau di kanan sebelum menyimpan.' ?> Tanda <b>*</b> wajib diisi.</p>
        </header>

        <form class="product-form-card" action="/?action=<?= $isEdit ? 'admin-product-update' : 'admin-product-store' ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">

            <?php if ($isEdit): ?>
                <input type="hidden" name="id_produk" value="<?= (int) $product['id_produk'] ?>">
            <?php endif; ?>

            <div class="product-form-sections">
                <?php if ($isEdit): ?>
                    <div class="product-edit-heading">
                        <div>
                            <h2><?= $escape($product['nama_produk']) ?></h2>
                            <p>Periksa kembali perubahan sebelum menyimpannya.</p>
                        </div>
                        <span id="productChangeCount" role="status" hidden></span>
                    </div>
                <?php endif; ?>
                <section class="product-form-section">
                    <h2><span>1</span>Jenis Produk</h2>
                    <p>Pilih kategori produk. Isian ukuran akan menyesuaikan.</p>
                    <fieldset class="product-categories" aria-label="Kategori">
                        <label><input type="radio" name="kategori_produk" value="SPREI" <?= $category === 'SPREI' ? 'checked' : '' ?>><strong>Sprei</strong><small>Fitted sheet, ada tinggi karet</small></label>
                        <label><input type="radio" name="kategori_produk" value="BED_COVER" <?= $category === 'BED_COVER' ? 'checked' : '' ?>><strong>Bed Cover</strong><small>Hanya panjang &amp; lebar</small></label>
                        <label><input type="radio" name="kategori_produk" value="SARUNG_BANTAL_GULING" <?= $category === 'SARUNG_BANTAL_GULING' ? 'checked' : '' ?>><strong>Sarung Bantal &amp; Guling</strong><small>Hanya panjang &amp; lebar</small></label>
                    </fieldset>
                    <div class="product-form-grid">
                        <label class="product-form-field product-form-field--full" for="nama_produk">
                            <span>Nama Produk <b>*</b></span>
                            <input type="text" id="nama_produk" name="nama_produk" maxlength="150" value="<?= $escape($product['nama_produk'] ?? '') ?>" required>
                            <small>Contoh penamaan: jenis + kain + motif. Maks. 150 karakter.</small>
                        </label>

                        <label class="product-form-field" for="jenis_kain">
                            <span>Jenis Kain <b>*</b></span>
                            <select id="jenis_kain" name="jenis_kain" required>
                                <option value="">Pilih jenis kain</option>
                                <option value="TENCEL" <?= ($product['jenis_kain'] ?? '') === 'TENCEL' ? 'selected' : '' ?>>Tencel</option>
                                <option value="KATUN_JEPANG" <?= ($product['jenis_kain'] ?? '') === 'KATUN_JEPANG' ? 'selected' : '' ?>>Katun Jepang</option>
                                <option value="KATUN_LOKAL" <?= ($product['jenis_kain'] ?? '') === 'KATUN_LOKAL' ? 'selected' : '' ?>>Katun Lokal</option>
                                <option value="MICROTENCEL" <?= ($product['jenis_kain'] ?? '') === 'MICROTENCEL' ? 'selected' : '' ?>>Microtencel</option>
                                <option value="MICROTEX" <?= ($product['jenis_kain'] ?? '') === 'MICROTEX' ? 'selected' : '' ?>>Microtex</option>
                            </select>
                        </label>

                        <label class="product-form-field" for="motif">
                            <span>Motif <small>(Opsional)</small></span>
                            <input type="text" id="motif" name="motif" value="<?= $escape($product['motif'] ?? '') ?>" placeholder="Contoh: Bunga kecil">
                        </label>
                    </div>
                </section>

                <section class="product-form-section">
                    <h2><span>2</span><strong id="productSizeTitle"><?= $hasHeight ? 'Ukuran Kasur' : 'Ukuran Produk' ?></strong></h2>
                    <p>Isi ukuran dalam sentimeter. Hanya Sprei yang memakai tinggi. Bed Cover serta Sarung Bantal &amp; Guling cukup panjang dan lebar.</p>
                    <div class="product-size-grid<?= $hasHeight ? '' : ' without-height' ?>">
                        <label class="product-form-field">
                            <span>Panjang <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="panjang_standar" name="panjang_standar" value="<?= $escape($product['panjang_standar'] ?? '') ?>" min="1" step="1" required>
                                <span aria-hidden="true">cm</span>
                            </span>
                        </label>
                        <label class="product-form-field">
                            <span>Lebar <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="lebar_standar" name="lebar_standar" value="<?= $escape($product['lebar_standar'] ?? '') ?>" min="1" step="1" required>
                                <span aria-hidden="true">cm</span>
                            </span>
                        </label>
                        <label class="product-form-field" id="tinggi_standar_field" <?= $hasHeight ? '' : 'hidden' ?>>
                            <span>Tinggi karet <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="tinggi_standar" name="tinggi_standar" value="<?= $escape($hasHeight ? ($product['tinggi_standar'] ?? '') : '') ?>" min="1" step="1" <?= $hasHeight ? 'required' : 'disabled' ?>>
                                <span aria-hidden="true">cm</span>
                            </span>
                            <small>Tinggi karet, khusus kategori Sprei.</small>
                        </label>
                    </div>
                </section>

                <section class="product-form-section">
                    <h2><span>3</span>Harga &amp; Stok</h2>
                    <p>Harga dasar adalah harga untuk ukuran standar di atas.</p>
                    <div class="product-form-grid">
                        <label class="product-form-field" for="harga_dasar">
                            <span>Harga Dasar <b>*</b></span>
                            <span class="product-currency-control">
                                <span class="product-currency-prefix" aria-hidden="true">Rp</span>
                                <input type="number" id="harga_dasar" name="harga_dasar" value="<?= $escape($product['harga_dasar'] ?? '') ?>" min="<?= $isEdit ? '0.01' : '1' ?>" step="<?= $isEdit ? 'any' : '1' ?>" placeholder="Contoh: 185000" required>
                            </span>
                            <small id="productPriceHelp">Harga yang tampil di katalog.</small>
                        </label>

                        <label class="product-form-field" for="stok">
                            <span>Stok <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="stok" name="stok" value="<?= $escape($product['stok'] ?? '') ?>" min="0" step="1" placeholder="Jumlah stok" required>
                                <span aria-hidden="true">Set</span>
                            </span>
                            <small>Isi 0 jika belum tersedia.</small>
                        </label>
                    </div>
                </section>

                <section class="product-form-section">
                    <h2><span>4</span>Foto &amp; Deskripsi</h2>
                    <p>Foto yang jelas membantu pelanggan memilih.</p>
                    <div class="product-form-field product-form-photo">

                        <div class="product-photo-picker">
                            <span class="product-photo-preview" id="productPhotoPreview" aria-hidden="true">
                                <?php if ($photoUrl): ?><img src="<?= $escape($photoUrl) ?>" alt=""><?php else: ?><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg><?php endif; ?>
                            </span>
                            <div class="product-photo-copy">
                                <strong id="productPhotoName"><?= $isEdit && $photoUrl ? 'Foto saat ini. Pilih foto baru untuk menggantinya.' : 'Seret foto ke sini, atau pilih dari perangkat' ?></strong>
                                <small><?= $isEdit ? 'JPG atau PNG, maksimal 2 MB. Unggah jika ingin mengganti foto.' : 'JPG atau PNG, maksimal 2 MB. Disarankan rasio persegi.' ?></small>
                            </div>
                            <input type="file" aria-label="Foto Produk" id="gambar_produk" name="gambar_produk" accept=".jpg,.jpeg,.png,image/jpeg,image/png" <?= $isEdit ? '' : 'required' ?>>
                            <button type="button" class="product-photo-button" id="productPhotoButton"><?= $isEdit && $photoUrl ? 'Ganti Foto' : 'Pilih Foto' ?></button>
                        </div>
                        <small class="product-photo-error" id="productPhotoError" role="status" aria-live="polite" hidden></small>
                    </div>

                    <label class="product-form-field product-form-field--full" for="deskripsi">
                        <span>Deskripsi Singkat <small>(Opsional)</small></span>
                        <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Tambahkan informasi singkat tentang produk"><?= $escape($product['deskripsi'] ?? '') ?></textarea>
                    </label>
                </section>
            </div>

            <aside class="product-form-summary">
                <section class="product-form-section">
                    <h2>Pratinjau di Katalog</h2>
                    <div class="product-catalog-card">
                        <div id="catalogPhoto" class="product-catalog-photo" aria-hidden="true"><?php if ($photoUrl): ?><img src="<?= $escape($photoUrl) ?>" alt=""><?php else: ?><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg><?php endif; ?></div>
                        <div class="product-catalog-details">
                            <small id="catalogCategory">Sprei</small>
                            <strong id="catalogName">Nama produk</strong>
                            <small id="catalogSize">Ukuran belum diisi</small>
                            <strong id="catalogPrice">Harga belum diisi</strong>
                            <small id="catalogStock">Stok belum diisi</small>
                        </div>
                    </div>
                </section>
                <?php if ($isEdit): ?>
                    <section class="product-form-section">
                        <h2>Ringkasan Perubahan</h2>
                        <div id="productChanges" class="product-edit-changes" aria-live="polite">Belum ada perubahan.</div>
                    </section>
                <?php else: ?>
                <section class="product-form-section">
                    <h2>Kelengkapan Data</h2>
                    <ul class="product-checklist">
                        <li data-check="nama_produk">Kategori &amp; nama produk</li>
                        <li data-check="jenis_kain">Jenis kain</li>
                        <li data-check="panjang_standar,lebar_standar,tinggi_standar">Ukuran kasur</li>
                        <li data-check="harga_dasar,stok">Harga &amp; stok</li>
                        <li class="product-photo-check" data-check="gambar_produk">
                            <span id="productPhotoCheckLabel">Foto produk belum dipilih</span>
                        </li>
                    </ul>
                </section>
                <?php endif; ?>
                <footer class="product-form-actions">
                    <a href="/?page=admin-products" class="product-form-cancel">Batal</a>
                    <button type="submit" class="product-form-save">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' ?>
                    </button>
                </footer>
            </aside>
        </form>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="<?= $escape($assetUrl('/assets/js/admin/product-category-height.js')) ?>"></script>
<script src="<?= $escape($assetUrl('/assets/js/admin/product-form.js')) ?>"></script>
</body>
</html>
