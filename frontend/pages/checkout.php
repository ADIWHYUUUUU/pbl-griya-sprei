<?php
// =====================================================
// HALAMAN CHECKOUT (FRONTEND, TANPA DATABASE) - GRIYA SPREI
// Letak file: pbl-griya-sprei/frontend/pages/checkout.php
// CSS: frontend/assets/css/checkout.css
// JS : frontend/assets/js/checkout.js
//
// Dibuka dari tombol "Checkout →" di keranjang.php  -> checkout.php?id=1
// Produk diambil dari $_SESSION['keranjang'] (sama seperti keranjang.php).
// Setelah "Kirim Pesanan": produk keluar dari keranjang,
// pesanan disimpan sementara di $_SESSION['pesanan'].
// Nanti tim backend tinggal mengganti bagian session dengan query database.
// =====================================================

session_start();

// Fungsi kecil supaya teks aman ditampilkan
function e($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

// ---------- 1. Data pemesan (contoh, nanti dari akun yang login) ----------
$pemesan = [
    'nama'    => 'Vico Dwi Wijaya',
    'telepon' => '+62 812-4491-1980',
    'email'   => 'vicodwiwijaya@gmail.com',
];

$alamatAwal  = 'Jl. Ngawi Barat No. 12, RT 03 / RW 07, Kel. Ngawi Barat, Kec. Lowokwaru, Kota Malang, Jawa Timur (Kode Pos 65141)';
$catatanAwal = 'Rumah pagar hitam samping warung Bu Sri, pagar selalu dibuka saja tidak dikunci.';

// ---------- 2. Pilihan metode pengiriman ----------
$metodeKirim = [
    'instan' => ['kelompok' => 'Kurir Instan', 'jenis' => 'instan', 'nama' => 'Gojek / Grab Express',
                 'info' => 'Khusus area Kota Malang',
                 'waktu' => 'Pesanan dikirim di hari yang sama setelah produksi selesai'],
    'jnt'    => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'J&T Express',
                 'info' => 'Pengiriman reguler dengan jaringan kemitraan ke seluruh area di Indonesia.',
                 'waktu' => 'Perkiraan: 1 – 3 hari'],
    'jne'    => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'JNE Express',
                 'info' => 'Pengiriman reguler dengan jaringan kemitraan ke seluruh area di Indonesia.',
                 'waktu' => 'Perkiraan: 1 – 3 hari'],
];

// ---------- 3. Pesanan berhasil dikirim -> tampilkan halaman sukses ----------
if (isset($_GET['berhasil']) && isset($_SESSION['pesanan_terakhir'])) {
    $pesananBaru = $_SESSION['pesanan_terakhir'];
}

// ---------- 4. Proses tombol "Kirim Pesanan" ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $metode = isset($_POST['metode']) ? $_POST['metode'] : '';
    $setuju = isset($_POST['setuju']);

    // Data tidak lengkap -> kembali ke checkout
    if (!isset($_SESSION['keranjang'][$id]) || !isset($metodeKirim[$metode]) || !$setuju) {
        header('Location: checkout.php?id=' . $id);
        exit;
    }

    $pesanan = [
        'kode'     => 'GS-' . date('ymd') . '-' . str_pad($id, 3, '0', STR_PAD_LEFT),
        'produk'   => $_SESSION['keranjang'][$id],
        'alamat'   => trim($_POST['alamat']) ?: $alamatAwal,
        'catatan_alamat' => trim($_POST['catatan_alamat']),
        'metode'   => $metodeKirim[$metode]['nama'],
        'catatan_kirim' => trim($_POST['catatan_kirim']),
        'status'   => 'Menunggu Verifikasi',
        'waktu'    => date('d M Y, H:i'),
    ];

    $_SESSION['pesanan'][]          = $pesanan;   // daftar untuk halaman Pesanan Saya
    $_SESSION['pesanan_terakhir']   = $pesanan;   // untuk ditampilkan di halaman sukses
    unset($_SESSION['keranjang'][$id]);           // produk keluar dari keranjang

    header('Location: checkout.php?berhasil=1');
    exit;
}

