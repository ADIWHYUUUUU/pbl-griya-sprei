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

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DashboardModel.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
$tahunSekarang = (int) date('Y');
$bulanSekarang = (int) date('n');
$bacaAngkaQuery = static function ($value, int $default): int {
    if (!is_string($value) && !is_int($value)) {
        return $default;
    }

    $angka = filter_var($value, FILTER_VALIDATE_INT);
    return $angka === false ? $default : $angka;
};
$bulanLaporan = $bacaAngkaQuery($_GET['bulan'] ?? $bulanSekarang, $bulanSekarang);
$tahunLaporan = $bacaAngkaQuery($_GET['tahun'] ?? $tahunSekarang, $tahunSekarang);

if ($bulanLaporan < 1 || $bulanLaporan > 12) {
    $bulanLaporan = $bulanSekarang;
}
if ($tahunLaporan < $tahunSekarang - 5 || $tahunLaporan > $tahunSekarang + 1) {
    $tahunLaporan = $tahunSekarang;
}

$dashboard = new DashboardModel($pdo);
$stats = $dashboard->getStats();
$pesananTerbaru = $dashboard->getRecentOrders();
$pesananVerifikasi = $dashboard->getWaitingVerificationOrders();
$pesananLaporan = $dashboard->getOrdersForMonth($bulanLaporan, $tahunLaporan);
$produk = (new ProductController())->getAllProducts();

$mingguan = array_fill(0, 4, ['total' => 0, 'transaksi' => 0]);
$totalPenjualan = 0.0;
$jumlahSelesai = 0;
$jumlahTerbayar = 0;
$statusPenjualan = ['DIPROSES', 'DIKIRIM', 'SELESAI'];

foreach ($pesananLaporan as $pesanan) {
    if (!in_array($pesanan['status_pesanan'], $statusPenjualan, true)) {
        continue;
    }

    $total = (float) $pesanan['total'];
    $hari = (int) date('j', strtotime($pesanan['tanggal_pesan']));
    $minggu = min(3, intdiv($hari - 1, 7));
    $mingguan[$minggu]['total'] += $total;
    $mingguan[$minggu]['transaksi']++;
    $totalPenjualan += $total;
    $jumlahTerbayar++;
    if ($pesanan['status_pesanan'] === 'SELESAI') {
        $jumlahSelesai++;
    }
}

$maksMingguan = max(1, ...array_column($mingguan, 'total'));
$maksStok = max(1, ...array_map(static function ($item) {
    return (int) $item['stok'];
}, $produk ?: [['stok' => 0]]));
$jumlahHalamanLaporan = max(1, (int) ceil(count($pesananLaporan) / 7));
$halamanDiminta = $bacaAngkaQuery($_GET['halaman'] ?? 1, 1);
$halamanLaporan = max(1, min($jumlahHalamanLaporan, $halamanDiminta));
$pesananLaporanHalaman = array_slice($pesananLaporan, ($halamanLaporan - 1) * 7, 7);
$tanggalAkhirBulan = (int) date('t', strtotime(sprintf('%04d-%02d-01', $tahunLaporan, $bulanLaporan)));

