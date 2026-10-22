<?php
session_start();

function e($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((int) $angka, 0, ',', '.');
}

function tanggal($teks) {
    return str_replace(
        ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        (string) $teks
    );
}
$rekening = ['bank' => 'Bank BCA', 'nomor' => '123-456-7890', 'atas_nama' => 'Griya Sprei'];

// ---------- Status pesanan ----------
$infoStatus = [
    'Menunggu Verifikasi' => [
        'kelas' => 'tunggu', 'label' => 'Menunggu Verifikasi',
    ],
    'Menunggu Pembayaran' => [
        'kelas' => 'bayar', 'label' => 'Terverifikasi Admin — Menunggu Pembayaran',
        'judul' => 'Silakan Lakukan Pembayaran',
        'teks'  => 'Transfer sesuai total tagihan ke rekening Griya Sprei, lalu unggah foto bukti transfer yang jelas dan utuh.',
        'aksi'  => 'Unggah Bukti Transfer',
    ],
    'Bukti Pembayaran Ditolak' => [
        'kelas' => 'tolak', 'label' => 'Bukti Pembayaran Ditolak',
        'judul' => 'Perlu Upload Ulang Bukti Transfer',
        'teks'  => 'Bukti transfer sebelumnya terpotong nominal dan tanggal. Silakan unggah foto yang jelas dan utuh.',
        'aksi'  => 'Unggah Bukti Transfer Baru',
    ],
    'Pembayaran Terverifikasi' => [
        'kelas' => 'lunas', 'label' => 'Pembayaran Terverifikasi',
    ],
    'Selesai Dikirim' => [
        'kelas' => 'selesai', 'label' => 'Selesai Dikirim',
    ],
];

// ----------  Data contoh  ----------
$pesananContoh = [
    [
        'kode' => 'INV/20261012/SP/001', 'waktu' => '12 Oct 2026, 14:20', 'waktu_verifikasi' => '12 Oct 2026, 15:00',
        'status' => 'Bukti Pembayaran Ditolak', 'tagihan' => 215000,
        'rincian' => 'Bahan & Jahit Rp 175.000 + Ongkir Rp 40.000',
        'catatan_admin' => 'Bukti transfer terpotong nominal dan tanggal transaksi tidak terbaca jelas. Mohon unggah ulang bukti transfer yang utuh.',
        'riwayat_bayar' => [['waktu' => '12 Oct 2026', 'jumlah' => 215000, 'file' => 'bukti_transfer_bca.jpg', 'status' => 'Ditolak']],
        'batas' => '15 Oct 2026, 23:59 WIB',
        'produk' => ['nama' => 'Sprei Katun Asterina Sage', 'gambar' => '', 'warna' => '#9bb87a', 'jenis' => 'custom',
                     'bahan' => 'Katun Micro Silk Asterina (Motif Salur Halus)', 'ukuran' => '200 × 160 × 30 cm',
                     'tambahan' => '2 sarung bantal + 1 sarung guling', 'catatan' => 'Karet keliling elastis & resleting penutup samping rapi', 'jumlah' => 1],
    ],
    [
        'kode' => 'INV/20261012/BC/002', 'waktu' => '12 Oct 2026, 09:16', 'waktu_verifikasi' => '12 Oct 2026, 11:30',
        'status' => 'Menunggu Pembayaran', 'tagihan' => 345000,
        'rincian' => 'Bed Cover Custom Rp 320.000 + Ongkir Ekspedisi Rp 25.000',
        'referensi' => 'INV-20261012-002', 'batas' => '13 Oct 2026, 23:59 WIB',
        'produk' => ['nama' => 'Bed Cover Katun Minimalis Earth Tone', 'gambar' => '', 'warna' => '', 'jenis' => 'custom',
                     'bahan' => 'Katun Minimalis Earth Tone', 'ukuran' => '200 × 200 × 35 cm',
                     'tambahan' => '-', 'catatan' => 'Karet keliling 4 sudut, resleting samping atas', 'jumlah' => 1],
    ],
    [
        'kode' => 'INV/20261011/BC/004', 'waktu' => '11 Oct 2026, 14:15',
        'status' => 'Menunggu Verifikasi', 'tagihan' => null,
        'produk' => ['nama' => 'Bed Cover Katun Minimalis Sage & Khaki', 'gambar' => '', 'warna' => '', 'jenis' => 'custom',
                     'bahan' => 'Katun Minimalis Sage & Khaki', 'ukuran' => '200 × 200 × 35 cm',
                     'tambahan' => '-', 'catatan' => 'Karet keliling tepi, lalan sisi tebal', 'jumlah' => 1],
    ],
    [
        'kode' => 'INV/20261010/SP/003', 'waktu' => '10 Oct 2026, 11:00',
        'status' => 'Selesai Dikirim', 'tagihan' => 195000, 'resi' => 'JNE · JN2026101012',
        'produk' => ['nama' => 'Sprei Katun Asterina Burgundy', 'gambar' => '', 'warna' => '', 'jenis' => 'standar',
                     'bahan' => 'Katun Asterina Burgundy', 'ukuran' => '180 × 200 × 30 cm',
                     'tambahan' => '-', 'catatan' => '-', 'jumlah' => 1],
    ],
];

