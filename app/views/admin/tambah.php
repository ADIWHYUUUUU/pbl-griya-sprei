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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/product-form.css">
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
        </header>

        <form class="product-form-card" action="/?action=admin-product-store" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">

            <div class="product-form-card__heading">
                <strong>Informasi Produk</strong>
                <p>Masukkan data produk</p>
            </div>

            <div class="product-form-grid">
                <label class="product-form-field product-form-field--full" for="nama_produk">
                    <span>Nama Produk <b>*</b></span>
                    <input type="text" id="nama_produk" name="nama_produk" maxlength="150" value="<?= $escape($product['nama_produk'] ?? '') ?>" required>
                </label>

                <label class="product-form-field" for="kategori_produk">
                    <span>Kategori <b>*</b></span>
                    <select id="kategori_produk" name="kategori_produk" required>
                        <option value="">Pilih kategori</option>
                        <option value="SPREI">Sprei (Fitted Sheet)</option>
                        <option value="BED_COVER">Bed Cover</option>
                        <option value="SARUNG_BANTAL">Sarung Bantal</option>
                        <option value="SARUNG_GULING">Sarung Guling</option>
                    </select>
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

                <fieldset class="product-form-field product-form-field--full product-size-field">
                    <legend>Ukuran Kasur (cm) <b>*</b></legend>
                    <div class="product-size-grid">
                        <label class="product-input-suffix">
                            <span class="visually-hidden">Panjang</span>
                            <input type="number" id="panjang_standar" name="panjang_standar" min="1" step="1" placeholder="Panjang" required>
                            <span aria-hidden="true">P</span>
                        </label>
                        <label class="product-input-suffix">
                            <span class="visually-hidden">Lebar</span>
                            <input type="number" id="lebar_standar" name="lebar_standar" min="1" step="1" placeholder="Lebar" required>
                            <span aria-hidden="true">L</span>
                        </label>
                        <label class="product-input-suffix">
                            <span class="visually-hidden">Tinggi</span>
                            <input type="number" id="tinggi_standar" name="tinggi_standar" min="1" step="1" placeholder="Tinggi" required>
                            <span aria-hidden="true">T (Karet)</span>
                        </label>
                    </div>
                </fieldset>

                <label class="product-form-field" for="harga_dasar">
                    <span>Harga Dasar (Rp) <b>*</b></span>
                    <span class="product-currency-control">
                        <span class="product-currency-prefix" aria-hidden="true">Rp</span>
                        <input type="number" id="harga_dasar" name="harga_dasar" min="1" step="1" placeholder="Contoh: 185000" required>
                    </span>
                </label>

                <label class="product-form-field" for="stok">
                    <span>Stok <b>*</b></span>
                    <span class="product-input-suffix">
                        <input type="number" id="stok" name="stok" min="0" step="1" placeholder="Jumlah stok" required>
                        <span aria-hidden="true">Set</span>
                    </span>
                </label>

                <div class="product-form-field product-form-field--full">
                    <span>Foto Produk <b>*</b></span>
                    <div class="product-photo-picker">
                        <span class="product-photo-preview" id="productPhotoPreview" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                        </span>
                        <div class="product-photo-copy">
                            <strong id="productPhotoName">Belum ada foto dipilih</strong>
                            <small>Format JPG atau PNG. Maksimal 2 MB.</small>
                        </div>
                        <input type="file" id="gambar_produk" name="gambar_produk" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                        <button type="button" class="product-photo-button" id="productPhotoButton">Pilih Foto</button>
                    </div>
                    <small class="product-photo-error" id="productPhotoError" role="status" aria-live="polite" hidden></small>
                </div>

                <label class="product-form-field product-form-field--full" for="deskripsi">
                    <span>Deskripsi Singkat <small>(Opsional)</small></span>
                    <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Tambahkan informasi singkat tentang produk"></textarea>
                </label>
            </div>

            <footer class="product-form-actions">
                <a href="/?page=admin-products" class="product-form-cancel">Batal</a>
                <button type="submit" class="product-form-save">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                    Simpan Produk
                </button>
            </footer>
        </form>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/product-form.js"></script>
</body>
</html>
