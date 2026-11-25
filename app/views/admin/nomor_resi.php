<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: /?page=admin-login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DashboardModel.php';

$orders = (new DashboardModel($pdo))->getOrdersForShipping(100);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$couriers = [
    'INSTANT_GOJEK' => 'Gojek Instant',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$hasTracking = static function (array $order): bool {
    $tracking = ($order['metode_pengiriman'] ?? '') === 'INSTANT_GOJEK'
        ? ($order['nomor_telepon_kurir'] ?? '')
        : ($order['nomor_resi'] ?? '');

    return trim((string) $tracking) !== '';
};
$filledTrackingCount = count(array_filter($orders, $hasTracking));
$formatDate = static function ($timestamp): string {
    if ($timestamp === null || $timestamp === '') {
        return '-';
    }

    $date = new DateTimeImmutable((string) $timestamp);
    return $date->format('d M Y');
};
$formatLongDate = static function ($timestamp): string {
    if ($timestamp === null || $timestamp === '') {
        return '-';
    }

    return (new DateTimeImmutable((string) $timestamp))->format('d F Y');
};
$normalizeSpecLines = static function ($value, $fallback = 'Detail pesanan'): array {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '' || strtolower($raw) === 'spesifikasi tidak tersedia' || strtolower($raw) === 'produk tidak tersedia') {
        return [$fallback];
    }

    $lines = preg_split('/\R+/', $raw) ?: [$raw];
    $cleaned = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '' && strtolower($line) !== 'spesifikasi tidak tersedia' && strtolower($line) !== 'produk tidak tersedia') {
            $cleaned[] = $line;
        }
    }

    if ($cleaned === []) {
        return [$fallback];
    }

    $unique = [];
    foreach ($cleaned as $line) {
        if (!in_array($line, $unique, true)) {
            $unique[] = $line;
        }
    }

    return $unique;
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nomor Resi - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/nomor_resi.css?v=<?= (int) filemtime(__DIR__ . '/../../../public/assets/css/admin/nomor_resi.css') ?>">
</head>
<body class="resi-page">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="page-breadcrumb">Admin <span aria-hidden="true">›</span> <strong>Nomor Resi Pengiriman</strong></div>
            <h1 class="page-title">Daftar Nomor Resi Pengiriman</h1>
            <p class="page-description">Isi nomor resi atau kontak kurir untuk pesanan yang siap dikirim.</p>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="flash-message <?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

            <div class="order-toolbar">
                <label class="order-search">
                    <span class="visually-hidden">Cari invoice atau pelanggan</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg>
                    <input type="search" id="orderSearch" placeholder="Cari invoice atau nama pelanggan">
                </label>
                <div class="tracking-filters" role="group" aria-label="Filter status nomor resi">
                    <button type="button" class="tracking-filter active" data-tracking-filter="all" aria-pressed="true">Semua <span><?= count($orders) ?></span></button>
                    <button type="button" class="tracking-filter" data-tracking-filter="empty" aria-pressed="false">Belum diisi <span><?= count($orders) - $filledTrackingCount ?></span></button>
                    <button type="button" class="tracking-filter" data-tracking-filter="filled" aria-pressed="false">Sudah diisi <span><?= $filledTrackingCount ?></span></button>
                </div>
            </div>

            <section class="order-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. Invoice &amp; Tgl</th>
                            <th>Pelanggan &amp; Tujuan</th>
                            <th>Item &amp; Sprei</th>
                            <th>Pengiriman</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders === []): ?>
                            <tr>
                                <td colspan="5" class="empty-state">Belum ada pesanan yang siap dikirim atau memiliki nomor resi.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $order):
                            $courierKey = (string) ($order['metode_pengiriman'] ?? '');
                            $trackingIsFilled = $hasTracking($order);
                            $tracking = trim((string) ($courierKey === 'INSTANT_GOJEK'
                                ? ($order['nomor_telepon_kurir'] ?? '')
                                : ($order['nomor_resi'] ?? '')));
                            $orderId = (int) ($order['id_pesanan'] ?? 0);
                            $courierLabel = $couriers[$courierKey] ?? $courierKey ?: 'Ekspedisi';
                            $specLines = $normalizeSpecLines($order['daftar_spesifikasi'] ?? '');
                            $specDisplay = implode('<br>', array_map(static fn ($line) => htmlspecialchars((string) $line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), array_slice($specLines, 0, 2)));
                            $searchText = implode(' ', [
                                (string) ($order['kode'] ?? ''),
                                (string) ($order['nama_pelanggan'] ?? ''),
                                (string) ($order['telepon'] ?? ''),
                                (string) ($order['alamat_pengiriman'] ?? ''),
                                implode(' ', $specLines),
                            ]);
                            $detailData = [
                                'invoice' => (string) ($order['kode'] ?? 'INV/000000'),
                                'resi' => $tracking,
                                'resiLabel' => $courierKey === 'INSTANT_GOJEK'
                                    ? 'NOMOR TELEPON KURIR'
                                    : 'NOMOR RESI PENGIRIMAN',
                                'courier' => $courierLabel,
                                'courierKey' => $courierKey,
                                'trackingStatus' => $trackingIsFilled ? 'Sudah diisi' : 'Belum diisi',
                                'customer' => (string) ($order['nama_pelanggan'] ?? 'Pelanggan'),
                                'phone' => (string) ($order['telepon'] ?? '-'),
                                'address' => (string) ($order['alamat_pengiriman'] ?? 'Alamat belum tersedia'),
                                'product' => implode("\n", $specLines),
                                'date' => $formatLongDate($order['tanggal_pesan'] ?? null),
                                'note' => trim((string) ($order['catatan_pengiriman'] ?? '')),
                                'orderId' => $orderId,
                            ];
                        ?>
                            <tr data-tracking-status="<?= $trackingIsFilled ? 'filled' : 'empty' ?>" data-search="<?= $escape($searchText) ?>">
                                <td>
                                    <span class="invoice"><?= $escape($order['kode'] ?? 'INV/000000') ?></span>
                                    <span class="date"><?= $escape($formatDate($order['tanggal_pesan'] ?? null)) ?></span>
                                </td>
                                <td>
                                    <span class="customer-name"><?= $escape($order['nama_pelanggan'] ?? 'Pelanggan') ?></span>
                                    <span class="muted"><?= $escape($order['telepon'] ?? '-') ?></span>
                                    <div class="shipping-name"><?= $escape($order['alamat_pengiriman'] ?? 'Alamat belum tersedia') ?></div>
                                </td>
                                <td>
                                    <div class="spec"><?= $specDisplay ?></div>
                                </td>
                                <td>
                                    <div class="shipping-status">
                                        <span class="courier-badge"><?= $escape($courierLabel) ?></span>
                                        <span class="tracking-status <?= $trackingIsFilled ? 'filled' : 'empty' ?>"><?= $trackingIsFilled ? 'Sudah diisi' : 'Belum diisi' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($tracking !== ''): ?>
                                        <div class="resi-action-group">
                                            <button type="button" class="action-btn secondary resi-detail-trigger" data-detail='<?= json_encode($detailData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'><?= $courierKey === 'INSTANT_GOJEK' ? 'Lihat Telepon' : 'Lihat Resi' ?></button>
                                        </div>
                                    <?php else: ?>
                                        <div class="resi-action-group">
                                            <a href="/?page=admin-resi-input&id=<?= (int) $orderId ?>" class="action-btn"><?= $courierKey === 'INSTANT_GOJEK' ? 'Input Telepon' : 'Input Resi' ?></a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($orders !== []): ?>
                            <tr class="filter-empty-state" hidden>
                                <td colspan="5" class="empty-state">Tidak ada pesanan yang sesuai dengan pencarian atau filter ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="order-footer">
                    <div class="footer-note" id="orderCount" data-total="<?= count($orders) ?>">Menampilkan <?= count($orders) ?> pesanan yang siap dikirim atau telah dikirim.</div>
                    <nav class="pager" id="orderPager" aria-label="Halaman daftar resi"></nav>
                </div>
            </section>
        </main>
    </div>

    <div class="resi-modal" id="resiModal" aria-hidden="true">
        <div class="resi-modal-backdrop" data-close="resi-modal"></div>
        <div class="resi-modal-panel" role="dialog" aria-modal="true" aria-labelledby="resiModalTitle">
            <div class="resi-modal-header">
                <div class="resi-modal-heading">
                    <span class="resi-modal-icon" id="resiModalIcon" aria-hidden="true">
                        <svg class="package-icon" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="16" rx="2"></rect><path d="M9 8h6M9 12h6M9 16h3"></path></svg>
                        <svg class="phone-icon" viewBox="0 0 24 24"><path d="M6 3h4l2 5-3 2c1.4 2.8 3.2 4.6 6 6l2-3 5 2v4c0 1.1-.9 2-2 2C10 21 3 14 3 5c0-1.1.9-2 2-2h1Z"></path></svg>
                    </span>
                    <div>
                        <h2 id="resiModalTitle">Nomor Resi Pengiriman</h2>
                        <p id="resiModalSubtitle">INV/000000</p>
                    </div>
                </div>
                <button type="button" class="resi-close-btn" aria-label="Tutup detail resi" data-close="resi-modal">×</button>
            </div>

            <div class="resi-modal-body">
                <div class="resi-box">
                    <div class="resi-box-main">
                        <div class="resi-box-label" id="resiLabelValue">NOMOR RESI</div>
                        <div class="resi-box-number" id="resiNumberValue">-</div>
                    </div>
                    <button type="button" class="resi-copy-btn" id="copyResiBtn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="12" rx="2"></rect><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2"></path></svg>
                        <span>Salin</span>
                    </button>
                </div>

                <div class="resi-detail-grid">
                    <div class="resi-detail-item">
                        <h3>Pengiriman</h3>
                        <span class="courier-badge" id="resiCourierValue">Ekspedisi</span>
                    </div>
                    <div class="resi-detail-item">
                        <h3>Status</h3>
                        <span class="tracking-status filled" id="resiTrackingStatus">Sudah diisi</span>
                    </div>
                    <div class="resi-detail-item">
                        <h3>Produk</h3>
                        <strong id="resiProductName">-</strong>
                        <span id="resiProductMeta">-</span>
                    </div>
                    <div class="resi-detail-item">
                        <h3>Tanggal Pesanan</h3>
                        <span id="resiOrderDate">-</span>
                    </div>
                    <div class="resi-detail-item">
                        <h3>Pelanggan</h3>
                        <strong id="resiCustomerName">-</strong>
                        <span id="resiCustomerPhone">-</span>
                        <span id="resiCustomerAddress">-</span>
                    </div>
                    <div class="resi-detail-item">
                        <h3>Catatan Pengiriman</h3>
                        <span id="resiProductNote">Tidak ada catatan</span>
                    </div>
                </div>

                <div class="resi-modal-footer">
                    <button type="button" class="resi-secondary-btn" data-close="resi-modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/nomor_resi.js?v=<?= (int) filemtime(__DIR__ . '/../../../public/assets/js/admin/nomor_resi.js') ?>"></script>
</body>
</html>
