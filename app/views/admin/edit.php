<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /frontend/pages/login.php');
    exit;
}
require_once __DIR__ . '/../../../backend/app/controllers/product_controller.php';
$controller = new ProductController();
$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}
$product = $controller->getProductById($id);
if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Griya Sprei</title>
    <link rel="stylesheet" href="../../assets/css/admin-edit.css">
</head>
<body>
    <main class="container">
        <div class="form-card">
            <h1>Edit Produk</h1>
            <p>
                Perbarui informasi produk Griya Sprei.
            </p>
            <form
                action="/backend/actions/admin/update.php"
                method="POST"
                enctype="multipart/form-data">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>">
                <!-- ID PRODUK -->
                <input
                    type="hidden"
                    name="id_produk"
                    value="<?= (int) $product['id_produk'] ?>">
                <!-- =====================================
                 NAMA PRODUK
            ===================================== -->
                <div class="form-group">
                    <label for="nama_produk">
                        Nama Produk
                    </label>
                    <input
                        type="text"
                        id="nama_produk"
                        name="nama_produk"
                        maxlength="150"
                        value="<?= e($product['nama_produk']) ?>"
                        required>
                </div>
                <!-- =====================================
                 KATEGORI PRODUK
            ===================================== -->
                <div class="form-group">
                    <label for="kategori_produk">
                        Kategori Produk
                    </label>
                    <select
                        id="kategori_produk"
                        name="kategori_produk"
                        required>
                        <option value="">
                            -- Pilih Kategori --
                        </option>
                        <option
                            value="SPREI"
                            <?= $product['kategori_produk'] === 'SPREI' ? 'selected' : '' ?>>
                            Sprei
                        </option>
                        <option
                            value="BEDCOVER"
                            <?= $product['kategori_produk'] === 'BEDCOVER' ? 'selected' : '' ?>>
                            Bedcover
                        </option>
                    </select>
                </div>
                <!-- =====================================
                 UKURAN STANDAR
            ===================================== -->
                <div class="form-group">
                    <label>
                        Ukuran Standar (cm)
                    </label>
                    <div class="sizes">
                        <!-- PANJANG -->
                        <div>
                            <label for="panjang_standar">
                                Panjang
                            </label>
                            <input
                                type="number"
                                id="panjang_standar"
                                name="panjang_standar"
                                min="1"
                                step="0.01"
                                value="<?= e($product['panjang_standar']) ?>"
                                required>
                        </div>
                        <!-- LEBAR -->
                        <div>
                            <label for="lebar_standar">
                                Lebar
                            </label>
                            <input
                                type="number"
                                id="lebar_standar"
                                name="lebar_standar"
                                min="1"
                                step="0.01"
                                value="<?= e($product['lebar_standar']) ?>"
                                required>
                        </div>
                        <!-- TINGGI -->
                        <div>
                            <label for="tinggi_standar">
                                Tinggi
                            </label>
                            <input
                                type="number"
                                id="tinggi_standar"
                                name="tinggi_standar"
                                min="1"
                                step="0.01"
                                value="<?= e($product['tinggi_standar']) ?>"
                                required>
                        </div>
                    </div>
                </div>
                <!-- =====================================
                 HARGA DASAR
            ===================================== -->
                <div class="form-group">
                    <label for="harga_dasar">
                        Harga Dasar (Rp)
                    </label>
                    <input
                        type="number"
                        id="harga_dasar"
                        name="harga_dasar"
                        min="0"
                        step="1"
                        value="<?= e($product['harga_dasar']) ?>"
                        required>
                </div>
                <!-- =====================================
                 STOK PRODUK
            ===================================== -->
                <div class="form-group">
                    <label for="stok">
                        Stok
                    </label>
                    <input
                        type="number"
                        id="stok"
                        name="stok"
                        min="0"
                        step="1"
                        value="<?= e($product['stok']) ?>"
                        required>
                </div>
                <!-- =====================================
                 DESKRIPSI PRODUK
            ===================================== -->
                <div class="form-group">
                    <label for="deskripsi">
                        Deskripsi Produk
                    </label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        required><?= e($product['deskripsi']) ?></textarea>
                </div>
                <!-- =====================================
                 GAMBAR PRODUK
            ===================================== -->
                <div class="form-group">
                    <label>
                        Gambar Produk Saat Ini
                    </label>
                    <div class="image-preview">
                        <?php if (!empty($product['gambar_produk'])): ?>
                            <img
                                id="previewGambar"
                                src="/frontend/uploads/produk/<?= rawurlencode(basename($product['gambar_produk'])) ?>"
                                alt="<?= e($product['nama_produk']) ?>"
                                class="product-image">
                        <?php else: ?>
                            <img
                                id="previewGambar"
                                alt="Preview gambar produk"
                                class="product-image"
                                style="display: none;">
                            <p id="pesanGambar">
                                Belum ada gambar produk.
                            </p>
                        <?php endif; ?>
                    </div>
                    <!-- INPUT FILE -->
                    <input
                        type="file"
                        id="gambar_produk"
                        name="gambar_produk"
                        accept=".jpg,.jpeg,.png,.webp">
                    <!-- TOMBOL UBAH GAMBAR -->
                    <button
                        type="button"
                        class="btn-image"
                        id="btnUbahGambar">
                        Ubah Gambar
                    </button>
                    <!-- NAMA FILE -->
                    <p
                        class="hint"
                        id="namaFile"
                        aria-live="polite">
                        Belum memilih gambar baru.
                    </p>
                    <p class="hint">
                        Format JPG, PNG, atau WEBP.
                        Maksimal 2 MB.
                        Jika tidak memilih gambar baru,
                        gambar lama tetap digunakan.
                    </p>
                </div>
                <!-- =====================================
                 TOMBOL AKSI
            ===================================== -->
                <div class="actions">
                    <button
                        type="submit"
                        class="btn btn-save">
                        Simpan Perubahan
                    </button>
                    <a
                        href="/frontend/pages/admin/produk.php"
                        class="btn btn-back">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </main>
    <!-- =====================================
     JAVASCRIPT PREVIEW GAMBAR
===================================== -->
    <script>
        const inputGambar = document.getElementById('gambar_produk');
        const previewGambar = document.getElementById('previewGambar');
        const namaFile = document.getElementById('namaFile');
        const btnUbahGambar = document.getElementById('btnUbahGambar');
        const pesanGambar = document.getElementById('pesanGambar');
        // Membuka file explorer saat tombol diklik
        btnUbahGambar.addEventListener('click', function() {
            inputGambar.click();
        });
        // Menampilkan preview setelah memilih gambar
        inputGambar.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                return;
            }
            // Maksimal 2 MB
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran gambar maksimal 2 MB.');
                this.value = '';
                return;
            }
            // Validasi format file
            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];
            if (!allowedTypes.includes(file.type)) {
                alert('Format gambar harus JPG, PNG, atau WEBP.');
                this.value = '';
                return;
            }
            // Membuat preview gambar baru
            const imageUrl = URL.createObjectURL(file);
            previewGambar.onload = function() {
                URL.revokeObjectURL(imageUrl);
            };
            previewGambar.src = imageUrl;
            previewGambar.style.display = 'block';
            // Menyembunyikan pesan jika sebelumnya tidak ada gambar
            if (pesanGambar) {
                pesanGambar.style.display = 'none';
            }
            // Menampilkan nama file yang dipilih
            namaFile.textContent = 'Gambar baru: ' + file.name;
        });
    </script>
</body>
</html>