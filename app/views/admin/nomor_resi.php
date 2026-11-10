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
    'INSTANT_GOJEK' => 'GoJek Instant',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$formatDate = static function ($timestamp): string {
    if ($timestamp === null || $timestamp === '') {
        return '-';
    }

    $date = new DateTimeImmutable((string) $timestamp);
    return $date->format('d M Y');
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
    <link rel="stylesheet" href="/assets/css/admin/nomor_resi.css">
</head>
<body class="resi-page">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="page-breadcrumb">Admin <span aria-hidden="true">›</span> <strong>Nomor Resi Pengiriman</strong></div>
            <h1 class="page-title">Daftar Nomor Resi Pengiriman</h1>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="flash-message <?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

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
                            $tracking = trim((string) ($order['nomor_resi'] ?? ''));
                            $orderId = (int) ($order['id_pesanan'] ?? 0);
                            $courierKey = (string) ($order['metode_pengiriman'] ?? '');
                            $courierLabel = $couriers[$courierKey] ?? $courierKey ?: 'Ekspedisi';
                            $specLines = $normalizeSpecLines($order['daftar_spesifikasi'] ?? '');
                            $specDisplay = implode('<br>', array_map(static fn ($line) => htmlspecialchars((string) $line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), array_slice($specLines, 0, 2)));
                            $detailData = [
                                'invoice' => (string) ($order['kode'] ?? 'INV/000000'),
                                'resi' => $tracking,
                                'courier' => $courierLabel,
                                'courierKey' => $courierKey,
                                'customer' => (string) ($order['nama_pelanggan'] ?? 'Pelanggan'),
                                'phone' => (string) ($order['telepon'] ?? '-'),
                                'address' => (string) ($order['alamat_pengiriman'] ?? 'Alamat belum tersedia'),
                                'product' => implode("\n", $specLines),
                                'note' => trim((string) ($order['catatan_pengiriman'] ?? '')),
                                'orderId' => $orderId,
                            ];
                        ?>
                            <tr>
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
                                    <span class="courier-badge"><?= $escape($courierLabel) ?></span>
                                </td>
                                <td>
                                    <?php if ($tracking !== ''): ?>
                                        <button type="button" class="action-btn secondary resi-detail-trigger" data-detail='<?= json_encode($detailData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'>Lihat Resi</button>
                                        <?php if (in_array($order['status_pesanan'] ?? '', ['DIPROSES', 'DIKIRIM'], true)): ?>
                                            <a href="/?page=admin-resi-input&amp;id=<?= $orderId ?>" class="action-btn">Ubah Resi</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="/?page=admin-resi-input&id=<?= (int) $orderId ?>" class="action-btn">Input Resi</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <div class="order-footer">
                <div class="footer-note">Menampilkan <?= count($orders) ?> pesanan yang siap dikirim atau telah dikirim.</div>
            </div>
        </main>
    </div>

    <div class="resi-modal" id="resiModal" aria-hidden="true">
        <div class="resi-modal-backdrop" data-close="resi-modal"></div>
        <div class="resi-modal-panel" role="dialog" aria-modal="true" aria-labelledby="resiModalTitle">
            <div class="resi-modal-header">
                <h2 id="resiModalTitle">Detail Nomor Resi &amp; Pengiriman</h2>
                <button type="button" class="resi-close-btn" aria-label="Tutup detail resi" data-close="resi-modal">×</button>
            </div>

            <div class="resi-modal-body">
                <div class="resi-modal-subtitle" id="resiModalSubtitle">Detail nomor resi dan pengiriman</div>

                <div class="resi-box">
                    <div class="resi-box-main">
                        <div class="resi-box-label">NOMOR RESI PENGIRIMAN</div>
                        <div class="resi-box-number" id="resiNumberValue">-</div>
                    </div>
                    <button type="button" class="resi-copy-btn" id="copyResiBtn">Salin Resi</button>
                </div>

                <div class="resi-info-row">
                    <div class="resi-info-label">KURIR &amp; LAYANAN</div>
                    <div class="resi-info-value" id="resiCourierValue">J&amp;T Express</div>
                    <div class="resi-info-subvalue" id="resiCourierService">Layanan pengiriman</div>
                </div>

                <div class="resi-two-col">
                    <div class="resi-card">
                        <div class="resi-card-title">PENERIMA</div>
                        <div class="resi-card-name" id="resiCustomerName">-</div>
                        <div class="resi-card-phone" id="resiCustomerPhone">-</div>
                        <div class="resi-card-address" id="resiCustomerAddress">-</div>
                    </div>

                    <div class="resi-card">
                        <div class="resi-card-title">RINCIAN BARANG</div>
                        <div class="resi-card-product">
                            <div class="resi-product-copy">
                                <div class="resi-product-name" id="resiProductName">-</div>
                                <div class="resi-product-meta" id="resiProductMeta">-</div>
                            </div>
                        </div>
                        <div class="resi-product-note" id="resiProductNote">Tidak ada catatan pengiriman.</div>
                    </div>
                </div>

                <div class="resi-modal-footer">
                    <button type="button" class="resi-secondary-btn" data-close="resi-modal">Tutup</button>
                    <button type="button" class="resi-primary-btn fill" id="printLabelBtn">Cetak Label Pengiriman</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/nomor_resi.js"></script>
</body>
</html>
