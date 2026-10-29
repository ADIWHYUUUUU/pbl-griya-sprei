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

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DashboardModel.php';

$orders = (new DashboardModel($pdo))->getRecentOrders(100);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$orderStatuses = [
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'waiting'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'waiting'],
    'DIPROSES' => ['Diproses (Sedang Dijahit)', 'processing'],
    'DIKIRIM' => ['Dikirim', 'processing'],
    'SELESAI' => ['Selesai', 'complete'],
    'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
];
$paymentStatuses = [
    'MENUNGGU_VERIFIKASI' => 'Menunggu verifikasi',
    'TERVERIFIKASI' => 'Terverifikasi',
    'DITOLAK' => 'Ditolak',
];
$bulanPendek = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$categoryFor = static function (array $order): string {
    if (
        ($order['status_pembayaran'] ?? '') === 'MENUNGGU_VERIFIKASI' ||
        ($order['status_pesanan'] ?? '') === 'MENUNGGU_VERIFIKASI'
    ) {
        return 'verifikasi';
    }

    if (in_array($order['status_pesanan'] ?? '', ['MENUNGGU_PEMBAYARAN', 'DIPROSES', 'DIKIRIM'], true)) {
        return 'proses';
    }

    if (($order['status_pesanan'] ?? '') === 'SELESAI') {
        return 'selesai';
    }

    return 'lainnya';
};
$categoryCounts = ['semua' => count($orders), 'verifikasi' => 0, 'proses' => 0, 'selesai' => 0];
foreach ($orders as $order) {
    $category = $categoryFor($order);
    if (isset($categoryCounts[$category])) {
        $categoryCounts[$category]++;
    }
}

$requestedFilter = $_GET['status'] ?? 'semua';
$activeFilter = is_string($requestedFilter) && in_array($requestedFilter, ['semua', 'verifikasi', 'proses', 'selesai'], true)
    ? $requestedFilter
    : 'semua';
