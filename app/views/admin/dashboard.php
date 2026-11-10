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
$dashboard = new DashboardModel($pdo);
$tahunAwalLaporan = min($tahunSekarang, $dashboard->getEarliestOrderYear() ?? $tahunSekarang);
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
if ($tahunLaporan < $tahunAwalLaporan || $tahunLaporan > $tahunSekarang) {
    $tahunLaporan = $tahunSekarang;
}

$stats = $dashboard->getStats();
$pesananTerbaru = $dashboard->getRecentOrders();
$pembayaranVerifikasi = $dashboard->getWaitingVerificationPayments();
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
$maksTransaksiMingguan = max(1, ...array_column($mingguan, 'transaksi'));
$maksStok = max(1, ...array_map(static function ($item) {
    return (int) $item['stok'];
}, $produk ?: [['stok' => 0]]));
$skalaStok = max(50, $maksStok);
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
    $bulanSingkat = [
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];
    $timestamp = strtotime($date);

    return date('d', $timestamp)
        . ' ' . $bulanSingkat[(int) date('n', $timestamp) - 1]
        . ' ' . date('Y', $timestamp);
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
    <title>Dashboard - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
</head>
<body class="admin-dashboard">

<?php require __DIR__ . '/../layouts/sidebar.php'; ?>

<main>
    <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

    <h1 class="header-title">Dashboard</h1>

    <div id="flashArea"></div>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="label">Pesanan Baru</div>
            <div class="value" id="statBaru"><?= $esc($stats['pesanan_baru'] ?? 0) ?></div>
            <div class="subtext">Pesanan Baru</div>
        </div>

        <a class="stat-card clickable" href="#verifikasi">
            <div class="label">Verifikasi Transfer</div>
            <div class="value" id="statVerifikasi"><?= $esc($stats['menunggu_verifikasi'] ?? 0) ?></div>
            <div class="subtext">Menunggu Konfirmasi</div>
        </a>

        <div class="stat-card stat-card-stock">
            <div class="stat-card-heading">
                <div class="label">Stok Sprei</div>
                <button type="button" class="btn-text" data-modal="modalStok">Lihat Stok</button>
            </div>
            <div class="value" id="statStok"><?= $esc($stats['total_stok'] ?? 0) ?></div>
            <div class="subtext">Total Stok Tersedia</div>
        </div>
    </section>

    <section class="chart-section">
        <div class="section-header">
            <h3>Grafik penjualan</h3>
            <button type="button" class="btn-text" data-modal="modalLaporan">Lihat laporan</button>
        </div>

        <div class="sales-metrics">
            <div class="metric-item">
                <small id="mLabelTotal">Total Penjualan</small>
                <div class="m-val" id="mTotal"><?= $esc($formatRupiah($totalPenjualan)) ?></div>
            </div>
            <div class="metric-item">
                <small>Total Pesanan Selesai</small>
                <div class="m-val" id="mSelesai"><?= $esc($jumlahSelesai) ?> pesanan</div>
            </div>
        </div>

        <div class="bar-chart" id="barChart" role="img" aria-label="Grafik penjualan per minggu">
            <?php foreach ($mingguan as $index => $minggu): ?>
                <?php
                $tinggiPenjualan = $minggu['total'] > 0
                    ? max(4, (int) round($minggu['total'] / $maksMingguan * 100))
                    : 0;
                $tinggiTransaksi = $minggu['transaksi'] > 0
                    ? max(4, (int) round($minggu['transaksi'] / $maksTransaksiMingguan * 100))
                    : 0;
                ?>
                <div class="bar-group">
                    <div class="bars">
                        <span class="bar bar-a" style="height: <?= $tinggiPenjualan ?>%" title="<?= $esc($formatRupiah($minggu['total'])) ?>"></span>
                        <span class="bar bar-b" style="height: <?= $tinggiTransaksi ?>%" title="<?= $esc($minggu['transaksi']) ?> transaksi"></span>
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

    <section class="verification-box" id="verifikasi">
        <div class="section-header">
            <h3>Membutuhkan verifikasi</h3>
        </div>
        <div id="listVerifikasi">
            <?php if ($pembayaranVerifikasi === []): ?>
                <p class="empty-text">Tidak ada pembayaran yang menunggu verifikasi.</p>
            <?php else: ?>
                <?php foreach (array_slice($pembayaranVerifikasi, 0, 2) as $pembayaran): ?>
                    <div class="verif-item">
                        <span class="verification-check" aria-hidden="true">&#10003;</span>
                        <div class="verif-info">
                            <strong><?= $esc($pembayaran['nama_pelanggan']) ?> / <?= $esc($pembayaran['kode']) ?></strong>
                            <span>Transfer pembayaran perlu diperiksa.</span>
                            <small><?= $esc($formatTanggal($pembayaran['tanggal_upload'])) ?></small>
                        </div>
                        <a class="btn-outline" href="/?page=admin-payment-verify&amp;id=<?= (int) $pembayaran['id_pembayaran'] ?>">Cek Pembayaran</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="table-box" id="daftarPesanan">
        <div class="section-header">
            <h3>Daftar pesanan</h3>
            <a class="btn-text" href="/?page=admin-orders">Lihat Pesanan</a>
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
                <tbody id="tabelPesanan">
                    <?php if ($pesananTerbaru === []): ?>
                        <tr><td class="empty-cell" colspan="6">Belum ada pesanan.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($pesananTerbaru, 0, 4) as $pesanan): ?>
                            <tr>
                                <td>
                                    <strong><?= $esc($pesanan['kode']) ?></strong>
                                    <small>Pesanan online</small>
                                </td>
                                <td>
                                    <strong><?= $esc($pesanan['nama_pelanggan']) ?></strong>
                                    <small><?= $esc($pesanan['daftar_produk']) ?></small>
                                </td>
                                <td><?= $esc($formatTanggal($pesanan['tanggal_pesan'])) ?></td>
                                <td class="order-total"><?= $esc($formatRupiah($pesanan['total'])) ?></td>
                                <td><?= $statusBadge($pesanan['status_pesanan']) ?></td>
                                <td>
                                    <button type="button" class="btn-outline" <?= $detailAttributes($pesanan) ?>>Lihat detail</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- POPUP: DETAIL PESANAN -->
