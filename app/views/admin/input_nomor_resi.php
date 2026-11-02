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
require_once __DIR__ . '/../../models/OrderModel.php';

$orderId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$order = null;
if ($orderId !== false && $orderId > 0) {
    $order = (new OrderModel($pdo))->getAdminOrderDetail($orderId);
}
if ($order === null) {
    $order = [
        'id_pesanan' => 5,
        'no_invoice' => 'INV/202601013/SP/003',
        'tanggal_pesan' => '2026-10-13 14:10:00',
        'status_pesanan' => 'DIPROSES',
        'nomor_resi' => '',
        'alamat_pengiriman' => 'Jl. Mawar No.12, Lowokwaru, Kota Malang, Jawa Timur',
        'catatan_alamat' => 'Rumah warna putih',
        'metode_pengiriman' => 'EKSPEDISI_JNE',
        'catatan_pengiriman' => '',
        'nama_pelanggan' => 'Maya Salsabila',
        'telepon' => '0878-1122-9099',
        'items' => [[
            'nama_produk' => 'Sprei Katun Motif Katak',
            'gambar_produk' => '',
            'tipe_ukuran' => 'STANDARD',
            'jumlah' => 1,
            'panjang' => 120,
            'lebar' => 200,
            'tinggi' => 25,
            'tambah_sarban' => 0,
            'tambah_sargul' => 0,
        ]],
    ];
}

$normalizeStatus = static function ($status): array {
    $map = [
        'MENUNGGU_VERIFIKASI' => ['Perlu Verifikasi', 'waiting'],
        'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'waiting'],
        'DIPROSES' => ['Diproses', 'processing'],
        'DIKIRIM' => ['Dikirim', 'processing'],
        'SELESAI' => ['Selesai', 'complete'],
        'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
    ];
    return $map[$status] ?? [$status ?: 'Belum ada status', 'other'];
};

$status = $normalizeStatus((string) ($order['status_pesanan'] ?? 'DIPROSES'));
$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$phone = preg_replace('/\D+/', '', (string) ($order['telepon'] ?? ''));
$whatsappPhone = $phone !== '' && str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
$deliveryDate = date('d F Y', strtotime((string) ($order['tanggal_pesan'] ?? 'now')));
$item = $order['items'][0] ?? null;
$image = $item['gambar_produk'] ?? '';
if ($image !== '') {
    $image = '/uploads/produk/' . rawurlencode(basename((string) $image));
}
$tracking = trim((string) ($order['nomor_resi'] ?? ''));
$customerAddress = trim((string) ($order['alamat_pengiriman'] ?? ''));
$customerAddress .= $customerAddress !== '' && trim((string) ($order['catatan_alamat'] ?? '')) !== '' ? '\n' . trim((string) ($order['catatan_alamat'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nomor Resi - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/input_nomor_resi.css">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="page-breadcrumb"><a href="/?page=admin-resi-list">&larr; Kembali ke Daftar</a> <span aria-hidden="true">›</span> <strong>Input Nomor Resi</strong></div>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="flash-message <?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

            <h1 class="page-title">Input Nomor Resi Pengiriman</h1>

            <div class="form-card">
                <div class="order-meta">
                    <strong><?= $escape($order['no_invoice'] ?? 'INV/000000') ?></strong>
                    <span><?= $escape($deliveryDate) ?></span>
                    <span class="badge-courier"><?= $escape($status[0]) ?></span>
                </div>

                <div class="order-brief">
                    <?php if ($image !== ''): ?>
                        <div class="product-image"><img src="<?= $escape($image) ?>" alt="<?= $escape($item['nama_produk'] ?? 'Produk') ?>"></div>
                    <?php else: ?>
                        <div class="product-image" aria-hidden="true"></div>
                    <?php endif; ?>
                    <div class="product-meta">
                        <strong><?= $escape($item['nama_produk'] ?? 'Produk') ?></strong>
                        <span><?= $escape(($item['tipe_ukuran'] ?? 'STANDARD') === 'CUSTOM' ? 'Custom Size' : 'Custom Size') ?> • <?= (int) ($item['panjang'] ?? 0) ?> × <?= (int) ($item['lebar'] ?? 0) ?> × <?= (int) ($item['tinggi'] ?? 0) ?> cm</span>
                    </div>
                </div>

                <div class="customer-card">
                    <div class="customer-meta">
                        <div>
                            <div><strong><?= $escape($order['nama_pelanggan'] ?? 'Pelanggan') ?></strong></div>
                            <div><?= $escape($order['telepon'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="badge-courier"><?= $escape(($order['metode_pengiriman'] ?? '') === 'INSTANT_GOJEK' ? 'GoJek Instant' : (($order['metode_pengiriman'] ?? '') === 'EKSPEDISI_JNT' ? 'J&T Express' : 'JNE Express')) ?></div>
                </div>

                <form action="/?action=admin-order-resi-save" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_pesanan" value="<?= (int) ($order['id_pesanan'] ?? $orderId ?? 0) ?>">

                    <div class="section-header">
                        <div>Rincian Pengiriman</div>
                        <button type="button" class="badge-courier" style="color:#2f5f59; background:#eef5f0;">Wajib Diisi</button>
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="field field-wide">
                                <label for="nomor_resi">Nomor Resi Pengiriman</label>
                                <input id="nomor_resi" name="nomor_resi" type="text" value="<?= $escape($tracking) ?>" placeholder="CONTOH: JNE882910481290" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="catatan_pengiriman">Catatan Pengiriman</label>
                            <textarea id="catatan_pengiriman" name="catatan_pengiriman" placeholder="Masukkan catatan pengiriman (opsional)"><?= $escape((string) ($order['catatan_pengiriman'] ?? '')) ?></textarea>
                        </div>

                        <div class="note-row">
                            <input class="checkbox" type="checkbox" id="konfirmasi" required>
                            <label for="konfirmasi">Konfirmasi Standar Operasional Pengiriman (SOP): Status pesanan otomatis diperbarui menjadi dikirim dan nomor resi dapat disimpan.</label>
                        </div>
                    </div>

                    <div class="footer-actions">
                        <a class="button secondary" href="/?page=admin-resi-list">Batal</a>
                        <button type="submit" class="button primary">Simpan &amp; Konfirmasi Pengiriman</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script src="/assets/js/admin/input_nomor_resi.js"></script>
</body>
</html>
