<?php

// ==========================================
// 1. SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the add-to-cart form has a session token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate the product ID before loading its details.
$rawId = $_GET['id'] ?? null;
$id = is_string($rawId) || is_int($rawId)
    ? filter_var($rawId, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ])
    : false;

if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../helpers/product_pricing.php';
$productController = new ProductController();
$dataProduk = $productController->getProductById($id);

if (!$dataProduk) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

// ==========================================
// 8. FORMAT DATA PRODUK
// ==========================================
$produk = [
    'id' => (int) $dataProduk['id_produk'],
    'nama' => $dataProduk['nama_produk'],
    'varian' => $dataProduk['kategori_produk'],
    'gambar' => $dataProduk['gambar_produk'],
    'panjang' => (float) $dataProduk['panjang_standar'],
    'lebar' => (float) $dataProduk['lebar_standar'],
    'tinggi' => $dataProduk['tinggi_standar'] === null
        ? null
        : (float) $dataProduk['tinggi_standar'],
    'harga' => (float) $dataProduk['harga_dasar'],
    'stok' => (int) $dataProduk['stok'],
    'deskripsi' => $dataProduk['deskripsi']
];
$ukuranStandarSaja = product_is_pillow_bolster_category((string) $produk['varian']);
$bolehTambahSarung = $produk['varian'] === 'SPREI';
$jenisKainProduk = (string) ($dataProduk['jenis_kain'] ?? '');
$semuaPilihanHarga = product_pricing_options($produk['varian']);
$pilihanHarga = isset($semuaPilihanHarga[$jenisKainProduk])
    ? [$jenisKainProduk => $semuaPilihanHarga[$jenisKainProduk]]
    : [];
$kainTerpilih = $jenisKainProduk;
$ukuranKatalog = (int) $produk['lebar'];
$ukuranTerpilih = isset($pilihanHarga[$kainTerpilih]['prices'][$ukuranKatalog])
    ? $ukuranKatalog
    : (int) (array_key_first($pilihanHarga[$kainTerpilih]['prices'] ?? []) ?? 0);
$hargaTerpilih = product_price_for($produk['varian'], $kainTerpilih, $ukuranTerpilih)
    ?? (int) $produk['harga'];
$labelProduk = match ($produk['varian']) {
    'SPREI' => 'Sprei',
    'BED_COVER' => 'Bed Cover',
    'SARUNG_BANTAL' => 'Sarung Bantal & Guling',
    'SARUNG_GULING' => 'Sarung Bantal & Guling',
    'SARUNG_BANTAL_GULING' => 'Sarung Bantal & Guling',
    default => 'Produk',
};

// ==========================================
// 9. GAMBAR PRODUK
// ==========================================
$gambarProduk = !empty($produk['gambar'])
    ? product_image_url($produk['gambar'])
    : '';

// ==========================================
// 10. DIMENSI KASUR
// ==========================================
$dimensi = [
    [
        'id' => 'length',
        'label' => 'Panjang',
        'nilai' => $produk['panjang']
    ],
    [
        'id' => 'width',
        'label' => 'Lebar',
        'nilai' => $produk['lebar']
    ],
];
if (!$ukuranStandarSaja) {
    $dimensi[] = [
        'id' => 'height',
        'label' => 'Tinggi',
        'nilai' => $produk['tinggi']
    ];
}

// ==========================================
// 11. TAMBAHAN SARUNG
// ==========================================
$tambahan = [
    [
        'id' => 'qtyBantal',
        'judul' => 'Tambah Sarung Bantal'
    ],
    [
        'id' => 'qtyGuling',
        'judul' => 'Tambah Sarung Guling'
    ]
];