<div class="modal-overlay" id="modalDetail" role="dialog" aria-modal="true" aria-labelledby="judulDetail" aria-hidden="true">
    <section class="modal-container detail-order-modal">
        <div class="modal-form">
            <header class="modal-header">
                <div class="detail-order-heading">
                    <span class="detail-order-icon" aria-hidden="true">&#9638;</span>
                    <div>
                        <h3 id="judulDetail">Detail Pesanan Ringkas</h3>
                        <small class="muted" id="dKodeTanggal"></small>
                    </div>
                </div>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </header>

            <div class="modal-body">
                <div class="detail-order-grid">
                    <div class="detail-order-column">
                        <section class="detail-order-card">
                            <small class="card-label">Pelanggan</small>
                            <strong id="dNama"></strong>
                            <p class="detail-contact"><span aria-hidden="true">&#9742;</span><span id="dTelepon"></span></p>
                            <p class="detail-contact"><span aria-hidden="true">&#9679;</span><span id="dAlamat"></span></p>
                        </section>

                        <section class="detail-order-card detail-product-card">
                            <small class="card-label">Produk sprei</small>
                            <div id="dItems"></div>
                            <p class="detail-customer-note" id="dCatatanPelanggan"></p>
                        </section>
                    </div>

                    <div class="detail-order-column">
                        <section class="detail-order-card detail-total-card">
                            <small class="card-label">Total tagihan</small>
                            <p class="price-text" id="dTotal"></p>
                            <p class="detail-payment-status" id="dMetode"></p>
                        </section>

                        <section class="detail-order-card detail-edit-card">
                            <div class="form-group">
                                <label for="dStatus">Status Pesanan</label>
                                <input class="form-control" type="text" id="dStatus" readonly>
                            </div>

                            <div class="form-group">
                                <label for="dCatatanAdmin">Catatan untuk Admin</label>
                                <input class="form-control" type="text" id="dCatatanAdmin" readonly>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <footer class="modal-footer detail-order-footer" aria-hidden="true"></footer>
        </div>
    </section>
</div>

