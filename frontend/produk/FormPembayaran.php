<?php
require __DIR__ . '/config.php';

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
    flash('error', 'Pesanan tidak ditemukan.');
    redirect('CekPesanan.php');
}

$order = $_SESSION['pesanan'][$orderIndex];
$errors = [];
$history = $order['riwayat_bayar'] ?? [];
$payment = $history ? $history[array_key_last($history)] : [];
$values = [
    'tagihan_pokok' => (int)($order['tagihan_pokok'] ?? $order['tagihan'] ?? 0),
    'ongkir' => (int)($order['ongkir'] ?? 0),
    'diskon' => (int)($order['diskon'] ?? 0),
    'dibayar' => (int)($payment['jumlah'] ?? 0),
    'status' => ($payment['status'] ?? '') === 'Terverifikasi' || ($order['status'] ?? '') === 'Pembayaran Terverifikasi' ? 'Lunas' : 'Belum Lunas',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['tagihan_pokok', 'ongkir', 'diskon', 'dibayar'] as $field) {
        $values[$field] = (int)preg_replace('/\D/', '', (string)($_POST[$field] ?? ''));
    }
    $values['status'] = $_POST['status'] ?? 'Belum Lunas';
    if ($values['tagihan_pokok'] < 1) $errors[] = 'Tagihan pokok harus lebih besar dari 0.';
    if ($values['ongkir'] < 0 || $values['diskon'] < 0 || $values['dibayar'] < 0) $errors[] = 'Nilai pembayaran tidak boleh negatif.';
    if (!in_array($values['status'], ['Lunas', 'Belum Lunas'], true)) $errors[] = 'Status pembayaran tidak valid.';

    $total = max(0, $values['tagihan_pokok'] + $values['ongkir'] - $values['diskon']);
    if ($values['dibayar'] > $total) $errors[] = 'Nominal yang sudah dibayar tidak boleh melebihi total tagihan.';

    if (!$errors) {
        $_SESSION['pesanan'][$orderIndex]['tagihan_pokok'] = $values['tagihan_pokok'];
        $_SESSION['pesanan'][$orderIndex]['ongkir'] = $values['ongkir'];
        $_SESSION['pesanan'][$orderIndex]['diskon'] = $values['diskon'];
        $_SESSION['pesanan'][$orderIndex]['tagihan'] = $total;
        if (!empty($_SESSION['pesanan'][$orderIndex]['riwayat_bayar'])) {
            $paymentIndex = array_key_last($_SESSION['pesanan'][$orderIndex]['riwayat_bayar']);
            $_SESSION['pesanan'][$orderIndex]['riwayat_bayar'][$paymentIndex]['jumlah'] = $values['dibayar'];
            $_SESSION['pesanan'][$orderIndex]['riwayat_bayar'][$paymentIndex]['status'] = $values['status'] === 'Lunas' ? 'Terverifikasi' : 'Menunggu Verifikasi';
        }
        $_SESSION['pesanan'][$orderIndex]['status'] = $values['status'] === 'Lunas' ? 'Pembayaran Terverifikasi' : 'Menunggu Pembayaran';
        flash('success', 'Form pembayaran berhasil diperbarui.');
        redirect('FormPembayaran.php?kode=' . urlencode($kode));
    }
}

$total = max(0, $values['tagihan_pokok'] + $values['ongkir'] - $values['diskon']);
$pageTitle = 'Form Pembayaran';
$activeNav = 'payments';
$crumbs = [['Admin', null], ['Cek Pembayaran', 'CekPembayaran.php'], ['Form Pembayaran', null]];
require __DIR__ . '/partials/header.php';
?>
<a href="CekPembayaran.php" class="btn btn-light btn-back"><?= icon('arrow-l', 16) ?> Kembali ke List Pembayaran</a>
<div class="payment-detail-heading"><div><h1>Form Pembayaran Pesanan</h1><span class="payment-invoice-badge"><?= e($kode) ?></span></div></div>
<?php if ($errors): ?><div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="payment-detail-grid payment-form-grid">
    <section class="card payment-detail-card"><h2><?= icon('clipboard', 18) ?> Ringkasan Pesanan</h2><dl class="order-data-list"><div><dt>Nama Pelanggan</dt><dd><?= e($order['penerima'] ?? 'Pelanggan') ?></dd></div><div><dt>No. WhatsApp</dt><dd><?= e($order['telepon'] ?? '-') ?></dd></div><div><dt>Alamat Pengiriman</dt><dd><?= e($order['alamat'] ?? '-') ?></dd></div><div><dt>Produk</dt><dd><?= e($order['produk']['nama'] ?? 'Produk') ?></dd></div></dl></section>
    <section class="card payment-detail-card"><h2><?= icon('card', 18) ?> Form Pembayaran &amp; Kalkulasi</h2><form method="post" class="payment-form"><?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($kode) ?>"><div class="grid-3"><div class="field"><label for="tagihan_pokok">Tagihan Pokok (Rp)</label><input class="input" type="text" inputmode="numeric" id="tagihan_pokok" name="tagihan_pokok" data-rupiah value="<?= e(angka($values['tagihan_pokok'])) ?>"></div><div class="field"><label for="ongkir">Ongkos Kirim (Rp)</label><input class="input" type="text" inputmode="numeric" id="ongkir" name="ongkir" data-rupiah value="<?= e(angka($values['ongkir'])) ?>"></div><div class="field"><label for="diskon">Diskon / Potongan (Rp)</label><input class="input" type="text" inputmode="numeric" id="diskon" name="diskon" data-rupiah value="<?= e(angka($values['diskon'])) ?>"></div></div><div class="payment-total-box"><span>Total Tagihan (Otomatis)</span><strong><?= e(rupiah($total)) ?></strong><label for="dibayar">Nominal Sudah Dibayar (Rp)</label><input class="input" type="text" inputmode="numeric" id="dibayar" name="dibayar" data-rupiah value="<?= e(angka($values['dibayar'])) ?>"></div><fieldset class="payment-status-choice"><legend>Status Pembayaran</legend><label><input type="radio" name="status" value="Lunas" <?= $values['status'] === 'Lunas' ? 'checked' : '' ?>> Lunas</label><label><input type="radio" name="status" value="Belum Lunas" <?= $values['status'] === 'Belum Lunas' ? 'checked' : '' ?>> Belum Lunas</label></fieldset><div class="form-actions"><a class="btn btn-light" href="CekPembayaran.php">Batal</a><button type="submit" class="btn btn-primary btn-lg"><?= icon('check', 18) ?> Simpan Perubahan</button></div></form></section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
