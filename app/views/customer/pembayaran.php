<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'CUSTOMER'
) {
    $_SESSION['cart_message'] = 'Silakan login dengan akun customer untuk melakukan pembayaran.';
    $_SESSION['cart_message_type'] = 'error';
    header('Location: /?page=customer-login', true, 303);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../controllers/PaymentController.php';
$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$controller = new PaymentController();
$order = $controller->getOrder($orderId === false ? null : $orderId);
if (!$order) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$config = require __DIR__ . '/../../config/payment.php';
$form = is_array($_SESSION['payment_form'] ?? null) ? $_SESSION['payment_form'] : [];
$message = (string) ($_SESSION['payment_message'] ?? '');
$messageType = ($_SESSION['payment_message_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['payment_form'], $_SESSION['payment_message'], $_SESSION['payment_message_type']);
$subtotal = 0.0;
foreach ($order['items'] as $item) {
    $subtotal += (float) $item['jumlah'] * (float) $item['harga_satuan'];
}
$principal = (float) ($order['tagihan_pokok'] ?: $subtotal);
$total = $principal + (float) $order['ongkir'] - (float) $order['diskon'];
$payment = $order['payment'];
$canPay = $order['status_pesanan'] === 'MENUNGGU_PEMBAYARAN';
$paymentActive = $payment && $payment['status_pembayaran'] !== 'DITOLAK';
$accounts = array_filter(
    $config['accounts'],
    static fn (array $account): bool => !str_contains(strtoupper((string) $account['account']), 'TODO')
);
$selectedMethod = (string) ($form['metode_pembayaran'] ?? array_key_first($accounts) ?? '');
if (!isset($accounts[$selectedMethod])) {
    $selectedMethod = (string) (array_key_first($accounts) ?? '');
}
$orderDate = strtotime((string) $order['tanggal_pesan']);
$orderDateLabel = $orderDate === false ? '' : date('j M Y', $orderDate);
$shippingMethods = [
    'INSTANT_GOJEK' => 'Kurir instan (Gojek / Grab Express)',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$shippingMethod = $shippingMethods[$order['metode_pengiriman']] ?? 'Kurir pengiriman';
$methodLabels = [
    'TRANSFER_BCA' => 'BCA',
    'TRANSFER_MANDIRI' => 'Mandiri',
    'TRANSFER_BRI' => 'BRI',
    'TRANSFER_BSI' => 'BSI',
];
$formattedTotal = number_format($total, 0, ',', '.');
$copyTotal = fmod($total, 1.0) === 0.0
    ? number_format($total, 0, '', '')
    : number_format($total, 2, '.', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/customer/pembayaran.css">
</head>
<body>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="payment-page">
    <div class="payment-container">
        <nav class="payment-breadcrumb">
            <a href="/?page=customer-home">Beranda</a> /
            <a href="/?page=customer-orders">Pesanan Saya</a> /
            <b>Pembayaran</b>
        </nav>
        <header class="payment-header">
            <div>
                <h1>Pembayaran Pesanan</h1>
                <p>Invoice <strong><?= $escape($order['no_invoice'] ?: 'ORD-' . $order['id_pesanan']) ?></strong> · <?= $escape($orderDateLabel) ?></p>
            </div>
        </header>

        <?php if ($message !== ''): ?><div class="payment-message payment-message--<?= $escape($messageType) ?>" role="alert"><?= $escape($message) ?></div><?php endif; ?>

        <section class="delivery-card" aria-label="Informasi pengiriman">
            <h2>Dikirim kepada</h2>
            <strong><?= $escape($order['nama_pelanggan']) ?></strong>
            <p>WA <?= $escape($order['telepon_pelanggan']) ?> · <?= $escape($order['alamat_pengiriman']) ?></p>
        </section>

        <div class="payment-overview">
            <section class="order-card">
                <h2>Rincian pesanan · <?= count($order['items']) ?> item</h2>
                <div class="order-table" role="table" aria-label="Rincian produk">
                    <div class="order-table__head" role="row">
                        <span role="columnheader">Produk</span><span role="columnheader">Jumlah</span><span role="columnheader">Total</span>
                    </div>
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="order-table__row" role="row">
                            <div class="order-product" role="cell">
                                <strong><?= $escape($item['nama_produk']) ?></strong>
                                <small>
                                    <?= (int) $item['panjang'] ?> × <?= (int) $item['lebar'] ?> × <?= (int) $item['tinggi'] ?> cm
                                    · <?= $escape(ucfirst(strtolower($item['tipe_ukuran']))) ?>
                                    <?php
                                    $extras = [];
                                    if ((int) $item['tambah_sarban'] > 0) {
                                        $extras[] = (int) $item['tambah_sarban'] . ' sarung bantal';
                                    }
                                    if ((int) $item['tambah_sargul'] > 0) {
                                        $extras[] = (int) $item['tambah_sargul'] . ' sarung guling';
                                    }
                                    ?>
                                    <?= $extras === [] ? '' : ' · ' . $escape(implode(' + ', $extras)) ?>
                                </small>
                            </div>
                            <span role="cell"><?= (int) $item['jumlah'] ?> set</span>
                            <strong role="cell">Rp <?= number_format((float) $item['jumlah'] * (float) $item['harga_satuan'], 0, ',', '.') ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="payment-summary" aria-label="Ringkasan biaya">
                <h2>Ringkasan biaya</h2>
                <div class="summary-row"><span>Subtotal produk &amp; jahit</span><span>Rp <?= number_format($principal, 0, ',', '.') ?></span></div>
                <div class="summary-row"><span>Ongkos kirim (<?= $escape($shippingMethod) ?>)</span><span>Rp <?= number_format((float) $order['ongkir'], 0, ',', '.') ?></span></div>
                <?php if ((float) $order['diskon'] > 0): ?><div class="summary-row"><span>Diskon</span><span>Rp <?= number_format((float) $order['diskon'], 0, ',', '.') ?></span></div><?php endif; ?>
                <div class="summary-total"><span>Total tagihan</span><strong>Rp <?= $formattedTotal ?></strong></div>
                <?php if ($canPay && !$paymentActive): ?>
                    <button class="copy-amount" type="button" data-copy="<?= $escape($copyTotal) ?>">Salin nominal</button>
                    <p class="summary-hint">Transfer tepat Rp <?= $formattedTotal ?> agar mudah diverifikasi.</p>
                <?php endif; ?>
            </section>
        </div>

        <?php if (!$canPay): ?>
            <div class="payment-notice">
                <?= $order['status_pesanan'] === 'MENUNGGU_VERIFIKASI'
                    ? 'Nominal final masih menunggu konfirmasi admin. Form pembayaran akan terbuka setelah pesanan berstatus Menunggu Pembayaran.'
                    : 'Pesanan ini belum dapat menerima pembayaran.' ?>
            </div>
        <?php elseif ($paymentActive): ?>
            <div class="payment-notice">Bukti pembayaran Anda sedang diproses admin.</div>
        <?php else: ?>
            <form class="payment-form" action="/?action=customer-payment-submit" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                <input type="hidden" name="nominal_transfer" value="<?= $escape(number_format($total, 2, '.', '')) ?>">
                <section class="payment-step">
                    <h2>1. Pilih bank tujuan</h2>
                    <p class="step-description">Nomor rekening muncul pada bank yang Anda pilih.</p>
                    <fieldset class="method-options">
                    <legend class="visually-hidden">Pilih bank tujuan</legend>
                    <?php foreach ($accounts as $method => $account): ?>
                        <?php
                        $accountParts = explode(' - ', (string) $account['account'], 2);
                        $accountName = trim($accountParts[0]);
                        $accountNumber = trim($accountParts[1] ?? $accountParts[0]);
                        ?>
                        <div class="method-option<?= $selectedMethod === $method ? ' method-option--selected' : '' ?>">
                            <label class="method-option__label">
                                <input type="radio" name="metode_pembayaran" value="<?= $escape($method) ?>" required<?= $selectedMethod === $method ? ' checked' : '' ?>>
                                <span class="method-option__summary">
                                    <span class="bank-radio" aria-hidden="true"></span>
                                    <span class="bank-logo bank-logo--<?= $escape(strtolower($methodLabels[$method] ?? 'bank')) ?>"><?= $escape($methodLabels[$method] ?? 'BANK') ?></span>
                                    <span class="bank-name"><?= $escape($account['bank_name'] ?? $account['label']) ?></span>
                                </span>
                            </label>
                            <div class="account-details"<?= $selectedMethod === $method ? '' : ' hidden' ?>>
                                <div>
                                    <small class="account-owner"><?= $escape($accountName) ?></small>
                                    <strong class="account-number"><?= $escape($accountNumber) ?></strong>
                                </div>
                                <button class="copy-account" type="button">Salin nomor rekening</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($accounts === []): ?>
                        <p class="payment-notice">Belum ada rekening tujuan yang dikonfigurasi. Silakan hubungi admin sebelum melakukan pembayaran.</p>
                    <?php endif; ?>
                    </fieldset>
                </section>

                <section class="payment-step upload-step">
                    <h2>2. Unggah bukti transfer</h2>
                    <div class="upload-control">
                        <span class="upload-file-icon" aria-hidden="true">JPG</span>
                        <span class="upload-file-info">
                            <strong class="upload-file-name">Belum ada file dipilih</strong>
                            <small class="upload-file-size">JPG atau PNG · maksimal 8 MB</small>
                        </span>
                        <span class="upload-actions">
                            <button class="upload-remove" type="button" disabled>Hapus</button>
                            <button class="upload-browse" type="button">Pilih file</button>
                        </span>
                        <input id="payment-proof" type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png" aria-label="Pilih bukti transfer" required>
                    </div>
                    <p class="upload-help">JPG atau PNG, maksimal 8 MB. Pastikan nominal dan tanggal terlihat jelas.</p>
                    <label class="payment-field">
                        Nama pemilik rekening pengirim <span>(opsional)</span>
                        <input type="text" name="nama_pemilik_pengirim" maxlength="100" value="<?= $escape($form['nama_pemilik_pengirim'] ?? '') ?>" placeholder="Contoh: Vico Dwi Wijaya" autocomplete="name">
                    </label>
                    <label class="payment-field">
                        Nomor rekening pengirim <span>(opsional)</span>
                        <input type="text" name="nomor_rekening_pengirim" maxlength="50" value="<?= $escape($form['nomor_rekening_pengirim'] ?? '') ?>" placeholder="Contoh: 1234567890" inputmode="numeric" autocomplete="off">
                    </label>
                </section>
                <button class="payment-submit" type="submit"<?= $accounts === [] ? ' disabled' : '' ?>>Kirim bukti pembayaran</button>
                <p class="payment-footer-note">Setelah dikirim, admin akan memverifikasi. Status tampil di Pesanan Saya.</p>
            </form>
        <?php endif; ?>

        <?php if ($canPay && $paymentActive): ?>
            <!-- Tombol kembali: hanya saat bukti transfer sedang diproses admin (tampil di tablet & HP) -->
            <a class="payment-back" href="/?page=customer-orders">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
                Kembali ke Pesanan Saya
            </a>
        <?php endif; ?>
    </div>
</main>
<script src="/assets/js/customer/pembayaran.js"></script>
</body>
</html>
