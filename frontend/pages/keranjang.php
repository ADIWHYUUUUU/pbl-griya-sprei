<?php

session_start();

if (!isset($_SESSION['keranjang']) || isset($_GET['reset'])) {
    $_SESSION['keranjang'] = [];
    if (isset($_GET['reset'])) {
        header('Location: keranjang.php');
        exit;
    }
}

// kosongkan keranjang
if (isset($_GET['kosongkan'])) {
    $_SESSION['keranjang'] = [];
    header('Location: keranjang.php');
    exit;
}

$cari    = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$halaman = isset($_GET['hal']) ? (int) $_GET['hal'] : 1;
if ($halaman < 1) $halaman = 1;

// Proses tombol hapus/tambah/kurang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

    if ($aksi === 'tambah_produk') {
        $nama = trim($_POST['nama'] ?? '');
        $jenis = ($_POST['jenis'] ?? '') === 'custom' ? 'custom' : 'standar';
        $panjang = filter_var($_POST['panjang'] ?? null, FILTER_VALIDATE_INT);
        $lebar = filter_var($_POST['lebar'] ?? null, FILTER_VALIDATE_INT);
        $tinggi = filter_var($_POST['tinggi'] ?? null, FILTER_VALIDATE_INT);
        $jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
        $bantal = filter_var($_POST['bantal'] ?? null, FILTER_VALIDATE_INT);
        $guling = filter_var($_POST['guling'] ?? null, FILTER_VALIDATE_INT);
        $catatan = trim($_POST['catatan'] ?? '');
        $warna = $_POST['warna'] ?? '';

        if (
            $nama === '' || strlen($nama) > 200 ||
            $panjang === false || $panjang < 100 || $panjang > 300 ||
            $lebar === false || $lebar < 80 || $lebar > 260 ||
            $tinggi === false || $tinggi < 10 || $tinggi > 50 ||
            $jumlah === false || $jumlah < 1 || $jumlah > 50 ||
            $bantal === false || $bantal < 0 || $bantal > 20 ||
            $guling === false || $guling < 0 || $guling > 20 ||
            strlen($catatan) > 500
        ) {
            http_response_code(400);
            exit('Data produk tidak valid. Kembali ke detail produk dan periksa isian Anda.');
        }

        $tambahan = [];
        if ($bantal > 0) $tambahan[] = $bantal . ' sarung bantal';
        if ($guling > 0) $tambahan[] = $guling . ' sarung guling';

        $keys = array_map('intval', array_keys($_SESSION['keranjang']));
        $idBaru = $keys ? max($keys) + 1 : 1;
        $_SESSION['keranjang'][$idBaru] = [
            'nama' => $nama,
            'gambar' => '',
            'warna' => preg_match('/^#[0-9a-fA-F]{6}$/', $warna) ? $warna : '#d9ccb4',
            'jenis' => $jenis,
            'ukuran' => "$panjang × $lebar × $tinggi cm",
            'tambahan' => $tambahan ? implode(' + ', $tambahan) : '-',
            'catatan' => $catatan ?: '-',
            'jumlah' => $jumlah,
        ];

        header('Location: keranjang.php');
        exit;
    }

    if (isset($_SESSION['keranjang'][$id])) {
        if ($aksi === 'hapus') {
            unset($_SESSION['keranjang'][$id]);
        } elseif ($aksi === 'tambah') {
            $_SESSION['keranjang'][$id]['jumlah']++;
        } elseif ($aksi === 'kurang') {
            $_SESSION['keranjang'][$id]['jumlah']--;
            if ($_SESSION['keranjang'][$id]['jumlah'] < 1) {
                unset($_SESSION['keranjang'][$id]);
            }
        }
    }
    header('Location: keranjang.php?hal=' . $halaman . '&cari=' . urlencode($cari));
    exit;
}

//  Ambil isi keranjang 
$semuaProduk = $_SESSION['keranjang'];
$keranjangKosong = count($semuaProduk) == 0;
$judulKosong     = 'KERANJANG SAAT INI KOSONG';
$deskripsiKosong = 'Belum ada sprei atau bed cover yang ditambahkan ke keranjang.
                    Jelajahi katalog produk kami untuk memilih motif dan ukuran kasur
                    standar maupun custom yang sesuai dengan kebutuhan Anda.';


$totalItem = 0;
foreach ($semuaProduk as $produk) {
    $totalItem += $produk['jumlah'];
}

if ($cari != '') {
    foreach ($semuaProduk as $id => $produk) {
        if (stripos($produk['nama'], $cari) === false) {
            unset($semuaProduk[$id]);
        }
    }
}

$perHalaman   = 3;
$totalHalaman = max(1, ceil(count($semuaProduk) / $perHalaman));
if ($halaman > $totalHalaman) $halaman = $totalHalaman;  
$produkTampil = array_slice($semuaProduk, ($halaman - 1) * $perHalaman, $perHalaman, true);

