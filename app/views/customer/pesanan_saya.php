<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/OrderModel.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

require_once __DIR__ . '/../../helpers/order_display.php';

$customerId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($customerId === false || $customerId === null) {
    http_response_code(403);
    exit('Akun customer tidak valid.');
}

// Customer sedang membuka Pesanan Saya: semua update dianggap sudah dibaca
require_once __DIR__ . '/../../helpers/order_updates.php';
orderUpdatesMarkSeen($customerId);

$requestedSearch = $_GET['cari'] ?? '';
$cari = is_string($requestedSearch) ? trim($requestedSearch) : '';
$orders = (new OrderModel($pdo))->getCustomerOrders($customerId);
if ($cari !== '') {
    $orders = array_values(array_filter($orders, static function (array $order) use ($cari): bool {
        $productNames = implode(' ', array_column($order['produk'], 'nama'));
        return stripos($order['kode'], $cari) !== false
            || stripos($productNames, $cari) !== false;
    }));
}

$orderStatusLabels = [
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi Admin', 'tunggu'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'bayar'],
    'DIPROSES' => ['Diproses', 'proses'],
    'DIKIRIM' => ['Dikirim', 'proses'],
    'SELESAI' => ['Selesai', 'selesai'],
    'DIBATALKAN' => ['Dibatalkan', 'tolak'],
];
$requestedStatus = $_GET['status'] ?? '';
$statusFilter = is_string($requestedStatus) && isset($orderStatusLabels[$requestedStatus]) ? $requestedStatus : '';
if ($statusFilter !== '') {
    $orders = array_values(array_filter($orders, static fn (array $order): bool => $order['status_pesanan'] === $statusFilter));
}
$statusFilters = ['' => 'Semua'] + array_map(static fn (array $status): string => $status[0], $orderStatusLabels);
$statusFilters['MENUNGGU_VERIFIKASI'] = 'Menunggu Verifikasi';
$shippingLabels = [
    'INSTANT_GOJEK' => 'Gojek / Grab Express',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$paymentMethodLabels = [
    'TRANSFER_BCA' => 'Transfer BCA',
    'TRANSFER_BRI' => 'Transfer BRI',
    'TRANSFER_BSI' => 'Transfer BSI',
    'TRANSFER_MANDIRI' => 'Transfer Mandiri',
];
foreach ($orders as &$order) {
    $productNames = array_column($order['produk'], 'nama');
    $extras = [];
    foreach ($order['produk'] as $product) {
        if ($product['tambah_sarban'] > 0) {
            $extras[] = $product['tambah_sarban'] . ' sarung bantal';
        }
        if ($product['tambah_sargul'] > 0) {
            $extras[] = $product['tambah_sargul'] . ' sarung guling';
        }
    }

    $order['tagihan_final'] = !in_array($order['status_pesanan'], ['MENUNGGU_VERIFIKASI', 'DIBATALKAN'], true);
    $order['lunas'] = $order['status_pembayaran'] === 'TERVERIFIKASI'
        || in_array($order['status_pesanan'], ['DIPROSES', 'DIKIRIM', 'SELESAI'], true);
    $order['nama_produk'] = $productNames === [] ? 'Produk tidak tersedia' : implode(', ', $productNames);
    $order['tambahan'] = $extras === [] ? '-' : implode(', ', array_unique($extras));
    $order['gambar'] = '';
    foreach ($order['produk'] as $product) {
        if ($product['gambar'] !== '') {
            $order['gambar'] = product_image_url($product['gambar']);
            break;
        }
    }

}
unset($order);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/customer/pesanan_saya.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/customer/pesanan_saya.css') ?>">
</head>
<body class="pesanan-saya-page">
    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="isi">
            <nav class="breadcrumb">
                <a href="/?page=customer-home">Beranda</a> /
                <b>Pesanan Saya</b>
            </nav>

            <h1 class="judul-halaman">Pesanan Saya</h1>
            <p class="sub-judul">Pantau status pesanan, tagihan, pembayaran, dan pengiriman Anda.</p>

            <form method="get" action="/" class="kotak-cari kartu" role="search">
                <input type="hidden" name="page" value="customer-orders">
                <?php if ($statusFilter !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                <?php endif; ?>
                <label class="sr-only" for="cariPesanan">Cari nomor invoice atau nama produk</label>
                <input id="cariPesanan" type="search" name="cari" placeholder="Cari nomor invoice atau nama produk..." value="<?= e($cari) ?>">
                <button type="submit" class="tombol">Cari</button>
            </form>

            <nav class="filter-pesanan" aria-label="Filter status pesanan">
                <?php foreach ($statusFilters as $status => $label): ?>
                    <?php $filterQuery = array_filter(['page' => 'customer-orders', 'status' => $status, 'cari' => $cari], static fn (string $value): bool => $value !== ''); ?>
                    <a class="filter-pesanan-chip" href="/?<?= e(http_build_query($filterQuery)) ?>" <?= $status === $statusFilter ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($orders === []): ?>
                <?php $hasFilters = $cari !== '' || $statusFilter !== ''; ?>
                <div class="kartu kosong">
                    <p><?= $hasFilters ? 'Pesanan tidak ditemukan.' : 'Anda belum memiliki pesanan.' ?></p>
                    <?php if ($hasFilters): ?>
                        <a href="/?page=customer-orders" class="tombol">Tampilkan Semua</a>
                    <?php else: ?>
                        <a href="/?page=customer-catalog" class="tombol">Mulai Belanja</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($orders as $order): ?>
                <?php
                $waitingForPayment = $order['status_pesanan'] === 'MENUNGGU_PEMBAYARAN';
                $rejected = $order['status_pembayaran'] === 'DITOLAK';
                $rejectionReason = $rejected ? trim($order['catatan_admin']) : '';
                if ($rejectionReason !== '' && !preg_match('/[.!?…]$/u', $rejectionReason)) {
                    $rejectionReason .= '.';
                }
                $unpaid = $waitingForPayment && $order['status_pembayaran'] === '';
                $needsPayment = $unpaid || ($waitingForPayment && $rejected);
                $secondsRemaining = $needsPayment
                    ? (new DateTimeImmutable($order['batas_pembayaran'], new DateTimeZone('UTC')))->getTimestamp() - time()
                    : 0;
                [$cardStatusLabel, $cardStatusClass] = $orderStatusLabels[$order['status_pesanan']]
                    ?? [$order['status_pesanan'], 'netral'];
                $paymentMethod = $paymentMethodLabels[$order['metode_pembayaran']] ?? '';
                $paymentBank = $paymentMethod !== '' ? ' (' . substr($paymentMethod, strlen('Transfer ')) . ')' : '';
                [$paymentLabel, $paymentClass] = match (true) {
                    $order['status_pesanan'] === 'DIBATALKAN' => ['Dibatalkan', 'netral'],
                    $rejected => ['Pembayaran Ditolak', 'tolak'],
                    $order['status_pembayaran'] === 'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi Pembayaran' . $paymentBank, 'tunggu'],
                    $order['lunas'] => ['Terverifikasi' . $paymentBank, 'lunas'],
                    $unpaid => ['Belum Dibayar', 'tunggu'],
                    default => ['Belum Ada Tagihan', 'netral'],
                };
                [$shippingLabel, $shippingClass] = match ($order['status_pesanan']) {
                    'DIPROSES' => ['Sedang Dikemas', 'proses'],
                    'DIKIRIM' => ['Dikirim', 'bayar'],
                    'SELESAI' => ['Selesai Dikirim', 'selesai'],
                    'DIBATALKAN' => ['Tidak Dikirim', 'netral'],
                    default => ['Belum Dikirim', 'netral'],
                };
                ?>
                <article class="kartu pesanan" data-pesanan-card>
                    <div class="pesanan-kepala">
                        <div class="info-waktu">
                            <b>[<?= e($order['kode']) ?>]</b>
                            <span>· Dipesan: <?= e(tanggal($order['tanggal_pesan'])) ?> WIB</span>
                        </div>
                        <span class="badge badge-<?= e($cardStatusClass) ?>"><?= e($cardStatusLabel) ?></span>
                    </div>

                    <div class="pesanan-isi">
                        <?php if ($order['gambar'] !== ''): ?>
                            <img src="<?= e($order['gambar']) ?>" alt="" class="foto-produk">
                        <?php else: ?>
                            <div class="foto-produk foto-kosong" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <circle cx="9" cy="10" r="2" />
                                    <path d="m21 16-5-5-9 9" />
                                </svg>
                            </div>
                        <?php endif; ?>

                        <div class="info-produk">
                            <h3><?= e($order['nama_produk']) ?></h3>
                            <?php foreach ($order['produk'] as $product): ?>
                                <p>
                                    <small><?= e($product['tipe_ukuran'] === 'CUSTOM' ? 'Custom' : 'Standar') ?>:</small>
                                    <?= (int) $product['panjang'] ?> × <?= (int) $product['lebar'] ?><?= $product['tinggi'] === null ? '' : ' × ' . (int) $product['tinggi'] ?> cm
                                    · <?= (int) $product['jumlah'] ?> pcs
                                </p>
                            <?php endforeach; ?>
                            <?php if ($order['tambahan'] !== '-'): ?>
                                <p><small>Kelengkapan:</small> <?= e($order['tambahan']) ?></p>
                            <?php endif; ?>
                            <?php
                                $shippingTracking = $order['metode_pengiriman'] === 'INSTANT_GOJEK'
                                    ? ($order['nomor_telepon_kurir'] ?? '')
                                    : ($order['nomor_resi'] ?? '');
                            ?>
                            <p>
                                Pengiriman: <?= e($shippingLabels[$order['metode_pengiriman']] ?? $order['metode_pengiriman']) ?>
                                <?php if ($shippingTracking !== ''): ?>
                                    · <?= $order['metode_pengiriman'] === 'INSTANT_GOJEK' ? 'Telepon kurir:' : 'Resi:' ?> <?= e($shippingTracking) ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="info-tagihan">
                            <?php if ($order['tagihan_final']): ?>
                                <small>Total Tagihan</small>
                                <b class="nominal"><?= e(rupiah($order['total'])) ?></b>
                                <small>
                                    <?php if ($order['diskon'] > 0): ?>
                                        Diskon −<?= e(rupiah($order['diskon'])) ?> ·
                                    <?php endif; ?>
                                    Ongkir <?= e(rupiah($order['ongkir'])) ?>
                                </small>
                            <?php else: ?>
                                <small>Total Tagihan</small>
                                <b class="belum">Belum ditetapkan</b>
                                <small>Tagihan resmi akan muncul setelah pesanan diperiksa Admin.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pesanan-status">
                        <div class="pesanan-status-item">
                            <span>Pembayaran</span>
                            <span class="badge badge-<?= e($paymentClass) ?>"><?= e($paymentLabel) ?></span>
                        </div>
                        <div class="pesanan-status-item">
                            <span>Pengiriman</span>
                            <span class="badge badge-<?= e($shippingClass) ?>"><?= e($shippingLabel) ?></span>
                        </div>
                    </div>

                    <?php if ($needsPayment): ?>
                        <div class="kotak-status <?= $unpaid ? 'kotak-tenggat' : 'kotak-tolak' ?>">
                            <?php if ($rejected): ?>
                                <?= $rejectionReason !== ''
                                    ? 'Bukti ditolak Admin: ' . e($rejectionReason)
                                    : 'Bukti ditolak Admin.' ?>
                            <?php endif; ?>
                            <?php if ($secondsRemaining > 0): ?>
                                <?php if ($rejected): ?>
                                    Unggah ulang sebelum <b><?= e(tanggal($order['batas_pembayaran'])) ?> WIB</b>,
                                    atau pesanan dibatalkan otomatis.
                                <?php else: ?>
                                    Bayar sebelum <b><?= e(tanggal($order['batas_pembayaran'])) ?> WIB</b>
                                    (sisa <?= e(sisaWaktuPembayaran($secondsRemaining)) ?>).
                                    Pesanan dibatalkan otomatis jika belum dibayar dalam 2×24 jam sejak diverifikasi Admin.
                                <?php endif; ?>
                            <?php else: ?>
                                Batas waktu <?= $rejected ? 'unggah ulang bukti' : 'pembayaran' ?> telah lewat pada
                                <b><?= e(tanggal($order['batas_pembayaran'])) ?> WIB</b>.
                            <?php endif; ?>
                        </div>
                    <?php elseif ($waitingForPayment && $order['status_pembayaran'] === 'MENUNGGU_VERIFIKASI'): ?>
                        <div class="kotak-status kotak-tunggu">
                            <p>Bukti pembayaran Anda sedang menunggu verifikasi Admin.</p>
                        </div>
                    <?php elseif ($order['status_pesanan'] === 'DIKIRIM'): ?>
                        <div class="kotak-status kotak-tunggu">Sudah menerima paketnya? Konfirmasi agar pesanan ditandai selesai.</div>
                    <?php elseif ($order['status_pesanan'] === 'DIBATALKAN'): ?>
                        <div class="kotak-status kotak-tunggu">
                            Dibatalkan otomatis: tidak ada pembayaran dalam 2×24 jam sejak diverifikasi Admin.
                        </div>
                    <?php elseif ($order['status_pesanan'] === 'MENUNGGU_VERIFIKASI'): ?>
                        <div class="kotak-status kotak-tunggu">
                            <p>Pesanan Anda sedang diperiksa Admin. Tagihan resmi akan muncul setelah verifikasi.</p>
                        </div>
                    <?php endif; ?>

                    <div class="pesanan-aksi">
                        <div class="kiri">
                            <?php if ($order['status_pesanan'] === 'DIBATALKAN'): ?>
                                <button type="button" class="tombol tombol-garis" disabled>Lihat Detail</button>
                            <?php else: ?>
                                <a class="tombol tombol-garis" href="/?page=customer-order-detail&amp;id=<?= (int) $order['id_pesanan'] ?>">Lihat Detail</a>
                            <?php endif; ?>
                            <?php if ($needsPayment && $secondsRemaining > 0): ?>
                                <a class="tombol" href="/?page=customer-payment&amp;id=<?= (int) $order['id_pesanan'] ?>">
                                    <?= $order['status_pembayaran'] === 'DITOLAK' ? 'Unggah Ulang Bukti Pembayaran' : 'Bayar Sekarang' ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <nav class="pagination" id="paginationPesanan" aria-label="Halaman pesanan"></nav>
        </div>
    </main>

    <script src="/assets/js/customer/pesanan_saya.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/customer/pesanan_saya.js') ?>"></script>
    <?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