$requestedSearch = $_GET['cari'] ?? '';
$searchTerm = is_string($requestedSearch) ? trim($requestedSearch) : '';
$visibleOrders = array_values(array_filter($orders, static function (array $order) use ($activeFilter, $categoryFor, $searchTerm): bool {
    if ($activeFilter !== 'semua' && $categoryFor($order) !== $activeFilter) {
        return false;
    }

    if ($searchTerm === '') {
        return true;
    }

    $searchable = implode(' ', [
        $order['kode'] ?? '',
        $order['nama_pelanggan'] ?? '',
        $order['telepon'] ?? '',
        $order['daftar_produk'] ?? '',
    ]);

    return stripos($searchable, $searchTerm) !== false;
}));
$visibleOrderIds = array_fill_keys(array_column($visibleOrders, 'id_pesanan'), true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Pesanan - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/cekpesanan.css">
</head>
<body class="admin-dashboard admin-orders">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="dashboard-main orders-main">
            <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

            <div class="orders-breadcrumb"><span>Admin</span><span aria-hidden="true">›</span><strong>Cek Pesanan</strong></div>

            <header class="dashboard-header orders-header">
                <div>
                    <h1>Cek Pesanan</h1>
                </div>
            </header>

            <section class="orders-content" aria-label="Daftar pesanan">
                <div class="orders-toolbar">
                    <nav class="order-tabs" aria-label="Filter pesanan">
                        <?php
                        $filters = [
                            'semua' => 'Semua',
                            'verifikasi' => 'Menunggu Verifikasi',
                            'proses' => 'Diproses',
                            'selesai' => 'Selesai',
                        ];
                        foreach ($filters as $filter => $label):
                            $isActive = $activeFilter === $filter;
                        ?>
                            <a
                                class="order-tab<?= $isActive ? ' is-active' : '' ?>"
                                href="/?page=admin-orders&amp;status=<?= rawurlencode($filter) ?>"
                                data-order-filter="<?= $escape($filter) ?>"
                                <?= $isActive ? 'aria-current="page"' : '' ?>
                            >
                                <?= $escape($label) ?>
                                <span><?= (int) $categoryCounts[$filter] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <form class="order-search" action="/" method="get" role="search">
                        <input type="hidden" name="page" value="admin-orders">
                        <input type="hidden" name="status" value="<?= $escape($activeFilter) ?>">
                        <label class="sr-only" for="orderSearch">Cari invoice, pelanggan, atau produk</label>
                        <input
                            id="orderSearch"
                            type="search"
                            name="cari"
                            value="<?= $escape($searchTerm) ?>"
                            placeholder="Cari invoice / nama..."
                        >
                        <button type="submit" aria-label="Cari">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg>
                        </button>
                    </form>
                </div>

                <div class="orders-panel orders-table-scroll">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th scope="col">No. Invoice &amp; Tanggal</th>
                                <th scope="col">Pelanggan</th>
                                <th scope="col">Spesifikasi Sprei</th>
                                <th scope="col">Tagihan</th>
                                <th scope="col">Status pesanan</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $status = $orderStatuses[$order['status_pesanan']] ?? [$order['status_pesanan'], 'other'];
                                $paymentStatus = $paymentStatuses[$order['status_pembayaran'] ?? ''] ?? 'Belum ada pembayaran';
                                $paymentClass = match ($order['status_pembayaran'] ?? '') {
                                    'MENUNGGU_VERIFIKASI' => 'waiting',
                                    'TERVERIFIKASI' => 'complete',
                                    'DITOLAK' => 'cancelled',
                                    default => 'other',
                                };
                                $tanggalPesanan = strtotime((string) $order['tanggal_pesan']);
                                $tanggalLabel = $tanggalPesanan
                                    ? date('d', $tanggalPesanan) . ' ' . $bulanPendek[(int) date('n', $tanggalPesanan)] . date(' Y, H:i', $tanggalPesanan) . ' WIB'
                                    : (string) $order['tanggal_pesan'];
                                $aksiLabel = ($order['status_pesanan'] ?? '') === 'MENUNGGU_VERIFIKASI' ? 'Verifikasi' : 'Detail';
                                ?>
                                <tr data-order-row data-category="<?= $escape($categoryFor($order)) ?>"<?= isset($visibleOrderIds[$order['id_pesanan']]) ? '' : ' hidden' ?>>
                                    <td>
                                        <strong class="order-invoice"><?= $escape($order['kode']) ?></strong>
                                        <small><?= $escape($tanggalLabel) ?></small>
                                        <span class="order-size-label"><?= $escape(strtolower((string) ($order['daftar_spesifikasi'] ?? '')) !== '' && str_contains((string) $order['daftar_spesifikasi'], 'Custom:') ? 'Custom' : 'Standar') ?></span>
                                    </td>
                                    <td>
                                        <strong><?= $escape($order['nama_pelanggan']) ?></strong>
                                        <small class="order-phone">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h3l2 5-2 1.5a15 15 0 0 0 4.5 4.5L16 12l5 2v3c0 1.1-.9 2-2 2C10 19 5 14 5 5c0-1.1.9-2 2-2Z"></path></svg>
                                            <?= $escape($order['telepon']) ?>
                                        </small>
                                    </td>
                                    <td class="order-products"><?= $escape($order['daftar_spesifikasi'] ?? $order['daftar_produk']) ?></td>
                                    <td class="order-total">
                                        <strong>Rp <?= number_format((float) $order['total'], 0, ',', '.') ?></strong>
                                        <?php if ((float) ($order['ongkir'] ?? 0) <= 0): ?><small>(+ Ongkir manual)</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="order-status order-status--<?= $escape($status[1]) ?>">
                                            <?= $escape($status[0]) ?>
                                            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4"></path></svg>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="order-action<?= $aksiLabel === 'Verifikasi' ? ' order-action--verify' : '' ?>" type="button" data-toggle-details aria-expanded="false" aria-controls="order-detail-<?= (int) $order['id_pesanan'] ?>">
                                            <?= $escape($aksiLabel) ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="order-details-row" id="order-detail-<?= (int) $order['id_pesanan'] ?>" data-order-details hidden>
                                    <td colspan="6">
                                        <div class="order-details">
                                            <div><small>Alamat Pengiriman</small><p><?= $escape($order['alamat_pengiriman'] ?: '-') ?></p></div>
                                            <div><small>Metode Pengiriman</small><p><?= $escape(str_replace('_', ' ', (string) $order['metode_pengiriman'])) ?></p></div>
                                            <div><small>Status Pembayaran</small><p><span class="order-payment order-payment--<?= $escape($paymentClass) ?>"><?= $escape($paymentStatus) ?></span></p></div>
                                            <?php if (!empty($order['catatan_pengiriman'])): ?><div><small>Catatan Pengiriman</small><p><?= $escape($order['catatan_pengiriman']) ?></p></div><?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="orders-empty" id="ordersEmpty"<?= $visibleOrders ? ' hidden' : '' ?>>
                                <td colspan="6">
                                    <?= $orders ? 'Tidak ada pesanan yang cocok dengan filter atau pencarian.' : 'Belum ada pesanan.' ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/cekpesanan.js"></script>
</body>
</html>
