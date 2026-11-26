<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PaymentModel.php';
require_once __DIR__ . '/../../models/OrderModel.php';
require_once __DIR__ . '/../../helpers/order_display.php';
require_once __DIR__ . '/../../helpers/storage.php';

$positiveInt = ['options' => ['min_range' => 1]];
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, $positiveInt);
$customer = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, $positiveInt);
$order = $id && $customer ? (new PaymentModel($pdo))->getCustomerOrder($id, $customer) : null;
if (!$order) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$detailUrl = '/?page=customer-order-detail&id=' . $id;
header('Cache-Control: private, no-store');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $newStatus = match ($_POST['intent'] ?? '') {
        'receive' => 'SELESAI',
        'cancel' => 'DIBATALKAN',
        default => null,
    };
    if (!$newStatus || !is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }
    try {
        $changed = (new OrderModel($pdo))->changeCustomerOrderStatus($id, $customer, $newStatus);
        $_SESSION['order_detail_notice'][$id] = $changed
            ? ($newStatus === 'SELESAI' ? 'Pesanan ditandai selesai.' : 'Pesanan dibatalkan.')
            : 'Status pesanan sudah berubah. Periksa kembali detailnya.';
    } catch (Throwable $error) {
        error_log('[customer-order-detail] ' . $error->getMessage());
        $_SESSION['order_detail_notice'][$id] = 'Perubahan gagal disimpan. Coba lagi.';
    }
    header('Location: ' . $detailUrl, true, 303);
    exit;
}