<!-- POPUP: LIHAT STOK -->
<div class="modal-overlay" id="modalStok" role="dialog" aria-modal="true" aria-labelledby="judulStok" aria-hidden="true">
    <section class="modal-container stock-list-modal">
        <header class="modal-header">
            <h3 id="judulStok">LIHAT STOK</h3>
            <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
        </header>
        <div class="modal-body">
            <div id="daftarStok" class="stock-list">
                <?php if ($produk === []): ?>
                    <p class="empty-text">Belum ada produk dengan stok.</p>
                <?php else: ?>
                    <?php foreach ($produk as $item): ?>
                        <?php $stokProduk = max(0, (int) $item['stok']); ?>
                        <div class="stock-row">
                            <div class="stock-row-heading">
                                <span class="stock-name"><?= $esc($item['nama_produk']) ?></span>
                                <strong class="stock-qty"><?= $esc($stokProduk) ?> set</strong>
                            </div>
                            <span class="progress" aria-label="Stok <?= $esc($stokProduk) ?> set">
                                <span class="progress-fill" style="width: <?= min(100, (int) round($stokProduk / $skalaStok * 100)) ?>%"></span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="stock-add-link" data-modal="modalTambahStok">
                <span aria-hidden="true">+</span> Tambah Stok Sprei
            </button>
        </div>
    </section>
</div>

<!-- POPUP: TAMBAH / RESTOCK STOK -->
<div class="modal-overlay" id="modalTambahStok" role="dialog" aria-modal="true" aria-labelledby="judulTambahStok" aria-hidden="true">
    <section class="modal-container stock-form-modal">
        <form class="modal-form" id="formStok" novalidate>
            <header class="modal-header">
                <div class="stock-form-heading">
                    <span class="stock-form-icon" aria-hidden="true">+</span>
                    <h3 id="judulTambahStok">Tambah / Restock Stok Sprei</h3>
                </div>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </header>

            <div class="modal-body">
                <div class="form-group">
                    <label for="selectProduk">PILIH VARIAN SPREI READY</label>
                    <select class="form-control" id="selectProduk"></select>
                </div>

                <div class="stock-status-panel">
                    <div>
                        <small>Stok Saat Ini</small>
                        <strong id="infoStok">-</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label for="inputJumlah">JUMLAH TAMBAHAN STOK (SET)</label>
                    <div class="stepper stock-stepper">
                        <button type="button" class="step-btn" data-step="-1" aria-label="Kurangi">&minus;</button>
                        <input class="form-control" type="number" id="inputJumlah" min="1" max="10000" value="1">
                        <button type="button" class="step-btn" data-step="1" aria-label="Tambah">+</button>
                    </div>
                </div>

                <div class="form-group stock-source-group">
                    <label for="selectSumber">SUMBER MASUK / KETERANGAN (OPSIONAL)</label>
                    <select class="form-control" id="selectSumber"></select>
                    <input class="form-control" type="text" id="inputKeterangan" maxlength="255" placeholder="Catatan tambahan (misal: Selesai jahit Mbak Siti).">
                </div>
            </div>

            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Batal</button>
                <button type="submit" class="btn btn-primary"><span aria-hidden="true">&#10003;</span> Simpan Tambah Stok</button>
            </footer>
        </form>
    </section>
</div>

