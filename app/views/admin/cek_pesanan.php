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
    'DIPROSES' => ['Sedang Diproses', 'processing'],
    'DIKIRIM' => ['Dikirim', 'shipped'],
    'SELESAI' => ['Selesai', 'complete'],
    'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
];
$paymentStatuses = [
    'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
    'TERVERIFIKASI' => 'Sudah Dibayar',
    'DITOLAK' => 'Ditolak',
];
$paymentMethods = [
    'TRANSFER_BCA' => 'Transfer BCA',
    'TRANSFER_BRI' => 'Transfer BRI',
    'TRANSFER_BSI' => 'Transfer BSI',
    'TRANSFER_MANDIRI' => 'Transfer Mandiri',
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

            <?php if ((int) ($statusCounts['MENUNGGU_VERIFIKASI'] ?? 0) > 0): ?>
                <section class="orders-queue-banner" aria-label="Ringkasan pesanan yang perlu dikonfirmasi">
                    <div>
                        <strong><?= (int) $statusCounts['MENUNGGU_VERIFIKASI'] ?> pesanan menunggu konfirmasi</strong>
                        <span>Periksa pembayaran dan lanjutkan pesanan yang perlu ditindaklanjuti.</span>
                    </div>
                    <a href="#ordersTablePanel">Lihat pesanan <span aria-hidden="true">→</span></a>
                </section>
            <?php endif; ?>

            <section class="order-summary-grid" aria-label="Ringkasan status pesanan">
                <article class="order-summary-card order-summary-card--new">
                    <span>Pesanan Baru</span>
                    <strong><?= (int) ($statusCounts['MENUNGGU_VERIFIKASI'] + $statusCounts['MENUNGGU_PEMBAYARAN']) ?></strong>
                    <small>Menunggu pembayaran atau verifikasi</small>
                </article>
                <article class="order-summary-card order-summary-card--processing">
                    <span>Diproses</span>
                    <strong><?= (int) $statusCounts['DIPROSES'] ?></strong>
                    <small>Sedang dikerjakan</small>
                </article>
                <article class="order-summary-card order-summary-card--shipped">
                    <span>Dikirim</span>
                    <strong><?= (int) $statusCounts['DIKIRIM'] ?></strong>
                    <small>Pesanan dalam pengiriman</small>
                </article>
                <article class="order-summary-card order-summary-card--complete">
                    <span>Selesai</span>
                    <strong><?= (int) $statusCounts['SELESAI'] ?></strong>
                    <small>Pesanan telah diterima</small>
                </article>
                <article class="order-summary-card order-summary-card--cancelled">
                    <span>Dibatalkan</span>
                    <strong><?= (int) $statusCounts['DIBATALKAN'] ?></strong>
                    <small>Pesanan berstatus batal</small>
                </article>
            </section>

            <section class="orders-content" aria-label="Daftar pesanan">
                <div class="orders-toolbar">
                    <div class="orders-filter-grid" aria-label="Pencarian dan filter pesanan">
                        <label class="order-filter-field order-filter-field--search">
                            <span>Nomor pesanan</span>
                            <input id="orderSearchInvoice" type="search" placeholder="Cari nomor pesanan..." aria-label="Cari nomor pesanan" value="<?= $escape($searchTerm) ?>">
                        </label>
                        <label class="order-filter-field order-filter-field--search">
                            <span>Nama pelanggan</span>
                            <input id="orderSearchCustomer" type="search" placeholder="Cari nama pelanggan..." aria-label="Cari nama pelanggan">
                        </label>
                        <label class="order-filter-field order-filter-field--search">
                            <span>Nomor WhatsApp</span>
                            <input id="orderSearchPhone" type="search" placeholder="Cari nomor WhatsApp..." aria-label="Cari nomor WhatsApp">
                        </label>
                        <label class="order-filter-field">
                            <span>Status pesanan</span>
                            <select id="orderStatusFilter" aria-label="Filter status pesanan">
                                <option value="semua">Semua status</option>
                                <?php foreach ($filters as $filter => $label): ?>
                                    <?php if ($filter !== 'semua'): ?>
                                        <option value="<?= $escape($filter) ?>"<?= $activeFilter === $filter ? ' selected' : '' ?>><?= $escape($label) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="order-filter-field">
                            <span>Tanggal pesanan</span>
                            <select id="orderDateFilter" aria-label="Filter tanggal pesanan">
                                <option value="">Semua tanggal</option>
                                <option value="today">Hari ini</option>
                                <option value="7">7 hari terakhir</option>
                                <option value="30">30 hari terakhir</option>
                            </select>
                        </label>
                        <label class="order-filter-field">
                            <span>Metode pembayaran</span>
                            <select id="orderMethodFilter" aria-label="Filter metode pembayaran">
                                <option value="">Semua metode</option>
                                <?php foreach ($paymentMethods as $method => $label): ?>
                                    <option value="<?= $escape($method) ?>"><?= $escape($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="order-filter-field">
                            <span>Urutkan pesanan</span>
                            <select id="orderSort">
                                <option value="newest">Terbaru</option>
                                <option value="oldest">Terlama</option>
                                <option value="amount-high">Total tertinggi</option>
                                <option value="amount-low">Total terendah</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="orders-panel orders-table-shell" id="ordersTablePanel">
                    <nav class="orders-tabs" aria-label="Filter status pesanan">
                        <button type="button" class="order-tab<?= $activeFilter === 'MENUNGGU_VERIFIKASI' ? ' is-active' : '' ?>" data-order-status-filter="MENUNGGU_VERIFIKASI" aria-pressed="<?= $activeFilter === 'MENUNGGU_VERIFIKASI' ? 'true' : 'false' ?>">
                            Perlu dikonfirmasi <span><?= (int) ($statusCounts['MENUNGGU_VERIFIKASI'] ?? 0) ?></span>
                        </button>
                        <button type="button" class="order-tab<?= $activeFilter === 'MENUNGGU_PEMBAYARAN' ? ' is-active' : '' ?>" data-order-status-filter="MENUNGGU_PEMBAYARAN" aria-pressed="<?= $activeFilter === 'MENUNGGU_PEMBAYARAN' ? 'true' : 'false' ?>">
                            Menunggu pembayaran <span><?= (int) ($statusCounts['MENUNGGU_PEMBAYARAN'] ?? 0) ?></span>
                        </button>
                        <button type="button" class="order-tab<?= $activeFilter === 'DIPROSES' ? ' is-active' : '' ?>" data-order-status-filter="DIPROSES" aria-pressed="<?= $activeFilter === 'DIPROSES' ? 'true' : 'false' ?>">
                            Diproses <span><?= (int) ($statusCounts['DIPROSES'] ?? 0) ?></span>
                        </button>
                        <button type="button" class="order-tab<?= $activeFilter === 'DIKIRIM' ? ' is-active' : '' ?>" data-order-status-filter="DIKIRIM" aria-pressed="<?= $activeFilter === 'DIKIRIM' ? 'true' : 'false' ?>">
                            Dikirim <span><?= (int) ($statusCounts['DIKIRIM'] ?? 0) ?></span>
                        </button>
                        <button type="button" class="order-tab<?= $activeFilter === 'SELESAI' ? ' is-active' : '' ?>" data-order-status-filter="SELESAI" aria-pressed="<?= $activeFilter === 'SELESAI' ? 'true' : 'false' ?>">
                            Selesai <span><?= (int) ($statusCounts['SELESAI'] ?? 0) ?></span>
                        </button>
                        <button type="button" class="order-tab<?= $activeFilter === 'DIBATALKAN' ? ' is-active' : '' ?>" data-order-status-filter="DIBATALKAN" aria-pressed="<?= $activeFilter === 'DIBATALKAN' ? 'true' : 'false' ?>">
                            Dibatalkan <span><?= (int) ($statusCounts['DIBATALKAN'] ?? 0) ?></span>
                        </button>
                        <button type="button" class="order-tab<?= $activeFilter === 'semua' ? ' is-active' : '' ?>" data-order-status-filter="semua" aria-pressed="<?= $activeFilter === 'semua' ? 'true' : 'false' ?>">
                            Semua <span><?= (int) ($statusCounts['semua'] ?? 0) ?></span>
                        </button>
                    </nav>
                    <div class="orders-table-scroll">
                        <table class="orders-table">
                        <thead>
                            <tr>
                                <th scope="col">Nomor Pesanan &amp; Tanggal</th>
                                <th scope="col">Nama Pelanggan</th>
                                <th scope="col">Nomor WhatsApp</th>
                                <th scope="col">Total Pesanan</th>
                                <th scope="col">Metode Pembayaran</th>
                                <th scope="col">Status Pembayaran</th>
                                <th scope="col">Status Pesanan</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $statusCode = (string) ($order['status_pesanan'] ?? '');
                                $status = $orderStatuses[$statusCode] ?? [$statusCode, 'other'];
                                $paymentCode = (string) ($order['status_pembayaran'] ?? '');
                                $paymentStatus = $paymentStatuses[$paymentCode] ?? (
                                    $statusCode === 'MENUNGGU_PEMBAYARAN' ? 'Menunggu Pembayaran' : 'Belum ada pembayaran'
                                );
                                $paymentClass = match ($order['status_pembayaran'] ?? '') {
                                    'MENUNGGU_VERIFIKASI' => 'verification',
                                    'TERVERIFIKASI' => 'complete',
                                    'DITOLAK' => 'cancelled',
                                    default => $statusCode === 'MENUNGGU_PEMBAYARAN' ? 'payment' : 'other',
                                };
                                $method = (string) ($order['metode_pembayaran'] ?? '');
                                $tanggalPesanan = strtotime((string) $order['tanggal_pesan']);
                                $tanggalLabel = $tanggalPesanan
                                    ? date('d', $tanggalPesanan) . ' ' . $bulanPendek[(int) date('n', $tanggalPesanan)] . date(' Y, H:i', $tanggalPesanan) . ' WIB'
                                    : (string) $order['tanggal_pesan'];
                                $specificationText = trim((string) ($order['daftar_spesifikasi'] ?? ''));
                                $specifications = $specificationText !== ''
                                    ? preg_split('/\n\s*\n/', $specificationText)
                                    : [(string) ($order['daftar_produk'] ?? 'Produk tidak tersedia')];
                                $specifications = array_values(array_filter($specifications, static fn ($value) => trim((string) $value) !== ''));
                                $phone = (string) ($order['telepon'] ?? '');
                                $phoneSearch = preg_replace('/\D+/', '', $phone);
                                $waPhone = $phoneSearch !== '' && str_starts_with($phoneSearch, '0')
                                    ? '62' . substr($phoneSearch, 1)
                                    : $phoneSearch;
                                ?>
                                <tr
                                    data-order-row
                                    data-category="<?= $escape($statusCode) ?>"
                                    data-invoice="<?= $escape(strtolower((string) ($order['kode'] ?? ''))) ?>"
                                    data-customer="<?= $escape(strtolower((string) ($order['nama_pelanggan'] ?? ''))) ?>"
                                    data-phone="<?= $escape($phoneSearch) ?>"
                                    data-method="<?= $escape($method) ?>"
                                    data-payment="<?= $escape($paymentCode) ?>"
                                    data-date="<?= $tanggalPesanan !== false ? (int) $tanggalPesanan : 0 ?>"
                                    data-amount="<?= $escape((float) ($order['total'] ?? 0)) ?>"
                                    <?= isset($visibleOrderIds[$order['id_pesanan']]) ? '' : 'hidden' ?>
                                >
                                    <td>
                                        <strong class="order-invoice"><?= $escape($order['kode']) ?></strong>
                                        <small><?= $escape($tanggalLabel) ?></small>
                                    </td>
                                    <td>
                                        <strong class="order-customer-name"><?= $escape($order['nama_pelanggan']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($phone !== ''): ?>
                                            <a class="order-phone" href="https://wa.me/<?= $escape($waPhone) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($phone) ?></a>
                                        <?php else: ?><span class="order-muted">-</span><?php endif; ?>
                                    </td>
                                    <td class="order-total">
                                        <strong>
                                            <?= (float) $order['total'] > 0
                                                ? 'Rp ' . number_format((float) $order['total'], 0, ',', '.')
                                                : 'Belum ditagih' ?>
                                        </strong>
                                        <?php if ((float) ($order['ongkir'] ?? 0) <= 0): ?><small>Ongkir belum ditetapkan</small><?php endif; ?>
                                    </td>
                                    <td><span class="order-method"><?= $escape($paymentMethods[$method] ?? ($method !== '' ? str_replace('_', ' ', $method) : 'Belum dipilih')) ?></span></td>
                                    <td>
                                        <span class="order-payment order-payment--<?= $escape($paymentClass) ?>"><?= $escape($paymentStatus) ?></span>
                                    </td>
                                    <td><span class="order-status order-status--<?= $escape($status[1]) ?>"><span class="order-status-dot" aria-hidden="true"></span><?= $escape($status[0]) ?></span></td>
                                    <td>
                                        <div class="order-actions">
                                            <button class="order-action" type="button" data-toggle-details aria-expanded="false">Ringkasan</button>
                                            <a class="order-action" href="/?page=admin-order-detail&amp;id=<?= (int) $order['id_pesanan'] ?>">Detail</a>
                                            <?php if ($statusCode === 'MENUNGGU_VERIFIKASI'): ?>
                                                <a class="order-action order-action--verify" href="/?page=admin-order-verification&amp;id=<?= (int) $order['id_pesanan'] ?>">Proses Pesanan</a>
                                            <?php elseif ($statusCode === 'DIPROSES'): ?>
                                                <a class="order-action order-action--verify" href="/?page=admin-resi-input&amp;id=<?= (int) $order['id_pesanan'] ?>">Proses Pesanan</a>
                                            <?php elseif (!empty($directTransitions[$statusCode])): ?>
                                                <form class="status-change-form" method="post" action="/?action=admin-order-status-save">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>">
                                                    <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                                                    <label class="sr-only" for="status-<?= (int) $order['id_pesanan'] ?>">Ubah status pesanan <?= $escape($order['kode']) ?></label>
                                                    <select id="status-<?= (int) $order['id_pesanan'] ?>" name="status_pesanan" required>
                                                        <option value="">Proses pesanan</option>
                                                        <?php foreach ($directTransitions[$statusCode] as $targetStatus): ?>
                                                            <option value="<?= $escape($targetStatus) ?>"><?= $escape($orderStatuses[$targetStatus][0]) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit">Simpan</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($paymentCode !== ''): ?>
                                                <a class="order-action order-action--payment" href="/?page=admin-payments&amp;cari=<?= rawurlencode((string) $order['kode']) ?>">Lihat Pembayaran</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="order-details-row" data-order-details hidden>
                                    <td colspan="8">
                                        <div class="order-details">
                                            <div><small>Spesifikasi Produk</small><p><?= nl2br($escape($specificationText !== '' ? $specificationText : implode("\n\n", $specifications))) ?></p></div>
                                            <div><small>Alamat Pengiriman</small><p><?= $escape($order['alamat_pengiriman'] ?: '-') ?></p></div>
                                            <div><small>Metode Pengiriman</small><p><?= $escape(str_replace('_', ' ', (string) $order['metode_pengiriman'])) ?></p></div>
                                            <div><small>Status Pembayaran</small><p><span class="order-payment order-payment--<?= $escape($paymentClass) ?>"><?= $escape($paymentStatus) ?></span></p></div>
                                            <div><small>Ongkos Kirim</small><p><?= (float) ($order['ongkir'] ?? 0) > 0 ? $escape('Rp ' . number_format((float) $order['ongkir'], 0, ',', '.')) : 'Belum ditetapkan' ?></p></div>
                                            <div><small>Total Pembayaran</small><p><?= (float) ($order['total'] ?? 0) > 0 ? $escape('Rp ' . number_format((float) $order['total'], 0, ',', '.')) : 'Belum ditagih' ?></p></div>
                                            <?php if (!empty($order['catatan_pengiriman'])): ?><div><small>Catatan Pengiriman</small><p><?= $escape($order['catatan_pengiriman']) ?></p></div><?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="orders-empty" id="ordersEmpty"<?= $visibleOrders ? ' hidden' : '' ?>>
                                <td colspan="8">
                                    <?= $orders ? 'Tidak ada pesanan yang cocok dengan filter atau pencarian.' : 'Belum ada pesanan.' ?>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>
                </div>
                <div class="orders-table-footer">
                    <p class="orders-list-note" id="ordersResultCount" data-loaded-count="<?= count($orders) ?>">Menampilkan <?= count($orders) ?> pesanan terbaru.</p>
                    <nav class="orders-pagination" id="ordersPagination" aria-label="Halaman daftar pesanan"></nav>
                </div>
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