// ==========================================
// 13. HELPER HTML
// ==========================================
function aman($teks): string
{
    return htmlspecialchars(
        (string) $teks,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ==========================================
// 14. FLASH ERROR KERANJANG
// ==========================================
$cartMessage = $_SESSION['cart_message'] ?? '';
$cartMessageType = $_SESSION['cart_message_type'] ?? 'error';
unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        <?= aman($produk['nama']) ?> - Griya Sprei
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="/assets/css/base.css">

    <link
        rel="stylesheet"
        href="/assets/css/customer/detail.css">

</head>

<body>

    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <!-- ==========================================
     ISI HALAMAN
========================================== -->
    <main class="container">

        <!-- BREADCRUMB -->
        <nav
            class="breadcrumb"
            aria-label="Breadcrumb">

            <a href="/?page=customer-home">
                Beranda
            </a>

            <span>/</span>

            <a href="/?page=customer-catalog">
                Katalog
            </a>

            <span>/</span>

            <strong>
                <?= aman($produk['nama']) ?>
            </strong>

        </nav>

        <?php if ($cartMessage !== ''): ?>

            <p
                class="form__message form__message--<?= $cartMessageType === 'success' ? 'ok' : 'error' ?>"
                role="alert"
                style="display:block;margin-bottom:16px;">
                <?= aman($cartMessage) ?>
            </p>

        <?php endif; ?>

        <div class="detail">

            <!-- ==================================
             KIRI: FOTO DAN INFO PRODUK
        =================================== -->
            <div class="detail__left">

                <!-- FOTO -->
                <div class="panel panel--image">

                    <div class="product-image media">

                        <?php if ($gambarProduk !== ''): ?>

                            <img
                                src="<?= aman($gambarProduk) ?>"
                                alt="<?= aman($produk['nama']) ?>">

                        <?php else: ?>

                            <p>
                                Gambar produk belum tersedia.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- INFORMASI -->
                <div class="panel panel--info">

                    <h2 class="product-title">
                        <?= aman($produk['nama']) ?>
                    </h2>

                    <p class="product-size">

                        Ukuran Terpilih:

                        <strong id="standardSizeText">
                            <?= aman($produk['panjang'] . ' × ' . $produk['lebar']) ?>
                            <?= $ukuranStandarSaja ? 'cm' : '× ' . aman($produk['tinggi']) . ' cm' ?>
                        </strong>

                    </p>

                    <p class="product-desc">
                        <?= aman($produk['deskripsi']) ?>
                    </p>

                    <!-- PEMBERITAHUAN -->
                    <div class="notice">

                        <svg
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.6">
                            <path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>

                        <div>

                            <h3>
                                Pemberitahuan Tagihan &amp; Verifikasi
                            </h3>

                            <p>
                                Harga dan tagihan final akan dikonfirmasi
                                oleh Admin setelah spesifikasi pesanan
                                Anda diverifikasi.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ==================================
             KANAN: FORM PESANAN
        =================================== -->
            <form
                class="panel panel--form is-standard"
                id="orderForm"
                action="/?action=customer-cart-add"
                method="POST"
                data-stock="<?= (int) $produk['stok'] ?>"
                data-price="<?= aman($produk['harga']) ?>"
                data-catalog-length="<?= aman($produk['panjang']) ?>"
                data-catalog-width="<?= aman($produk['lebar']) ?>"
                data-catalog-height="<?= aman($produk['tinggi']) ?>"
                data-standard-only="<?= $ukuranStandarSaja ? 'true' : 'false' ?>"
                data-pricing="<?= aman(json_encode(
                    $pilihanHarga,
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                )) ?>"
                novalidate>

                <!-- ID PRODUK -->
                <input
                    type="hidden"
                    name="id_produk"
                    value="<?= (int) $produk['id'] ?>">

                <!-- CSRF TOKEN -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= aman($_SESSION['csrf_token']) ?>">

                <input
                    type="hidden"
                    name="jenis_kain"
                    value="<?= aman($jenisKainProduk) ?>">

                <!-- MODE STANDARD / CUSTOM -->
                <input
                    type="hidden"
                    name="mode"
                    id="modeInput"
                    value="standard">

                <!-- NILAI JUMLAH UNTUK BACKEND -->
                <input
                    type="hidden"
                    name="qtySprei"
                    id="qtySpreiInput"
                    value="1">

                <input
                    type="hidden"
                    name="qtyBantal"
                    id="qtyBantalInput"
                    value="0">

                <input
                    type="hidden"
                    name="qtyGuling"
                    id="qtyGulingInput"
                    value="0">

                <div class="form-heading">
                    <div>
                        <h2 class="form__title">Konfigurasi Ukuran &amp; Spesifikasi</h2>
                        <p><?= $ukuranStandarSaja ? 'Ukuran standar produk' : 'Sesuaikan dimensi kasur dan aksesoris' ?></p>
                    </div>
                    <div class="price-card" aria-live="polite">
                        <span>HARGA OTOMATIS</span>
                        <strong id="pricePreview">Rp <?= number_format($hargaTerpilih, 0, ',', '.') ?></strong>
                        <small id="customPriceNote" hidden>Harga custom dikonfirmasi Admin</small>
                        <small id="standardPriceNote">Estimasi harga ukuran standar</small>
                    </div>
                </div>

                <!-- ==================================
                 PILIH TIPE UKURAN
            =================================== -->
                <p class="form__label size-type-label"><span class="step-number">1</span> <?= $ukuranStandarSaja ? 'UKURAN PRODUK' : 'PILIH TIPE UKURAN KASUR' ?></p>

                <?php if ($ukuranStandarSaja): ?>
                    <p class="standard-only-note">Produk ini tersedia dalam ukuran standar.</p>
                <?php else: ?>
                    <div class="segmented" role="tablist">
                        <button
                            type="button"
                            class="segmented__btn segmented__btn--active"
                            data-mode="standard"
                            role="tab"
                            aria-selected="true">
                            Standar
                        </button>

                        <button
                            type="button"
                            class="segmented__btn"
                            data-mode="custom"
                            role="tab"
                            aria-selected="false">
                            Custom Ukuran
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($pilihanHarga !== []): ?>
                    <fieldset class="configuration-section size-selection">
                        <legend class="form__label">Pilih ukuran matras standar / rumah:</legend>
                        <div class="size-options">
                            <?php foreach ($pilihanHarga[$kainTerpilih]['prices'] as $width => $price): ?>
                                <?php
                                $sizeName = match ((int) $width) {
                                    90 => 'Single',
                                    100 => 'Single',
                                    120 => 'Super Single',
                                    140 => 'Queen',
                                    160 => 'Queen',
                                    180 => 'King',
                                    200 => 'Super King',
                                };
                                ?>
                                <label class="size-option">
                                    <input
                                        type="radio"
                                        name="ukuran_standar"
                                        value="<?= (int) $width ?>"
                                        required
                                        <?= (int) $width === $ukuranTerpilih ? 'checked' : '' ?>>
                                    <span class="size-option__content">
                                        <strong><?= aman($sizeName) ?> (<?= (int) $width ?>)</strong>
                                        <small>
                                            <?php if ((int) $width === $ukuranKatalog): ?>
                                                <?= (int) $produk['panjang'] ?> × <?= (int) $produk['lebar'] ?> × <?= (int) $produk['tinggi'] ?> cm
                                            <?php else: ?>
                                                <?= (int) $width ?> × 200 × <?= (int) $pilihanHarga[$kainTerpilih]['height'] ?> cm
                                            <?php endif; ?>
                                        </small>
                                        <b>Rp <?= number_format((int) $price, 0, ',', '.') ?></b>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <!-- ==================================
                 DIMENSI KASUR
            =================================== -->
                <fieldset class="dimension">

                    <legend class="dimension__title"><span class="step-number">2</span> <?= $ukuranStandarSaja ? 'UKURAN STANDAR (CM)' : 'DIMENSI KASUR (CM)' ?></legend>

                    <div class="dimension__grid <?= $ukuranStandarSaja ? 'dimension__grid--two' : '' ?>">

                        <?php foreach ($dimensi as $d): ?>

                            <div class="dim">

                                <label for="<?= aman($d['id']) ?>">
                                    <?= aman($d['label']) ?>
                                </label>

                                <div class="dim__input">

                                    <input
                                        type="number"
                                        id="<?= aman($d['id']) ?>"
                                        name="<?= aman($d['id']) ?>"
                                        min="1"
                                        step="1"
                                        value="<?= aman($d['nilai']) ?>"
                                        data-standard="<?= aman($d['nilai']) ?>"
                                        readonly
                                        required>

                                    <span>cm</span>

                                </div>

                                <p
                                    class="dim__hint"
                                    id="<?= aman($d['id']) ?>Hint">
                                    Standar katalog
                                </p>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </fieldset>

                <!-- ==================================
                 JUMLAH SPREI
            =================================== -->
                <div class="row-box">

                    <div>

                        <h3>Jumlah <?= aman($labelProduk) ?></h3>

                        <?php if ($bolehTambahSarung): ?>
                            <p>Termasuk sarung sesuai ukuran yang dipilih.</p>
                        <?php else: ?>
                            <p>Tentukan jumlah produk yang ingin dipesan.</p>
                        <?php endif; ?>

                    </div>

                    <div
                        class="stepper"
                        data-stepper
                        data-min="1"
                        data-max="<?= max(1, min(50, $produk['stok'])) ?>">

                        <button
                            type="button"
                            data-step="-1"
                            aria-label="Kurangi jumlah <?= aman(strtolower($labelProduk)) ?>">−</button>

                        <output id="qtySprei">1</output>

                        <button
                            type="button"
                            data-step="1"
                            aria-label="Tambah jumlah <?= aman(strtolower($labelProduk)) ?>">+</button>

                    </div>

                </div>

                <?php if ($bolehTambahSarung): ?>
                    <p class="form__label">
                        TAMBAHAN SARUNG BANTAL &amp; GULING
                    </p>

                    <div class="extras">

                    <?php foreach ($tambahan as $t): ?>

                        <div class="extra">

                            <div class="extra__head">

                                <h3>
                                    <?= aman($t['judul']) ?>
                                </h3>

                                <span>Opsional</span>

                            </div>

                            <div class="extra__row">

                                <div
                                    class="stepper stepper--sm"
                                    data-stepper
                                    data-min="0"
                                    data-max="20">

                                    <button
                                        type="button"
                                        data-step="-1"
                                        aria-label="Kurangi jumlah">−</button>

                                    <output id="<?= aman($t['id']) ?>">
                                        0
                                    </output>

                                    <button
                                        type="button"
                                        data-step="1"
                                        aria-label="Tambah jumlah">+</button>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                    </div>
                    <p class="extras-note">Harga tambahan akan dikonfirmasi Admin sesuai kain.</p>
                <?php endif; ?>

                <!-- ==================================
                 CATATAN
            =================================== -->
                <label
                    class="form__label"
                    for="note">
                    CATATAN:
                </label>

                <textarea
                    id="note"
                    name="catatan"
                    rows="4"
                    maxlength="1000"
                    placeholder="Contoh: Karet keliling penuh, resleting bawah, atau instruksi khusus penjahit..."></textarea>

                <!-- PESAN VALIDASI -->
                <p
                    class="form__message"
                    id="formMessage"
                    role="alert"
                    hidden></p>

                <div class="order-subtotal">
                    <span>Subtotal Pesanan</span>
                    <strong id="orderSubtotal">Rp <?= number_format($hargaTerpilih, 0, ',', '.') ?></strong>
                </div>
                <button
                    type="submit"
                    class="btn-cart"
                    <?= $produk['stok'] <= 0 ? 'disabled' : '' ?>>
                    Tambahkan ke Keranjang
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 12h14m-5-6 6 6-6 6" />
                    </svg>
                </button>

            </form>

        </div>

    </main>

    <!-- FOOTER -->
    <?php require __DIR__ . '/../layouts/footer.php'; ?>

    <script src="/assets/js/customer/detail.js"></script>

</body>

</html>