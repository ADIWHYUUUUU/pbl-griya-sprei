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

$orders = (new DashboardModel($pdo))->getRecentOrders(50);
if ($orders === []) {
    $orders = [
        [
            'id_pesanan' => 1,
            'kode' => 'INV/202601014/SP/001',
            'tanggal_pesan' => '2026-10-14 09:15:00',
            'nama_pelanggan' => 'Ratna Dewi Astuti',
            'telepon' => '0812-3341-9982',
            'alamat_pengiriman' => 'Lowokwaru, Kota Malang',
            'metode_pengiriman' => 'EKSPEDISI_JNE',
            'daftar_spesifikasi' => "Sprei Katun Asterina Sage\nUkuran 200 x 160 x 30 cm • 1 Set (2)",
            'nomor_resi' => '',
            'status_pesanan' => 'DIPROSES',
        ],
        [
            'id_pesanan' => 2,
            'kode' => 'INV/202601014/SP/004',
            'tanggal_pesan' => '2026-10-14 10:40:00',
            'nama_pelanggan' => 'Hendro Prasetyo',
            'telepon' => '0822-4560-1123',
            'alamat_pengiriman' => 'Klojen, Kota Malang',
            'metode_pengiriman' => 'INSTANT_GOJEK',
            'daftar_spesifikasi' => "Bed Cover Set Earth Tone...\nUkuran 200 x 200 x 35 cm • Bedcover\n+ Sprei Fitted",
            'nomor_resi' => '',
            'status_pesanan' => 'DIPROSES',
        ],
        [
            'id_pesanan' => 3,
            'kode' => 'INV/202601015/SP/002',
            'tanggal_pesan' => '2026-10-15 08:30:00',
            'nama_pelanggan' => 'dr. Annisa Rahmawati',
            'telepon' => '0856-7781-2290',
            'alamat_pengiriman' => 'Sukun, Kota Malang',
            'metode_pengiriman' => 'EKSPEDISI_JNE',
            'daftar_spesifikasi' => "Sprei Katun Motif Mawar...\nUkuran 200 x 200 x 30 cm • Sprei Rumbai Cantik",
            'nomor_resi' => '',
            'status_pesanan' => 'DIPROSES',
        ],
        [
            'id_pesanan' => 4,
            'kode' => 'INV/202601015/SP/005',
            'tanggal_pesan' => '2026-10-15 11:20:00',
            'nama_pelanggan' => 'Bu Hajjah Mardiyah',
            'telepon' => '0813-8822-4411',
            'alamat_pengiriman' => 'Kedungkandang, Malang',
            'metode_pengiriman' => 'EKSPEDISI_JNT',
            'daftar_spesifikasi' => "Sprei Tencel Organic...\nUkuran 180 x 200 x 40 cm • Extra Pillow Case",
            'nomor_resi' => '',
            'status_pesanan' => 'DIPROSES',
        ],
        [
            'id_pesanan' => 5,
            'kode' => 'INV/202601013/SP/003',
            'tanggal_pesan' => '2026-10-13 14:10:00',
            'nama_pelanggan' => 'Maya Salsabila',
            'telepon' => '0878-1122-9099',
            'alamat_pengiriman' => 'Blimbing, Kota Malang',
            'metode_pengiriman' => 'EKSPEDISI_JNE',
            'daftar_spesifikasi' => "Sprei Katun Motif Katak...\nUkuran 120 x 200 x 25 cm • Single Bed Kids",
            'nomor_resi' => 'JNE1234567890',
            'status_pesanan' => 'DIKIRIM',
        ],
    ];
}

