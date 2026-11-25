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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$product = is_array($product ?? null) ? $product : [];
$assetUrl = static function (string $path): string {
    return $path . '?v=' . filemtime(__DIR__ . '/../../../public' . $path);
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="<?= $escape($assetUrl('/assets/css/admin/product-form.css')) ?>">
</head>
<body class="admin-dashboard admin-product-form-page">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="dashboard-main product-form-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <nav class="product-form-breadcrumb" aria-label="Breadcrumb">
            <a href="/?page=admin-dashboard">Admin</a>
            <span aria-hidden="true">›</span>
            <a href="/?page=admin-products">Katalog Produk</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Tambah Produk Baru</span>
        </nav>

        <header class="product-form-heading">
            <h1>Tambah Katalog Produk</h1>
            <p>Isi data di bawah, lalu cek pratinjau di kanan sebelum menyimpan. Tanda <b>*</b> wajib diisi.</p>
        </header>

        <form class="product-form-card" action="/?action=admin-product-store" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">

            <div class="product-form-sections">
                <section class="product-form-section">
                    <h2><span>1</span>Jenis Produk</h2>
                    <p>Pilih kategori dulu, isian ukuran akan menyesuaikan.</p>
                    <div class="product-categories" role="radiogroup" aria-label="Kategori">
                        <label><input type="radio" name="kategori_produk" value="SPREI" checked><strong>Sprei</strong><small>Fitted sheet, ada tinggi karet</small></label>
                        <label><input type="radio" name="kategori_produk" value="BED_COVER"><strong>Bed Cover</strong><small>Panjang, lebar &amp; tinggi</small></label>
                        <label><input type="radio" name="kategori_produk" value="SARUNG_BANTAL_GULING"><strong>Sarung Bantal &amp; Guling</strong><small>Hanya panjang &amp; lebar</small></label>
                    </div>
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
                                <option value="TENCEL">Tencel</option>
                                <option value="KATUN_JEPANG">Katun Jepang</option>
                                <option value="KATUN_LOKAL">Katun Lokal</option>
                                <option value="MICROTENCEL">Microtencel</option>
                                <option value="MICROTEX">Microtex</option>
                            </select>
                        </label>

                        <label class="product-form-field" for="motif">
                            <span>Motif <small>(Opsional)</small></span>
                            <input type="text" id="motif" name="motif" placeholder="Contoh: Bunga kecil">
                        </label>
                    </div>
                </section>

                <section class="product-form-section">
                    <h2><span>2</span>Ukuran Kasur</h2>
                    <p>Isi ukuran dalam sentimeter. Sarung Bantal &amp; Guling cukup panjang dan lebar.</p>
                    <div class="product-size-grid">
                        <label class="product-form-field">
                            <span>Panjang <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="panjang_standar" name="panjang_standar" min="1" step="1" required>
                                <span aria-hidden="true">cm</span>
                            </span>
                        </label>
                        <label class="product-form-field">
                            <span>Lebar <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="lebar_standar" name="lebar_standar" min="1" step="1" required>
                                <span aria-hidden="true">cm</span>
                            </span>
                        </label>
                        <label class="product-form-field" id="tinggi_standar_field">
                            <span><span id="productHeightLabel">Tinggi karet</span> <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="tinggi_standar" name="tinggi_standar" min="1" step="1" required>
                                <span aria-hidden="true">cm</span>
                            </span>
                            <small id="productHeightHelp">Tinggi karet untuk kategori Sprei.</small>
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
                                <input type="number" id="harga_dasar" name="harga_dasar" min="1" step="1" placeholder="Contoh: 185000" required>
                            </span>
                            <small id="productPriceHelp">Harga yang tampil di katalog.</small>
                        </label>

                        <label class="product-form-field" for="stok">
                            <span>Stok <b>*</b></span>
                            <span class="product-input-suffix">
                                <input type="number" id="stok" name="stok" min="0" step="1" placeholder="Jumlah stok" required>
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
                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                            </span>
                            <div class="product-photo-copy">
                                <strong id="productPhotoName">Seret foto ke sini, atau pilih dari perangkat</strong>
                                <small>JPG atau PNG, maksimal 2 MB. Disarankan rasio persegi.</small>
                            </div>
                            <input type="file" aria-label="Foto Produk" id="gambar_produk" name="gambar_produk" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                            <button type="button" class="product-photo-button" id="productPhotoButton">Pilih Foto</button>
                        </div>
                        <small class="product-photo-error" id="productPhotoError" role="status" aria-live="polite" hidden></small>
                    </div>

                    <label class="product-form-field product-form-field--full" for="deskripsi">
                        <span>Deskripsi Singkat <small>(Opsional)</small></span>
                        <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Tambahkan informasi singkat tentang produk"></textarea>
                    </label>
                </section>
            </div>

            <aside class="product-form-summary">
                <section class="product-form-section">
                    <h2>Pratinjau di Katalog</h2>
                    <div class="product-catalog-card">
                        <div id="catalogPhoto" class="product-catalog-photo" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg></div>
                        <div class="product-catalog-details">
                            <small id="catalogCategory">Sprei</small>
                            <strong id="catalogName">Nama produk</strong>
                            <small id="catalogSize">Ukuran belum diisi</small>
                            <strong id="catalogPrice">Harga belum diisi</strong>
                            <small id="catalogStock">Stok belum diisi</small>
                        </div>
                    </div>
                </section>
                <section class="product-form-section">
                    <h2>Kelengkapan Data</h2>
                    <ul class="product-checklist">
                        <li data-check="nama_produk">Kategori &amp; nama produk</li>
                        <li data-check="jenis_kain">Jenis kain</li>
                        <li data-check="panjang_standar,lebar_standar,tinggi_standar">Ukuran kasur</li>
                        <li data-check="harga_dasar,stok">Harga &amp; stok</li>
                        <li data-check="gambar_produk">Foto produk</li>
                    </ul>
                </section>
                <footer class="product-form-actions">
                    <a href="/?page=admin-products" class="product-form-cancel">Batal</a>
                    <button type="submit" class="product-form-save">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        Simpan Produk
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
