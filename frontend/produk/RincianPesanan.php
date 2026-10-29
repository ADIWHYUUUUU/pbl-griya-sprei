<?php
require __DIR__ . '/config.php';

const DETAIL_ORDER_STATUSES = [
    'Menunggu Verifikasi',
    'Menunggu Pembayaran',
    'Pembayaran Terverifikasi',
    'Diproses (Sedang Dijahit)',
    'Selesai Dikirim',
];

$kode = trim($_POST['kode'] ?? $_GET['kode'] ?? '');
$orderIndex = null;
foreach ($_SESSION['pesanan'] ?? [] as $index => $savedOrder) {
    if (($savedOrder['kode'] ?? '') === $kode) {
        $orderIndex = $index;
        break;
    }
}

if ($orderIndex === null) {
    http_response_code(404);
    $pageTitle = 'Pesanan Tidak Ditemukan';
    $activeNav = 'orders';
    $crumbs = [['Admin', null], ['Cek Pesanan', 'CekPesanan.php'], ['Tidak Ditemukan', null]];
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="card order-not-found">
        <h1>Pesanan tidak ditemukan</h1>
        <p>Pesanan mungkin sudah tidak tersedia pada sesi ini.</p>
        <a class="btn btn-primary" href="CekPesanan.php">Kembali ke Cek Pesanan</a>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $newStatus = $_POST['status'] ?? '';
    $rawCharge = preg_replace('/\D/', '', (string)($_POST['tagihan'] ?? ''));
    $charge = $rawCharge === '' ? null : (int)$rawCharge;

    if (!in_array($newStatus, DETAIL_ORDER_STATUSES, true)) {
        $errors[] = 'Status pesanan tidak valid.';
    }
    if ($newStatus === 'Menunggu Pembayaran' && (!$charge || $charge < 1)) {
        $errors[] = 'Isi total tagihan sebelum mengirim invoice pembayaran.';
    }

    if (!$errors) {
        $_SESSION['pesanan'][$orderIndex]['status'] = $newStatus;
        if ($charge !== null) {
            $_SESSION['pesanan'][$orderIndex]['tagihan'] = $charge;
        }
        if ($newStatus === 'Menunggu Pembayaran') {
            $_SESSION['pesanan'][$orderIndex]['waktu_verifikasi'] = date('d M Y, H:i');
            $_SESSION['pesanan'][$orderIndex]['referensi'] = str_replace('/', '-', $kode);
        }
        flash('success', 'Status dan rincian pesanan berhasil diperbarui.');
        redirect('RincianPesanan.php?kode=' . urlencode($kode));
    }
}

$order = $_SESSION['pesanan'][$orderIndex];
$product = $order['produk'] ?? [];
$status = $order['status'] ?? 'Menunggu Verifikasi';
$statusClass = $status === 'Menunggu Verifikasi' ? 'waiting'
    : ($status === 'Selesai Dikirim' ? 'complete' : 'processing');
$image = $product['gambar'] ?? '';
$pageTitle = 'Rincian Pesanan';
$activeNav = 'orders';
$crumbs = [['Admin', null], ['Cek Pesanan', 'CekPesanan.php'], ['Rincian Pesanan', null]];
require __DIR__ . '/partials/header.php';
?>
<a href="CekPesanan.php" class="btn btn-light btn-back"><?= icon('arrow-l', 16) ?> Kembali ke Cek Pesanan</a>

<div class="order-detail-heading">
    <div>
        <h1>#<?= e($kode) ?></h1>
        <small><?= e($order['waktu'] ?? '') ?> WIB</small>
    </div>
    <span class="order-status order-status--<?= $statusClass ?>"><?= e($status) ?></span>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="order-detail-grid">
    <section class="card order-detail-card">
        <h2>Data Pelanggan</h2>
        <dl class="order-data-list">
            <div><dt>Nama</dt><dd><?= e($order['penerima'] ?? 'Pelanggan') ?></dd></div>
            <div><dt>WhatsApp</dt><dd><?= e($order['telepon'] ?? '-') ?></dd></div>
            <div><dt>Alamat</dt><dd><?= e($order['alamat'] ?? '-') ?></dd></div>
            <?php if (!empty($order['catatan_alamat'])): ?><div><dt>Catatan Alamat</dt><dd><?= e($order['catatan_alamat']) ?></dd></div><?php endif; ?>
            <div><dt>Kurir</dt><dd><?= e($order['metode'] ?? '-') ?></dd></div>
        </dl>
    </section>

    <section class="card order-detail-card">
        <h2>Spesifikasi Pesanan</h2>
        <div class="order-product">
            <?php if ($image !== ''): ?>
                <img src="../<?= e(ltrim($image, '/')) ?>" alt="<?= e($product['nama'] ?? 'Foto produk') ?>">
            <?php else: ?>
                <span class="order-product-placeholder"><?= icon('image', 24) ?></span>
            <?php endif; ?>
            <div>
                <strong><?= e($product['nama'] ?? 'Produk') ?></strong>
                <small><?= ($product['jenis'] ?? '') === 'custom' ? 'Custom Size' : 'Ukuran Standar' ?></small>
            </div>
        </div>
        <dl class="order-data-list order-spec-list">
            <div><dt>Ukuran Kasur</dt><dd><?= e($product['ukuran'] ?? '-') ?></dd></div>
            <div><dt>Kelengkapan</dt><dd><?= e($product['tambahan'] ?? '-') ?></dd></div>
            <?php if (!empty($product['tambahan'])): ?><div><dt>Jumlah</dt><dd><?= (int)($product['jumlah'] ?? 1) ?> set</dd></div><?php endif; ?>
            <?php if (!empty($product['catatan']) && $product['catatan'] !== '-'): ?>
                <div class="order-note"><dt>Catatan Pelanggan</dt><dd>“<?= e($product['catatan']) ?>”</dd></div>
            <?php endif; ?>
            <?php if (!empty($order['catatan_kirim'])): ?><div class="order-note"><dt>Catatan Pengiriman</dt><dd><?= e($order['catatan_kirim']) ?></dd></div><?php endif; ?>
        </dl>
    </section>
</div>

<section class="card order-update-card" id="tagihan">
    <h2>Verifikasi &amp; Pembaruan Pesanan</h2>
    <form method="post" class="order-update-form">
        <?= csrf_field() ?>
        <input type="hidden" name="kode" value="<?= e($kode) ?>">
        <div class="order-update-fields">
            <div class="field">
                <label for="tagihan">Total Tagihan (Rp)</label>
                <input class="input" type="text" inputmode="numeric" id="tagihan" name="tagihan" data-rupiah value="<?= isset($order['tagihan']) ? e(angka((int)$order['tagihan'])) : '' ?>" placeholder="Contoh: 215.000">
                <small>Wajib diisi saat status diubah menjadi Menunggu Pembayaran.</small>
            </div>
            <div class="field">
                <label for="status">Status Pesanan</label>
                <div class="select-wrap">
                    <select class="input" id="status" name="status">
                        <?php foreach (DETAIL_ORDER_STATUSES as $option): ?>
                            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= icon('chev-d', 16) ?>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= icon('check', 18) ?> Simpan Perubahan</button>
        </div>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
