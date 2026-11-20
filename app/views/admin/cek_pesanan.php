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

$dashboard = new DashboardModel($pdo);
$orders = $dashboard->getRecentOrders(100);
$statusCounts = $dashboard->getOrderStatusCounts();
$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$orderStatuses = [
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'verification'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'payment'],
    'DIPROSES' => ['Diproses (Sedang Dijahit)', 'processing'],
    'DIKIRIM' => ['Dikirim', 'shipped'],
    'SELESAI' => ['Selesai', 'complete'],
    'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
];
$paymentStatuses = [
    'MENUNGGU_VERIFIKASI' => 'Menunggu verifikasi',
    'TERVERIFIKASI' => 'Terverifikasi',
    'DITOLAK' => 'Ditolak',
];
$transitions = [
    'MENUNGGU_VERIFIKASI' => ['MENUNGGU_PEMBAYARAN', 'DIBATALKAN'],
    'MENUNGGU_PEMBAYARAN' => ['DIPROSES', 'DIBATALKAN'],
    'DIPROSES' => ['DIKIRIM', 'DIBATALKAN'],
    'DIKIRIM' => ['SELESAI'],
    'SELESAI' => [],
    'DIBATALKAN' => [],
];
$directTransitions = [
    'MENUNGGU_VERIFIKASI' => ['DIBATALKAN'],
    'MENUNGGU_PEMBAYARAN' => ['DIBATALKAN'],
    'DIPROSES' => ['DIBATALKAN'],
    'DIKIRIM' => ['SELESAI'],
    'SELESAI' => [],
    'DIBATALKAN' => [],
];
$filters = [
    'semua' => 'Semua',
    'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
    'MENUNGGU_PEMBAYARAN' => 'Menunggu Pembayaran',
    'DIPROSES' => 'Diproses',
    'DIKIRIM' => 'Dikirim',
    'SELESAI' => 'Selesai',
    'DIBATALKAN' => 'Dibatalkan',
];
$requestedFilter = $_GET['status'] ?? 'semua';
$activeFilter = is_string($requestedFilter) && array_key_exists($requestedFilter, $filters)
    ? $requestedFilter
    : 'semua';
