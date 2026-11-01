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

$requestedPage = filter_var($_GET['hal'] ?? 1, FILTER_VALIDATE_INT);
$paymentPage = $requestedPage === false ? 1 : max(1, $requestedPage);
$paymentData = (new AdminPaymentController())->index($paymentPage);
$payments = $paymentData['rows'];
$currentPage = $paymentData['page'];
$totalPages = $paymentData['total_pages'];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$formatDate = static function ($value) use ($months): string {
    $timestamp = strtotime((string) $value);
    return $timestamp
        ? date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . date(' Y, H:i', $timestamp) . ' WIB'
        : (string) $value;
};
$formatRupiah = static fn ($value): string => 'Rp ' . number_format((float) $value, 2, ',', '.');
$methodLabels = [
    'TRANSFER_BCA' => 'Transfer BCA',
    'TRANSFER_BRI' => 'Transfer BRI',
    'TRANSFER_BSI' => 'Transfer BSI',
    'TRANSFER_MANDIRI' => 'Transfer Mandiri',
];
$statusLabels = [
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'waiting'],
    'TERVERIFIKASI' => ['Terverifikasi', 'verified'],
    'DITOLAK' => ['Ditolak', 'rejected'],
];
$message = (string) ($_SESSION['admin_payment_message'] ?? '');
$messageType = ($_SESSION['admin_payment_message_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['admin_payment_message'], $_SESSION['admin_payment_message_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Pembayaran - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/cekpesanan.css">
    <link rel="stylesheet" href="/assets/css/admin/pembayaran.css">
</head>
<body class="admin-dashboard admin-payment-page">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="dashboard-main payment-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="payment-breadcrumb"><span>Admin</span><span aria-hidden="true">›</span><strong>Cek Pembayaran</strong></div>
        <header class="dashboard-header payment-header"><h1>Cek Pembayaran</h1></header>

        <?php if ($message !== ''): ?>
            <div class="payment-alert payment-alert--<?= $escape($messageType) ?>" role="alert"><?= $escape($message) ?></div>
        <?php endif; ?>

        <section class="payment-table-panel">
            <div class="payment-table-scroll">
                <table class="payment-table">
                    <thead>
                    <tr>
                        <th>No. Invoice &amp; Tanggal</th>
                        <th>Nama &amp;<br>WhatsApp</th>
                        <th>Alamat<br>Pengiriman</th>
                        <th>Metode<br>Bayar</th>
                        <th>Status</th>
                        <th>Total<br>Tagihan</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$payments): ?>
                        <tr><td class="payment-empty" colspan="7">Belum ada data pembayaran.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <?php
                        $status = $statusLabels[$payment['status_pembayaran']] ?? [$payment['status_pembayaran'], 'other'];
                        $phone = preg_replace('/\D+/', '', (string) ($payment['telepon'] ?? ''));
                        $waPhone = $phone !== '' && str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
                        ?>
                        <tr>
                            <td>
                                <strong class="payment-invoice">&#128438; <?= $escape($payment['no_invoice']) ?></strong>
                                <small><?= $escape($formatDate($payment['tanggal_upload'])) ?></small>
                            </td>
                            <td>
                                <strong><?= $escape($payment['nama_pelanggan']) ?></strong>
                                <?php if ($waPhone !== ''): ?>
                                    <a class="payment-phone" href="https://wa.me/<?= $escape($waPhone) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($payment['telepon']) ?></a>
                                <?php else: ?><small>-</small><?php endif; ?>
                            </td>
                            <td><span class="payment-address"><?= nl2br($escape($payment['alamat_pengiriman'] ?: '-')) ?></span></td>
                            <td><span class="payment-method"><?= $escape($methodLabels[$payment['metode_pembayaran']] ?? $payment['metode_pembayaran']) ?></span></td>
                            <td><span class="payment-status payment-status--<?= $escape($status[1]) ?>"><?= $escape($status[0]) ?></span></td>
                            <td class="payment-total"><?= $escape($formatRupiah($payment['total_tagihan'])) ?></td>
                            <td>
                                <a class="payment-action" href="/?page=admin-payment-verify&amp;id=<?= (int) $payment['id_pembayaran'] ?>">
                                    <?= $payment['status_pembayaran'] === 'MENUNGGU_VERIFIKASI' ? 'Verifikasi Pembayaran' : 'Lihat' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <nav class="payment-pagination" aria-label="Pagination pembayaran">
            <?php if ($currentPage > 1): ?><a href="/?page=admin-payments&amp;hal=<?= $currentPage - 1 ?>" aria-label="Halaman sebelumnya">&lsaquo;</a><?php else: ?><span aria-hidden="true">&lsaquo;</span><?php endif; ?>
            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <?php if ($page === $currentPage): ?><strong aria-current="page"><?= $page ?></strong><?php else: ?><a href="/?page=admin-payments&amp;hal=<?= $page ?>"><?= $page ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?><a href="/?page=admin-payments&amp;hal=<?= $currentPage + 1 ?>" aria-label="Halaman berikutnya">&rsaquo;</a><?php else: ?><span aria-hidden="true">&rsaquo;</span><?php endif; ?>
        </nav>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
</body>
</html>
