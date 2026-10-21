<?php
// =====================================================
// HALAMAN KERANJANG (FRONTEND) - GRIYA SPREI
// Letak file: pbl-griya-sprei/frontend/pages/keranjang.php
// CSS: frontend/assets/css/keranjang.css
// JS : frontend/assets/js/keranjang.js
// Sudah termasuk tampilan "Keranjang Kosong" (keranjangKosong.php tidak dipakai lagi)
// Demo: keranjang.php?reset=1 (isi ulang) | keranjang.php?kosongkan=1 (kosongkan)
// =====================================================
session_start();

// ---------- 1. Data contoh (dipakai pertama kali / saat reset) ----------
$dataContoh = [
    1 => ['nama' => 'Sprei katun jepang', 'gambar' => '../assets/img/sprei1.jpg', 'jenis' => 'standar',
          'ukuran' => '200 × 160 × 30 cm', 'tambahan' => '2 sarung bantal + 1 sarung guling',
          'catatan' => 'Karet keliling 4 sudut kencang, jahitan rapi untuk kasur busa', 'jumlah' => 1],
    2 => ['nama' => 'Sprei Katun Modal Hilton', 'gambar' => '../assets/img/sprei2.jpg', 'jenis' => 'custom',
          'ukuran' => '200 × 160 × 30 cm', 'tambahan' => '2 sarung bantal',
          'catatan' => 'Sudut fitted elastic, penambahan tinggi +10 cm (ekstra)', 'jumlah' => 1],
    3 => ['nama' => 'Bedcover Set Asterina', 'gambar' => '../assets/img/bedcover1.jpg', 'jenis' => 'standar',
          'ukuran' => 'King 200 × 200 × 25 cm', 'tambahan' => '2 sarung bantal + 2 sarung guling',
          'catatan' => 'Kombinasi Two-Tone, dacron 7 oz premium', 'jumlah' => 1],
    4 => ['nama' => 'Sarung Bantal Polos', 'gambar' => '../assets/img/bantal1.jpg', 'jenis' => 'standar',
          'ukuran' => '50 × 70 cm', 'tambahan' => '-',
          'catatan' => 'Warna abu muda', 'jumlah' => 2],
];

// Isi keranjang pertama kali, atau isi ulang lewat keranjang.php?reset=1
if (!isset($_SESSION['keranjang']) || isset($_GET['reset'])) {
    $_SESSION['keranjang'] = $dataContoh;
    if (isset($_GET['reset'])) {
        header('Location: keranjang.php');
        exit;
    }
}

// Demo: kosongkan keranjang lewat keranjang.php?kosongkan=1
if (isset($_GET['kosongkan'])) {
    $_SESSION['keranjang'] = [];
    header('Location: keranjang.php');
    exit;
}

$cari    = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$halaman = isset($_GET['hal']) ? (int) $_GET['hal'] : 1;
if ($halaman < 1) $halaman = 1;

// ---------- 2. Proses tombol hapus / tambah / kurang ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

    if (isset($_SESSION['keranjang'][$id])) {
        if ($aksi === 'hapus') {
            unset($_SESSION['keranjang'][$id]);
        } elseif ($aksi === 'tambah') {
            $_SESSION['keranjang'][$id]['jumlah']++;
        } elseif ($aksi === 'kurang') {
            $_SESSION['keranjang'][$id]['jumlah']--;
            // jumlah 0 = produk dibuang dari keranjang
            if ($_SESSION['keranjang'][$id]['jumlah'] < 1) {
                unset($_SESSION['keranjang'][$id]);
            }
        }
    }

    // Kembali ke halaman ini (GET) supaya refresh tidak mengirim form lagi
    header('Location: keranjang.php?hal=' . $halaman . '&cari=' . urlencode($cari));
    exit;
}

// ---------- 3. Ambil isi keranjang dari session ----------
$semuaProduk = $_SESSION['keranjang'];

// Keranjang benar-benar kosong -> tampilkan bagian "Keranjang Kosong" di halaman ini
// (dulu halaman terpisah keranjangKosong.php, sekarang digabung)
$keranjangKosong = count($semuaProduk) == 0;

// Teks bagian keranjang kosong (mudah diubah di sini)
$judulKosong     = 'KERANJANG SAAT INI KOSONG';
$deskripsiKosong = 'Belum ada sprei atau bed cover yang ditambahkan ke keranjang.
                    Jelajahi katalog produk kami untuk memilih motif dan ukuran kasur
                    standar maupun custom yang sesuai dengan kebutuhan Anda.';

// ---------- 4. Siapkan data untuk ditampilkan ----------

// Total item (dihitung dari seluruh keranjang, bukan hasil cari)
$totalItem = 0;
foreach ($semuaProduk as $produk) {
    $totalItem += $produk['jumlah'];
}

// Pencarian: buang produk yang namanya tidak mengandung kata yang dicari
if ($cari != '') {
    foreach ($semuaProduk as $id => $produk) {
        if (stripos($produk['nama'], $cari) === false) {
            unset($semuaProduk[$id]);
        }
    }
}

// Paginasi: 3 produk per halaman
$perHalaman   = 3;
$totalHalaman = max(1, ceil(count($semuaProduk) / $perHalaman));
if ($halaman > $totalHalaman) $halaman = $totalHalaman;   // misal habis hapus item terakhir di hal. 2
$produkTampil = array_slice($semuaProduk, ($halaman - 1) * $perHalaman, $perHalaman, true);

// Fungsi kecil supaya teks aman ditampilkan
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

  <!-- ===== HEADER (sama dengan halaman lain) ===== -->
