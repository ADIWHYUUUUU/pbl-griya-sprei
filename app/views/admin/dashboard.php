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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
$namaBulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$tahunSekarang = (int) date('Y');
$dashboard = new DashboardModel($pdo);
$tahunAwalLaporan = min($tahunSekarang, $dashboard->getEarliestOrderYear() ?? $tahunSekarang);
$bulanSekarang = (int) date('n');
$tanggalHariIni = new DateTimeImmutable('today');
$awalBulanIni = $tanggalHariIni->modify('first day of this month');
$tanggalPesananPertama = $dashboard->getEarliestOrderDate();
$awalBulanPesananPertama = ($tanggalPesananPertama ?? $tanggalHariIni)->modify('first day of this month');
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
$pembayaranVerifikasi = $dashboard->getWaitingVerificationPayments();
$pesananLaporan = $dashboard->getOrdersForDateRange(
    $tanggalAwalLaporan->format('Y-m-d'),
    $tanggalAkhirLaporan->format('Y-m-d')
);

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
    $namaBulanSingkat,
    $formatRupiah,
    $formatNilaiGrafik,
    $esc
): string {
    $kiri = 100;
    $kanan = 980;
    $lebar = 1000;
    $tinggi = 360;
    $atas = 48;
    $bawah = 300;
    $jumlahInterval = 4;
    $jarakLabelX = max(1, (int) ceil(count($chartData) / 10));
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
            ? $namaBulanSingkat[(int) $dataHarian['tanggal']->format('n') - 1]
                . ' ' . $dataHarian['tanggal']->format('Y')
            : $dataHarian['tanggal']->format('d') . ' '
                . $namaBulanSingkat[(int) $dataHarian['tanggal']->format('n') - 1];
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
    foreach ($titik as $index => $titikHarian) {
        $labelTitik = $titikHarian['tanggal'] . ': ' . $formatRupiah($titikHarian['total']);
        if ($titikHarian['total'] > 0) {
            $svg .= '<text class="daily-chart-value-label" x="' . $titikHarian['x'] .
                '" y="' . ($titikHarian['y'] - 12) . '" text-anchor="middle">' .
                $esc($formatRupiah($titikHarian['total'])) . '</text>';
        }
        if ($index % $jarakLabelX === 0 || $index === count($titik) - 1) {
            $svg .= '<text class="daily-chart-x-label" x="' . $titikHarian['x'] .
                '" y="' . ($bawah + 24) . '" text-anchor="middle">' .
                $esc($titikHarian['tanggal']) . '</text>';
        }
        $svg .= '<circle class="daily-chart-point" cx="' . $titikHarian['x'] .
            '" cy="' . $titikHarian['y'] . '" r="4" tabindex="0" aria-label="' .
            $esc($labelTitik) . '"><title>' . $esc($labelTitik) . '</title></circle>';
    }

    return $svg . '</svg></div>';
};
$jumlahHalamanLaporan = max(1, (int) ceil(count($pesananLaporan) / 7));
$halamanDiminta = $bacaAngkaQuery($_GET['halaman'] ?? 1, 1);
$halamanLaporan = max(1, min($jumlahHalamanLaporan, $halamanDiminta));
$pesananLaporanHalaman = $pesananLaporan;
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
$tanggalBulanSaatIni = $tanggalHariIni->modify('first day of this month');
$tanggalBulanLaporan = $tanggalAwalLaporan->modify('first day of this month');
$tanggalBulanSebelumnya = $tanggalBulanLaporan->modify('-1 month');
$tanggalBulanBerikutnya = $tanggalBulanLaporan->modify('+1 month');
$urlBulanSebelumnya = '/?' . http_build_query([
    'page' => 'admin-dashboard',
    'periode' => 'bulan',
    'bulan' => (int) $tanggalBulanSebelumnya->format('n'),
    'tahun' => (int) $tanggalBulanSebelumnya->format('Y'),
    'halaman' => 1,
], '', '&', PHP_QUERY_RFC3986) . '#modalLaporan';
$urlBulanBerikutnya = '/?' . http_build_query([
    'page' => 'admin-dashboard',
    'periode' => 'bulan',
    'bulan' => (int) $tanggalBulanBerikutnya->format('n'),
    'tahun' => (int) $tanggalBulanBerikutnya->format('Y'),
    'halaman' => 1,
], '', '&', PHP_QUERY_RFC3986) . '#modalLaporan';
$bolehKeBulanSebelumnya = $tanggalBulanSebelumnya >= $awalBulanPesananPertama;
$bolehKeBulanBerikutnya = $tanggalBulanLaporan < $tanggalBulanSaatIni;
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
<body class="admin-dashboard admin-dashboard-home">

<?php require __DIR__ . '/../layouts/sidebar.php'; ?>

