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
$total = (float) $payment['total_tagihan'];
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
<body class="admin-dashboard admin-payment-page admin-payment-verification-page">
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
                <h2>Detail Pesanan &amp; Pelanggan</h2>
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
                <h2>Bukti Transfer</h2>
                <a class="proof-file" href="<?= $escape($filePath) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="proof-file-icon" aria-hidden="true"><?= $isImage ? '&#128444;' : '&#128196;' ?></span>
                    <span><strong><?= $escape($payment['bukti_nama_asli'] ?: basename($filePath)) ?></strong><small><?= $escape($fileSizeKb) ?> KB · Diunggah <?= $escape($uploadedLabel) ?></small></span>
                    <b><?= $escape($fileType) ?></b>
                </a>
                <div class="proof-preview">
                    <a class="proof-preview-media" href="<?= $escape($filePath) ?>" target="_blank" rel="noopener noreferrer">
                        <?php if ($isImage): ?>
                            <img src="<?= $escape($filePath) ?>" alt="Pratinjau bukti transfer">
                        <?php else: ?>
                            <span><?= $escape($fileType) ?> · Buka bukti transfer</span>
                        <?php endif; ?>
                    </a>
                    <a class="proof-preview-open" href="<?= $escape($filePath) ?>" target="_blank" rel="noopener noreferrer">Buka ukuran penuh</a>
                </div>
                <dl class="sender-transfer-details">
                    <div><dt>Nama pemilik rekening pengirim</dt><dd><?= $escape($payment['nama_pemilik_pengirim'] ?: '-') ?></dd></div>
                    <div><dt>Nomor rekening pengirim</dt><dd><?= $escape($payment['nomor_rekening_pengirim'] ?: '-') ?></dd></div>
                </dl>
            </section>
        </div>
        <?php if ($isWaiting): ?>
            <form class="payment-verification-form" action="/?action=admin-payment-verify-save" method="POST" id="paymentVerificationForm">
                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_pembayaran" value="<?= (int) $payment['id_pembayaran'] ?>">
                <input type="hidden" name="decision" id="paymentDecision" value="">
                <div class="reject-dialog-backdrop" id="rejectDialog" hidden>
                    <section class="reject-dialog" role="dialog" aria-modal="true" aria-labelledby="rejectDialogTitle" aria-describedby="rejectDialogDescription" tabindex="-1">
                        <button class="reject-dialog-close" type="button" id="closeRejectButton" aria-label="Tutup dialog">&times;</button>
                        <h2 id="rejectDialogTitle">Tolak Bukti Pembayaran</h2>
                        <span class="reject-dialog-invoice"><?= $escape($payment['no_invoice'] ?: 'ORD-' . $payment['id_pesanan']) ?></span>
                        <p class="reject-dialog-description" id="rejectDialogDescription">Tulis alasan agar pelanggan tahu apa yang perlu diperbaiki.</p>
                        <div class="reject-reasons">
                            <strong>Pilih alasan cepat</strong>
                            <div>
                                <button type="button" class="reject-reason-chip" data-reason="Nominal tidak sesuai">Nominal tidak sesuai</button>
                                <button type="button" class="reject-reason-chip" data-reason="Bukti buram atau tidak terbaca">Bukti buram atau tidak terbaca</button>
                                <button type="button" class="reject-reason-chip" data-reason="Rekening tujuan salah">Rekening tujuan salah</button>
                                <button type="button" class="reject-reason-chip" data-reason="Tanggal transfer tidak sesuai">Tanggal transfer tidak sesuai</button>
                                <button type="button" class="reject-reason-chip" data-reason="Bukan bukti transfer">Bukan bukti transfer</button>
                            </div>
                        </div>
                        <label class="reject-note-label" for="adminNote">Alasan penolakan <span>(wajib diisi)</span></label>
                        <textarea id="adminNote" name="catatan_admin" form="paymentVerificationForm" rows="4" maxlength="300" aria-required="true" aria-describedby="adminNoteError adminNoteCounter" placeholder="Contoh: Nominal transfer kurang dari total tagihan."></textarea>
                        <div class="reject-note-meta">
                            <span id="adminNoteError" role="alert">Alasan penolakan wajib diisi.</span>
                            <span id="adminNoteCounter" aria-live="polite">0/300</span>
                        </div>
                        <div class="reject-dialog-actions">
                            <button type="button" id="cancelRejectButton">Batal</button>
                            <button type="button" id="confirmRejectButton" disabled>Tolak Bukti</button>
                        </div>
                    </section>
                </div>
                <div class="payment-actions">
                    <button class="payment-reject-button" type="button" id="rejectPaymentButton">Tolak Bukti</button>
                    <button class="payment-verify-button" type="submit" data-decision="verify">Verifikasi</button>
                </div>
            </form>
        <?php else: ?>
            <div class="payment-final-status">Status: <strong><?= $escape($statusLabels[$payment['status_pembayaran']] ?? $payment['status_pembayaran']) ?></strong></div>
        <?php endif; ?>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/cek_pembayaran.js"></script>
</body>
</html>