if (!isset($_SESSION['pesanan'])) {
    $_SESSION['pesanan'] = [];
}

if (!isset($_SESSION['pesanan_contoh_dimuat']) || isset($_GET['reset'])) {
    $asli = array_filter($_SESSION['pesanan'], function ($p) { return empty($p['contoh']); });
    foreach ($pesananContoh as &$c) { $c['contoh'] = true; }
    unset($c);
    $_SESSION['pesanan'] = array_merge(array_values($asli), $pesananContoh);
    $_SESSION['pesanan_contoh_dimuat'] = true;

    if (isset($_GET['reset'])) {
        header('Location: pesananSaya.php');
        exit;
    }
}

// ---------- Proses unggah bukti transfer ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'unggah_bukti') {
    $kode = $_POST['kode'] ?? '';
    $file = $_FILES['bukti'] ?? null;
    $ekstensi = $file ? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) : '';

    if ($file && $file['error'] === UPLOAD_ERR_OK && in_array($ekstensi, ['jpg', 'jpeg', 'png', 'pdf']) && $file['size'] <= 2 * 1024 * 1024) {
        foreach ($_SESSION['pesanan'] as $i => $p) {
            if ($p['kode'] === $kode && in_array($p['status'], ['Menunggu Pembayaran', 'Bukti Pembayaran Ditolak'])) {
                $_SESSION['pesanan'][$i]['riwayat_bayar'][] = [
                    'waktu'  => date('d M Y'),
                    'jumlah' => $p['tagihan'],
                    'file'   => basename($file['name']),
                    'status' => 'Dicek Admin',
                ];
                $_SESSION['pesanan'][$i]['status'] = 'Menunggu Verifikasi';
                break;
            }
        }
    }

    header('Location: pesananSaya.php');
    exit;
}

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

$daftar = $_SESSION['pesanan'];
usort($daftar, function ($a, $b) {          // pesanan terbaru di atas
    return strtotime(str_replace(',', '', $b['waktu'])) <=> strtotime(str_replace(',', '', $a['waktu']));
});

if ($cari !== '') {
    $daftar = array_filter($daftar, function ($p) use ($cari) {
        return stripos($p['kode'], $cari) !== false || stripos($p['produk']['nama'], $cari) !== false;
    });
}