<main>
    <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

    <header class="dashboard-intro">
        <div>
            <h1 class="header-title">Dashboard</h1>
            <p class="dashboard-subtitle">Ringkasan toko dan hal yang perlu segera ditindaklanjuti.</p>
        </div>
    </header>

    <div id="flashArea"></div>

    <section class="stats-grid">
        <a class="stat-card clickable" href="/?page=admin-orders">
            <div class="label">Pesanan Baru</div>
            <div class="value" id="statBaru"><?= $esc($stats['pesanan_baru'] ?? 0) ?></div>
            <div class="subtext"><span>Pesanan baru</span><span>Lihat Pesanan ›</span></div>
        </a>

        <a class="stat-card clickable" href="/?page=admin-payments">
            <div class="label">Verifikasi Transfer</div>
            <div class="value" id="statVerifikasi"><?= $esc($stats['menunggu_verifikasi'] ?? 0) ?></div>
            <div class="subtext"><span>Menunggu verifikasi</span><span>Cek Pembayaran ›</span></div>
        </a>

        <a class="stat-card clickable" href="/?page=admin-resi-list">
            <div class="label">Perlu Resi</div>
            <div class="value" id="statResi"><?= $esc($stats['perlu_resi'] ?? 0) ?></div>
            <div class="subtext"><span>Belum ada resi atau telepon</span><span>Isi Resi ›</span></div>
        </a>
    </section>

    <section class="verification-box" id="verifikasi">
        <div class="section-header">
            <h3><a class="verification-heading-link" href="/?page=admin-payments">Membutuhkan verifikasi</a></h3>
            <?php if ($pembayaranVerifikasi !== []): ?>
                <span class="badge badge-warn"><?= $esc($stats['menunggu_verifikasi'] ?? 0) ?> menunggu</span>
            <?php endif; ?>
        </div>
        <div id="listVerifikasi">
            <?php if ($pembayaranVerifikasi === []): ?>
                <p class="empty-text">Tidak ada pembayaran yang menunggu verifikasi.</p>
            <?php else: ?>
                <?php foreach (array_slice($pembayaranVerifikasi, 0, 2) as $pembayaran): ?>
                    <div class="verif-item">
                        <span class="verification-check" aria-hidden="true">&#9675;</span>
                        <div class="verif-info">
                            <strong><?= $esc($pembayaran['nama_pelanggan']) ?> / <?= $esc($pembayaran['kode']) ?></strong>
                            <span>Menunggu verifikasi pembayaran · <?= $esc($formatTanggal($pembayaran['tanggal_upload'])) ?></span>
                        </div>
                        <a class="btn-outline" href="/?page=admin-payment-verify&amp;id=<?= (int) $pembayaran['id_pembayaran'] ?>">Cek Pembayaran</a>
                    </div>
                <?php endforeach; ?>
                <a class="verification-all-link" href="/?page=admin-payments">Lihat semua transfer yang menunggu ›</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="chart-section">
        <div class="section-header">
            <h3>Grafik pendapatan — <?= $esc($labelPeriode) ?></h3>
            <div class="chart-header-actions">
                <form class="chart-report-filter" action="/" method="get" id="filterLaporan">
                    <input type="hidden" name="page" value="admin-dashboard">
                    <div class="chart-filter-field">
                        <label for="filterPeriode">Tampilkan laporan</label>
                        <select id="filterPeriode" name="periode" aria-label="Tampilkan laporan">
                            <option value="bulan" <?= $periodeLaporan === 'bulan' ? 'selected' : '' ?>>Per bulan</option>
                            <option value="tahun" <?= $periodeLaporan === 'tahun' ? 'selected' : '' ?>>Per tahun</option>
                            <option value="rentang" <?= $periodeLaporan === 'rentang' ? 'selected' : '' ?>>Rentang tanggal</option>
                        </select>
                    </div>
                    <div class="chart-filter-field" data-periode-group="bulan">
                        <label for="filterBulan">Bulan</label>
                        <select id="filterBulan" name="bulan" aria-label="Bulan">
                            <?php foreach ($namaBulan as $index => $nama): ?>
                                <option value="<?= $index + 1 ?>" <?= $bulanLaporan === $index + 1 ? 'selected' : '' ?>><?= $esc($nama) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="chart-filter-field" data-periode-group="bulan tahun">
                        <label for="filterTahun">Tahun</label>
                        <select id="filterTahun" name="tahun" aria-label="Tahun">
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
                <button type="button" class="btn-text" data-modal="modalLaporan">Lihat laporan</button>
            </div>
        </div>
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

</main>

