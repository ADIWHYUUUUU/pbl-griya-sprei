<?php
require __DIR__ . '/config.php';

const ORDER_STATUSES = [
    'Menunggu Verifikasi',
    'Menunggu Pembayaran',
    'Pembayaran Terverifikasi',
    'Diproses (Sedang Dijahit)',
    'Selesai Dikirim',
];

$orders = $_SESSION['pesanan'] ?? [];
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['cari'] ?? '');
$processStatuses = ['Menunggu Pembayaran', 'Pembayaran Terverifikasi', 'Diproses (Sedang Dijahit)'];

if ($statusFilter !== '' && $statusFilter !== 'proses' && !in_array($statusFilter, ORDER_STATUSES, true)) {
    $statusFilter = '';
}

$counts = ['semua' => count($orders), 'verifikasi' => 0, 'diproses' => 0, 'selesai' => 0];
foreach ($orders as $order) {
    $status = $order['status'] ?? 'Menunggu Verifikasi';
    if ($status === 'Menunggu Verifikasi') {
        $counts['verifikasi']++;
    } elseif ($status === 'Selesai Dikirim') {
        $counts['selesai']++;
    } else {
        $counts['diproses']++;
    }
}

$filteredOrders = array_filter($orders, static function (array $order) use ($statusFilter, $search, $processStatuses): bool {
    $status = $order['status'] ?? 'Menunggu Verifikasi';
    $productName = $order['produk']['nama'] ?? '';
    $customerName = $order['penerima'] ?? '';

    $matchesStatus = $statusFilter === ''
        || ($statusFilter === 'proses' && in_array($status, $processStatuses, true))
        || $status === $statusFilter;
    return $matchesStatus
        && ($search === '' || stripos((string)($order['kode'] ?? ''), $search) !== false
            || stripos((string)$productName, $search) !== false
            || stripos((string)$customerName, $search) !== false);
});

usort($filteredOrders, static function (array $a, array $b): int {
    return strtotime((string)($b['waktu'] ?? '')) <=> strtotime((string)($a['waktu'] ?? ''));
});

$pageTitle = 'Cek Pesanan';
$activeNav = 'orders';
$crumbs = [['Admin', null], ['Cek Pesanan', null]];
require __DIR__ . '/partials/header.php';
?>
<div class="page-head order-page-head">
    <h1>Cek Pesanan</h1>
</div>

<div class="order-toolbar">
    <nav class="order-tabs" aria-label="Filter pesanan">
        <a class="order-tab<?= $statusFilter === '' ? ' is-active' : '' ?>" href="CekPesanan.php">Semua <span><?= $counts['semua'] ?></span></a>
        <a class="order-tab<?= $statusFilter === 'Menunggu Verifikasi' ? ' is-active' : '' ?>" href="?status=<?= urlencode('Menunggu Verifikasi') ?>">Menunggu Verifikasi <span><?= $counts['verifikasi'] ?></span></a>
        <a class="order-tab<?= $statusFilter === 'proses' ? ' is-active' : '' ?>" href="?status=proses">Diproses <span><?= $counts['diproses'] ?></span></a>
        <a class="order-tab<?= $statusFilter === 'Selesai Dikirim' ? ' is-active' : '' ?>" href="?status=<?= urlencode('Selesai Dikirim') ?>">Selesai <span><?= $counts['selesai'] ?></span></a>
    </nav>
    <form class="order-search" method="get">
        <?php if ($statusFilter !== ''): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
        <label class="sr-only" for="orderSearch">Cari invoice, nama pelanggan, atau produk</label>
        <input id="orderSearch" type="search" name="cari" value="<?= e($search) ?>" placeholder="Cari invoice / nama...">
        <button type="submit" aria-label="Cari"><?= icon('search', 17) ?></button>
    </form>
</div>

<div class="card table-card order-table-card">
    <div class="table-scroll">
        <table class="table order-table">
            <thead>
                <tr>
                    <th>No. Invoice &amp; Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Spesifikasi Sprei</th>
                    <th>Tagihan</th>
                    <th>Status Pesanan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$filteredOrders): ?>
                <tr><td class="empty" colspan="6"><?= $orders ? 'Pesanan tidak ditemukan.' : 'Belum ada pesanan untuk ditampilkan.' ?></td></tr>
            <?php endif; ?>
            <?php foreach ($filteredOrders as $order):
                $product = $order['produk'] ?? [];
                $status = $order['status'] ?? 'Menunggu Verifikasi';
                $statusClass = $status === 'Menunggu Verifikasi' ? 'waiting'
                    : ($status === 'Selesai Dikirim' ? 'complete' : 'processing');
                $size = $product['ukuran'] ?? 'Ukuran belum tersedia';
                $invoice = $order['kode'] ?? '';
                ?>
                <tr>
                    <td>
                        <a class="order-invoice" href="RincianPesanan.php?kode=<?= urlencode($invoice) ?>"><?= e($invoice) ?></a>
                        <small class="order-date"><?= e($order['waktu'] ?? '') ?> WIB</small>
                        <span class="order-kind"><?= ($product['jenis'] ?? '') === 'custom' ? 'Custom' : 'Standar' ?></span>
                    </td>
                    <td>
                        <strong><?= e($order['penerima'] ?? 'Pelanggan') ?></strong>
                        <?php if (!empty($order['telepon'])): ?><small class="order-phone"><?= e($order['telepon']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($product['nama'] ?? 'Produk') ?></strong>
                        <small><?= e(ucfirst($product['jenis'] ?? 'standar')) ?>: <?= e($size) ?></small>
                        <?php if (!empty($product['tambahan']) && $product['tambahan'] !== '-'): ?><small><?= e($product['tambahan']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($order['tagihan']) && $order['tagihan'] !== null): ?>
                            <strong><?= e(rupiah((int)$order['tagihan'])) ?></strong>
                            <?php if (!empty($order['ongkir'])): ?><small>(termasuk ongkir)</small><?php endif; ?>
                        <?php else: ?>
                            <span class="order-unbilled">Belum ditetapkan</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="order-status order-status--<?= $statusClass ?>"><?= e($status) ?></span></td>
                    <td>
                        <a class="btn btn-sm <?= $status === 'Menunggu Verifikasi' ? 'btn-primary' : 'btn-light' ?>" href="RincianPesanan.php?kode=<?= urlencode($invoice) ?><?= $status === 'Menunggu Verifikasi' ? '#tagihan' : '' ?>">
                            <?= $status === 'Menunggu Verifikasi' ? 'Verifikasi' : 'Detail' ?>
                        </a>
                        <a class="btn btn-sm btn-light" href="FormPembayaran.php?kode=<?= urlencode($invoice) ?>">Pembayaran</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
