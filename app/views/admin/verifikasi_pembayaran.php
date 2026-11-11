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

require_once __DIR__ . '/../../controllers/AdminPaymentController.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$paymentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$payment = (new AdminPaymentController())->verifyForm($paymentId === false ? null : $paymentId);
if (!$payment) {
    http_response_code(404);
    exit('Pembayaran tidak ditemukan.');
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$config = require __DIR__ . '/../../config/payment.php';
$account = $config['accounts'][$payment['metode_pembayaran']]['account'] ?? '-';
$methodLabel = $config['accounts'][$payment['metode_pembayaran']]['label'] ?? $payment['metode_pembayaran'];
$total = (float) $payment['total_tagihan'];
$transfer = (float) $payment['nominal_transfer'];
$difference = abs($transfer - $total);
$isMatching = abs($transfer - $total) < 0.005;
$isWaiting = $payment['status_pembayaran'] === 'MENUNGGU_VERIFIKASI';
$filePath = '/' . ltrim(str_replace('\\', '/', (string) $payment['bukti_transfer']), '/');
$fileMime = (string) ($payment['bukti_mime'] ?? '');
$fileType = match ($fileMime) {
    'image/png' => 'PNG',
    'image/jpeg' => 'JPG',
    'application/pdf' => 'PDF',
    default => strtoupper(pathinfo($filePath, PATHINFO_EXTENSION) ?: 'FILE'),
};
$isImage = in_array($fileMime, ['image/png', 'image/jpeg'], true);
$fileSizeKb = number_format(((int) ($payment['bukti_ukuran'] ?? 0)) / 1024, 0, ',', '.');
$uploadedAt = strtotime((string) $payment['tanggal_upload']);
$uploadedLabel = $uploadedAt ? date('d M Y, H:i', $uploadedAt) . ' WIB' : (string) $payment['tanggal_upload'];
$phone = preg_replace('/\D+/', '', (string) ($payment['telepon'] ?? ''));
$waPhone = $phone !== '' && str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
$statusLabels = [
    'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
    'TERVERIFIKASI' => 'Terverifikasi',
    'DITOLAK' => 'Ditolak',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/cek_pembayaran.css">
</head>
<body class="admin-dashboard admin-payment-page">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="dashboard-main payment-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="payment-breadcrumb">
            <span>Admin</span><span aria-hidden="true">›</span><a href="/?page=admin-payments">Cek Pembayaran</a><span aria-hidden="true">›</span><strong>Verifikasi Pembayaran</strong>
        </div>
        <header class="payment-detail-header">
            <a class="payment-detail-back" href="/?page=admin-payments">&larr; Kembali ke Cek Pembayaran</a>
            <div class="payment-title-row"><h1>Verifikasi Pembayaran</h1><span class="invoice-chip"><?= $escape($payment['no_invoice'] ?: 'ORD-' . $payment['id_pesanan']) ?></span></div>
        </header>

        <div class="payment-detail-grid">
            <section class="payment-card payment-order-card">
                <h2><span aria-hidden="true">&#128438;</span> Detail Pesanan &amp; Pelanggan</h2>
                <dl class="payment-customer-grid">
                    <div><dt>Nama Pelanggan</dt><dd><?= $escape($payment['nama_pelanggan']) ?></dd></div>
                    <div><dt>No. Telp / WhatsApp</dt><dd><?php if ($waPhone !== ''): ?><a href="https://wa.me/<?= $escape($waPhone) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($payment['telepon']) ?></a><?php else: ?>-<?php endif; ?></dd></div>
                    <div class="payment-customer-full"><dt>Alamat Pengiriman</dt><dd><?= nl2br($escape(trim((string) $payment['alamat_pengiriman'] . "\n" . (string) $payment['catatan_alamat']))) ?></dd></div>
                </dl>
                <div class="payment-section-label">Spesifikasi Pesanan</div>
                <?php foreach ($payment['items'] as $item): ?>
                    <?php
                    $extras = [];
                    if ((int) $item['tambah_sarban'] > 0) {
                        $extras[] = '+' . (int) $item['tambah_sarban'] . ' Sarung Bantal';
                    }
                    if ((int) $item['tambah_sargul'] > 0) {
                        $extras[] = '+' . (int) $item['tambah_sargul'] . ' Sarung Guling';
                    }
                    ?>
                    <article class="payment-item">
                        <strong><?= $escape($item['nama_produk']) ?> (<?= $item['tipe_ukuran'] === 'CUSTOM' ? 'Custom' : 'Standar' ?> <?= (int) $item['panjang'] ?> × <?= (int) $item['lebar'] ?> × <?= (int) $item['tinggi'] ?> cm)</strong>
                        <span><?= (int) $item['jumlah'] ?> Sprei<?= $extras ? ' · ' . $escape(implode(', ', $extras)) : '' ?></span>
                    </article>
                <?php endforeach; ?>
                <div class="payment-section-label">Catatan Pembeli</div>
                <div class="buyer-note">
                    <?php $notes = array_filter(array_map(static fn ($item): string => trim((string) $item['catatan_pesanan']), $payment['items'])); ?>
                    <?= $notes ? nl2br($escape(implode("\n", $notes))) : '-' ?>
                </div>
                <div class="payment-order-total"><span>Total Tagihan</span><strong><?= $escape('Rp ' . number_format($total, 0, ',', '.')) ?></strong></div>
            </section>

            <section class="payment-card payment-proof-card">
                <h2><span aria-hidden="true">&#128179;</span> Bukti Transfer</h2>
                <a class="proof-file" href="<?= $escape($filePath) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="proof-file-icon" aria-hidden="true"><?= $isImage ? '&#128444;' : '&#128196;' ?></span>
                    <span><strong><?= $escape($payment['bukti_nama_asli'] ?: basename($filePath)) ?></strong><small><?= $escape($fileSizeKb) ?> KB · Diunggah <?= $escape($uploadedLabel) ?></small></span>
                    <b><?= $escape($fileType) ?></b>
                </a>
                <?php if ($isImage): ?>
                    <a class="proof-preview" href="<?= $escape($filePath) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= $escape($filePath) ?>" alt="Pratinjau bukti transfer"></a>
                <?php else: ?>
                    <a class="proof-pdf-link" href="<?= $escape($filePath) ?>" target="_blank" rel="noopener noreferrer">Buka bukti transfer PDF</a>
                <?php endif; ?>
                <div class="proof-amount">
                    <span>NOMINAL BUKTI TRANSFER</span>
                    <strong><?= $escape('Rp ' . number_format($transfer, 0, ',', '.')) ?></strong>
                    <small><?= $escape($methodLabel) ?> ke <?= $escape($account) ?></small>
                    <?php if ($isMatching): ?>
                        <em class="proof-match">&#10003; Nominal Sesuai dengan Tagihan</em>
                    <?php else: ?>
                        <em class="proof-mismatch">! Nominal berbeda dari tagihan (selisih <?= $escape('Rp ' . number_format($difference, 0, ',', '.')) ?>)</em>
                    <?php endif; ?>
                </div>
                <dl class="sender-transfer-details">
                    <div><dt>Nama pemilik rekening pengirim</dt><dd><?= $escape($payment['nama_pemilik_pengirim'] ?: '-') ?></dd></div>
                    <div><dt>Nomor rekening pengirim</dt><dd><?= $escape($payment['nomor_rekening_pengirim'] ?: '-') ?></dd></div>
                </dl>
                <?php if ($isWaiting): ?>
                    <form class="payment-verification-form" action="/?action=admin-payment-verify-save" method="POST" id="paymentVerificationForm">
                        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id_pembayaran" value="<?= (int) $payment['id_pembayaran'] ?>">
                        <input type="hidden" name="decision" id="paymentDecision" value="">
                        <div class="payment-actions">
                            <button class="payment-reject-button" type="button" id="rejectPaymentButton">Tolak Bukti</button>
                            <button class="payment-verify-button" type="submit" data-decision="verify">Verifikasi</button>
                        </div>
                        <div class="reject-dialog" id="rejectDialog" hidden>
                            <label for="adminNote">Alasan penolakan</label>
                            <textarea id="adminNote" name="catatan_admin" rows="3" maxlength="1000"></textarea>
                            <div><button type="button" id="cancelRejectButton">Batal</button><button type="button" id="confirmRejectButton">Tolak Bukti</button></div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="payment-final-status">Status: <strong><?= $escape($statusLabels[$payment['status_pembayaran']] ?? $payment['status_pembayaran']) ?></strong></div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/cek_pembayaran.js"></script>
</body>
</html>
