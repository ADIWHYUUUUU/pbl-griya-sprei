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
    'DIPROSES' => ['Diproses', 'processing'],
    'DIKIRIM' => ['Dikirim', 'processing'],
    'SELESAI' => ['Selesai', 'complete'],
    'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
];
$paymentStatuses = [
    'MENUNGGU_VERIFIKASI' => 'Menunggu verifikasi',
    'TERVERIFIKASI' => 'Terverifikasi',
    'DITOLAK' => 'Ditolak',
];
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

            <header class="dashboard-header orders-header">
                <div>
                    <p class="eyebrow">PANEL ADMIN</p>
                    <h1>Cek Pesanan</h1>
                    <p class="dashboard-intro">Pantau pesanan terbaru dan status pembayarannya.</p>
                </div>
            </header>

            <section class="orders-panel" aria-label="Daftar pesanan">
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
                        <button type="submit">Cari</button>
                    </form>
                </div>

                <p class="orders-result-count" id="ordersResultCount" aria-live="polite">
                    Menampilkan <?= count($visibleOrders) ?> dari <?= count($orders) ?> pesanan terbaru
                </p>

                <div class="orders-table-scroll">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th scope="col">Invoice &amp; tanggal</th>
                                <th scope="col">Pelanggan</th>
                                <th scope="col">Produk</th>
                                <th scope="col">Tagihan</th>
                                <th scope="col">Status pesanan</th>
                                <th scope="col">Pembayaran</th>
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
                                ?>
                                <tr data-order-row data-category="<?= $escape($categoryFor($order)) ?>"<?= isset($visibleOrderIds[$order['id_pesanan']]) ? '' : ' hidden' ?>>
                                    <td>
                                        <strong class="order-invoice"><?= $escape($order['kode']) ?></strong>
                                        <small><?= $escape(date('d/m/Y H:i', strtotime($order['tanggal_pesan']))) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= $escape($order['nama_pelanggan']) ?></strong>
                                        <small><?= $escape($order['telepon']) ?></small>
                                    </td>
                                    <td class="order-products"><?= $escape($order['daftar_produk']) ?></td>
                                    <td class="order-total">Rp <?= number_format((float) $order['total'], 0, ',', '.') ?></td>
                                    <td><span class="order-status order-status--<?= $escape($status[1]) ?>"><?= $escape($status[0]) ?></span></td>
                                    <td><span class="order-payment order-payment--<?= $escape($paymentClass) ?>"><?= $escape($paymentStatus) ?></span></td>
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
