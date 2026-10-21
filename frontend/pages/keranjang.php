<?php
// =====================================================
// HALAMAN KERANJANG (FRONTEND) - GRIYA SPREI
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

// Keranjang benar-benar kosong -> halaman Keranjang Kosong
if (count($semuaProduk) == 0) {
    header('Location: keranjangKosong.php');
    exit;
}

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="../assets/css/keranjang.css">
</head>
<body>

<!-- ================= ISI HALAMAN ================= -->
<main class="isi">


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
</main>


<script src="../assets/js/keranjang.js"></script>
</body>
</html>