$statusPesanan = [
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'badge-info'],
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'badge-warn'],
    'DIPROSES' => ['Siap Kirim', 'badge-ok'],
    'DIKIRIM' => ['Dikirim', 'badge-blue'],
    'SELESAI' => ['Selesai', 'badge-done'],
    'DIBATALKAN' => ['Dibatalkan', 'badge-danger'],
];
$formatRupiah = static function ($amount): string {
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$formatTanggal = static function ($date): string {
    return date('d/m/Y', strtotime($date));
};
$esc = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$statusBadge = static function ($status) use ($statusPesanan, $esc): string {
    $detail = $statusPesanan[$status] ?? [$status, 'badge-done'];
    return '<span class="badge ' . $esc($detail[1]) . '">' . $esc($detail[0]) . '</span>';
};
$detailAttributes = static function (array $order) use ($esc, $formatRupiah, $formatTanggal, $statusPesanan): string {
    $status = $statusPesanan[$order['status_pesanan']][0] ?? $order['status_pesanan'];
    $payment = $order['status_pembayaran'] === 'TERVERIFIKASI'
        ? 'Terverifikasi'
        : ($order['status_pembayaran'] === 'MENUNGGU_VERIFIKASI'
            ? 'Menunggu verifikasi'
            : ($order['status_pembayaran'] === 'DITOLAK' ? 'Ditolak' : 'Belum ada pembayaran'));

    return 'data-order-detail data-kode="' . $esc($order['kode']) .
        '" data-nama="' . $esc($order['nama_pelanggan']) .
        '" data-telepon="' . $esc($order['telepon']) .
        '" data-alamat="' . $esc($order['alamat_pengiriman']) .
        '" data-produk="' . $esc($order['daftar_produk']) .
        '" data-total="' . $esc($formatRupiah($order['total'])) .
        '" data-tanggal="' . $esc($formatTanggal($order['tanggal_pesan'])) .
        '" data-status="' . $esc($status) .
        '" data-pembayaran="' . $esc($payment) .
        '" data-metode="' . $esc($order['metode_pembayaran'] ?? 'Belum tersedia') .
        '" data-catatan="' . $esc($order['catatan_admin'] ?? '') . '"';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
</head>
<body class="admin-dashboard">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="dashboard-main">
            <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
            <header class="dashboard-header">
                <div>
                    <p class="eyebrow">PANEL ADMIN</p>
                    <h1>Dashboard</h1>
                    <p class="dashboard-intro">Ringkasan aktivitas toko Griya Sprei.</p>
                </div>
            </header>

            <div id="flashArea" aria-live="polite"></div>

            <section class="stats-grid" aria-label="Ringkasan toko">
                <a class="stat-card" href="#daftarPesanan">
                    <span class="stat-label">Pesanan Baru</span>
                    <strong class="stat-value"><?= (int) $stats['pesanan_baru'] ?></strong>
                    <span class="stat-subtext">Menunggu pembayaran</span>
                </a>
                <a class="stat-card" href="#verifikasi">
                    <span class="stat-label">Verifikasi Transfer</span>
                    <strong class="stat-value"><?= (int) $stats['menunggu_verifikasi'] ?></strong>
                    <span class="stat-subtext">Menunggu verifikasi</span>
                </a>
                <button class="stat-card stat-card-button" type="button" data-modal="modalStok">
                    <span class="stat-label">Stok Produk</span>
                    <strong class="stat-value"><?= (int) $stats['total_stok'] ?></strong>
                    <span class="stat-subtext">Total stok gudang</span>
                </button>
            </section>

            <section class="panel chart-section" aria-labelledby="grafikTitle">
                <div class="section-header">
                    <h2 id="grafikTitle">Grafik penjualan</h2>
                    <button type="button" class="btn-text" data-modal="modalLaporan">Lihat laporan</button>
                </div>
                <div class="sales-metrics">
                    <div class="metric-item">
                        <small>Total penjualan (<?= $esc($namaBulan[$bulanLaporan - 1] . ' ' . $tahunLaporan) ?>)</small>
                        <strong><?= $esc($formatRupiah($totalPenjualan)) ?></strong>
                    </div>
                    <div class="metric-item">
                        <small>Transaksi selesai</small>
                        <strong><?= $jumlahSelesai ?> pesanan</strong>
                    </div>
                    <div class="metric-item">
                        <small>Rata-rata pesanan</small>
                        <strong><?= $esc($formatRupiah($jumlahTerbayar ? $totalPenjualan / $jumlahTerbayar : 0)) ?></strong>
                    </div>
                </div>
                <div class="bar-chart" role="img" aria-label="Grafik penjualan mingguan pada <?= $esc($namaBulan[$bulanLaporan - 1] . ' ' . $tahunLaporan) ?>">
                    <?php foreach ($mingguan as $index => $minggu): ?>
                        <div class="bar-group">
                            <div class="bars">
                                <span class="bar bar-a" title="<?= $esc($formatRupiah($minggu['total'])) ?>" style="height: <?= $minggu['total'] ? max(3, (int) round($minggu['total'] / $maksMingguan * 100)) : 0 ?>%"></span>
                                <span class="bar bar-b" title="<?= (int) $minggu['transaksi'] ?> transaksi" style="height: <?= $minggu['transaksi'] ? max(3, (int) round($minggu['transaksi'] / max(1, ...array_column($mingguan, 'transaksi')) * 100)) : 0 ?>%"></span>
                            </div>
                            <span class="bar-label">Minggu <?= $index + 1 ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chart-legend">
                    <span><i class="dot dot-a"></i>Penjualan</span>
                    <span><i class="dot dot-b"></i>Transaksi</span>
                </div>
            </section>

            <section class="panel verification-box" id="verifikasi" aria-labelledby="verifikasiTitle">
                <div class="section-header"><h2 id="verifikasiTitle">Membutuhkan verifikasi</h2></div>
                <?php if (!$pesananVerifikasi): ?>
                    <p class="empty-text">Tidak ada bukti transfer yang menunggu verifikasi.</p>
                <?php else: ?>
                    <?php foreach ($pesananVerifikasi as $pesanan): ?>
                        <div class="verif-item">
                            <div class="verif-info">
                                <strong><?= $esc($pesanan['kode'] . ' · ' . $pesanan['nama_pelanggan']) ?></strong>
                                <span><?= $esc($formatRupiah($pesanan['total'])) ?> · <?= $esc($formatTanggal($pesanan['tanggal_pesan'])) ?></span>
                            </div>
                            <button type="button" class="btn-text" <?= $detailAttributes($pesanan) ?>>Lihat pesanan</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="panel table-box" id="daftarPesanan" aria-labelledby="pesananTitle">
                <div class="section-header">
                    <h2 id="pesananTitle">Daftar pesanan terbaru</h2>
                    <button type="button" class="btn-text" data-modal="modalLaporan">Lihat laporan</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Pesanan</th>
                                <th>Pelanggan &amp; Produk</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$pesananTerbaru): ?>
                            <tr><td colspan="6" class="empty-text">Belum ada pesanan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pesananTerbaru as $pesanan): ?>
                                <tr>
                                    <td><strong><?= $esc($pesanan['kode']) ?></strong></td>
                                    <td><?= $esc($pesanan['nama_pelanggan']) ?><br><small class="muted"><?= $esc($pesanan['daftar_produk']) ?></small></td>
                                    <td><?= $esc($formatTanggal($pesanan['tanggal_pesan'])) ?></td>
                                    <td><?= $esc($formatRupiah($pesanan['total'])) ?></td>
                                    <td><?= $statusBadge($pesanan['status_pesanan']) ?></td>
                                    <td><button type="button" class="btn-text" <?= $detailAttributes($pesanan) ?>>Lihat detail</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="modalDetail" role="dialog" aria-modal="true" aria-labelledby="judulDetail" aria-hidden="true">
        <section class="modal-container detail-order-modal">
            <header class="modal-header">
                <div>
                    <h2 id="judulDetail">Detail Pesanan</h2>
                    <small class="muted" id="dKodeTanggal"></small>
                </div>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </header>
            <div class="modal-body">
                <div class="detail-order-grid">
                    <section class="detail-order-card">
                        <small class="card-label">Pelanggan</small>
                        <strong id="dNama"></strong>
                        <p id="dTelepon"></p>
                        <p id="dAlamat"></p>
                    </section>
                    <section class="detail-order-card">
                        <small class="card-label">Produk pesanan</small>
                        <p id="dProduk"></p>
                    </section>
                    <section class="detail-order-card">
                        <small class="card-label">Total tagihan</small>
                        <strong class="price-text" id="dTotal"></strong>
                        <p id="dPembayaran"></p>
                    </section>
                    <section class="detail-order-card">
                        <small class="card-label">Status Pesanan</small>
                        <p id="dStatus"></p>
                        <small class="card-label">Catatan Admin</small>
                        <p id="dCatatan"></p>
                    </section>
                </div>
            </div>
        </section>
    </div>

    <div class="modal-overlay" id="modalStok" role="dialog" aria-modal="true" aria-labelledby="judulStok" aria-hidden="true">
        <section class="modal-container stock-list-modal">
            <header class="modal-header">
                <h2 id="judulStok">Stok Produk</h2>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </header>
            <div class="modal-body">
                <div class="stock-list">
                    <?php if (!$produk): ?>
                        <p class="empty-text">Belum ada produk di katalog.</p>
                    <?php else: ?>
                        <?php foreach ($produk as $item): ?>
                            <?php $stokItem = (int) $item['stok']; ?>
                            <div class="stock-row">
                                <div class="stock-row-heading">
                                    <span class="stock-name"><?= $esc($item['nama_produk']) ?></span>
                                    <strong class="stock-qty"><?= $stokItem ?> set</strong>
                                </div>
                                <span class="progress" aria-label="Stok <?= $stokItem ?> set">
                                    <span class="progress-fill" style="width: <?= (int) round($stokItem / $maksStok * 100) ?>%"></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a class="stock-add-link" href="/?page=admin-products">Kelola stok melalui manajemen produk</a>
            </div>
        </section>
    </div>

    <div class="modal-overlay" id="modalLaporan" role="dialog" aria-modal="true" aria-labelledby="judulLaporan" aria-hidden="true">
        <section class="modal-container modal-lg">
            <header class="modal-header report-modal-header">
                <div>
                    <h2 id="judulLaporan">Laporan Penjualan Bulanan</h2>
                    <small class="muted">Ringkasan transaksi berdasarkan data pesanan tersimpan.</small>
                </div>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </header>
            <div class="modal-body">
                <form class="report-filter" action="/?page=admin-dashboard#modalLaporan" method="get">
                    <input type="hidden" name="halaman" value="1">
                    <label for="lapBulan">Bulan</label>
                    <select id="lapBulan" name="bulan">
                        <?php foreach ($namaBulan as $index => $bulan): ?>
                            <option value="<?= $index + 1 ?>" <?= $bulanLaporan === $index + 1 ? 'selected' : '' ?>><?= $esc($bulan) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="lapTahun">Tahun</label>
                    <select id="lapTahun" name="tahun">
                        <?php for ($tahun = $tahunSekarang - 5; $tahun <= $tahunSekarang + 1; $tahun++): ?>
                            <option value="<?= $tahun ?>" <?= $tahunLaporan === $tahun ? 'selected' : '' ?>><?= $tahun ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </form>

                <div class="report-summary">
                    <div class="report-summary-card">
                        <small>Total penjualan</small>
                        <strong class="summary-val"><?= $esc($formatRupiah($totalPenjualan)) ?></strong>
                    </div>
                    <div class="report-summary-card">
                        <small>Pesanan selesai</small>
                        <strong class="summary-val"><?= $jumlahSelesai ?> pesanan</strong>
                    </div>
                </div>
                <section class="report-panel">
                    <h3 class="report-title">Tren penjualan mingguan (<?= $esc($namaBulan[$bulanLaporan - 1] . ' ' . $tahunLaporan) ?>)</h3>
                    <div class="trend-grid">
                        <?php foreach ($mingguan as $index => $minggu): ?>
                            <?php
                            $hariAwal = $index * 7 + 1;
                            $hariAkhir = min(($index + 1) * 7, $tanggalAkhirBulan);
                            $tinggi = $minggu['total'] ? max(8, (int) round($minggu['total'] / $maksMingguan * 100)) : 0;
                            ?>
                            <div class="trend-item">
                                <strong><?= $esc($formatRupiah($minggu['total'])) ?></strong>
                                <div class="trend-bar-track"><span class="trend-bar" style="height: <?= $tinggi ?>%"></span></div>
                                <span>Minggu <?= $index + 1 ?></span>
                                <small><?= $hariAwal ?>–<?= $hariAkhir ?> <?= $esc(substr($namaBulan[$bulanLaporan - 1], 0, 3)) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="report-table-panel">
                    <h3 class="report-title">Pesanan pada <?= $esc($namaBulan[$bulanLaporan - 1] . ' ' . $tahunLaporan) ?></h3>
                    <div class="table-wrap">
                        <table class="report-table">
                            <thead><tr><th>Invoice / kode</th><th>Tanggal</th><th>Pelanggan &amp; produk</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (!$pesananLaporanHalaman): ?>
                                <tr><td colspan="5" class="empty-text">Belum ada pesanan pada periode ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pesananLaporanHalaman as $pesanan): ?>
                                    <tr>
                                        <td><?= $esc($pesanan['kode']) ?></td>
                                        <td><?= $esc($formatTanggal($pesanan['tanggal_pesan'])) ?></td>
                                        <td><?= $esc($pesanan['nama_pelanggan']) ?><br><small class="muted"><?= $esc($pesanan['daftar_produk']) ?></small></td>
                                        <td><?= $esc($formatRupiah($pesanan['total'])) ?></td>
                                        <td><?= $statusBadge($pesanan['status_pesanan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="report-pagination" aria-label="Halaman laporan">
                        <?php if ($halamanLaporan > 1): ?>
                            <a href="/?page=admin-dashboard&amp;bulan=<?= $bulanLaporan ?>&amp;tahun=<?= $tahunLaporan ?>&amp;halaman=<?= $halamanLaporan - 1 ?>#modalLaporan" aria-label="Halaman sebelumnya">&lsaquo;</a>
                        <?php else: ?><span aria-disabled="true">&lsaquo;</span><?php endif; ?>
                        <strong aria-live="polite"><?= $halamanLaporan ?> / <?= $jumlahHalamanLaporan ?></strong>
                        <?php if ($halamanLaporan < $jumlahHalamanLaporan): ?>
                            <a href="/?page=admin-dashboard&amp;bulan=<?= $bulanLaporan ?>&amp;tahun=<?= $tahunLaporan ?>&amp;halaman=<?= $halamanLaporan + 1 ?>#modalLaporan" aria-label="Halaman berikutnya">&rsaquo;</a>
                        <?php else: ?><span aria-disabled="true">&rsaquo;</span><?php endif; ?>
                    </nav>
                </section>
            </div>
        </section>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/dashboard.js"></script>
</body>
</html>
