<?php
require __DIR__ . '/config.php';

$kode = trim($_POST['kode'] ?? $_GET['kode'] ?? '');
$orderIndex = null;
foreach ($_SESSION['pesanan'] ?? [] as $index => $order) {
    if (($order['kode'] ?? '') === $kode) {
        $orderIndex = $index;
        break;
    }
}

if ($orderIndex === null) {
    http_response_code(404);
    $pageTitle = 'Pembayaran Tidak Ditemukan';
    $activeNav = 'payments';
    $crumbs = [['Admin', null], ['Cek Pembayaran', 'CekPembayaran.php'], ['Tidak Ditemukan', null]];
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="card order-not-found">
        <h1>Data pembayaran tidak ditemukan</h1>
        <p>Pesanan tidak tersedia pada sesi ini.</p>
        <a class="btn btn-primary" href="CekPembayaran.php">Kembali ke Cek Pembayaran</a>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$order = $_SESSION['pesanan'][$orderIndex];
$history = $order['riwayat_bayar'] ?? [];
$paymentIndex = array_key_last($history);
if ($paymentIndex === null || ($history[$paymentIndex]['status'] ?? '') !== 'Menunggu Verifikasi') {
    flash('error', 'Bukti pembayaran ini sudah diverifikasi atau tidak tersedia.');
    redirect('CekPembayaran.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['aksi'] ?? '';
    $note = trim($_POST['catatan_admin'] ?? '');

    if (!in_array($action, ['verifikasi', 'tolak'], true)) {
        $errors[] = 'Aksi verifikasi tidak valid.';
    } elseif ($action === 'tolak' && $note === '') {
        $errors[] = 'Tuliskan alasan penolakan bukti pembayaran.';
    }

    if (!$errors) {
        $_SESSION['pesanan'][$orderIndex]['riwayat_bayar'][$paymentIndex]['status'] = $action === 'verifikasi' ? 'Terverifikasi' : 'Ditolak';
        $_SESSION['pesanan'][$orderIndex]['riwayat_bayar'][$paymentIndex]['waktu_verifikasi'] = date('d M Y, H:i');

        if ($action === 'verifikasi') {
            $_SESSION['pesanan'][$orderIndex]['status'] = 'Pembayaran Terverifikasi';
            unset($_SESSION['pesanan'][$orderIndex]['catatan_admin']);
            flash('success', 'Pembayaran berhasil diverifikasi.');
        } else {
            $_SESSION['pesanan'][$orderIndex]['status'] = 'Bukti Pembayaran Ditolak';
            $_SESSION['pesanan'][$orderIndex]['catatan_admin'] = $note;
            flash('success', 'Bukti pembayaran ditolak. Pelanggan dapat mengunggah bukti baru.');
        }

        redirect('CekPembayaran.php');
    }
}

$order = $_SESSION['pesanan'][$orderIndex];
$payment = $order['riwayat_bayar'][$paymentIndex];
$product = $order['produk'] ?? [];
$path = (string)($payment['path'] ?? '');
$safePath = $path !== '' && preg_match('~\Auploads/payments/[a-f0-9]{32}\.(?:jpg|png|pdf)\z~', $path) ? $path : '';
$proofExists = $safePath !== '' && is_file(dirname(__DIR__) . '/' . $safePath);
$mime = $payment['mime'] ?? '';
$pageTitle = 'Verifikasi Pembayaran';
$activeNav = 'payments';
$crumbs = [['Admin', null], ['Cek Pembayaran', 'CekPembayaran.php'], ['Verifikasi Pembayaran', null]];
require __DIR__ . '/partials/header.php';
?>
<div class="payment-detail-heading">
    <div>
        <h1>Verifikasi Pembayaran</h1>
        <span class="payment-invoice-badge"><?= e($kode) ?></span>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="payment-detail-grid">
    <section class="card payment-detail-card">
        <h2><?= icon('clipboard', 18) ?> Detail Pesanan &amp; Pelanggan</h2>
        <div class="payment-customer-grid">
            <div><small>Nama Pelanggan</small><strong><?= e($order['penerima'] ?? 'Pelanggan') ?></strong></div>
            <div><small>No. Telp / WhatsApp</small><strong><?= e($order['telepon'] ?? '-') ?></strong></div>
        </div>
        <div class="payment-detail-row">
            <small>Alamat Pengiriman</small>
            <p><?= e($order['alamat'] ?? '-') ?></p>
        </div>
        <div class="payment-detail-row">
            <small>Spesifikasi Pesanan</small>
            <strong><?= e($product['nama'] ?? 'Produk') ?> (<?= e(($product['jenis'] ?? '') === 'custom' ? 'Custom ' : 'Standar ') ?><?= e($product['ukuran'] ?? '-') ?>)</strong>
            <?php if (!empty($product['tambahan']) && $product['tambahan'] !== '-'): ?><p>+ <?= e($product['tambahan']) ?></p><?php endif; ?>
        </div>
        <?php if (!empty($product['catatan']) && $product['catatan'] !== '-'): ?>
            <div class="payment-customer-note">
                <small>Catatan Pembeli</small>
                <p>“<?= e($product['catatan']) ?>”</p>
            </div>
        <?php endif; ?>
        <div class="payment-charge">
            <span>Total Tagihan</span>
            <strong><?= e(rupiah((int)($order['tagihan'] ?? 0))) ?></strong>
        </div>
    </section>

    <section class="card payment-detail-card payment-proof-card">
        <h2><?= icon('card', 18) ?> Bukti Transfer</h2>
        <div class="payment-file">
            <span class="payment-file-icon"><?= icon('file', 20) ?></span>
            <div>
                <strong><?= e($payment['file'] ?? 'File bukti transfer') ?></strong>
                <small><?= e(strtoupper(pathinfo((string)($payment['file'] ?? ''), PATHINFO_EXTENSION))) ?> · <?= e($payment['waktu'] ?? '') ?></small>
            </div>
            <?php if ($proofExists): ?><a href="../<?= e($safePath) ?>" target="_blank" rel="noopener">Buka</a><?php endif; ?>
        </div>
        <?php if ($proofExists && $mime === 'application/pdf'): ?>
            <iframe class="payment-proof-pdf" src="../<?= e($safePath) ?>" title="Bukti pembayaran PDF"></iframe>
        <?php elseif ($proofExists && str_starts_with($mime, 'image/')): ?>
            <a class="payment-proof-preview" href="../<?= e($safePath) ?>" target="_blank" rel="noopener">
                <img src="../<?= e($safePath) ?>" alt="Bukti transfer dari pelanggan">
            </a>
        <?php else: ?>
            <div class="payment-proof-missing">File bukti lama tidak tersimpan. Minta pelanggan mengunggah ulang bukti transfer untuk melihat lampirannya.</div>
        <?php endif; ?>
        <?php if (!empty($payment['bank']) || !empty($payment['nama_pengirim']) || !empty($payment['rekening_pengirim'])): ?>
            <dl class="payment-transfer-data">
                <div><dt>Metode Bayar</dt><dd>Transfer <?= e($payment['bank'] ?? 'Bank') ?></dd></div>
                <div><dt>Nama Pemilik Rekening</dt><dd><?= e($payment['nama_pengirim'] ?? '-') ?></dd></div>
                <div><dt>No. Rekening Pengirim</dt><dd><?= e($payment['rekening_pengirim'] ?? '-') ?></dd></div>
            </dl>
        <?php endif; ?>
        <div class="payment-proof-amount">
            <small>Nominal Bukti Transfer</small>
            <strong><?= e(rupiah((int)($payment['jumlah'] ?? 0))) ?></strong>
            <span><?= (int)($payment['jumlah'] ?? 0) === (int)($order['tagihan'] ?? 0) ? 'Nominal sesuai dengan tagihan' : 'Nominal berbeda dari tagihan' ?></span>
        </div>
        <form method="post" class="payment-review-actions" id="paymentReviewForm">
            <?= csrf_field() ?>
            <input type="hidden" name="kode" value="<?= e($kode) ?>">
            <input type="hidden" name="aksi" id="paymentAction" value="">
            <button type="button" class="btn btn-light" id="rejectPayment"><?= icon('arrow-l', 16) ?> Tolak Bukti</button>
            <button type="button" class="btn btn-primary" id="approvePayment"><?= icon('check', 16) ?> Verifikasi</button>
        </form>
        <div class="payment-rejection" id="rejectionBox" hidden>
            <label for="rejectionNote">Alasan penolakan</label>
            <textarea class="input" id="rejectionNote" name="catatan_admin" form="paymentReviewForm" rows="3" maxlength="500" placeholder="Jelaskan mengapa bukti pembayaran ditolak"></textarea>
            <button type="button" class="btn btn-danger" id="confirmRejection">Kirim Penolakan</button>
        </div>
    </section>
</div>
<a href="CekPembayaran.php" class="btn btn-white payment-back"><?= icon('arrow-l', 16) ?> Kembali</a>
<script>
    (function () {
        var form = document.getElementById('paymentReviewForm');
        var action = document.getElementById('paymentAction');
        var rejection = document.getElementById('rejectionBox');
        document.getElementById('approvePayment').addEventListener('click', function () {
            action.value = 'verifikasi';
            form.requestSubmit();
        });
        document.getElementById('rejectPayment').addEventListener('click', function () {
            rejection.hidden = false;
            document.getElementById('rejectionNote').focus();
        });
        document.getElementById('confirmRejection').addEventListener('click', function () {
            action.value = 'tolak';
            form.requestSubmit();
        });
    })();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>