// ---------- 5. Ambil produk yang mau di-checkout ----------
if (!isset($pesananBaru)) {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Produk tidak ada di keranjang -> kembali ke keranjang
    if (!isset($_SESSION['keranjang'][$id])) {
        header('Location: keranjang.php');
        exit;
    }

    $produk = $_SESSION['keranjang'][$id];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Griya Sprei</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="../assets/css/checkout.css">
</head>
<body>

<main class="isi">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="beranda.html">Beranda</a> /
        <a href="keranjang.php">Keranjang</a> /
        <b>Checkout</b>
    </nav>

<?php if (isset($pesananBaru)) { ?>

    <!-- ================= PESANAN BERHASIL ================= -->
    <section class="kartu sukses">
        <div class="ikon-sukses">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h2>Pesanan Berhasil Dikirim</h2>
        <p>Kode pesanan <b><?= e($pesananBaru['kode']) ?></b> untuk <b><?= e($pesananBaru['produk']['nama']) ?></b>
           sekarang berstatus <span class="status">MENUNGGU VERIFIKASI</span></p>
        <p class="kecil">Admin akan memverifikasi ukuran dan menghitung total tagihan beserta ongkos kirim
           (<?= e($pesananBaru['metode']) ?>) maksimal 1×24 jam.</p>
        <div class="aksi-sukses">
            <a href="keranjang.php" class="tombol tombol-garis">Kembali ke Keranjang</a>
            <a href="pesanan_saya.php" class="tombol">Lihat Pesanan Saya →</a>
        </div>
    </section>

<?php } else { ?>

    <form method="post" id="formCheckout">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- ================= 1. DATA & ALAMAT ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                </span>
                <div>
                    <h2>Data &amp; Alamat Pengiriman</h2>
                    <small>Informasi pemesan dan tujuan pengiriman</small>
                </div>
                <button type="button" class="tombol-kecil" id="tombolUbahAlamat">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m14 5 5 5-10 10H4v-5z"/></svg>
                    <span>Ubah Alamat</span>
                </button>
            </div>

            <div class="dua-kolom">
                <div>
                    <small class="judul-kecil">IDENTITAS PEMESAN</small>
                    <p class="baris-ikon"><b><?= e($pemesan['nama']) ?></b></p>
                    <p class="baris-ikon">📞 <?= e($pemesan['telepon']) ?></p>
                    <p class="baris-ikon">✉️ <?= e($pemesan['email']) ?></p>
                </div>

                <div class="kotak-alamat">
                    <small class="judul-kecil">ALAMAT PENGIRIMAN LENGKAP</small>

                    <!-- Tampilan biasa -->
                    <div id="alamatTampil">
                        <p id="teksAlamat"><?= e($alamatAwal) ?></p>
                        <p class="catatan-alamat">ⓘ <b>Catatan alamat:</b> <span id="teksCatatanAlamat"><?= e($catatanAwal) ?></span></p>
                    </div>

                    <!-- Mode ubah (muncul saat tombol "Ubah Alamat" ditekan) -->
                    <div id="alamatUbah" class="sembunyi">
                        <label>Alamat lengkap
                            <textarea name="alamat" id="inputAlamat" rows="3"><?= e($alamatAwal) ?></textarea>
                        </label>
                        <label>Catatan alamat
                            <input type="text" name="catatan_alamat" id="inputCatatanAlamat" value="<?= e($catatanAwal) ?>">
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 2. DETAIL PESANAN ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                </span>
                <div>
                    <h2>Detail Pesanan</h2>
                    <small>Konfirmasi detail spesifikasi pesanan</small>
                </div>
            </div>

            <div class="produk">
                <img src="<?= e($produk['gambar']) ?>" alt="<?= e($produk['nama']) ?>" class="foto-produk">
                <div class="detail">
                    <h3>
                        <?= e($produk['nama']) ?>
                        <span class="label label-<?= e($produk['jenis']) ?>"><?= strtoupper(e($produk['jenis'])) ?></span>
                    </h3>
                    <p><small>Ukuran:</small> <?= e($produk['ukuran']) ?> &nbsp;&nbsp; <small>Jumlah:</small> <?= (int) $produk['jumlah'] ?> pcs</p>
                    <p><small>Tambahan:</small> <?= e($produk['tambahan']) ?></p>
                    <p class="catatan-produk"><b>Catatan:</b> <?= e($produk['catatan']) ?></p>
                </div>
            </div>
        </section>

        <!-- ================= 3. METODE PENGIRIMAN ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>
                </span>
                <div>
                    <h2>Metode Pengiriman</h2>
                    <small>Pilih preferensi pengiriman produk</small>
                </div>
            </div>

            <div class="pilihan-kirim">
                <?php $pertama = true; foreach ($metodeKirim as $kode => $m) { ?>
                <label class="opsi <?= $pertama ? 'terpilih' : '' ?>">
                    <input type="radio" name="metode" value="<?= $kode ?>" <?= $pertama ? 'checked' : '' ?>>
                    <span class="kelompok kelompok-<?= $m['jenis'] ?>"><?= e($m['kelompok']) ?></span>
                    <b><?= e($m['nama']) ?></b>
                    <small><?= e($m['info']) ?></small>
                    <small class="waktu"><?= e($m['waktu']) ?></small>
                </label>
                <?php $pertama = false; } ?>
            </div>

            <label class="label-catatan">Catatan pengiriman <small>(Opsional)</small>
                <textarea name="catatan_kirim" rows="2" placeholder="Contoh: Titipkan di satpam jika rumah sedang kosong, hubungi nomor ini sebelum datang"></textarea>
            </label>
        </section>

        <!-- ================= 4. ALUR & PERSETUJUAN ================= -->
        <section class="kartu kartu-info">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <div>
                    <h2>Alur Penetapan Total &amp; Validasi Admin</h2>
                    <p class="teks-alur">
                        Setelah Anda menekan tombol <b>"Kirim Pesanan"</b> di bawah, pesanan akan diteruskan ke sistem
                        produksi dengan status <span class="status">MENUNGGU VERIFIKASI</span>. Admin akan segera
                        memverifikasi pesanan dan menghitung total biaya serta ongkos kirim sesuai alamat Anda,
                        kemudian menerbitkan invoice resmi.
                    </p>
                </div>
            </div>

            <label class="persetujuan">
                <input type="checkbox" name="setuju" id="cekSetuju">
                <span>Saya mengonfirmasi bahwa data ukuran kasur (P × L × T) sudah diukur secara akurat. Saya mengerti
                      bahwa pesanan yang telah masuk tahap pemotongan kain tidak dapat dibatalkan atau diubah.</span>
            </label>
        </section>

        <!-- ================= TOMBOL KIRIM ================= -->
        <div class="bagian-kirim">
            <button type="submit" class="tombol tombol-besar" id="tombolKirim" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                Kirim Pesanan
            </button>
            <small>🕓 Verifikasi Admin: Maks 1×24 Jam</small>
        </div>
    </form>

<?php } ?>

</main>

<script src="../assets/js/checkout.js"></script>
</body>
</html>