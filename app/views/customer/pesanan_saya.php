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

function tanggal($value): string
{
    $timestamp = new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
    return str_replace(
        ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        $timestamp->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d M Y, H:i')
    );
}

$customerId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($customerId === false || $customerId === null) {
    http_response_code(403);
    exit('Akun customer tidak valid.');
}

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
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'tunggu'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'bayar'],
    'DIPROSES' => ['Diproses', 'bayar'],
    'DIKIRIM' => ['Dikirim', 'bayar'],
    'SELESAI' => ['Selesai Dikirim', 'selesai'],
    'DIBATALKAN' => ['Dibatalkan', 'tolak'],
];
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
$dataModal = [];
foreach ($orders as &$order) {
    [$statusLabel, $statusClass] = $orderStatusLabels[$order['status_pesanan']]
        ?? [$order['status_pesanan'], 'tunggu'];

    if ($order['status_pembayaran'] === 'DITOLAK') {
        $statusLabel = 'Bukti Pembayaran Ditolak';
        $statusClass = 'tolak';
    } elseif (
        $order['status_pesanan'] === 'MENUNGGU_PEMBAYARAN'
        && $order['status_pembayaran'] === 'MENUNGGU_VERIFIKASI'
    ) {
        $statusLabel = 'Menunggu Verifikasi Pembayaran';
        $statusClass = 'tunggu';
    }

    $productNames = [];
    $productSpecs = [];
    $extras = [];
    $itemNotes = [];
    foreach ($order['produk'] as $product) {
        $productNames[] = $product['nama'];
        $productSpecs[] = sprintf(
            '%s: %d × %d × %d cm · %d pcs',
            $product['tipe_ukuran'] === 'CUSTOM' ? 'Custom' : 'Standar',
            $product['panjang'],
            $product['lebar'],
            $product['tinggi'],
            $product['jumlah']
        );
        if ($product['tambah_sarban'] > 0) {
            $extras[] = $product['tambah_sarban'] . ' sarung bantal';
        }
        if ($product['tambah_sargul'] > 0) {
            $extras[] = $product['tambah_sargul'] . ' sarung guling';
        }
        if ($product['catatan'] !== '') {
            $itemNotes[] = $product['nama'] . ': ' . $product['catatan'];
        }
    }

    $order['status_label'] = $statusLabel;
    $order['status_class'] = $statusClass;
    $order['tagihan_final'] = !in_array($order['status_pesanan'], ['MENUNGGU_VERIFIKASI', 'DIBATALKAN'], true);
    $order['lunas'] = $order['status_pembayaran'] === 'TERVERIFIKASI'
        || in_array($order['status_pesanan'], ['DIPROSES', 'DIKIRIM', 'SELESAI'], true);
    $order['nama_produk'] = $productNames === [] ? 'Produk tidak tersedia' : implode(', ', $productNames);
    $order['spesifikasi'] = implode("\n", $productSpecs);
    $order['tambahan'] = $extras === [] ? '-' : implode(', ', array_unique($extras));
    $order['catatan_produk'] = $itemNotes === [] ? '-' : implode("\n", $itemNotes);
    $order['gambar'] = '';
    foreach ($order['produk'] as $product) {
        if ($product['gambar'] !== '') {
            $order['gambar'] = '/uploads/produk/' . rawurlencode(basename($product['gambar']));
            break;
        }
    }

    $dataModal[$order['kode']] = [
        'kode' => $order['kode'],
        'status' => $statusLabel,
        'tagihan' => $order['tagihan_final'] ? rupiah($order['total']) : 'Belum ditetapkan',
        'produk' => $order['nama_produk'],
        'spesifikasi' => $order['spesifikasi'],
        'tambahan' => $order['tambahan'],
        'catatan_produk' => $order['catatan_produk'],
        'alamat' => $order['alamat_pengiriman'],
        'catatan_alamat' => $order['catatan_alamat'],
        'pengiriman' => $shippingLabels[$order['metode_pengiriman']] ?? $order['metode_pengiriman'],
        'catatan_pengiriman' => $order['catatan_pengiriman'],
        'resi' => $order['nomor_resi'],
        'status_pembayaran' => match ($order['status_pembayaran']) {
            'MENUNGGU_VERIFIKASI' => 'Menunggu verifikasi pembayaran',
            'TERVERIFIKASI' => 'Terverifikasi',
            'DITOLAK' => 'Ditolak',
            default => 'Belum ada pembayaran',
        },
        'metode_pembayaran' => $paymentMethodLabels[$order['metode_pembayaran']] ?? '-',
        'nominal_transfer' => $order['nominal_transfer'] === null
            ? '-'
            : rupiah($order['nominal_transfer']),
        'catatan_admin' => $order['catatan_admin'],
    ];
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/customer/pesanan_saya.css">
</head>
<body>
    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="isi">
            <nav class="breadcrumb">
                <a href="/?page=customer-home">Beranda</a> /
                <b>Pesanan Saya</b>
            </nav>

            <h1 class="judul-halaman">Pesanan Saya</h1>
            <p class="sub-judul">Pantau status pesanan, tagihan, dan pembayaran Anda.</p>

            <form method="get" action="/" class="kotak-cari kartu" role="search">
                <input type="hidden" name="page" value="customer-orders">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                </svg>
                <label class="sr-only" for="cariPesanan">Cari nomor invoice atau nama produk</label>
                <input id="cariPesanan" type="search" name="cari" placeholder="Cari nomor invoice atau nama produk..." value="<?= e($cari) ?>">
                <button type="submit" class="tombol">Cari</button>
            </form>

            <?php if ($orders === []): ?>
                <div class="kartu kosong">
                    <p><?= $cari === '' ? 'Anda belum memiliki pesanan.' : 'Pesanan tidak ditemukan.' ?></p>
                    <?php if ($cari !== ''): ?>
                        <a href="/?page=customer-orders" class="tombol">Tampilkan Semua</a>
                    <?php else: ?>
                        <a href="/?page=customer-catalog" class="tombol">Mulai Belanja</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($orders as $order): ?>
                <article class="kartu pesanan">
                    <div class="pesanan-kepala">
                        <div class="info-waktu">
                            <b>[<?= e($order['kode']) ?>]</b>
                            <span>· Dipesan: <?= e(tanggal($order['tanggal_pesan'])) ?> WIB</span>
                        </div>
                        <span class="badge badge-<?= e($order['status_class']) ?>"><?= e($order['status_label']) ?></span>
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
                                    <?= (int) $product['panjang'] ?> × <?= (int) $product['lebar'] ?> × <?= (int) $product['tinggi'] ?> cm
                                    · <?= (int) $product['jumlah'] ?> pcs
                                </p>
                            <?php endforeach; ?>
                            <?php if ($order['tambahan'] !== '-'): ?>
                                <p><small>Kelengkapan:</small> <?= e($order['tambahan']) ?></p>
                            <?php endif; ?>
                            <?php if ($order['nomor_resi'] !== ''): ?>
                                <p><small>Resi:</small> <?= e($order['nomor_resi']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="info-tagihan">
                            <?php if ($order['tagihan_final']): ?>
                                <small>Total Tagihan</small>
                                <b class="nominal"><?= e(rupiah($order['total'])) ?></b>
                                <?php if ($order['lunas']): ?>
                                    <small class="lunas">✓ Pembayaran terverifikasi</small>
                                <?php elseif ($order['status_pembayaran'] === 'DITOLAK'): ?>
                                    <small>Bukti pembayaran ditolak</small>
                                <?php else: ?>
                                    <small>Ongkir <?= e(rupiah($order['ongkir'])) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small>Total Tagihan</small>
                                <b class="belum">Belum ditetapkan</b>
                                <small>Tagihan resmi akan muncul setelah pesanan diperiksa Admin.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($order['status_pembayaran'] === 'DITOLAK'): ?>
                        <div class="kotak-status kotak-tolak">
                            <b>Bukti pembayaran ditolak oleh Admin.</b>
                            <?php if ($order['catatan_admin'] !== ''): ?>
                                <p><b>Catatan Admin:</b> <?= e($order['catatan_admin']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($order['status_pembayaran'] === 'MENUNGGU_VERIFIKASI'): ?>
                        <div class="kotak-status kotak-tunggu">
                            <span class="ikon-jam" aria-hidden="true">⌛</span>
                            <p>Bukti pembayaran Anda sedang menunggu verifikasi Admin.</p>
                        </div>
                    <?php elseif ($order['status_pesanan'] === 'MENUNGGU_VERIFIKASI'): ?>
                        <div class="kotak-status kotak-tunggu">
                            <span class="ikon-jam" aria-hidden="true">⌛</span>
                            <p>Admin sedang memeriksa spesifikasi pesanan dan menyiapkan tagihan resmi.</p>
                        </div>
                    <?php endif; ?>

                    <div class="pesanan-aksi">
                        <button type="button" class="tombol tombol-garis" data-buka-detail="<?= e($order['kode']) ?>">Lihat Detail</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <div class="modal-latar" id="modalDetail">
        <div class="modal modal-lebar" role="dialog" aria-modal="true" aria-labelledby="detailJudul">
            <div class="modal-kepala">
                <div>
                    <h2 id="detailJudul">Detail Pesanan <span class="kode-kecil">[<span data-isi="kode"></span>]</span></h2>
                    <small>Status: <span data-isi="status"></span></small>
                </div>
                <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
            </div>
            <div class="modal-isi">
                <section class="bagian-detail">
                    <div class="judul-detail"><b>RINCIAN PESANAN</b></div>
                    <h3 data-isi="produk"></h3>
                    <p class="kutipan" data-isi="spesifikasi"></p>
                    <p><small>Kelengkapan:</small> <span data-isi="tambahan"></span></p>
                    <p><small>Catatan produk:</small></p>
                    <p class="kutipan" data-isi="catatan_produk"></p>
                </section>
                <section class="bagian-detail">
                    <div class="judul-detail"><b>PENGIRIMAN &amp; PEMBAYARAN</b></div>
                    <p><small>Total tagihan:</small> <b data-isi="tagihan"></b></p>
                    <p><small>Metode pengiriman:</small> <span data-isi="pengiriman"></span></p>
                    <p><small>Alamat:</small> <span data-isi="alamat"></span></p>
                    <p><small>Catatan alamat:</small> <span data-isi="catatan_alamat"></span></p>
                    <p><small>Catatan pengiriman:</small> <span data-isi="catatan_pengiriman"></span></p>
                    <p><small>Nomor resi:</small> <span data-isi="resi"></span></p>
                    <p><small>Status pembayaran:</small> <span data-isi="status_pembayaran"></span></p>
                    <p><small>Metode pembayaran:</small> <span data-isi="metode_pembayaran"></span></p>
                    <p><small>Nominal transfer:</small> <span data-isi="nominal_transfer"></span></p>
                    <div class="catatan-admin" id="detailCatatanAdmin">
                        <b>Catatan Admin:</b>
                        <p data-isi="catatan_admin"></p>
                    </div>
                </section>
            </div>
            <div class="modal-kaki">
                <button type="button" class="tombol tombol-garis" data-tutup-modal>Tutup</button>
            </div>
        </div>
    </div>

    <script type="application/json" id="dataPesanan"><?= json_encode($dataModal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="/assets/js/customer/pesanan_saya.js"></script>
    <?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
