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
$canPay = in_array($order['status_pesanan'], ['MENUNGGU_PEMBAYARAN', 'DIPROSES', 'DIKIRIM', 'SELESAI'], true);
$paymentActive = $payment && $payment['status_pembayaran'] !== 'DITOLAK';
$selectedMethod = (string) ($form['metode_pembayaran'] ?? '');
$amountValue = (string) ($form['nominal_transfer'] ?? '');
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
        <nav class="payment-breadcrumb"><a href="/?page=customer-home">Beranda</a> / <b>Pembayaran</b></nav>
        <header class="payment-header">
            <h1>Pembayaran Pesanan</h1>
            <p>Invoice: <strong><?= $escape($order['no_invoice'] ?: 'ORD-' . $order['id_pesanan']) ?></strong></p>
        </header>

        <?php if ($message !== ''): ?><div class="payment-message payment-message--<?= $escape($messageType) ?>" role="alert"><?= $escape($message) ?></div><?php endif; ?>
        <section class="payment-summary">
            <h2>Ringkasan Tagihan</h2>
            <?php foreach ($order['items'] as $item): ?><div class="summary-row"><span><?= $escape($item['nama_produk']) ?> × <?= (int) $item['jumlah'] ?></span><strong>Rp <?= number_format((float) $item['jumlah'] * (float) $item['harga_satuan'], 0, ',', '.') ?></strong></div><?php endforeach; ?>
            <div class="summary-row"><span>Tagihan Pokok</span><strong>Rp <?= number_format($principal, 0, ',', '.') ?></strong></div>
            <div class="summary-row"><span>Ongkos Kirim</span><strong>Rp <?= number_format((float) $order['ongkir'], 0, ',', '.') ?></strong></div>
            <div class="summary-row"><span>Diskon</span><strong>- Rp <?= number_format((float) $order['diskon'], 0, ',', '.') ?></strong></div>
            <div class="summary-total"><span>Total Tagihan</span><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></div>
        </section>

        <?php if (!$canPay): ?>
            <div class="payment-notice">Nominal final masih menunggu konfirmasi admin. Form pembayaran akan terbuka setelah pesanan minimal berstatus Menunggu Pembayaran.</div>
        <?php elseif ($paymentActive): ?>
            <div class="payment-notice">Bukti pembayaran Anda sedang diproses admin.</div>
        <?php else: ?>
            <form class="payment-form" action="/?action=customer-payment-submit" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                <h2>Transfer Pembayaran</h2>
                <fieldset class="method-options"><legend>Metode Transfer</legend>
                    <?php foreach ($config['accounts'] as $method => $account): ?>
                        <label class="method-option"><input type="radio" name="metode_pembayaran" value="<?= $escape($method) ?>"<?= $selectedMethod === $method ? ' checked' : '' ?> required><span><strong><?= $escape($account['label']) ?></strong><small><?= $escape($account['account']) ?></small></span></label>
                    <?php endforeach; ?>
                </fieldset>
                <label class="payment-field">Nominal Transfer (Rp)<input type="number" name="nominal_transfer" min="1" step="0.01" value="<?= $escape($amountValue) ?>" required></label>
                <label class="payment-field">Bukti Transfer (JPG, PNG, PDF, maksimal 2 MB)<input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required></label>
                <button class="payment-submit" type="submit">Kirim Bukti Pembayaran</button>
            </form>
        <?php endif; ?>
    </div>
</main>
<script src="/assets/js/customer/pembayaran.js"></script>
</body>
</html>
