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
$tanggalHariIni = new DateTimeImmutable('today');
$awalBulanIni = $tanggalHariIni->modify('first day of this month');
$bacaAngkaQuery = static function ($value, int $default): int {
    if (!is_string($value) && !is_int($value)) {
        return $default;
    }

    $angka = filter_var($value, FILTER_VALIDATE_INT);
    return $angka === false ? $default : $angka;
};
$bacaTanggalQuery = static function ($value): ?DateTimeImmutable {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }

    $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $kesalahan = DateTimeImmutable::getLastErrors();
    if (
        $tanggal === false ||
        ($kesalahan !== false && ($kesalahan['warning_count'] > 0 || $kesalahan['error_count'] > 0)) ||
        $tanggal->format('Y-m-d') !== $value
    ) {
        return null;
    }

    return $tanggal;
};
$periodeLaporan = $_GET['periode'] ?? 'bulan';
if (!in_array($periodeLaporan, ['bulan', 'tahun', 'rentang'], true)) {
    $periodeLaporan = 'bulan';
}
$bulanLaporan = $bacaAngkaQuery($_GET['bulan'] ?? $bulanSekarang, $bulanSekarang);
$tahunLaporan = $bacaAngkaQuery($_GET['tahun'] ?? $tahunSekarang, $tahunSekarang);

if ($bulanLaporan < 1 || $bulanLaporan > 12) {
    $bulanLaporan = $bulanSekarang;
}
if ($tahunLaporan < $tahunAwalLaporan || $tahunLaporan > $tahunSekarang) {
    $tahunLaporan = $tahunSekarang;
}

$kesalahanLaporan = '';
if ($periodeLaporan === 'tahun') {
    $tanggalAwalLaporan = new DateTimeImmutable(sprintf('%04d-01-01', $tahunLaporan));
    $tanggalAkhirLaporan = new DateTimeImmutable(sprintf('%04d-12-31', $tahunLaporan));
    $labelPeriode = 'Tahun ' . $tahunLaporan;
} elseif ($periodeLaporan === 'rentang') {
    $tanggalAwalLaporan = $bacaTanggalQuery($_GET['tanggal_awal'] ?? null);
    $tanggalAkhirLaporan = $bacaTanggalQuery($_GET['tanggal_akhir'] ?? null);
    if ($tanggalAwalLaporan === null || $tanggalAkhirLaporan === null) {
        $kesalahanLaporan = 'Tanggal awal dan tanggal akhir harus diisi dengan tanggal yang valid.';
    } elseif ($tanggalAwalLaporan > $tanggalAkhirLaporan) {
        $kesalahanLaporan = 'Tanggal awal tidak boleh melewati tanggal akhir.';
    } elseif (
        $tanggalAwalLaporan < new DateTimeImmutable(sprintf('%04d-01-01', $tahunAwalLaporan)) ||
        $tanggalAkhirLaporan > $tanggalHariIni
    ) {
        $kesalahanLaporan = 'Rentang tanggal harus berada di antara data pesanan pertama dan hari ini.';
    }

    if ($kesalahanLaporan !== '') {
        $tanggalAwalLaporan = $awalBulanIni;
        $tanggalAkhirLaporan = $tanggalHariIni;
    }
    $labelPeriode = 'Periode ' . $tanggalAwalLaporan->format('d/m/Y')
        . ' – ' . $tanggalAkhirLaporan->format('d/m/Y');
} else {
    $tanggalAwalLaporan = new DateTimeImmutable(sprintf('%04d-%02d-01', $tahunLaporan, $bulanLaporan));
    $tanggalAkhirLaporan = $tanggalAwalLaporan->modify('+1 month')->modify('-1 day');
    $labelPeriode = $namaBulan[$bulanLaporan - 1] . ' ' . $tahunLaporan;
}