function e($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - Griya Sprei</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/keranjang.css">
</head>
<body>


<!-- ================= ISI HALAMAN ================= -->
<main class="container">
<div class="isi">

<?php if ($keranjangKosong) { ?>

    <!-- ================= KOTAK KERANJANG KOSONG ================= -->
    <div class="kosong-besar" id="kotakKosong">
        <div class="ikon-kosong">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 10h18l-2 9a2 2 0 0 1-2 1.6H7A2 2 0 0 1 5 19z"/>
                <path d="M8 10l2-5M16 10l-2-5"/>
                <circle cx="12" cy="15" r="0.6" fill="currentColor"/>
            </svg>
            <span class="titik">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                    <path d="M12 3v18M3 12h18M6 6l12 12M18 6 6 18"/>
                </svg>
            </span>
        </div>

        <h2><?= $judulKosong ?></h2>
        <p><?= $deskripsiKosong ?></p>

        <a href="katalog.html" class="tombol">Jelajahi Katalog &nbsp;→</a>
    </div>

<?php } else { ?>

    <div class="baris-atas">
        <form method="get" class="kotak-cari">
            <input type="text" name="cari" placeholder="🔍  Cari produk di keranjang..." value="<?= e($cari) ?>">
        </form>
        <a href="katalog.html" class="tombol">+ Tambah Produk Lain</a>
    </div>

    <div class="dua-kolom">

        <!-- =====daftar produk ===== -->
        <section>

            <?php if (count($produkTampil) == 0) { ?>
                <div class="kartu kosong">
                    <p>Produk "<b><?= e($cari) ?></b>" tidak ditemukan di keranjang.</p>
                    <a href="keranjang.php" class="tombol">Tampilkan Semua</a>
                </div>
            <?php } ?>

            <?php foreach ($produkTampil as $id => $produk) { ?>
            <div class="kartu produk">

                <?php if (!empty($produk['gambar'])) { ?>
                    <img src="<?= e($produk['gambar']) ?>" alt="<?= e($produk['nama']) ?>" class="foto-produk">
                <?php } else { ?>
                    <div class="foto-produk" role="img" aria-label="<?= e($produk['nama']) ?>"
                         style="background-color: <?= e($produk['warna'] ?? '#d9ccb4') ?>"></div>
                <?php } ?>

                <div class="detail">
                    <div class="judul-baris">
                        <h3>
                            <?= e($produk['nama']) ?>
                            <span class="label label-<?= e($produk['jenis']) ?>"><?= strtoupper(e($produk['jenis'])) ?></span>
                        </h3>
                        <div>
                            <a href="edit_item.php?id=<?= $id ?>" class="ikon" title="Ubah">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h12M3 12h8M3 18h5"/><path d="m14 19 6-6-2-2-6 6v2z"/></svg>
                            </a>

                            <form method="post" action="?hal=<?= $halaman ?>&cari=<?= urlencode($cari) ?>" class="form-hapus" style="display:inline">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <button type="submit" class="ikon" title="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="spesifikasi">
                        <div>
                            <small>Ukuran (P × L × T)</small>
                            <p><?= e($produk['ukuran']) ?></p>
                        </div>
                        <div>
                            <small>Tambahan</small>
                            <p><?= e($produk['tambahan']) ?></p>
                        </div>
                        <div class="penuh">
                            <small>Catatan</small>
                            <p><?= e($produk['catatan']) ?></p>
                        </div>
                    </div>
                    <div class="bawah">
                        <form method="post" action="?hal=<?= $halaman ?>&cari=<?= urlencode($cari) ?>" class="jumlah form-jumlah">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" name="aksi" value="kurang" data-jumlah="<?= $produk['jumlah'] ?>">−</button>
                            <span><?= $produk['jumlah'] ?></span>
                            <button type="submit" name="aksi" value="tambah">+</button>
                        </form>

                        <a href="checkout.php?id=<?= $id ?>" class="tombol">Checkout →</a>
                    </div>

                </div>
            </div>
            <?php } ?>
            <?php if ($totalHalaman > 1) { ?>
            <div class="paginasi">
                <a href="?hal=<?= $halaman - 1 ?>&cari=<?= urlencode($cari) ?>" class="<?= $halaman <= 1 ? 'mati' : '' ?>">‹</a>

                <?php for ($i = 1; $i <= $totalHalaman; $i++) { ?>
                    <a href="?hal=<?= $i ?>&cari=<?= urlencode($cari) ?>" class="<?= $i == $halaman ? 'aktif' : '' ?>"><?= $i ?></a>
                <?php } ?>
                <a href="?hal=<?= $halaman + 1 ?>&cari=<?= urlencode($cari) ?>" class="<?= $halaman >= $totalHalaman ? 'mati' : '' ?>">›</a>
            </div>
            <?php } ?>

        </section>

        <aside class="kartu ringkasan">
            <h2>Ringkasan keranjang</h2>

            <div class="kotak-abu">
                <div class="baris">
                    <span>Produk di keranjang</span>
                    <b><?= $totalItem ?> item</b>
                </div>
                <div class="baris">
                    <span>Total tagihan</span>
                    <b>Ditetapkan Admin</b>
                </div>
            </div>

            <p>Tekan <b>Checkout</b> pada produk yang ingin dipesan. Alamat dan metode pengiriman diisi pada langkah checkout untuk item tersebut.</p>
            <p>Setelah checkout, pesanan berstatus <b>Menunggu Verifikasi</b>. Total tagihan muncul di halaman Pesanan Saya setelah Admin memeriksa spesifikasi.</p>
        </aside>

    </div>

<?php } ?>

</div>
</main>
<script src="../assets/js/keranjang.js"></script>
</body>
</html>