<!-- POPUP: LAPORAN BULANAN -->
<div class="modal-overlay" id="modalLaporan" role="dialog" aria-modal="true" aria-labelledby="judulLaporan" aria-hidden="true">
    <section class="modal-container modal-lg">
        <header class="modal-header report-modal-header">
            <div class="report-heading">
                <span class="report-heading-icon" aria-hidden="true">&#128202;</span>
                <div>
                    <h3 id="judulLaporan">Laporan &amp; Ringkasan Penjualan Bulanan</h3>
                    <small class="muted">Ikhtisar grafik performa penjualan dan rekapitulasi transaksi bulanan</small>
                </div>
            </div>
            <form class="report-controls" action="/#modalLaporan" method="get">
                <input type="hidden" name="page" value="admin-dashboard">
                <label class="sr-only" for="lapBulan">Pilih bulan laporan</label>
                <select class="report-select" id="lapBulan" name="bulan">
                    <?php foreach ($namaBulan as $index => $nama): ?>
                        <option value="<?= $index + 1 ?>" <?= $bulanLaporan === $index + 1 ? 'selected' : '' ?>><?= $esc($nama) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="sr-only" for="lapTahun">Pilih tahun laporan</label>
                <select class="report-select" id="lapTahun" name="tahun">
                    <?php for ($tahun = $tahunAwalLaporan; $tahun <= $tahunSekarang; $tahun++): ?>
                        <option value="<?= $tahun ?>" <?= $tahunLaporan === $tahun ? 'selected' : '' ?>><?= $tahun ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary" id="terapkanLaporan">Terapkan</button>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </form>
        </header>

        <div class="modal-body">
            <div class="report-summary">
                <div class="report-summary-card">
                    <small>Total penjualan</small>
                    <div class="summary-val" id="lapTotal"><?= $esc($formatRupiah($totalPenjualan)) ?></div>
                </div>
                <div class="report-summary-card">
                    <small>Transaksi selesai</small>
                    <div class="summary-line">
                        <div class="summary-val" id="lapSelesai"><?= $esc($jumlahSelesai) ?> pesanan</div>
                        <span class="period-badge">30 Hari</span>
                    </div>
                    <small class="summary-note" id="lapTerbayar"><?= $esc($jumlahTerbayar) ?> transaksi berbayar</small>
                </div>
            </div>

            <section class="report-panel">
                <h4 class="report-title" id="judulTren">Grafik tren penjualan mingguan (<?= $esc($namaBulan[$bulanLaporan - 1]) ?> <?= $tahunLaporan ?>)</h4>
                <div class="trend-grid" id="lapTren">
                    <?php foreach ($mingguan as $index => $minggu): ?>
                        <?php
                        $hariMulai = $index * 7 + 1;
                        $hariSelesai = min(($index + 1) * 7, $tanggalAkhirBulan);
                        $tinggiTren = $minggu['total'] > 0
                            ? max(8, (int) round($minggu['total'] / $maksMingguan * 100))
                            : 0;
                        ?>
                        <div class="trend-item">
                            <strong><?= $esc($formatRupiah($minggu['total'])) ?></strong>
                            <div class="trend-bar-track">
                                <span class="trend-bar" style="height: <?= $tinggiTren ?>%"></span>
                            </div>
                            <span>Minggu <?= $index + 1 ?></span>
                            <small>(<?= $hariMulai ?>–<?= $hariSelesai ?> <?= $esc(substr($namaBulan[$bulanLaporan - 1], 0, 3)) ?>)</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="report-table-panel">
                <h4 class="report-title">Daftar pesanan &amp; transaksi bulanan (<?= $esc($namaBulan[$bulanLaporan - 1]) ?> <?= $tahunLaporan ?>)</h4>
                <div class="table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>No. invoice / kode</th>
                                <th>Tanggal</th>
                                <th>Pelanggan &amp; produk</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="lapTabel">
                            <?php if ($pesananLaporanHalaman === []): ?>
                                <tr><td class="empty-cell" colspan="5">Tidak ada pesanan pada bulan ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pesananLaporanHalaman as $pesanan): ?>
                                    <tr>
                                        <td><strong><?= $esc($pesanan['kode']) ?></strong></td>
                                        <td><?= $esc($formatTanggal($pesanan['tanggal_pesan'])) ?></td>
                                        <td>
                                            <strong><?= $esc($pesanan['nama_pelanggan']) ?></strong>
                                            <small><?= $esc($pesanan['daftar_produk']) ?></small>
                                        </td>
                                        <td><?= $esc($formatRupiah($pesanan['total'])) ?></td>
                                        <td><?= $statusBadge($pesanan['status_pesanan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="report-pagination">
                    <div class="pagination-controls" aria-label="Halaman laporan">
                        <?php
                        $linkLaporan = static function (int $page) use ($bulanLaporan, $tahunLaporan): string {
                            return '/?page=admin-dashboard&bulan=' . $bulanLaporan
                                . '&tahun=' . $tahunLaporan . '&halaman=' . $page . '#modalLaporan';
                        };
                        ?>
                        <a href="<?= $esc($linkLaporan(max(1, $halamanLaporan - 1))) ?>" aria-label="Halaman sebelumnya" <?= $halamanLaporan <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>&lsaquo;</a>
                        <?php for ($page = 1; $page <= $jumlahHalamanLaporan; $page++): ?>
                            <a href="<?= $esc($linkLaporan($page)) ?>" <?= $page === $halamanLaporan ? 'class="current" aria-current="page"' : '' ?>><?= $page ?></a>
                        <?php endfor; ?>
                        <a href="<?= $esc($linkLaporan(min($jumlahHalamanLaporan, $halamanLaporan + 1))) ?>" aria-label="Halaman berikutnya" <?= $halamanLaporan >= $jumlahHalamanLaporan ? 'aria-disabled="true" tabindex="-1"' : '' ?>>&rsaquo;</a>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>

<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/dashboard.js"></script>
</body>
</html>