$dataModal = [];
foreach ($daftar as $p) {
    $s = $infoStatus[$p['status']] ?? $infoStatus['Menunggu Verifikasi'];
    $lunas = in_array($p['status'], ['Pembayaran Terverifikasi', 'Selesai Dikirim']);
    $ditolak = $p['status'] === 'Bukti Pembayaran Ditolak';

    $dataModal[$p['kode']] = [
        'kode'          => $p['kode'],
        'waktu'         => tanggal($p['waktu']),
        'label'         => $s['label'],
        'kelas'         => $s['kelas'],
        'judul'         => $s['judul'] ?? '',
        'teks'          => $s['teks'] ?? '',
        'aksi'          => $s['aksi'] ?? '',
        'tagihan'       => isset($p['tagihan']) && $p['tagihan'] !== null ? rupiah($p['tagihan']) : 'Belum ditetapkan',
        'catatan_admin' => $p['catatan_admin'] ?? '',
        'metode'        => $lunas ? 'Transfer ' . $rekening['bank'] . ' - Lunas'
                         : (isset($p['tagihan']) && $p['tagihan'] !== null ? 'Transfer ' . $rekening['bank'] . ' - Belum Lunas' : '-'),
        'produk'        => [
            'nama'     => $p['produk']['nama'] . ($p['produk']['jenis'] === 'custom' ? ' (Custom)' : ''),
            'jenis'    => $p['produk']['jenis'] === 'custom' ? 'Ukuran Custom' : 'Ukuran Standar',
            'bahan'    => $p['produk']['bahan'] ?? $p['produk']['nama'],
            'ukuran'   => $p['produk']['ukuran'],
            'tambahan' => $p['produk']['tambahan'],
            'catatan'  => $p['produk']['catatan'],
            'catatan_kutip' => $p['produk']['catatan'] !== '-' ? '"' . $p['produk']['catatan'] . '"' : '-',
        ],
    ];
}
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
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/pesananSaya.css">
</head>
<body>

