<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: /?page=admin-login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/OrderModel.php';

$rawOrderId = $_GET['id'] ?? null;
$orderId = is_string($rawOrderId) || is_int($rawOrderId)
    ? filter_var($rawOrderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$order = $orderId === false
    ? null
    : (new OrderModel($pdo))->getAdminOrderDetail((int) $orderId);
if ($order === null) {
    $_SESSION['admin_order_message'] = [
        'type' => 'error',
        'text' => 'Pesanan tidak ditemukan. Pilih pesanan yang siap dikirim dari daftar.',
    ];
    header('Location: /?page=admin-resi-list', true, 303);
    exit;
}
if (!in_array($order['status_pesanan'] ?? '', ['DIPROSES', 'DIKIRIM'], true)) {
    $_SESSION['admin_order_message'] = [
        'type' => 'error',
        'text' => 'Nomor resi hanya dapat diinput untuk pesanan yang sedang diproses atau dikirim.',
    ];
    header('Location: /?page=admin-resi-list', true, 303);
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$normalizeStatus = static function ($status): array {
    $map = [
        'MENUNGGU_VERIFIKASI' => ['Perlu Verifikasi', 'waiting'],
        'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'waiting'],
        'DIPROSES' => ['Diproses', 'processing'],
        'DIKIRIM' => ['Dikirim', 'processing'],
        'SELESAI' => ['Selesai', 'complete'],
        'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
    ];
    return $map[$status] ?? [$status ?: 'Belum ada status', 'other'];
};

$status = $normalizeStatus((string) ($order['status_pesanan'] ?? ''));
$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$phone = preg_replace('/\D+/', '', (string) ($order['telepon'] ?? ''));
$whatsappPhone = $phone !== '' && str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
$deliveryDate = date('d F Y', strtotime((string) ($order['tanggal_pesan'] ?? 'now')));
$item = $order['items'][0] ?? null;
$image = $item['gambar_produk'] ?? '';
if ($image !== '') {
    $image = product_image_url($image);
}
$isGojek = ($order['metode_pengiriman'] ?? '') === 'INSTANT_GOJEK';
$tracking = trim((string) ($isGojek
    ? ($order['nomor_telepon_kurir'] ?? '')
    : ($order['nomor_resi'] ?? '')));
$customerAddress = trim((string) ($order['alamat_pengiriman'] ?? ''));
$customerAddress .= $customerAddress !== '' && trim((string) ($order['catatan_alamat'] ?? '')) !== '' ? "\n" . trim((string) ($order['catatan_alamat'] ?? '')) : '';
$trackingLabel = $isGojek ? 'Nomor Telepon Kurir Gojek' : 'Nomor Resi Pengiriman';
$trackingPlaceholder = $isGojek ? 'Contoh: 081234567890' : 'Contoh: JNE882910481290';
$trackingHint = $isGojek
    ? 'Masukkan nomor telepon kurir Gojek.'
    : 'Masukkan nomor resi dari ekspedisi J&T atau JNE.';
$courierLabel = ($order['metode_pengiriman'] ?? '') === 'INSTANT_GOJEK'
    ? 'GoJek Instant'
    : (($order['metode_pengiriman'] ?? '') === 'EKSPEDISI_JNT' ? 'J&T Express' : 'JNE Express');
$productSize = ($item['tipe_ukuran'] ?? 'STANDARD') === 'CUSTOM' ? 'Custom Size' : 'Ukuran Standar';
$productSize .= ' · ' . (int) ($item['panjang'] ?? 0) . ' × ' . (int) ($item['lebar'] ?? 0);
$productSize .= ($item['tinggi'] ?? null) === null ? '' : ' × ' . (int) $item['tinggi'];
$productSize .= ' cm';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nomor Pengiriman - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/input_nomor_resi.css?v=<?= (int) filemtime(__DIR__ . '/../../../public/assets/css/admin/input_nomor_resi.css') ?>">
</head>
<body class="resi-input-page">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="resi-input-content">
                <div class="page-heading">
                    <div>
                        <h1 class="page-title"><?= $isGojek ? 'Input Nomor Telepon Kurir Gojek' : 'Input Nomor Resi Pengiriman' ?></h1>
                        <p class="page-description">Simpan nomor pengiriman agar pesanan dapat dilacak oleh pelanggan.</p>
                    </div>
                    <a class="resi-back-link" href="/?page=admin-resi-list">Kembali ke daftar</a>
                </div>

                <?php if (is_array($flash) && isset($flash['text'])): ?>
                    <div class="flash-message <?= $escape($flash['type'] ?? 'error') ?>" role="status">
                        <?= $escape($flash['text']) ?>
                    </div>
                <?php endif; ?>

                <div class="resi-layout">
                    <form class="form-card shipping-form" action="/?action=admin-order-resi-save" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">

                        <div class="form-section-heading">
                            <h2>Rincian Pengiriman</h2>
                            <p>Lengkapi informasi pengiriman untuk pesanan ini.</p>
                        </div>
                        <div class="form-fields">
                            <div class="field field-wide">
                                <label for="nomor_resi"><?= $escape($trackingLabel) ?> <span class="required-mark">*</span></label>
                                <input id="nomor_resi" name="nomor_pengiriman"
                                    type="<?= $isGojek ? 'tel' : 'text' ?>"
                                    value="<?= $escape($tracking) ?>"
                                    placeholder="<?= $escape($trackingPlaceholder) ?>"
                                    maxlength="50"
                                    <?= $isGojek ? 'inputmode="tel" autocomplete="tel"' : '' ?>
                                    aria-describedby="trackingHint"
                                    required>
                                <small class="field-hint" id="trackingHint"><?= $escape($trackingHint) ?></small>
                            </div>

                            <div class="field field-note">
                                <label for="catatan_pengiriman">Catatan Pengiriman <span class="optional-label">(opsional)</span></label>
                                <textarea id="catatan_pengiriman" name="catatan_pengiriman" placeholder="Masukkan catatan pengiriman"><?= $escape((string) ($order['catatan_pengiriman'] ?? '')) ?></textarea>
                            </div>

                            <div class="note-row">
                                <input class="checkbox" type="checkbox" id="konfirmasi" name="konfirmasi" value="1" required>
                                <label for="konfirmasi">Saya mengonfirmasi pesanan telah diserahkan kepada kurir. Status pesanan akan diperbarui menjadi dikirim.</label>
                            </div>
                        </div>

                        <div class="footer-actions">
                            <a class="button secondary" href="/?page=admin-resi-list">Batal</a>
                            <button type="submit" class="button primary"><?= $isGojek ? 'Simpan Nomor Telepon' : 'Simpan Nomor Resi' ?></button>
                        </div>
                    </form>

                    <aside class="order-summary" aria-labelledby="summaryTitle">
                        <h2 id="summaryTitle">Ringkasan Pesanan</h2>
                        <div class="summary-invoice">
                            <div>
                                <span class="summary-label">Nomor Pesanan</span>
                                <strong><?= $escape($order['no_invoice'] ?? 'INV/000000') ?></strong>
                            </div>
                            <span class="status-badge"><?= $escape($status[0]) ?></span>
                        </div>

                        <div class="summary-product">
                            <?php if ($image !== ''): ?>
                                <div class="product-image"><img src="<?= $escape($image) ?>" alt="<?= $escape($item['nama_produk'] ?? 'Produk') ?>"></div>
                            <?php else: ?>
                                <div class="product-image" aria-hidden="true"></div>
                            <?php endif; ?>
                            <div class="product-meta">
                                <strong><?= $escape($item['nama_produk'] ?? 'Produk') ?></strong>
                                <span><?= $escape($productSize) ?></span>
                            </div>
                        </div>

                        <div class="summary-details">
                            <div class="summary-group">
                                <span class="summary-label">Pelanggan</span>
                                <strong><?= $escape($order['nama_pelanggan'] ?? 'Pelanggan') ?></strong>
                                <span><?= $escape($order['telepon'] ?? '-') ?></span>
                            </div>
                            <div class="summary-group">
                                <span class="summary-label">Pengiriman</span>
                                <strong><?= $escape($courierLabel) ?></strong>
                                <span><?= nl2br($escape($customerAddress !== '' ? $customerAddress : 'Alamat belum tersedia')) ?></span>
                            </div>
                            <div class="summary-group summary-date">
                                <span class="summary-label">Tanggal Pesanan</span>
                                <span><?= $escape($deliveryDate) ?></span>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    </div>
    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/input_nomor_resi.js"></script>
</body>
</html>
