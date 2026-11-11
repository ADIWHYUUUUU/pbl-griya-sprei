<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: /?page=admin-login', true, 303);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/OrderModel.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$rupiah = static function ($value): string {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
};
$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($orderId === false || $orderId === null) {
    http_response_code(400);
    exit('ID pesanan tidak valid.');
}

$order = (new OrderModel($pdo))->getOrderForVerification($orderId);
if ($order === null) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$shippingLabels = [
    'INSTANT_GOJEK' => 'Gojek / Grab Express',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$canVerify = $order['status_pesanan'] === 'MENUNGGU_VERIFIKASI';
$principal = $order['tagihan_pokok'] ?? $order['subtotal'];
$total = max(0, $principal + $order['ongkir'] - $order['diskon']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pesanan - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/cekpesanan.css">
    <link rel="stylesheet" href="/assets/css/admin/verifikasi_pesanan.css">
</head>
<body class="admin-dashboard admin-orders">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="dashboard-main orders-main verification-main">
            <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

            <div class="verification-breadcrumb">
                <a href="/?page=admin-dashboard">Admin</a>
                <span aria-hidden="true">›</span>
                <a href="/?page=admin-orders">Cek Pesanan</a>
                <span aria-hidden="true">›</span>
                <strong>Verifikasi Pesanan</strong>
            </div>

            <header class="verification-header">
                <div>
                    <h1>Verifikasi Pesanan</h1>
                    <p>Periksa rincian pesanan, lalu tetapkan tagihan sebelum pelanggan membayar.</p>
                </div>
            </header>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="verification-flash verification-flash--<?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

            <?php if (!$canVerify): ?>
                <div class="verification-flash verification-flash--info" role="status">
                    Pesanan ini sudah diproses dengan status
                    <strong><?= $escape(str_replace('_', ' ', $order['status_pesanan'])) ?></strong>.
                    Perubahan tagihan hanya dapat dilakukan saat menunggu verifikasi.
                </div>
            <?php endif; ?>

            <div class="verification-grid">
                <section class="verification-card verification-summary" aria-labelledby="summaryTitle">
                    <div class="verification-card-heading">
                        <h2 id="summaryTitle">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h11l3 3v15H5z"/><path d="M9 3v6h6V3M8 13h8M8 17h8"/></svg>
                            Ringkasan Pesanan
                        </h2>
                        <span class="verification-invoice"><?= $escape($order['kode']) ?></span>
                    </div>

                    <dl class="verification-customer">
                        <div><dt>Nama Pelanggan</dt><dd><?= $escape($order['nama_pelanggan']) ?></dd></div>
                        <div><dt>No. WhatsApp</dt><dd><?= $escape($order['telepon_pelanggan']) ?></dd></div>
                        <div><dt>Email</dt><dd><?= $escape($order['email_pelanggan']) ?></dd></div>
                        <div class="verification-address">
                            <dt>Alamat Pengiriman</dt>
                            <dd><?= nl2br($escape($order['alamat_pengiriman'])) ?></dd>
                        </div>
                        <?php if ($order['catatan_alamat'] !== ''): ?>
                            <div class="verification-address">
                                <dt>Catatan Alamat</dt>
                                <dd><?= nl2br($escape($order['catatan_alamat'])) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>

                    <?php foreach ($order['produk'] as $product): ?>
                        <article class="verification-product">
                            <h3><?= $escape($product['nama']) ?></h3>
                            <dl>
                                <div>
                                    <dt>Ukuran Kasur</dt>
                                    <dd><?= $escape($product['tipe_ukuran'] === 'CUSTOM' ? 'Custom' : 'Standar') ?>:
                                        <strong><?= (int) $product['panjang'] ?> × <?= (int) $product['lebar'] ?> × <?= (int) $product['tinggi'] ?> cm</strong>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Kelengkapan</dt>
                                    <dd>
                                        <?php
                                        $extras = [];
                                        if ($product['tambah_sarban'] > 0) {
                                            $extras[] = $product['tambah_sarban'] . ' Sarung Bantal';
                                        }
                                        if ($product['tambah_sargul'] > 0) {
                                            $extras[] = $product['tambah_sargul'] . ' Sarung Guling';
                                        }
                                        ?>
                                        <?= $escape($extras === [] ? '-' : implode(', ', $extras)) ?>
                                    </dd>
                                </div>
                                <div><dt>Jumlah</dt><dd><?= (int) $product['jumlah'] ?> pcs</dd></div>
                                <div><dt>Subtotal Produk</dt><dd><?= $escape($rupiah($product['subtotal'])) ?></dd></div>
                            </dl>
                            <?php if ($product['catatan'] !== ''): ?>
                                <div class="verification-note">
                                    <strong>Catatan Jahit</strong>
                                    <p><?= $escape($product['catatan']) ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <div class="verification-shipping">
                        <strong>Metode Pengiriman</strong>
                        <span><?= $escape($shippingLabels[$order['metode_pengiriman']] ?? $order['metode_pengiriman']) ?></span>
                        <?php if ($order['catatan_pengiriman'] !== ''): ?>
                            <p><?= $escape($order['catatan_pengiriman']) ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="verification-card verification-form-card" aria-labelledby="formTitle">
                    <div class="verification-card-heading">
                        <h2 id="formTitle">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                            Form Pembayaran
                        </h2>
                    </div>

                    <?php if ($canVerify): ?>
                        <form method="post" action="/?action=admin-order-verify-save" class="verification-form">
                            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">

                            <label for="tagihanPokok">Tagihan Pokok (Rp)</label>
                            <div class="verification-input">
                                <span>Rp</span>
                                <input id="tagihanPokok" name="tagihan_pokok" type="number" min="1" max="9999999999" step="1" required value="<?= (int) $principal ?>">
                            </div>

                            <label for="ongkir">Ongkos Kirim (Rp)</label>
                            <div class="verification-input">
                                <span>Rp</span>
                                <input id="ongkir" name="ongkir" type="number" min="0" max="9999999999" step="1" required value="<?= (int) $order['ongkir'] ?>">
                            </div>

                            <label for="diskon">Diskon / Potongan (Rp)</label>
                            <div class="verification-input">
                                <span>Rp</span>
                                <input id="diskon" name="diskon" type="number" min="0" max="9999999999" step="1" required value="<?= (int) $order['diskon'] ?>">
                            </div>

                            <div class="verification-total">
                                <span>Total Tagihan Pelanggan</span>
                                <strong id="totalTagihan"><?= $escape($rupiah($total)) ?></strong>
                                <small>Tagihan pokok + ongkos kirim − diskon</small>
                            </div>

                            <div class="verification-actions">
                                <a class="verification-cancel" href="/?page=admin-orders">Batal</a>
                                <button class="verification-submit" type="submit">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="verification-saved">
                            <span>Total Tagihan</span>
                            <strong><?= $escape($rupiah($total)) ?></strong>
                            <small>Tagihan sudah disimpan.</small>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/verifikasi_pesanan.js"></script>
</body>
</html>