<main class="container">
<div class="isi">

    <nav class="breadcrumb">
        <a href="beranda.html">Beranda</a> /
        <b>Pesanan Saya</b>
    </nav>

    <h1 class="judul-halaman">Pesanan Saya</h1>
    <p class="sub-judul">Pantau status pesanan, total tagihan dari Admin, dan status pembayaran Anda.</p>

    <!-- ================= PENCARIAN ================= -->
    <form method="get" class="kotak-cari kartu">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" name="cari" placeholder="Cari nomor invoice atau nama sprei..." value="<?= e($cari) ?>">
        <button type="submit" class="tombol">Cari</button>
    </form>

    <?php if (count($daftar) === 0) { ?>
        <div class="kartu kosong">
            <p>Pesanan tidak ditemukan.</p>
            <a href="pesananSaya.php" class="tombol">Tampilkan Semua</a>
        </div>
    <?php } ?>

    <!-- ================= DAFTAR PESANAN ================= -->
    <?php foreach ($daftar as $p) {
        $s       = $infoStatus[$p['status']] ?? $infoStatus['Menunggu Verifikasi'];
        $produk  = $p['produk'];
        $riwayat = $p['riwayat_bayar'] ?? [];
        $adaTagihan = isset($p['tagihan']) && $p['tagihan'] !== null;
        $lunas   = in_array($p['status'], ['Pembayaran Terverifikasi', 'Selesai Dikirim']);
    ?>
    <article class="kartu pesanan">

        <!-- kartu -->
        <div class="pesanan-kepala">
            <div class="info-waktu">
                <b>[<?= e($p['kode']) ?>]</b>
                <span>· Dipesan: <?= e(tanggal($p['waktu'])) ?> WIB</span>
                <?php if (!empty($p['waktu_verifikasi'])) { ?>
                    <span class="terverifikasi">· Terverifikasi: <?= e(tanggal($p['waktu_verifikasi'])) ?> WIB</span>
                <?php } ?>
            </div>
            <span class="badge badge-<?= $s['kelas'] ?>"><?= e($s['label']) ?></span>
        </div>

        <!-- Isi kartu -->
        <div class="pesanan-isi">
            <?php if (!empty($produk['gambar']) && file_exists(__DIR__ . '/' . $produk['gambar'])) { ?>
                <img src="<?= e($produk['gambar']) ?>" alt="<?= e($produk['nama']) ?>" class="foto-produk">
            <?php } elseif (!empty($produk['warna'])) { ?>
                <div class="foto-produk" style="background: <?= e($produk['warna']) ?>"></div>
            <?php } else { ?>
                <div class="foto-produk foto-kosong">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg>
                </div>
            <?php } ?>

            <div class="info-produk">
                <h3><?= e($produk['nama']) ?><?= $produk['jenis'] === 'custom' ? ' (Custom)' : '' ?></h3>
                <p><small>Spesifikasi:</small> <?= e($produk['ukuran']) ?> • <?= (int) $produk['jumlah'] ?> pcs</p>
                <?php if ($produk['tambahan'] !== '-') { ?>
                    <p><small>Kelengkapan:</small> <?= e($produk['tambahan']) ?></p>
                <?php } ?>
                <?php if ($produk['catatan'] !== '-') { ?>
                    <p><small>Catatan:</small> <?= e($produk['catatan']) ?></p>
                <?php } ?>
                <?php if (!empty($p['resi'])) { ?>
                    <p><small>Resi:</small> <?= e($p['resi']) ?></p>
                <?php } ?>
                <?php if (!empty($p['rincian'])) { ?>
                    <span class="pil-rincian">Rincian: <?= e($p['rincian']) ?></span>
                <?php } ?>
            </div>

            <div class="info-tagihan">
                <?php if ($lunas) { ?>
                    <small>Total Tagihan</small>
                    <b class="nominal"><?= rupiah($p['tagihan']) ?></b>
                    <small class="lunas">✓ Pembayaran Lunas</small>
                <?php } elseif ($adaTagihan) { ?>
                    <small>Total Tagihan (Resmi Admin)</small>
                    <b class="nominal"><?= rupiah($p['tagihan']) ?></b>
                    <small class="hijau">Tagihan sudah final &amp; ditetapkan</small>
                <?php } else { ?>
                    <small>Total Tagihan</small>
                    <b class="belum">Belum ditetapkan</b>
                    <small>Admin sedang memeriksa spesifikasi pesanan, tagihan resmi akan muncul di sini.</small>
                <?php } ?>
            </div>
        </div>

        <!-- status -->
        <?php if ($p['status'] === 'Bukti Pembayaran Ditolak') { ?>
            <div class="kotak-status kotak-tolak">
                <?php foreach ($riwayat as $r) { ?>
                    <div class="baris-riwayat">
                        <span><b>Riwayat Bukti Pembayaran</b> · <?= e(tanggal($r['waktu'])) ?> · <?= rupiah($r['jumlah']) ?> · <?= e($r['file']) ?></span>
                        <span class="badge-kecil">Status: <?= e($r['status']) ?></span>
                    </div>
                <?php } ?>
                <?php if (!empty($p['catatan_admin'])) { ?>
                    <p><b>Catatan Admin:</b> <?= e($p['catatan_admin']) ?></p>
                <?php } ?>
            </div>

        <?php } elseif ($p['status'] === 'Menunggu Pembayaran') { ?>
            <div class="kotak-status kotak-bayar">
                <div>
                    <b>Rekening Pembayaran Resmi Griya Sprei</b>
                    <p><?= e($rekening['bank']) ?>: <b><?= e($rekening['nomor']) ?></b></p>
                    <small>a.n <?= e($rekening['atas_nama']) ?></small>
                </div>
                <b><?= e($p['referensi'] ?? str_replace('/', '-', $p['kode'])) ?></b>
            </div>

        <?php } elseif ($p['status'] === 'Menunggu Verifikasi') { ?>
            <div class="kotak-status kotak-tunggu">
                <span class="ikon-jam">🕓</span>
                <p><b>Menunggu konfirmasi Admin Griya Sprei</b> — Verifikasi spesifikasi bahan/ukuran, pemilihan ongkir,
                   dan pencocokan total tagihan dilakukan langsung oleh pihak Admin. Pelanggan hanya perlu menunggu
                   konfirmasi tagihan resmi diterbitkan.</p>
                <span class="badge-kecil">Dicek oleh Admin</span>
            </div>
        <?php } ?>

        <!-- Tombol aksi -->
        <div class="pesanan-aksi">
            <div class="kiri">
                <?php if ($p['status'] === 'Bukti Pembayaran Ditolak') { ?>
                    <button type="button" class="tombol" data-buka-status="<?= e($p['kode']) ?>">Unggah Ulang Bukti Transfer</button>
                <?php } elseif ($p['status'] === 'Menunggu Pembayaran') { ?>
                    <a href="pembayaran.php?kode=<?= urlencode($p['kode']) ?>" class="tombol">Bayar Sekarang</a>
                <?php } ?>
                <button type="button" class="tombol tombol-garis" data-buka-detail="<?= e($p['kode']) ?>">Lihat Detail</button>
            </div>
            <?php if (!empty($p['batas']) && in_array($p['status'], ['Bukti Pembayaran Ditolak', 'Menunggu Pembayaran'])) { ?>
                <small>Batas <?= $p['status'] === 'Menunggu Pembayaran' ? 'pembayaran' : 'unggah ulang' ?>: <?= e(tanggal($p['batas'])) ?></small>
            <?php } ?>
        </div>
    </article>
    <?php } ?>