$requestedSearch = $_GET['cari'] ?? '';
$searchTerm = is_string($requestedSearch) ? trim($requestedSearch) : '';
$visibleOrders = array_values(array_filter($orders, static function (array $order) use ($activeFilter, $searchTerm): bool {
    if ($activeFilter !== 'semua' && ($order['status_pesanan'] ?? '') !== $activeFilter) {
        return false;
    }

    return $searchTerm === '' || stripos(
        (string) ($order['kode'] ?? '') . ' ' . (string) ($order['nama_pelanggan'] ?? ''),
        $searchTerm
    ) !== false;
}));
$visibleOrderIds = array_fill_keys(array_column($visibleOrders, 'id_pesanan'), true);
$statusCounts['semua'] = array_sum($statusCounts);
$bulanPendek = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
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
<body class="admin-dashboard admin-orders admin-orders-list">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="dashboard-main orders-main">
            <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

            <nav class="orders-breadcrumb" aria-label="Breadcrumb">
                <a href="/?page=admin-dashboard">Admin</a>
                <span aria-hidden="true">›</span>
                <strong aria-current="page">Cek Pesanan</strong>
            </nav>

            <header class="dashboard-header orders-header">
                <div>
                    <h1>Cek Pesanan</h1>
                    <p>Kelola dan pantau status pesanan pelanggan.</p>
                </div>
            </header>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="verification-flash verification-flash--<?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

            <section class="orders-content" aria-label="Daftar pesanan">
                <div class="orders-toolbar">
                    <nav class="order-tabs" aria-label="Filter status pesanan">
                        <?php foreach ($filters as $filter => $label): ?>
                            <?php
                            $isActive = $activeFilter === $filter;
                            $count = $filter === 'semua' ? $statusCounts['semua'] : ($statusCounts[$filter] ?? 0);
                            ?>
                            <a
                                class="order-tab<?= $isActive ? ' is-active' : '' ?>"
                                href="/?page=admin-orders&amp;status=<?= rawurlencode($filter) ?><?= $searchTerm !== '' ? '&amp;cari=' . rawurlencode($searchTerm) : '' ?>"
                                data-order-filter="<?= $escape($filter) ?>"
                                <?= $isActive ? 'aria-current="page"' : '' ?>
                            >
                                <?= $escape($label) ?>
                                <span><?= (int) $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <form class="order-search" action="/" method="get" role="search">
                        <input type="hidden" name="page" value="admin-orders">
                        <input type="hidden" name="status" value="<?= $escape($activeFilter) ?>">
                        <label class="sr-only" for="orderSearch">Cari nomor invoice atau nama pelanggan</label>
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
                                <th scope="col">Status Pesanan</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $statusCode = (string) ($order['status_pesanan'] ?? '');
                                $status = $orderStatuses[$statusCode] ?? [$statusCode, 'other'];
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
                                $specificationText = trim((string) ($order['daftar_spesifikasi'] ?? ''));
                                $specifications = $specificationText !== ''
                                    ? preg_split('/\n\s*\n/', $specificationText)
                                    : [(string) ($order['daftar_produk'] ?? 'Produk tidak tersedia')];
                                $specifications = array_values(array_filter($specifications, static fn ($value) => trim((string) $value) !== ''));
                                $shownSpecifications = array_slice($specifications, 0, 2);
                                $sizeType = stripos($specificationText, 'Custom:') !== false ? 'Custom' : 'Standar';
                                ?>
                                <tr
                                    data-order-row
                                    data-category="<?= $escape($statusCode) ?>"
                                    data-search="<?= $escape(strtolower((string) ($order['kode'] ?? '') . ' ' . (string) ($order['nama_pelanggan'] ?? ''))) ?>"
                                    <?= isset($visibleOrderIds[$order['id_pesanan']]) ? '' : 'hidden' ?>
                                >
                                    <td>
                                        <strong class="order-invoice"><?= $escape($order['kode']) ?></strong>
                                        <small><?= $escape($tanggalLabel) ?></small>
                                        <span class="order-size-label order-size-label--<?= strtolower($sizeType) ?>"><?= $escape($sizeType) ?></span>
                                    </td>
                                    <td>
                                        <strong class="order-customer-name"><?= $escape($order['nama_pelanggan']) ?></strong>
                                        <small class="order-phone">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h3l2 5-2 1.5a15 15 0 0 0 4.5 4.5L16 12l2-2 5 2v3c0 1.1-.9 2-2 2C10 19 5 14 5 5c0-1.1.9-2 2-2Z"></path></svg>
                                            <?= $escape($order['telepon']) ?>
                                        </small>
                                    </td>
                                    <td class="order-products">
                                        <?php foreach ($shownSpecifications as $specification): ?>
                                            <div class="order-product-spec"><?= nl2br($escape(trim((string) $specification))) ?></div>
                                        <?php endforeach; ?>
                                        <?php if (count($specifications) > count($shownSpecifications)): ?>
                                            <small class="order-more-items">+<?= count($specifications) - count($shownSpecifications) ?> item lainnya</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="order-total">
                                        <strong>
                                            <?= (float) $order['total'] > 0
                                                ? 'Rp ' . number_format((float) $order['total'], 0, ',', '.')
                                                : 'Belum ditagih' ?>
                                        </strong>
                                        <?php if ((float) ($order['ongkir'] ?? 0) <= 0): ?><small>Ongkir belum ditetapkan</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="order-status order-status--<?= $escape($status[1]) ?>">
                                            <span class="order-status-dot" aria-hidden="true"></span>
                                            <?= $escape($status[0]) ?>
                                        </span>
                                        <?php if (!empty($directTransitions[$statusCode])): ?>
                                            <form class="status-change-form" method="post" action="/?action=admin-order-status-save">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>">
                                                <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                                                <label class="sr-only" for="status-<?= (int) $order['id_pesanan'] ?>">Ubah status pesanan <?= $escape($order['kode']) ?></label>
                                                <select id="status-<?= (int) $order['id_pesanan'] ?>" name="status_pesanan" required>
                                                    <option value="">Ubah status…</option>
                                                    <?php foreach ($directTransitions[$statusCode] as $targetStatus): ?>
                                                        <option value="<?= $escape($targetStatus) ?>"><?= $escape($orderStatuses[$targetStatus][0]) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit">Simpan</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <button class="order-action" type="button" data-toggle-details aria-expanded="false" aria-controls="order-detail-<?= (int) $order['id_pesanan'] ?>">Detail</button>
                                            <?php if ($statusCode === 'MENUNGGU_VERIFIKASI'): ?>
                                                <a class="order-action order-action--verify" href="/?page=admin-order-verification&amp;id=<?= (int) $order['id_pesanan'] ?>">Verifikasi</a>
                                            <?php else: ?>
                                                <a class="order-action order-action--verify" href="/?page=admin-order-detail&amp;id=<?= (int) $order['id_pesanan'] ?>">Rincian</a>
                                            <?php endif; ?>
                                        </div>
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
                <p class="orders-list-note">Menampilkan maksimal 100 pesanan terbaru. Jumlah pada tab menghitung seluruh data pesanan.</p>
            </section>

            <section class="orders-info-card" aria-labelledby="statusFlowTitle">
                <h2 id="statusFlowTitle">Alur Status yang Diizinkan</h2>
                <div class="status-flow" aria-label="Menunggu Verifikasi menuju Menunggu Pembayaran, Diproses, Dikirim, lalu Selesai">
                    <?php foreach (['MENUNGGU_VERIFIKASI', 'MENUNGGU_PEMBAYARAN', 'DIPROSES', 'DIKIRIM', 'SELESAI'] as $index => $flowStatus): ?>
                        <?php if ($index > 0): ?><span class="status-flow-arrow" aria-hidden="true">→</span><?php endif; ?>
                        <span class="order-status order-status--<?= $escape($orderStatuses[$flowStatus][1]) ?>">
                            <span class="order-status-dot" aria-hidden="true"></span>
                            <?= $escape($orderStatuses[$flowStatus][0]) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <p class="status-flow-note">
                    Pesanan dapat dibatalkan saat Menunggu Verifikasi, Menunggu Pembayaran, atau Diproses.
                    Status Selesai dan Dibatalkan bersifat final.
                </p>
                <span class="order-status order-status--cancelled"><span class="order-status-dot" aria-hidden="true"></span>Dibatalkan</span>
            </section>

            <section class="orders-info-card" aria-labelledby="statusActionsTitle">
                <h2 id="statusActionsTitle">Aksi per Status</h2>
                <div class="status-actions-scroll">
                    <table class="status-actions-table">
                        <thead>
                            <tr>
                                <th scope="col">Status</th>
                                <th scope="col">Tombol Aksi</th>
                                <th scope="col">Bisa Dipindah ke</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderStatuses as $statusCode => [$label, $tone]): ?>
                                <?php
                                $destinations = array_map(
                                    static fn ($target) => $orderStatuses[$target][0],
                                    $transitions[$statusCode]
                                );
                                $actions = match ($statusCode) {
                                    'MENUNGGU_VERIFIKASI' => 'Detail + Verifikasi',
                                    'MENUNGGU_PEMBAYARAN' => 'Detail + Rincian + Cek Pembayaran',
                                    'DIPROSES' => 'Detail + Rincian + Input Resi',
                                    default => 'Detail + Rincian',
                                };
                                ?>
                                <tr>
                                    <td><span class="order-status order-status--<?= $escape($tone) ?>"><span class="order-status-dot" aria-hidden="true"></span><?= $escape($label) ?></span></td>
                                    <td><?= $escape($actions) ?></td>
                                    <td><?= $destinations === [] ? 'Final, tidak bisa dipindah' : $escape(implode(' · ', $destinations)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="status-actions-note">Perubahan melalui Verifikasi, Cek Pembayaran, dan Nomor Resi tetap memakai alur khusus agar tagihan, pembayaran, dan nomor resi tercatat dengan benar. Dropdown hanya menyimpan pembatalan atau penyelesaian yang diizinkan, dan server memvalidasi ulang setiap perubahan.</p>
            </section>
        </main>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/cek_pesanan.js"></script>
</body>
</html>