<!-- POPUP: LAPORAN PENJUALAN -->
<div class="modal-overlay" id="modalLaporan" role="dialog" aria-modal="true" aria-labelledby="judulLaporan" aria-hidden="true">
    <section class="modal-container modal-lg">
        <header class="modal-header report-modal-header">
            <div class="report-heading">
                <span class="report-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" focusable="false">
                        <path d="M3 16h14M5 14V9m5 5V5m5 9v-7"></path>
                    </svg>
                </span>
                <h3 id="judulLaporan">Laporan &amp; Ringkasan Penjualan</h3>
            </div>
            <div class="report-header-actions">
                <?php if ($periodeLaporan === 'bulan'): ?>
                    <?php if ($bolehKeBulanSebelumnya): ?>
                        <a class="report-period-arrow" href="<?= $esc($urlBulanSebelumnya) ?>" aria-label="Bulan sebelumnya">&lsaquo;</a>
                    <?php else: ?>
                        <span class="report-period-arrow is-disabled" aria-hidden="true">&lsaquo;</span>
                    <?php endif; ?>
                    <strong class="report-period-label"><?= $esc($labelPeriode) ?></strong>
                    <?php if ($bolehKeBulanBerikutnya): ?>
                        <a class="report-period-arrow" href="<?= $esc($urlBulanBerikutnya) ?>" aria-label="Bulan berikutnya">&rsaquo;</a>
                    <?php else: ?>
                        <span class="report-period-arrow is-disabled" aria-hidden="true">&rsaquo;</span>
                    <?php endif; ?>
                <?php else: ?>
                    <strong class="report-period-label"><?= $esc($labelPeriode) ?></strong>
                <?php endif; ?>
                <a class="btn btn-primary report-export-button" href="<?= $esc($urlEksporLaporan) ?>">
                    <span aria-hidden="true">&#8681;</span> Ekspor ke Excel
                </a>
                <button type="button" class="close-btn" data-close aria-label="Tutup">&times;</button>
            </div>
        </header>

        <div class="modal-body">
            <div class="report-summary">
                <div class="report-summary-card">
                    <small>Total penjualan</small>
                    <div class="summary-val" id="lapTotal"><?= $esc($formatRupiah($totalPenjualan)) ?></div>
                </div>
                <div class="report-summary-card">
                    <small>Pesanan selesai</small>
                    <div class="summary-val" id="lapSelesai"><?= $esc($jumlahSelesai) ?> pesanan</div>
                </div>
                <div class="report-summary-card">
                    <small>Transaksi berbayar</small>
                    <div class="summary-val" id="lapTerbayar"><?= $esc($jumlahTerbayar) ?> transaksi</div>
                </div>
            </div>

            <section class="report-panel">
                <h4 class="report-title" id="judulTren">Grafik pendapatan harian</h4>
                <?= $grafikHarian('grafikTrenHarian') ?>
            </section>

            <section class="report-table-panel">
                <div class="report-table-heading">
                    <h4 class="report-title">Daftar pesanan &amp; transaksi</h4>
                    <div class="report-table-filters">
                        <label class="report-search">
                            <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false">
                                <circle cx="8.5" cy="8.5" r="5.5"></circle>
                                <path d="m13 13 4 4"></path>
                            </svg>
                            <input type="search" id="reportSearch" placeholder="Cari invoice atau pelanggan" aria-label="Cari invoice atau pelanggan">
                        </label>
                        <label>
                            <span class="sr-only">Filter status pesanan</span>
                            <select id="reportStatusFilter" aria-label="Filter status pesanan">
                                <option value="">Semua status</option>
                                <?php foreach ($statusPesanan as $status => $detail): ?>
                                    <option value="<?= $esc($status) ?>"><?= $esc($detail[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>No. invoice / kode</th>
                                <th>Tanggal</th>
                                <th>Pelanggan &amp; produk</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="lapTabel" data-initial-page="<?= $halamanLaporan ?>">
                            <?php if ($pesananLaporan !== []): ?>
                                <?php foreach ($pesananLaporanHalaman as $pesanan): ?>
                                    <tr data-report-order data-status="<?= $esc($pesanan['status_pesanan']) ?>">
                                        <td><strong><?= $esc($pesanan['kode']) ?></strong></td>
                                        <td><?= $esc($formatTanggal($pesanan['tanggal_pesan'])) ?></td>
                                        <td>
                                            <strong><?= $esc($pesanan['nama_pelanggan']) ?></strong>
                                            <small><?= $esc($pesanan['daftar_produk']) ?></small>
                                        </td>
                                        <td><?= $esc($formatRupiah($pesanan['total'])) ?></td>
                                        <td><?= $statusBadge($pesanan['status_pesanan']) ?></td>
                                        <td><a class="report-detail-link" href="/?page=admin-orders&amp;cari=<?= rawurlencode((string) $pesanan['kode']) ?>">Detail</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="reportEmptyState" <?= $pesananLaporan === [] ? '' : 'hidden' ?>>
                                <td class="empty-cell" colspan="6"><?= $pesananLaporan === [] ? 'Tidak ada pesanan pada periode ini.' : 'Tidak ada pesanan yang cocok.' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="report-pagination">
                    <span class="report-page-summary" id="reportPageSummary" aria-live="polite"></span>
                    <nav class="pagination-controls" id="reportPagination" aria-label="Halaman laporan"></nav>
                </div>
            </section>
        </div>
    </section>
</div>

<script src="/assets/js/admin/sidebar.js"></script>
<script src="/assets/js/admin/dashboard.js"></script>
</body>
</html>