</div>
</main>


<!-- =====================================================
     STATUS PESANAN
     ===================================================== -->
<div class="modal-latar" id="modalStatus">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="statusJudul">
        <div class="modal-kepala">
            <div>
                <h2 id="statusJudul">Status Pesanan</h2>
                <small>Nomor: <span data-isi="kode"></span></small>
            </div>
            <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
        </div>

        <div class="modal-isi">
            <div class="kotak-info-status">
                <span class="ikon-status">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/></svg>
                </span>
                <div>
                    <b data-isi="judul"></b>
                    <p data-isi="teks"></p>
                </div>
            </div>

            <div class="ringkas-pesanan">
                <div>
                    <small>PESANAN</small>
                    <b data-isi="produk.nama"></b>
                </div>
                <div class="kanan">
                    <small>TOTAL TAGIHAN</small>
                    <b data-isi="tagihan"></b>
                </div>
            </div>
        </div>

        <div class="modal-kaki">
            <button type="button" class="tombol tombol-garis" data-tutup-modal>Tutup</button>
            <button type="button" class="tombol" id="tombolUnggah">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg>
                <span data-isi="aksi"></span>
            </button>
        </div>

        <!-- Form unggah -->
        <form method="post" enctype="multipart/form-data" id="formUnggah" hidden>
            <input type="hidden" name="aksi" value="unggah_bukti">
            <input type="hidden" name="kode" id="unggahKode">
            <input type="file" name="bukti" id="inputBukti" accept=".jpg,.jpeg,.png,.pdf">
        </form>
    </div>
</div>


<!-- =====================================================
    DETAIL PESANAN
     ===================================================== -->
<div class="modal-latar" id="modalDetail">
    <div class="modal modal-lebar" role="dialog" aria-modal="true" aria-labelledby="detailJudul">
        <div class="modal-kepala">
            <div>
                <h2 id="detailJudul">Detail Pesanan
                    <span class="kode-kecil">[<span data-isi="kode"></span>]</span>
                    <span class="badge" data-badge data-isi="label"></span>
                </h2>
                <small>Dipesan pada <span data-isi="waktu"></span> WIB</small>
            </div>
            <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
        </div>

        <div class="modal-isi">
            <!-- Status & verifikasi -->
            <section class="bagian-detail">
                <div class="judul-detail">
                    <b>STATUS PESANAN &amp; VERIFIKASI</b>
                    <span class="badge" data-badge data-isi="label"></span>
                </div>
                <div class="catatan-admin" id="detailCatatanAdmin">
                    <b>Catatan dari Admin Griya Sprei:</b>
                    <p>"<span data-isi="catatan_admin"></span>"</p>
                </div>
                <div class="dua-sisi">
                    <p><small>Total Tagihan</small> <b data-isi="tagihan"></b></p>
                    <p><small>Metode:</small> <b class="hijau" data-isi="metode"></b></p>
                </div>
            </section>

            <!-- Spesifikasi produk -->
            <section class="bagian-detail">
                <div class="judul-detail">
                    <b>Spesifikasi Produk</b>
                    <span class="badge-kecil" data-isi="produk.jenis"></span>
                </div>
                <h3 data-isi="produk.nama"></h3>
                <small>Bahan: <span data-isi="produk.bahan"></span></small>
                <div class="grid-spek">
                    <div>
                        <small>Ukuran Kasur</small>
                        <b data-isi="produk.ukuran"></b>
                    </div>
                    <div>
                        <small>Kelengkapan</small>
                        <b data-isi="produk.tambahan"></b>
                    </div>
                </div>
                <small>Catatan Khusus Pengiriman</small>
                <p class="kutipan" data-isi="produk.catatan_kutip"></p>
            </section>
        </div>

        <div class="modal-kaki">
            <button type="button" class="tombol tombol-garis" data-tutup-modal>Tutup</button>
        </div>
    </div>
</div>

<!-- Data pesanan  -->
<script type="application/json" id="dataPesanan"><?= json_encode($dataModal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="../assets/js/pesananSaya.js"></script>
</body>
</html>