$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$couriers = [
    'INSTANT_GOJEK' => 'GoJek Instant',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$formatDate = static function ($timestamp): string {
    if ($timestamp === null || $timestamp === '') {
        return '-';
    }

    $date = new DateTimeImmutable((string) $timestamp);
    return $date->format('d M Y');
};
$normalizeSpecLines = static function ($value, $fallback = 'Detail pesanan'): array {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '' || strtolower($raw) === 'spesifikasi tidak tersedia' || strtolower($raw) === 'produk tidak tersedia') {
        return [$fallback];
    }

    $lines = preg_split('/\R+/', $raw) ?: [$raw];
    $cleaned = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '' && strtolower($line) !== 'spesifikasi tidak tersedia' && strtolower($line) !== 'produk tidak tersedia') {
            $cleaned[] = $line;
        }
    }

    if ($cleaned === []) {
        return [$fallback];
    }

    $unique = [];
    foreach ($cleaned as $line) {
        if (!in_array($line, $unique, true)) {
            $unique[] = $line;
        }
    }

    return $unique;
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nomor Resi - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/nomor_resi.css">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="page-breadcrumb">Admin <span aria-hidden="true">›</span> <strong>Input Nomor Resi</strong></div>
            <h1 class="page-title">Daftar Input Nomor Resi</h1>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="flash-message <?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

            <section class="order-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. Invoice &amp; Tgl</th>
                            <th>Pelanggan &amp; Tujuan</th>
                            <th>Item &amp; Sprei</th>
                            <th>Pengiriman</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $tracking = trim((string) ($order['nomor_resi'] ?? ''));
                            $orderId = (int) ($order['id_pesanan'] ?? 0);
                            $courierKey = (string) ($order['metode_pengiriman'] ?? '');
                            $courierLabel = $couriers[$courierKey] ?? $courierKey ?: 'Ekspedisi';
                            $specLines = $normalizeSpecLines(
                                $order['daftar_spesifikasi'] ?? '',
                            "Bedcover Hotel Series Ivory\nStandar: 200 × 180 × 30 cm"
                            );
                            $specDisplay = implode('<br>', array_map(static fn ($line) => htmlspecialchars((string) $line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), array_slice($specLines, 0, 2)));
                            $detailData = [
                                'invoice' => (string) ($order['kode'] ?? 'INV/000000'),
                                'resi' => $tracking,
                                'courier' => $courierLabel,
                                'courierKey' => $courierKey,
                                'customer' => (string) ($order['nama_pelanggan'] ?? 'Pelanggan'),
                                'phone' => (string) ($order['telepon'] ?? '-'),
                                'address' => (string) ($order['alamat_pengiriman'] ?? 'Alamat belum tersedia'),
                                'product' => implode("\n", $specLines),
                                'orderId' => $orderId,
                            ];
                        ?>
                            <tr>
                                <td>
                                    <span class="invoice"><?= $escape($order['kode'] ?? 'INV/000000') ?></span>
                                    <span class="date"><?= $escape($formatDate($order['tanggal_pesan'] ?? null)) ?></span>
                                </td>
                                <td>
                                    <span class="customer-name"><?= $escape($order['nama_pelanggan'] ?? 'Pelanggan') ?></span>
                                    <span class="muted"><?= $escape($order['telepon'] ?? '-') ?></span>
                                    <div class="shipping-name"><?= $escape($order['alamat_pengiriman'] ?? 'Alamat belum tersedia') ?></div>
                                </td>
                                <td>
                                    <div class="spec"><?= $specDisplay ?></div>
                                </td>
                                <td>
                                    <span class="courier-badge"><?= $escape($courierLabel) ?></span>
                                </td>
                                <td>
                                    <?php if ($tracking !== ''): ?>
                                        <button type="button" class="action-btn secondary resi-detail-trigger" data-detail='<?= json_encode($detailData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'>Lihat Resi</button>
                                    <?php else: ?>
                                        <a href="/?page=admin-resi-input&id=<?= (int) $orderId ?>" class="action-btn">Input Resi</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <div class="order-footer">
                <div class="footer-note">Menampilkan 1 - 5 dari 5 pesanan</div>
                <div class="pager" aria-label="Pagination">
                    <a href="#" class="page-number ghost" aria-label="Sebelumnya">&#8249;</a>
                    <a href="#" class="page-number active">1</a>
                    <a href="#" class="page-number">2</a>
                    <a href="#" class="page-number">3</a>
                    <a href="#" class="page-number ghost" aria-label="Berikutnya">&#8250;</a>
                </div>
            </div>
        </main>
    </div>

    <div class="resi-modal" id="resiModal" aria-hidden="true">
        <div class="resi-modal-backdrop" data-close="resi-modal"></div>
        <div class="resi-modal-panel" role="dialog" aria-modal="true" aria-labelledby="resiModalTitle">
            <div class="resi-modal-header">
                <h2 id="resiModalTitle">Detail Nomor Resi &amp; Pengiriman</h2>
                <button type="button" class="resi-close-btn" aria-label="Tutup detail resi" data-close="resi-modal">×</button>
            </div>

            <div class="resi-modal-body">
                <div class="resi-modal-subtitle" id="resiModalSubtitle">No. Pesanan: INV/202602014/SP/001 • J&amp;T Express</div>

                <div class="resi-box">
                    <div class="resi-box-main">
                        <div class="resi-box-label">NOMOR RESI PENGIRIMAN</div>
                        <div class="resi-box-number" id="resiNumberValue">JNT98234190823</div>
                    </div>
                    <button type="button" class="resi-copy-btn" id="copyResiBtn">Salin Resi</button>
                </div>

                <div class="resi-info-row">
                    <div class="resi-info-label">KURIR &amp; LAYANAN</div>
                    <div class="resi-info-value" id="resiCourierValue">J&amp;T Express</div>
                    <div class="resi-info-subvalue" id="resiCourierService">Layanan Reguler (EZ)</div>
                </div>

                <div class="resi-two-col">
                    <div class="resi-card">
                        <div class="resi-card-title">PENERIMA</div>
                        <div class="resi-card-name" id="resiCustomerName">Ratna Dewi Astuti</div>
                        <div class="resi-card-phone" id="resiCustomerPhone">0812-3341-9982</div>
                        <div class="resi-card-address" id="resiCustomerAddress">Jl. Dinoyo Permata Timur No. 14, RT 02 / RW 04,
                            Dinoyo, Lowokwaru, Kota Malang 65144</div>
                    </div>

                    <div class="resi-card">
                        <div class="resi-card-title">RINCIAN BARANG</div>
                        <div class="resi-card-product">
                            <div class="resi-product-copy">
                                <div class="resi-product-name" id="resiProductName">Sprei Katun Asterina Sage</div>
                                <div class="resi-product-meta" id="resiProductMeta">200 × 160 × 30 cm (Queen)</div>
                            </div>
                        </div>
                        <div class="resi-product-note" id="resiProductNote">"Titipkan ke sekuriti jika rumah kosong."</div>
                    </div>
                </div>

                <div class="resi-modal-footer">
                    <button type="button" class="resi-secondary-btn" data-close="resi-modal">Tutup</button>
                    <button type="button" class="resi-primary-btn fill" id="printLabelBtn">Cetak Label Pengiriman</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin/nomor_resi.js"></script>
</body>
</html>
