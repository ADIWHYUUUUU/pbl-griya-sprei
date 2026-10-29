<?php
require __DIR__ . '/config.php';

$orders = $_SESSION['pesanan'] ?? [];
$search = trim($_GET['cari'] ?? '');
$payments = [];

foreach ($orders as $order) {
    $history = $order['riwayat_bayar'] ?? [];
    if (!$history) {
        continue;
    }

    $paymentIndex = array_key_last($history);
    $payment = $history[$paymentIndex];
    if (($payment['status'] ?? '') !== 'Menunggu Verifikasi') {
        continue;
    }

    $matchesSearch = $search === ''
        || stripos((string)($order['kode'] ?? ''), $search) !== false
        || stripos((string)($order['penerima'] ?? ''), $search) !== false
        || stripos((string)($order['alamat'] ?? ''), $search) !== false;
    if ($matchesSearch) {
        $payments[] = ['order' => $order, 'payment' => $payment];
    }
}

usort($payments, static function (array $a, array $b): int {
    return strtotime((string)($b['payment']['waktu'] ?? '')) <=> strtotime((string)($a['payment']['waktu'] ?? ''));
});

$pageSize = 5;
$totalPages = max(1, (int)ceil(count($payments) / $pageSize));
$page = min($totalPages, max(1, (int)($_GET['page'] ?? 1)));
$pagePayments = array_slice($payments, ($page - 1) * $pageSize, $pageSize);

$pageTitle = 'Cek Pembayaran';
$activeNav = 'payments';
$crumbs = [['Admin', null], ['Cek Pembayaran', null]];
require __DIR__ . '/partials/header.php';
?>
<div class="page-head payment-page-head">
    <h1>Cek Pembayaran</h1>
</div>

<form class="order-search payment-search" method="get">
    <label class="sr-only" for="paymentSearch">Cari invoice, nama, atau alamat pelanggan</label>
    <input id="paymentSearch" type="search" name="cari" value="<?= e($search) ?>" placeholder="Cari invoice / nama...">
    <button type="submit" aria-label="Cari"><?= icon('search', 17) ?></button>
</form>

<div class="card table-card order-table-card payment-table-card">
    <div class="table-scroll">
        <table class="table payment-table">
            <thead>
                <tr>
                    <th>No. Invoice &amp; Tanggal</th>
                    <th>Nama &amp; No. WhatsApp</th>
                    <th>Alamat Pengiriman</th>
                    <th>Metode Bayar</th>
                    <th>Status</th>
                    <th>Total Tagihan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$pagePayments): ?>
                <tr><td class="empty" colspan="7"><?= $payments ? 'Tidak ada pembayaran yang cocok dengan pencarian.' : 'Belum ada bukti pembayaran yang menunggu verifikasi.' ?></td></tr>
            <?php endif; ?>
            <?php foreach ($pagePayments as ['order' => $order, 'payment' => $payment]): ?>
                <tr>
                    <td>
                        <a class="order-invoice" href="VerifikasiPembayaran.php?kode=<?= urlencode($order['kode']) ?>"><?= icon('file', 14) ?> <?= e($order['kode']) ?></a>
                        <small class="order-date"><?= e($payment['waktu'] ?? $order['waktu'] ?? '') ?> WIB</small>
                    </td>
                    <td>
                        <strong><?= e($order['penerima'] ?? 'Pelanggan') ?></strong>
                        <small class="order-phone"><?= e($order['telepon'] ?? '-') ?></small>
                    </td>
                    <td class="payment-address"><?= e($order['alamat'] ?? '-') ?></td>
                    <td><span class="payment-method"><?= e($payment['bank'] ?? 'Transfer Bank') ?></span></td>
                    <td><span class="payment-status">Menunggu Verifikasi</span></td>
                    <td class="payment-total"><?= e(rupiah((int)($payment['jumlah'] ?? $order['tagihan'] ?? 0))) ?></td>
                    <td>
                        <div class="payment-actions">
                            <a class="btn btn-sm btn-light" href="RincianPesanan.php?kode=<?= urlencode($order['kode']) ?>">Detail Pesanan</a>
                            <a class="btn btn-sm btn-light" href="FormPembayaran.php?kode=<?= urlencode($order['kode']) ?>">Form Pembayaran</a>
                            <a class="btn btn-sm btn-primary" href="VerifikasiPembayaran.php?kode=<?= urlencode($order['kode']) ?>"><?= icon('check-c', 14) ?> Verifikasi Pembayaran</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="pagination" aria-label="Halaman pembayaran">
    <a class="pg<?= $page <= 1 ? ' is-disabled' : '' ?>" href="?page=<?= max(1, $page - 1) ?>&amp;cari=<?= urlencode($search) ?>" aria-label="Sebelumnya"><?= icon('chev-l', 16) ?></a>
    <?php for ($index = 1; $index <= $totalPages; $index++): ?>
        <a class="pg<?= $index === $page ? ' is-active' : '' ?>" href="?page=<?= $index ?>&amp;cari=<?= urlencode($search) ?>"><?= $index ?></a>
    <?php endfor; ?>
    <a class="pg<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="?page=<?= min($totalPages, $page + 1) ?>&amp;cari=<?= urlencode($search) ?>" aria-label="Berikutnya"><?= icon('chev-r', 16) ?></a>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>