$notice = $_SESSION['order_detail_notice'][$id] ?? '';
unset($_SESSION['order_detail_notice'][$id]);
$payment = $order['payment'];
$proof = $payment['bukti_transfer'] ?? '';
if (($_GET['bukti'] ?? '') === '1') {
    if ($proof === '') {
        http_response_code(404);
        exit('Bukti pembayaran tidak ditemukan.');
    }
    try {
        $proofUrl = payment_proof_url($proof);
        header('Location: ' . $proofUrl, true, 303);
    } catch (Throwable $error) {
        error_log('[customer-payment-proof] ' . get_class($error));
        http_response_code(503);
        echo 'Bukti pembayaran belum dapat dibuka. Coba lagi.';
    }
    exit;
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$money = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
$status = $order['status_pesanan'];
$paymentStatus = $payment['status_pembayaran'] ?? '';
$paymentActive = in_array($paymentStatus, ['MENUNGGU_VERIFIKASI', 'TERVERIFIKASI'], true);
$awaitingPayment = $status === 'MENUNGGU_PEMBAYARAN' && !$paymentActive;
$secondsRemaining = $awaitingPayment
    ? (new DateTimeImmutable($order['batas_pembayaran'], new DateTimeZone('UTC')))->getTimestamp() - time()
    : 0;
$canPay = $awaitingPayment && $secondsRemaining > 0 && (int) $order['pembayaran_kedaluwarsa'] === 0;
$canCancel = !$paymentActive && in_array($status, ['MENUNGGU_VERIFIKASI', 'MENUNGGU_PEMBAYARAN'], true);
$paymentUrl = '/?page=customer-payment&id=' . $id;
$rejected = $paymentStatus === 'DITOLAK';
$invoice = $order['no_invoice'] ?: 'ORD-' . $id;
$shippingMethod = $order['metode_pengiriman'];
$instant = $shippingMethod === 'INSTANT_GOJEK';
$shippingName = match ($shippingMethod) {
    'INSTANT_GOJEK' => 'Gojek Instant',
    'EKSPEDISI_JNE' => 'JNE Ekspedisi',
    'EKSPEDISI_JNT' => 'J&T Ekspedisi',
    default => $shippingMethod,
};
$shippingReference = trim((string) ($instant ? $order['nomor_telepon_kurir'] : $order['nomor_resi']));
$courierPhone = preg_replace('/[^0-9+]/', '', $shippingReference);
$trackingUrl = match ($shippingMethod) {
    'EKSPEDISI_JNE' => 'https://jne.co.id/tracking-package',
    'EKSPEDISI_JNT' => 'https://jet.co.id/track?' . http_build_query(['bills' => $shippingReference, 'lang' => 'id-id']),
    default => '',
};
[$statusText, $statusColor] = match ($status) {
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi Admin', 'tunggu'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'bayar'],
    'DIPROSES' => ['Diproses', 'proses'],
    'DIKIRIM' => ['Dikirim', 'proses'],
    'SELESAI' => ['Selesai', 'selesai'],
    'DIBATALKAN' => ['Dibatalkan', 'tolak'],
    default => [$status, 'netral'],
};
[$paymentText, $paymentColor] = match ($paymentStatus) {
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi Pembayaran', 'tunggu'],
    'TERVERIFIKASI' => ['Terverifikasi', 'lunas'],
    'DITOLAK' => ['Pembayaran Ditolak', 'tolak'],
    default => match ($status) {
        'MENUNGGU_PEMBAYARAN' => ['Belum Dibayar', 'tunggu'],
        'DIBATALKAN' => ['Dibatalkan', 'netral'],
        default => ['Belum Ada Tagihan', 'netral'],
    },
};
[$shippingText, $shippingColor] = match ($status) {
    'DIPROSES' => ['Sedang Dikemas', 'proses'],
    'DIKIRIM' => ['Dikirim', 'bayar'],
    'SELESAI' => ['Telah Diterima', 'selesai'],
    'DIBATALKAN' => ['Tidak Dikirim', 'netral'],
    default => ['Belum Dikirim', 'netral'],
};
$paymentMethod = match ($payment['metode_pembayaran'] ?? '') {
    'TRANSFER_BCA' => 'Transfer BCA',
    'TRANSFER_BRI' => 'Transfer BRI',
    'TRANSFER_BSI' => 'Transfer BSI',
    'TRANSFER_MANDIRI' => 'Transfer Mandiri',
    default => 'Transfer bank (BCA, BRI, BSI, atau Mandiri)',
};
$subtotal = array_sum(array_map(static fn (array $item): float => (float) $item['harga_satuan'] * (int) $item['jumlah'], $order['items']));
$principal = (float) ($order['tagihan_pokok'] ?? $subtotal);
$total = max(0, $principal + (float) $order['ongkir'] - (float) $order['diskon']);
$final = $status !== 'MENUNGGU_VERIFIKASI' && ($status !== 'DIBATALKAN' || $order['tagihan_pokok'] !== null);
$verified = $order['tanggal_verifikasi_admin'] && ($final || $payment);
$proofType = match ($payment['bukti_mime'] ?? '') {
    'image/jpeg' => 'JPG',
    'image/png' => 'PNG',
    default => 'FILE',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Griya Sprei</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/customer/pesanan_saya.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/customer/pesanan_saya.css') ?>">
    <link rel="stylesheet" href="/assets/css/customer/detail_pesanan.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/customer/detail_pesanan.css') ?>">
</head>
<body class="pesanan-saya-page">
<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="detail-pesanan">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/?page=customer-home">Beranda</a> /
        <a href="/?page=customer-orders">Pesanan Saya</a> /
        <b aria-current="page"><?= $escape($invoice) ?></b>
    </nav>
    <a class="detail-pesanan-back" href="/?page=customer-orders">← Kembali ke Pesanan Saya</a>
    <header class="detail-pesanan-heading">
        <div>
            <h1>[<?= $escape($invoice) ?>]</h1>
            <p class="detail-muted">Dipesan: <?= $escape(tanggal($order['tanggal_pesan'])) ?> WIB</p>
        </div>
        <div class="detail-pesanan-badges">
            <span class="badge badge-<?= $statusColor ?>"><?= $escape($statusText) ?></span>
            <span class="badge badge-<?= $paymentColor ?>">Pembayaran: <?= $escape($paymentText) ?></span>
            <span class="badge badge-<?= $shippingColor ?>">Pengiriman: <?= $escape($shippingText) ?></span>
        </div>
    </header>

    <?php if ($notice !== ''): ?>
        <div class="kotak-status kotak-tunggu" role="status"><?= $escape($notice) ?></div>
    <?php endif; ?>
    <?php if ($awaitingPayment): ?>
        <div class="kotak-status <?= $rejected ? 'kotak-tolak' : 'kotak-tenggat' ?>">
            <?php if ($rejected): ?>
                <p>Bukti ditolak Admin<?= trim((string) $payment['catatan_admin']) !== '' ? ': ' . $escape($payment['catatan_admin']) : '.' ?></p>
            <?php endif; ?>
            <?php if ($canPay): ?>
                <strong><?= $rejected ? 'Unggah ulang sebelum' : 'Bayar sebelum' ?> <?= $escape(tanggal($order['batas_pembayaran'])) ?> WIB</strong>
                (sisa <?= $escape(sisaWaktuPembayaran($secondsRemaining)) ?>).
                <?= $rejected
                    ? 'Pesanan dibatalkan otomatis jika bukti belum diunggah ulang dalam 2×24 jam sejak ditolak Admin.'
                    : 'Pesanan dibatalkan otomatis jika belum dibayar dalam 2×24 jam sejak diverifikasi Admin.' ?>
            <?php else: ?>
                Batas waktu <?= $rejected ? 'unggah ulang bukti' : 'pembayaran' ?> sudah lewat.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="detail-pesanan-columns">
        <div>
            <section class="detail-pesanan-card">
                <h2>Rincian Produk</h2>
                <?php foreach ($order['items'] as $item): ?>
                    <?php $image = product_image_url($item['gambar_produk']); ?>
                    <div class="detail-pesanan-item">
                        <?php if ($image !== ''): ?>
                            <img class="detail-pesanan-thumb" src="<?= $escape($image) ?>" alt="">
                        <?php else: ?>
                            <span class="detail-pesanan-thumb" aria-hidden="true"></span>
                        <?php endif; ?>
                        <div>
                            <strong><?= $escape($item['nama_produk']) ?></strong>
                            <p class="detail-muted">
                                <?= $item['tipe_ukuran'] === 'CUSTOM' ? 'Custom' : 'Standar' ?>:
                                <?= (int) $item['panjang'] ?> × <?= (int) $item['lebar'] ?><?= $item['tinggi'] === null ? '' : ' × ' . (int) $item['tinggi'] ?> cm
                                · <?= (int) $item['jumlah'] ?> pcs
                            </p>
                            <?php if ($item['tambah_sarban'] > 0): ?><p class="detail-muted">+ <?= (int) $item['tambah_sarban'] ?> sarung bantal</p><?php endif; ?>
                            <?php if ($item['tambah_sargul'] > 0): ?><p class="detail-muted">+ <?= (int) $item['tambah_sargul'] ?> sarung guling</p><?php endif; ?>
                            <?php if (!empty($item['catatan_pesanan'])): ?><p class="detail-muted">Catatan: <?= $escape($item['catatan_pesanan']) ?></p><?php endif; ?>
                        </div>
                        <?php if ($final): ?>
                            <div class="detail-pesanan-price">
                                <small class="detail-muted"><?= $money($item['harga_satuan']) ?> × <?= (int) $item['jumlah'] ?></small>
                                <strong><?= $money($item['harga_satuan'] * $item['jumlah']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$order['items']): ?><p class="detail-muted">Produk tidak tersedia.</p><?php endif; ?>
            </section>

            <section class="detail-pesanan-card">
                <h2>Pembayaran</h2>
                <dl class="detail-pesanan-facts">
                    <dt>Status</dt><dd><span class="badge badge-<?= $paymentColor ?>"><?= $escape($paymentText) ?></span></dd>
                    <?php if ($payment): ?>
                        <dt>Metode</dt><dd><?= $escape($paymentMethod) ?></dd>
                        <dt>Nominal transfer</dt><dd><?= $money($payment['nominal_transfer']) ?></dd>
                        <?php if (!empty($payment['nama_pemilik_pengirim']) || !empty($payment['akhir_rekening'])): ?>
                            <dt>Pengirim</dt><dd><?= $escape($payment['nama_pemilik_pengirim']) ?><?= !empty($payment['akhir_rekening']) ? ' · rekening •••• ' . $escape($payment['akhir_rekening']) : '' ?></dd>
                        <?php endif; ?>
                        <dt>Diunggah</dt><dd><?= $escape(tanggal($payment['tanggal_upload'])) ?> WIB</dd>
                        <?php if ($paymentStatus === 'TERVERIFIKASI' && $payment['tanggal_verifikasi']): ?>
                            <dt>Diverifikasi Admin</dt><dd><?= $escape(tanggal($payment['tanggal_verifikasi'])) ?> WIB</dd>
                        <?php endif; ?>
                    <?php elseif ($awaitingPayment): ?>
                        <dt>Nominal yang harus dibayar</dt><dd><?= $money($total) ?></dd>
                        <dt>Metode</dt><dd><?= $escape($paymentMethod) ?></dd>
                    <?php endif; ?>
                    <dt>Bukti transfer</dt>
                    <dd class="detail-pesanan-proof">
                        <span class="detail-proof-icon" aria-hidden="true"><?= $proof !== '' ? $proofType : '+' ?></span>
                        <div>
                            <?= $proof !== '' ? $escape($payment['bukti_nama_asli'] ?: 'Bukti transfer') : 'Belum ada bukti diunggah' ?>
                            <small class="detail-muted"><?= $proof !== '' && !empty($payment['bukti_ukuran']) ? number_format($payment['bukti_ukuran'] / 1024, 0, ',', '.') . ' KB' : ($proof === '' ? 'Format JPG atau PNG' : '') ?></small>
                        </div>
                        <?php if ($proof !== ''): ?><a class="tombol tombol-garis" href="<?= $escape($detailUrl . '&bukti=1') ?>" target="_blank" rel="noopener">Lihat Bukti</a><?php endif; ?>
                        <?php if ($canPay): ?><a class="tombol" href="<?= $escape($paymentUrl) ?>"><?= $rejected ? 'Unggah Ulang' : 'Unggah Bukti' ?></a><?php endif; ?>
                    </dd>
                </dl>
            </section>

            <section class="detail-pesanan-card">
                <h2>Pengiriman</h2>
                <dl class="detail-pesanan-facts">
                    <dt>Status</dt><dd><span class="badge badge-<?= $shippingColor ?>"><?= $escape($shippingText) ?></span></dd>
                    <dt>Metode</dt><dd><?= $escape($shippingName) ?></dd>
                    <dt><?= $instant ? 'Telepon kurir' : 'Nomor resi' ?></dt>
                    <dd><?= $shippingReference !== '' ? $escape($shippingReference) : ($status === 'DIBATALKAN' ? '—' : 'Muncul setelah paket dikirim') ?></dd>
                    <dt>Penerima</dt><dd><?= $escape($order['nama_pelanggan']) ?><?= $order['telepon_pelanggan'] ? ' · ' . $escape($order['telepon_pelanggan']) : '' ?></dd>
                    <dt>Alamat</dt><dd><?= nl2br($escape($order['alamat_pengiriman'])) ?></dd>
                    <?php if (!empty($order['catatan_alamat'])): ?><dt>Catatan alamat</dt><dd><?= nl2br($escape($order['catatan_alamat'])) ?></dd><?php endif; ?>
                    <?php if (!empty($order['catatan_pengiriman'])): ?><dt>Catatan pengiriman</dt><dd><?= nl2br($escape($order['catatan_pengiriman'])) ?></dd><?php endif; ?>
                </dl>
            </section>
        </div>

        <aside>
            <section class="detail-pesanan-card">
                <h2>Ringkasan Tagihan</h2>
                <dl class="detail-pesanan-totals">
                    <?php if ($final): ?>
                        <div><dt>Subtotal produk</dt><dd><?= $money($principal) ?></dd></div>
                        <div><dt>Diskon</dt><dd>− <?= $money($order['diskon']) ?></dd></div>
                        <div><dt>Ongkir</dt><dd><?= $money($order['ongkir']) ?></dd></div>
                    <?php endif; ?>
                    <div class="detail-pesanan-total"><dt>Total</dt><dd><?= $final ? $money($total) : 'Belum ditetapkan' ?></dd></div>
                </dl>
                <div class="detail-pesanan-actions">
                    <?php if ($canPay): ?><a class="tombol" href="<?= $escape($paymentUrl) ?>"><?= $rejected ? 'Unggah Ulang Bukti' : 'Bayar Sekarang' ?></a><?php endif; ?>
                    <?php if ($status === 'DIKIRIM' || $canCancel): ?>
                        <form method="post" action="<?= $escape($detailUrl) ?>" onsubmit="return confirm('<?= $status === 'DIKIRIM' ? 'Konfirmasi paket sudah diterima?' : 'Batalkan pesanan ini?' ?>')">
                            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                            <button class="tombol <?= $status === 'DIKIRIM' ? '' : 'tombol-garis' ?>" name="intent" value="<?= $status === 'DIKIRIM' ? 'receive' : 'cancel' ?>">
                                <?= $status === 'DIKIRIM' ? 'Pesanan Diterima' : 'Batalkan Pesanan' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($status === 'DIKIRIM' && $shippingReference !== ''): ?>
                        <?php if ($instant && preg_match('/\d/', $courierPhone)): ?>
                            <a class="tombol tombol-garis" href="tel:<?= $escape($courierPhone) ?>">Hubungi Kurir</a>
                        <?php elseif ($trackingUrl !== ''): ?>
                            <a class="tombol tombol-garis" href="<?= $escape($trackingUrl) ?>" target="_blank" rel="noopener noreferrer">Lacak Paket</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($awaitingPayment && $final): ?><p class="detail-muted detail-summary-note">Tagihan sudah final dan ditetapkan oleh Admin.</p><?php endif; ?>
            </section>

            <section class="detail-pesanan-card">
                <h2>Riwayat Pesanan</h2>
                <ol class="detail-pesanan-timeline">
                    <li><strong>Pesanan dibuat</strong><span class="detail-muted"><?= $escape(tanggal($order['tanggal_pesan'])) ?> WIB</span></li>
                    <?php if ($verified): ?>
                        <li><strong>Diverifikasi Admin</strong><span class="detail-muted"><?= $escape(tanggal($order['tanggal_verifikasi_admin'])) ?> WIB · tagihan ditetapkan</span></li>
                    <?php endif; ?>
                    <?php if ($paymentStatus === 'TERVERIFIKASI'): ?>
                        <li><strong>Pembayaran terverifikasi</strong><?php if ($payment['tanggal_verifikasi']): ?><span class="detail-muted"><?= $escape(tanggal($payment['tanggal_verifikasi'])) ?> WIB</span><?php endif; ?></li>
                    <?php endif; ?>
                    <?php if (in_array($status, ['DIKIRIM', 'SELESAI'], true)): ?>
                        <li class="<?= $status === 'DIKIRIM' ? 'detail-timeline-current' : '' ?>"><strong>Dikirim</strong><?php if ($shippingReference !== ''): ?><span class="detail-muted"><?= $instant ? 'Kurir: ' : 'Resi ' ?><?= $escape($shippingReference) ?></span><?php endif; ?></li>
                    <?php endif; ?>
                    <?php if ($status !== 'DIKIRIM'): ?>
                        <li class="detail-timeline-current">
                            <strong><?= $escape($status === 'MENUNGGU_PEMBAYARAN' && $paymentActive ? $paymentText : $statusText) ?></strong>
                            <?php if ($awaitingPayment): ?><span class="detail-muted"><?= $rejected ? 'Batas unggah ulang' : 'Batas bayar' ?> <?= $escape(tanggal($order['batas_pembayaran'])) ?> WIB</span><?php endif; ?>
                        </li>
                    <?php endif; ?>
                </ol>
            </section>
        </aside>
    </div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