<?php
  // Jumlah barang di keranjang untuk badge ikon keranjang
  $jumlahKeranjang = 0;
  foreach ($_SESSION['keranjang'] ?? [] as $isi) {
      $jumlahKeranjang += $isi['jumlah'];
  }
?>
  <header class="header">
    <div class="brand">
      <img class="brand__logo" src="../assets/images/image1.png" alt="Logo Griya Sprei">
      <div>
        <h1 class="brand__name">Griya Sprei</h1>
        <p class="brand__tagline">Homemade Pillow, Bolster, Sprei, Bedcover, Etc.</p>
      </div>
    </div>
    <nav class="nav" aria-label="Navigasi utama">
      <a href="beranda.html" class="nav__link">Beranda</a>
      <a href="katalog.html" class="nav__link">Katalog</a>
      <a href="#" class="nav__link">Pembayaran</a>
      <a href="#" class="nav__link">Riwayat Pesanan</a>
    </nav>
    <div class="header__actions">
      <a href="keranjang.php" class="cart" aria-label="Keranjang">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 4h2l2.4 11h10.2L20 7H6.5"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg>
        <?php if ($jumlahKeranjang > 0) { ?><span class="cart__badge"><?= $jumlahKeranjang ?></span><?php } ?>
      </a>
      <a href="login.html" class="btn-login">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        Masuk / Daftar
      </a>
    </div>
  </header>

<!-- ================= ISI HALAMAN ================= -->
<main class="container">
<div class="isi">

<?php if ($keranjangKosong) { ?>

    <!-- ================= KOTAK KERANJANG KOSONG ================= -->
    <div class="kosong-besar" id="kotakKosong">

        <!-- Lingkaran ikon keranjang -->
        <div class="ikon-kosong">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 10h18l-2 9a2 2 0 0 1-2 1.6H7A2 2 0 0 1 5 19z"/>
                <path d="M8 10l2-5M16 10l-2-5"/>
                <circle cx="12" cy="15" r="0.6" fill="currentColor"/>
            </svg>

            <!-- Bulatan hijau kecil di kanan atas -->
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

    <!-- ================= KERANJANG ADA ISINYA ================= -->


    <!-- Pencarian + tombol tambah -->
    <div class="baris-atas">
        <form method="get" class="kotak-cari">
            <input type="text" name="cari" placeholder="🔍  Cari produk di keranjang..." value="<?= e($cari) ?>">
        </form>
        <a href="katalog.html" class="tombol">+ Tambah Produk Lain</a>
    </div>

    <div class="dua-kolom">

        <!-- ===== KIRI: daftar produk ===== -->
        <section>

            <?php if (count($produkTampil) == 0) { ?>
                <!-- Keranjang ada isinya, tapi tidak cocok dengan kata yang dicari -->
                <div class="kartu kosong">
                    <p>Produk "<b><?= e($cari) ?></b>" tidak ditemukan di keranjang.</p>
                    <a href="keranjang.php" class="tombol">Tampilkan Semua</a>
                </div>
            <?php } ?>

            <?php foreach ($produkTampil as $id => $produk) { ?>
            <div class="kartu produk">

                <img src="<?= e($produk['gambar']) ?>" alt="<?= e($produk['nama']) ?>" class="foto-produk">

                <div class="detail">

                    <!-- Judul + label + ikon -->
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

                    <!-- Spesifikasi -->
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

                    <!-- Jumlah + checkout -->
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

            <!-- Paginasi -->
            <?php if ($totalHalaman > 1) { ?>
            <div class="paginasi">
                <!-- tombol sebelumnya (pudar kalau di halaman 1) -->
                <a href="?hal=<?= $halaman - 1 ?>&cari=<?= urlencode($cari) ?>" class="<?= $halaman <= 1 ? 'mati' : '' ?>">‹</a>

                <?php for ($i = 1; $i <= $totalHalaman; $i++) { ?>
                    <a href="?hal=<?= $i ?>&cari=<?= urlencode($cari) ?>" class="<?= $i == $halaman ? 'aktif' : '' ?>"><?= $i ?></a>
                <?php } ?>

                <!-- tombol berikutnya (pudar kalau di halaman terakhir) -->
                <a href="?hal=<?= $halaman + 1 ?>&cari=<?= urlencode($cari) ?>" class="<?= $halaman >= $totalHalaman ? 'mati' : '' ?>">›</a>
            </div>
            <?php } ?>

        </section>


        <!-- ===== KANAN: ringkasan ===== -->
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

  <!-- ===== FOOTER (sama dengan halaman lain) ===== -->
  <footer class="footer">
    <div class="footer__inner">
      <div>
        <h3 class="footer__title">Griya Sprei Malang</h3>
        <p class="footer__text">Melayani sprei, bed cover, sarung bantal, dan sarung guling untuk rumah tangga, kos, homestay, hingga rumah sakit sejak 2010.</p>
        <p class="footer__text">Jl. Kanjuruhan Asri No. 8, Tlogomas, Lowokwaru, Kota Malang</p>
      </div>
      <div>
        <h3 class="footer__title">Kontak Kami</h3>
        <ul class="footer__list">
          <li>+62 812-3456-7890</li>
          <li>@produksispreikatun</li>
          <li>griyasprei@gmail.com</li>
        </ul>
      </div>
      <div>
        <h3 class="footer__title">Tautan</h3>
        <ul class="footer__list">
          <li><a href="katalog.html">Katalog</a></li>
          <li><a href="#">Pembayaran</a></li>
          <li><a href="#">Riwayat Pesanan</a></li>
        </ul>
      </div>
    </div>
  </footer>



<script src="../assets/js/keranjang.js"></script>
</body>
</html>