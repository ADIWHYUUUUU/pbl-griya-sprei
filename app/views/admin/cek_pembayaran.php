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
$totalRows = $paymentData['total_rows'];
$statusCounts = $paymentData['status_counts'];
$oldestWaiting = $paymentData['oldest_waiting'];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$formatDate = static function ($value) use ($months): string {
    $timestamp = strtotime((string) $value);
    return $timestamp
        ? date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . date(' Y, H:i', $timestamp) . ' WIB'
        : (string) $value;
};
$methodLabels = [
    'TRANSFER_BCA' => 'Transfer BCA',
    'TRANSFER_BRI' => 'Transfer BRI',
    'TRANSFER_BSI' => 'Transfer BSI',
    'TRANSFER_MANDIRI' => 'Transfer Mandiri',
];
$shortMethodLabels = [
    'TRANSFER_BCA' => 'BCA',
    'TRANSFER_BRI' => 'BRI',
    'TRANSFER_BSI' => 'BSI',
    'TRANSFER_MANDIRI' => 'Mandiri',
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
    <link rel="stylesheet" href="/assets/css/admin/cek_pembayaran.css">
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

        <?php if ($oldestWaiting !== null): ?>
            <section class="payment-queue-banner" aria-label="Ringkasan pembayaran yang perlu diverifikasi">
                <div>
                    <strong><?= (int) $statusCounts['MENUNGGU_VERIFIKASI'] ?> pembayaran menunggu verifikasi</strong>
                    <span>Yang terlama sejak <?= $escape($formatDate($oldestWaiting['tanggal_upload'])) ?>.</span>
                </div>
                <a href="/?page=admin-payment-verify&amp;id=<?= (int) $oldestWaiting['id_pembayaran'] ?>">Verifikasi yang terlama <span aria-hidden="true">→</span></a>
            </section>
        <?php endif; ?>

        <section class="payment-controls" aria-label="Filter pembayaran">
            <label class="payment-search">
                <span aria-hidden="true">&#128269;</span>
                <input type="search" id="paymentSearch" placeholder="Cari invoice, nama, atau nomor WhatsApp" aria-label="Cari invoice, nama, atau nomor WhatsApp">
            </label>
            <label class="payment-select-label">
                <span class="sr-only">Filter metode pembayaran</span>
                <select id="paymentMethodFilter" aria-label="Filter metode pembayaran">
                    <option value="">Semua metode</option>
                    <?php foreach ($methodLabels as $method => $label): ?>
                        <option value="<?= $escape($method) ?>"><?= $escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="payment-select-label">
                <span class="sr-only">Filter tanggal pembayaran</span>
                <select id="paymentDateFilter" aria-label="Filter tanggal pembayaran">
                    <option value="">Semua tanggal</option>
                    <option value="today">Hari ini</option>
                    <option value="7">7 hari terakhir</option>
                    <option value="30">30 hari terakhir</option>
                </select>
            </label>
            <label class="payment-select-label">
                <span class="sr-only">Urutkan pembayaran</span>
                <select id="paymentSort" aria-label="Urutkan pembayaran">
                    <option value="action">Urut: Perlu tindakan</option>
                    <option value="newest">Urut: Terbaru</option>
                    <option value="oldest">Urut: Terlama</option>
                    <option value="amount-high">Urut: Nominal tertinggi</option>
                    <option value="amount-low">Urut: Nominal terendah</option>
                </select>
            </label>
        </section>

        <section class="payment-table-panel" aria-label="Daftar pembayaran">
            <nav class="payment-tabs" aria-label="Filter status pembayaran">
                <button type="button" class="payment-tab" data-status-filter="MENUNGGU_VERIFIKASI" aria-pressed="false">
                    Perlu diverifikasi <span><?= (int) $statusCounts['MENUNGGU_VERIFIKASI'] ?></span>
                </button>
                <button type="button" class="payment-tab" data-status-filter="TERVERIFIKASI" aria-pressed="false">
                    Terverifikasi <span><?= (int) $statusCounts['TERVERIFIKASI'] ?></span>
                </button>
                <button type="button" class="payment-tab" data-status-filter="DITOLAK" aria-pressed="false">
                    Ditolak <span><?= (int) $statusCounts['DITOLAK'] ?></span>
                </button>
                <button type="button" class="payment-tab is-active" data-status-filter="" aria-pressed="true">
                    Semua <span><?= (int) $totalRows ?></span>
                </button>
            </nav>
            <div class="payment-table-scroll">
                <table class="payment-table">
                    <thead>
                    <tr>
                        <th>Invoice &amp; Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="paymentTableBody">
                    <?php if (!$payments): ?>
                        <tr><td class="payment-empty" colspan="6">Belum ada data pembayaran.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <?php
                        $status = $statusLabels[$payment['status_pembayaran']] ?? [$payment['status_pembayaran'], 'other'];
                        $phone = preg_replace('/\D+/', '', (string) ($payment['telepon'] ?? ''));
                        $waPhone = $phone !== '' && str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
                        $uploadedAt = strtotime((string) $payment['tanggal_upload']);
                        $method = (string) $payment['metode_pembayaran'];
                        ?>
                        <tr
                            data-payment-row
                            data-status="<?= $escape($payment['status_pembayaran']) ?>"
                            data-method="<?= $escape($method) ?>"
                            data-date="<?= $uploadedAt !== false ? (int) $uploadedAt : 0 ?>"
                            data-amount="<?= $escape((float) $payment['total_tagihan']) ?>"
                            data-search="<?= $escape(strtolower($payment['no_invoice'] . ' ' . $payment['nama_pelanggan'] . ' ' . $payment['telepon'])) ?>"
                        >
                            <td>
                                <strong class="payment-invoice"><?= $escape($payment['no_invoice']) ?></strong>
                                <small><?= $escape($formatDate($payment['tanggal_upload'])) ?></small>
                            </td>
                            <td>
                                <strong><?= $escape($payment['nama_pelanggan']) ?></strong>
                                <?php if ($waPhone !== ''): ?>
                                    <a class="payment-phone" href="https://wa.me/<?= $escape($waPhone) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($payment['telepon']) ?></a>
                                <?php else: ?><small>-</small><?php endif; ?>
                            </td>
                            <td><span class="payment-method-short"><?= $escape($shortMethodLabels[$method] ?? $method) ?></span></td>
                            <td><span class="payment-status payment-status--<?= $escape($status[1]) ?>"><?= $escape($status[0]) ?></span></td>
                            <td class="payment-total"><?= $escape('Rp ' . number_format((float) $payment['total_tagihan'], 0, ',', '.')) ?></td>
                            <td>
                                <a class="payment-action<?= $payment['status_pembayaran'] === 'MENUNGGU_VERIFIKASI' ? ' payment-action--pending' : ' payment-action--secondary' ?>" href="/?page=admin-payment-verify&amp;id=<?= (int) $payment['id_pembayaran'] ?>">
                                    <?= $payment['status_pembayaran'] === 'MENUNGGU_VERIFIKASI' ? 'Verifikasi' : 'Lihat' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="payment-empty-filter" id="paymentFilterEmpty" hidden>
                        <td class="payment-empty" colspan="6">Tidak ada pembayaran yang cocok dengan filter.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="payment-table-note" id="paymentResultCount" data-page-total="<?= count($payments) ?>">Menampilkan <?= count($payments) ?> dari <?= count($payments) ?> pembayaran di halaman ini.</p>

        <?php if ($totalPages > 1): ?>
        <nav class="payment-pagination" aria-label="Pagination pembayaran">
            <?php if ($currentPage > 1): ?><a href="/?page=admin-payments&amp;hal=<?= $currentPage - 1 ?>" aria-label="Halaman sebelumnya">&lsaquo;</a><?php else: ?><span aria-hidden="true">&lsaquo;</span><?php endif; ?>
            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <?php if ($page === $currentPage): ?><strong aria-current="page"><?= $page ?></strong><?php else: ?><a href="/?page=admin-payments&amp;hal=<?= $page ?>"><?= $page ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?><a href="/?page=admin-payments&amp;hal=<?= $currentPage + 1 ?>" aria-label="Halaman berikutnya">&rsaquo;</a><?php else: ?><span aria-hidden="true">&rsaquo;</span><?php endif; ?>
        </nav>
        <?php endif; ?>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/payment-list.js"></script>
</body>
</html>