$stats = $dashboard->getStats();
$pesananTerbaru = $dashboard->getRecentOrders();
$pembayaranVerifikasi = $dashboard->getWaitingVerificationPayments();
$pesananLaporan = $dashboard->getOrdersForDateRange(
    $tanggalAwalLaporan->format('Y-m-d'),
    $tanggalAkhirLaporan->format('Y-m-d')
);
$produk = (new ProductController())->getAllProducts();

$jumlahHariPeriode = (int) $tanggalAwalLaporan->diff($tanggalAkhirLaporan)->days + 1;
$grafikPerBulan = $periodeLaporan === 'tahun' || $jumlahHariPeriode > 62;
$chartData = [];
$tanggalBucket = $grafikPerBulan
    ? $tanggalAwalLaporan->modify('first day of this month')
    : $tanggalAwalLaporan;
while ($tanggalBucket <= $tanggalAkhirLaporan) {
    $kunciBucket = $grafikPerBulan ? $tanggalBucket->format('Y-m') : $tanggalBucket->format('Y-m-d');
    $chartData[$kunciBucket] = [
        'tanggal' => $tanggalBucket,
        'total' => 0.0,
        'transaksi' => 0,
    ];
    $tanggalBucket = $grafikPerBulan
        ? $tanggalBucket->modify('+1 month')
        : $tanggalBucket->modify('+1 day');
}

$totalPenjualan = 0.0;
$jumlahSelesai = 0;
$jumlahTerbayar = 0;
$statusPenjualan = ['DIPROSES', 'DIKIRIM', 'SELESAI'];

foreach ($pesananLaporan as $pesanan) {
    if (!in_array($pesanan['status_pesanan'], $statusPenjualan, true)) {
        continue;
    }

    $total = (float) $pesanan['total'];
    $tanggalPesanan = new DateTimeImmutable($pesanan['tanggal_pesan']);
    $kunciBucket = $grafikPerBulan
        ? $tanggalPesanan->format('Y-m')
        : $tanggalPesanan->format('Y-m-d');
    if (isset($chartData[$kunciBucket])) {
        $chartData[$kunciBucket]['total'] += $total;
        $chartData[$kunciBucket]['transaksi']++;
    }
    $totalPenjualan += $total;
    $jumlahTerbayar++;
    if ($pesanan['status_pesanan'] === 'SELESAI') {
        $jumlahSelesai++;
    }
}

