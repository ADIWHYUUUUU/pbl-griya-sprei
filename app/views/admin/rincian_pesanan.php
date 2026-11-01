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

require_once __DIR__ . '/../../controllers/AdminOrderController.php';

$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$order = (new AdminOrderController())->detail($orderId === false ? null : $orderId);
if (!$order) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$statusLabels = [
    'MENUNGGU_VERIFIKASI' => ['Perlu Verifikasi', 'waiting'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'waiting'],
    'DIPROSES' => ['Diproses', 'processing'],
    'DIKIRIM' => ['Dikirim', 'processing'],
    'SELESAI' => ['Selesai', 'complete'],
    'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
];
$status = $statusLabels[$order['status_pesanan']] ?? [$order['status_pesanan'], 'other'];
$couriers = [
    'INSTANT_GOJEK' => 'Gojek Instant',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$phone = preg_replace('/\D+/', '', (string) ($order['telepon'] ?? ''));
$whatsappPhone = $phone !== '' && str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
$timestamp = strtotime((string) $order['tanggal_pesan']);
$dateLabel = $timestamp ? date('d M Y, H:i', $timestamp) . ' WIB' : (string) $order['tanggal_pesan'];
$formatCount = static function (int $count, string $label): string {
    return $count . ' ' . $label . ($count === 1 ? '' : '');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Pesanan - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/rincian_pesanan.css">
</head>
<body class="admin-dashboard admin-order-page">
<div class="admin-layout">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="dashboard-main admin-order-main">
        <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="order-breadcrumb"><span>Admin</span><span aria-hidden="true">›</span><a href="/?page=admin-orders">Cek Pesanan</a><span aria-hidden="true">›</span><strong>Rincian Pesanan</strong></div>

        <header class="order-page-header">
            <a class="back-link" href="/?page=admin-orders">&larr; Kembali ke Cek Pesanan</a>
            <div class="order-title-row">
                <h1>#<?= $escape($order['no_invoice'] ?: 'ORD-' . $order['id_pesanan']) ?></h1>
                <small><?= $escape($dateLabel) ?></small>
                <span class="order-badge order-badge--<?= $escape($status[1]) ?>"><?= $escape($status[0]) ?></span>
            </div>
        </header>

        <div class="order-detail-grid">
            <section class="detail-card customer-card">
                <h2>Data Pelanggan</h2>
                <dl class="detail-list">
                    <div><dt>Nama</dt><dd><?= $escape($order['nama_pelanggan']) ?></dd></div>
                    <div>
                        <dt>WhatsApp</dt>
                        <dd>
                            <?php if ($whatsappPhone !== ''): ?>
                                <a class="whatsapp-link" href="https://wa.me/<?= $escape($whatsappPhone) ?>" target="_blank" rel="noopener noreferrer">
                                    <span aria-hidden="true">◉</span> <?= $escape($order['telepon']) ?>
                                </a>
                            <?php else: ?>-<?php endif; ?>
                        </dd>
                    </div>
                    <div><dt>Alamat</dt><dd><?= nl2br($escape(trim((string) $order['alamat_pengiriman'] . "\n" . (string) $order['catatan_alamat']))) ?></dd></div>
                    <div><dt>Kurir</dt><dd><?= $escape($couriers[$order['metode_pengiriman']] ?? $order['metode_pengiriman']) ?></dd></div>
                </dl>
            </section>

            <section class="detail-card specification-card">
                <h2>Spesifikasi Pesanan</h2>
                <?php foreach ($order['items'] as $item): ?>
                    <?php
                    $imageUrl = !empty($item['gambar_produk'])
                        ? '/uploads/produk/' . rawurlencode(basename((string) $item['gambar_produk']))
                        : '';
                    $extras = [];
                    if ((int) $item['tambah_sarban'] > 0) {
                        $extras[] = '+' . (int) $item['tambah_sarban'] . ' Sarung Bantal';
                    }
                    if ((int) $item['tambah_sargul'] > 0) {
                        $extras[] = '+' . (int) $item['tambah_sargul'] . ' Sarung Guling';
                    }
                    $notes = trim((string) ($item['catatan_pesanan'] ?? ''));
                    if (trim((string) ($order['catatan_pengiriman'] ?? '')) !== '') {
                        $notes .= ($notes === '' ? '' : "\n") . 'Catatan Pengiriman: ' . $order['catatan_pengiriman'];
                    }
                    ?>
                    <article class="order-item">
                        <div class="item-heading">
                            <?php if ($imageUrl !== ''): ?>
                                <img src="<?= $escape($imageUrl) ?>" alt="<?= $escape($item['nama_produk']) ?>">
                            <?php else: ?>
                                <div class="item-image-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                            <div><h3><?= $escape($item['nama_produk']) ?></h3><span><?= $item['tipe_ukuran'] === 'CUSTOM' ? 'Custom Size' : 'Standar' ?></span></div>
                        </div>
                        <dl class="item-specs">
                            <div><dt>Ukuran Kasur</dt><dd><?= (int) $item['panjang'] ?> × <?= (int) $item['lebar'] ?> × <?= (int) $item['tinggi'] ?> cm</dd></div>
                            <div><dt>Kelengkapan</dt><dd><?= $formatCount((int) $item['jumlah'], 'Sprei') ?><?= (int) $item['tambah_sarban'] > 0 ? ' + ' . (int) $item['tambah_sarban'] . ' Sarban' : '' ?><?= (int) $item['tambah_sargul'] > 0 ? ' + ' . (int) $item['tambah_sargul'] . ' Sargul' : '' ?></dd></div>
                            <div><dt>Tambahan</dt><dd><?= $extras ? $escape(implode(', ', $extras)) : '-' ?></dd></div>
                        </dl>
                        <div class="customer-note"><strong>Catatan Pelanggan:</strong><p><?= $notes !== '' ? nl2br($escape($notes)) : '-' ?></p></div>
                    </article>
                <?php endforeach; ?>
            </section>
        </div>
    </main>
</div>
<script src="/assets/js/admin/sidebar.js"></script>
</body>
</html>