$formatRupiah = static function ($amount): string {
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$esc = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$maksPendapatanGrafik = max(array_column($chartData, 'total') ?: [0]);
$formatNilaiGrafik = static function (float $nilai): string {
    if ($nilai >= 1000000) {
        $nilaiRingkas = $nilai / 1000000;
        $satuan = 'jt';
    } elseif ($nilai >= 1000) {
        $nilaiRingkas = $nilai / 1000;
        $satuan = 'rb';
    } else {
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }

    $desimal = abs($nilaiRingkas - round($nilaiRingkas)) < 0.05 || $nilaiRingkas >= 10 ? 0 : 1;
    return 'Rp ' . number_format($nilaiRingkas, $desimal, ',', '.') . ' ' . $satuan;
};
$grafikHarian = static function (string $id) use (
    $chartData,
    $maksPendapatanGrafik,
    $grafikPerBulan,
    $labelPeriode,
    $formatRupiah,
    $formatNilaiGrafik,
    $esc
): string {
    $jarakTanggal = 110;
    $kiri = 100;
    $kanan = $kiri + max(900, (count($chartData) - 1) * $jarakTanggal);
    $lebar = $kanan + 20;
    $tinggi = 360;
    $atas = 48;
    $bawah = 300;
    $jumlahInterval = 4;
    $langkah = 1.0;

    if ($maksPendapatanGrafik > 0) {
        $langkahAwal = $maksPendapatanGrafik / $jumlahInterval;
        $orde = pow(10, floor(log10($langkahAwal)));
        $langkah = ceil($langkahAwal / $orde) * $orde;
    }

    $batasAtas = $langkah * $jumlahInterval;
    $rentangY = $bawah - $atas;
    $rentangX = $kanan - $kiri;
    $penyebutX = max(1, count($chartData) - 1);
    $titik = [];

    foreach (array_values($chartData) as $index => $dataHarian) {
        $x = $kiri + ($index / $penyebutX * $rentangX);
        $y = $bawah - ($dataHarian['total'] / $batasAtas * $rentangY);
        $tanggal = $grafikPerBulan
            ? $dataHarian['tanggal']->format('m/Y')
            : $dataHarian['tanggal']->format('d/m/Y');
        $titik[] = [
            'x' => number_format($x, 2, '.', ''),
            'y' => number_format($y, 2, '.', ''),
            'tanggal' => $tanggal,
            'total' => $dataHarian['total'],
        ];
    }

    $path = implode(' ', array_map(
        static function (array $titikHarian, int $index): string {
            return ($index === 0 ? 'M ' : 'L ') . $titikHarian['x'] . ' ' . $titikHarian['y'];
        },
        $titik,
        array_keys($titik)
    ));
    $svg = '<div class="daily-line-chart"><svg id="' . $esc($id) .
        '" width="' . $lebar . '" height="' . $tinggi . '" viewBox="0 0 ' . $lebar . ' ' . $tinggi .
        '" role="img" aria-label="Grafik pendapatan berdasarkan tanggal ' . $esc($labelPeriode) . '">';

    for ($interval = 0; $interval <= $jumlahInterval; $interval++) {
        $nilai = $batasAtas - ($interval * $langkah);
        $y = $atas + ($interval / $jumlahInterval * $rentangY);
        $svg .= '<line class="daily-chart-grid" x1="' . $kiri . '" y1="' . $y .
            '" x2="' . $kanan . '" y2="' . $y . '"></line>' .
            '<text class="daily-chart-y-label" x="' . ($kiri - 10) . '" y="' . ($y + 4) .
            '" text-anchor="end">' . $esc($formatNilaiGrafik($nilai)) . '</text>';
    }

    $svg .= '<path class="daily-chart-line" d="' . $path . '"></path>';
    foreach ($titik as $titikHarian) {
        $labelTitik = $titikHarian['tanggal'] . ': ' . $formatRupiah($titikHarian['total']);
        $svg .= '<text class="daily-chart-value-label" x="' . $titikHarian['x'] .
            '" y="' . ($titikHarian['y'] - 12) . '" text-anchor="middle">' .
            $esc($formatRupiah($titikHarian['total'])) . '</text>' .
            '<text class="daily-chart-x-label" x="' . $titikHarian['x'] .
            '" y="' . ($bawah + 24) . '" text-anchor="middle">' .
            $esc($titikHarian['tanggal']) . '</text>';
        $svg .= '<circle class="daily-chart-point" cx="' . $titikHarian['x'] .
            '" cy="' . $titikHarian['y'] . '" r="4" tabindex="0" aria-label="' .
            $esc($labelTitik) . '"><title>' . $esc($labelTitik) . '</title></circle>';
    }

    return $svg . '</svg></div>';
};
$maksStok = max(1, ...array_map(static function ($item) {
    return (int) $item['stok'];
}, $produk ?: [['stok' => 0]]));
$skalaStok = max(50, $maksStok);
$jumlahHalamanLaporan = max(1, (int) ceil(count($pesananLaporan) / 7));
$halamanDiminta = $bacaAngkaQuery($_GET['halaman'] ?? 1, 1);
$halamanLaporan = max(1, min($jumlahHalamanLaporan, $halamanDiminta));
$pesananLaporanHalaman = array_slice($pesananLaporan, ($halamanLaporan - 1) * 7, 7);
$statusPesanan = [
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'badge-info'],
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'badge-warn'],
    'DIPROSES' => ['Siap Kirim', 'badge-ok'],
    'DIKIRIM' => ['Dikirim', 'badge-blue'],
    'SELESAI' => ['Selesai', 'badge-done'],
    'DIBATALKAN' => ['Dibatalkan', 'badge-danger'],
];
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
$urlEksporLaporan = '/?' . http_build_query([
    'page' => 'admin-dashboard',
    'periode' => $periodeLaporan,
    'bulan' => $bulanLaporan,
    'tahun' => $tahunLaporan,
    'tanggal_awal' => $tanggalAwalLaporan->format('Y-m-d'),
    'tanggal_akhir' => $tanggalAkhirLaporan->format('Y-m-d'),
    'ekspor' => 'excel',
], '', '&', PHP_QUERY_RFC3986);
if (($_GET['ekspor'] ?? '') === 'excel') {
    $namaStatusEkspor = array_map(
        static function (array $status): string {
            return $status[0];
        },
        $statusPesanan
    );
    $amanUntukCsv = static function ($value): string {
        $value = (string) $value;
        return preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    };

    header('Content-Type: text/csv; charset=UTF-8');
    header(
        'Content-Disposition: attachment; filename="laporan-penjualan-'
        . $tanggalAwalLaporan->format('Ymd') . '-' . $tanggalAkhirLaporan->format('Ymd') . '.csv"'
    );
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('Gagal menyiapkan file ekspor laporan.');
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Laporan Penjualan', $labelPeriode], ';', '"', '');
    fputcsv($output, [], ';', '"', '');
    fputcsv($output, ['No. invoice / kode', 'Tanggal', 'Pelanggan', 'Produk', 'Total', 'Status'], ';', '"', '');
    foreach ($pesananLaporan as $pesanan) {
        fputcsv($output, [
            $amanUntukCsv($pesanan['kode']),
            $formatTanggal($pesanan['tanggal_pesan']),
            $amanUntukCsv($pesanan['nama_pelanggan']),
            $amanUntukCsv($pesanan['daftar_produk']),
            number_format((float) $pesanan['total'], 2, '.', ''),
            $amanUntukCsv($namaStatusEkspor[$pesanan['status_pesanan']] ?? $pesanan['status_pesanan']),
        ], ';', '"', '');
    }
    fclose($output);
    exit;
}
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
            <h3>Grafik pendapatan — <?= $esc($labelPeriode) ?></h3>
            <button type="button" class="btn-text" data-modal="modalLaporan">Lihat laporan</button>
        </div>

        <form class="chart-report-filter" action="/" method="get" id="filterLaporan">
            <input type="hidden" name="page" value="admin-dashboard">
            <div class="chart-filter-field">
                <label for="filterPeriode">Tampilkan laporan</label>
                <select id="filterPeriode" name="periode">
                    <option value="bulan" <?= $periodeLaporan === 'bulan' ? 'selected' : '' ?>>Per bulan</option>
                    <option value="tahun" <?= $periodeLaporan === 'tahun' ? 'selected' : '' ?>>Per tahun</option>
                    <option value="rentang" <?= $periodeLaporan === 'rentang' ? 'selected' : '' ?>>Rentang tanggal</option>
                </select>
            </div>
            <div class="chart-filter-field" data-periode-group="bulan">
                <label for="filterBulan">Bulan</label>
                <select id="filterBulan" name="bulan">
                    <?php foreach ($namaBulan as $index => $nama): ?>
                        <option value="<?= $index + 1 ?>" <?= $bulanLaporan === $index + 1 ? 'selected' : '' ?>><?= $esc($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="chart-filter-field" data-periode-group="bulan tahun">
                <label for="filterTahun">Tahun</label>
                <select id="filterTahun" name="tahun">
                    <?php for ($tahun = $tahunAwalLaporan; $tahun <= $tahunSekarang; $tahun++): ?>
                        <option value="<?= $tahun ?>" <?= $tahunLaporan === $tahun ? 'selected' : '' ?>><?= $tahun ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="chart-filter-field" data-periode-group="rentang">
                <label for="filterTanggalAwal">Tanggal awal</label>
                <input type="date" id="filterTanggalAwal" name="tanggal_awal"
                    min="<?= $tahunAwalLaporan ?>-01-01" max="<?= $tanggalHariIni->format('Y-m-d') ?>"
                    value="<?= $esc($tanggalAwalLaporan->format('Y-m-d')) ?>">
            </div>
            <div class="chart-filter-field" data-periode-group="rentang">
                <label for="filterTanggalAkhir">Tanggal akhir</label>
                <input type="date" id="filterTanggalAkhir" name="tanggal_akhir"
                    min="<?= $tahunAwalLaporan ?>-01-01" max="<?= $tanggalHariIni->format('Y-m-d') ?>"
                    value="<?= $esc($tanggalAkhirLaporan->format('Y-m-d')) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Terapkan filter</button>
        </form>
        <?php if ($kesalahanLaporan !== ''): ?>
            <p class="chart-filter-error" role="alert"><?= $esc($kesalahanLaporan) ?></p>
        <?php endif; ?>

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

        <?= $grafikHarian('grafikPendapatan') ?>
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
        </div>
    </section>
</div>

<!-- POPUP: LAPORAN PENJUALAN -->
<div class="modal-overlay" id="modalLaporan" role="dialog" aria-modal="true" aria-labelledby="judulLaporan" aria-hidden="true">
    <section class="modal-container modal-lg">
        <header class="modal-header report-modal-header">
            <div class="report-heading">
                <span class="report-heading-icon" aria-hidden="true">&#128202;</span>
                <div>
                    <h3 id="judulLaporan">Laporan &amp; Ringkasan Penjualan</h3>
                    <small class="muted">Periode: <?= $esc($labelPeriode) ?></small>
                </div>
            </div>
            <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
        </header>

        <div class="modal-body">
            <div class="report-export-actions">
                <a class="btn btn-primary report-export-button" href="<?= $esc($urlEksporLaporan) ?>">
                    <span aria-hidden="true">&#8681;</span> Ekspor ke Excel
                </a>
            </div>
            <div class="report-summary">
                <div class="report-summary-card">
                    <small>Total penjualan</small>
                    <div class="summary-val" id="lapTotal"><?= $esc($formatRupiah($totalPenjualan)) ?></div>
                </div>
                <div class="report-summary-card">
                    <small>Transaksi selesai</small>
                    <div class="summary-line">
                        <div class="summary-val" id="lapSelesai"><?= $esc($jumlahSelesai) ?> pesanan</div>
                        <span class="period-badge"><?= $esc($labelPeriode) ?></span>
                    </div>
                    <small class="summary-note" id="lapTerbayar"><?= $esc($jumlahTerbayar) ?> transaksi berbayar</small>
                </div>
            </div>

            <section class="report-panel">
                <h4 class="report-title" id="judulTren">Grafik pendapatan (<?= $esc($labelPeriode) ?>)</h4>
                <?= $grafikHarian('grafikTrenHarian') ?>
            </section>

            <section class="report-table-panel">
                <h4 class="report-title">Daftar pesanan &amp; transaksi (<?= $esc($labelPeriode) ?>)</h4>
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
                                <tr><td class="empty-cell" colspan="5">Tidak ada pesanan pada periode ini.</td></tr>
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
                        $linkLaporan = static function (int $page) use (
                            $periodeLaporan,
                            $bulanLaporan,
                            $tahunLaporan,
                            $tanggalAwalLaporan,
                            $tanggalAkhirLaporan
                        ): string {
                            return '/?page=admin-dashboard&periode=' . rawurlencode($periodeLaporan)
                                . '&bulan=' . $bulanLaporan
                                . '&tahun=' . $tahunLaporan
                                . '&tanggal_awal=' . $tanggalAwalLaporan->format('Y-m-d')
                                . '&tanggal_akhir=' . $tanggalAkhirLaporan->format('Y-m-d')
                                . '&halaman=' . $page . '#modalLaporan';
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