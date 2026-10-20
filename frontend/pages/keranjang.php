<?php
// =====================================================
// HALAMAN KERANJANG (DEMO TANPA DATABASE) - GRIYA SPREI
// Letak file: pbl-griya-sprei/frontend/pages/keranjang.php
// Satu file lengkap: PHP + CSS + JS, tanpa database.
// Data diambil dari array di bawah.
// =====================================================

$cari    = isset($_GET['cari']) ? $_GET['cari'] : '';
$halaman = isset($_GET['hal']) ? (int) $_GET['hal'] : 1;
if ($halaman < 1) $halaman = 1;

// Data contoh isi keranjang
$semuaProduk = [
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

// Pencarian: buang produk yang namanya tidak mengandung kata yang dicari
if ($cari != '') {
    foreach ($semuaProduk as $id => $produk) {
        if (stripos($produk['nama'], $cari) === false) {
            unset($semuaProduk[$id]);
        }
    }
}

// Total item
$totalItem = 0;
foreach ($semuaProduk as $produk) {
    $totalItem += $produk['jumlah'];
}

// Paginasi: 3 produk per halaman
$perHalaman   = 3;
$totalHalaman = ceil(count($semuaProduk) / $perHalaman);
$produkTampil = array_slice($semuaProduk, ($halaman - 1) * $perHalaman, $perHalaman, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - Griya Sprei</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
    <style>
/* =====================================================
   STYLE HALAMAN KERANJANG - GRIYA SPREI
   ===================================================== */

/* ---------- Warna utama (ubah di sini saja) ---------- */
:root {
    --hijau: #4b5e45;
    --hijau-muda: #e6ece2;
    --merah: #c0453d;
    --merah-muda: #fbe5e2;
    --latar: #f4f5f0;
    --garis: #e4e6df;
    --abu: #8a9086;
}

/* ---------- Dasar ---------- */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    background: var(--latar);
    color: #1f2a1c;
}

a {
    color: inherit;
    text-decoration: none;
}

small {
    color: var(--abu);
    font-size: 11px;
}


/* ---------- Tombol hijau ---------- */
.tombol {
    background: var(--hijau);
    color: white;
    padding: 9px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
}

.tombol:hover {
    opacity: 0.85;
}


/* ---------- Kartu putih ---------- */
.kartu {
    background: white;
    border: 1px solid var(--garis);
    border-radius: 12px;
    padding: 16px;
}


/* =====================================================
   ISI HALAMAN
   ===================================================== */
.isi {
    max-width: 1180px;
    margin: 0 auto;
    padding: 24px 20px 50px;
}

/* Baris pencarian */
.baris-atas {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
    max-width: 660px;
}

.kotak-cari {
    flex: 1;
}

.kotak-cari input {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--garis);
    border-radius: 10px;
    font-family: inherit;
}

.kotak-cari input:focus {
    outline: none;
    border-color: var(--hijau);
}

/* Kotak pemberitahuan database */
.peringatan {
    background: #fff6e0;
    border: 1px solid #f0d9a0;
    color: #6b5313;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 16px;
    font-size: 13px;
}

.peringatan small {
    color: #8a7340;
}

/* Layout: kiri produk, kanan ringkasan */
.dua-kolom {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 16px;
    align-items: start;
}


/* =====================================================
   KARTU PRODUK
   ===================================================== */
.produk {
    display: flex;
    gap: 22px;
    margin-bottom: 14px;
}

.foto-produk {
    width: 82px;
    height: 82px;
    object-fit: cover;
    border: 1px solid var(--garis);
    border-radius: 6px;
    background: #eceee7;     /* kotak abu kalau foto belum ada */
    color: transparent;      /* sembunyikan teks alt */
    font-size: 0;
}

.detail {
    flex: 1;
}

.judul-baris {
    display: flex;
    justify-content: space-between;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--garis);
}

.judul-baris h3 {
    font-size: 15px;
}

/* Label STANDAR / CUSTOM */
.label {
    font-size: 10px;
    padding: 3px 9px;
    border-radius: 20px;
    margin-left: 6px;
}

.label-standar {
    background: var(--hijau-muda);
    color: var(--hijau);
}

.label-custom {
    background: var(--merah-muda);
    color: var(--merah);
}

/* Ikon edit & hapus (garis abu-abu) */
.ikon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: var(--abu);
    display: inline-flex;
    border-radius: 6px;
}

.ikon:hover {
    color: #1f2a1c;
    background: var(--latar);
}

/* Spesifikasi 2 kolom */
.spesifikasi {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--garis);
}

.spesifikasi p {
    font-size: 13px;
}

.penuh {
    grid-column: 1 / 3;   /* memenuhi 2 kolom */
}

/* Bagian bawah kartu */
.bawah {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
}

/* Tombol jumlah − 1 + */
.jumlah {
    display: flex;
    align-items: center;
    border: 1px solid var(--garis);
    border-radius: 6px;
}

.jumlah button {
    width: 28px;
    height: 28px;
    background: white;
    border: none;
    cursor: pointer;
}

.jumlah button:hover {
    background: var(--latar);
}

.jumlah span {
    width: 30px;
    text-align: center;
}

.kosong {
    text-align: center;
    padding: 40px;
}

.kosong p {
    margin-bottom: 12px;
}


/* =====================================================
   PAGINASI
   ===================================================== */
.paginasi {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}

.paginasi a {
    width: 30px;
    height: 30px;
    line-height: 28px;
    text-align: center;
    background: white;
    border: 1px solid var(--garis);
    border-radius: 6px;
}

.paginasi a.mati {
    opacity: 0.4;
    pointer-events: none;   /* tidak bisa diklik */
}

.paginasi a.aktif {
    background: var(--hijau);
    color: white;
}


/* =====================================================
   RINGKASAN
   ===================================================== */
.ringkasan h2 {
    font-size: 16px;
    margin-bottom: 14px;
}

.kotak-abu {
    background: #f0f1ec;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 16px;
}

.baris {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--abu);
    padding: 4px 0;
}

.baris b {
    color: #1f2a1c;
}

.ringkasan p {
    font-size: 12px;
    color: #555;
    margin-bottom: 12px;
    line-height: 1.6;
}


/* =====================================================
   TAMPILAN HP (layar kecil)
   ===================================================== */
@media (max-width: 900px) {
    .dua-kolom {
        grid-template-columns: 1fr;   /* jadi 1 kolom */
    }
}

@media (max-width: 700px) {
    .baris-atas {
        flex-direction: column;
    }

    .produk {
        flex-direction: column;
    }

    .foto-produk {
        width: 100%;
        height: 160px;
    }
}
    </style>
</head>
<body>

<!-- ================= ISI HALAMAN ================= -->
<main class="isi">


    <!-- Pencarian + tombol tambah -->
    <div class="baris-atas">
        <form method="get" class="kotak-cari">
            <input type="text" name="cari" placeholder="🔍  Cari produk di keranjang..." value="<?= htmlspecialchars($cari) ?>">
        </form>
        <a href="katalog.html" class="tombol">+ Tambah Produk Lain</a>
    </div>

    <div class="dua-kolom">

        <!-- ===== KIRI: daftar produk ===== -->
        <section>

            <?php if (count($produkTampil) == 0) { ?>
                <div class="kartu kosong">
                    <p>Keranjang masih kosong.</p>
                    <a href="katalog.html" class="tombol">Lihat Katalog</a>
                </div>
            <?php } ?>

            <?php foreach ($produkTampil as $id => $produk) { ?>
            <div class="kartu produk">

                <img src="<?= $produk['gambar'] ?>" alt="<?= $produk['nama'] ?>" class="foto-produk">

                <div class="detail">

                    <!-- Judul + label + ikon -->
                    <div class="judul-baris">
                        <h3>
                            <?= $produk['nama'] ?>
                            <span class="label label-<?= $produk['jenis'] ?>"><?= strtoupper($produk['jenis']) ?></span>
                        </h3>
                        <div>
                            <a href="edit_item.php?id=<?= $id ?>" class="ikon" title="Ubah">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h12M3 12h8M3 18h5"/><path d="m14 19 6-6-2-2-6 6v2z"/></svg>
                            </a>

                            <form method="post" class="form-hapus" style="display:inline">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" name="aksi" value="hapus" class="ikon" title="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Spesifikasi -->
                    <div class="spesifikasi">
                        <div>
                            <small>Ukuran (P × L × T)</small>
                            <p><?= $produk['ukuran'] ?></p>
                        </div>
                        <div>
                            <small>Tambahan</small>
                            <p><?= $produk['tambahan'] ?></p>
                        </div>
                        <div class="penuh">
                            <small>Catatan</small>
                            <p><?= $produk['catatan'] ?></p>
                        </div>
                    </div>

                    <!-- Jumlah + checkout -->
                    <div class="bawah">
                        <form method="post" class="jumlah">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" name="aksi" value="kurang">−</button>
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


<script>
// =====================================================
// SCRIPT HALAMAN KERANJANG - GRIYA SPREI
// =====================================================


// Konfirmasi sebelum menghapus produk
var semuaFormHapus = document.querySelectorAll('.form-hapus');

semuaFormHapus.forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var yakin = confirm('Yakin ingin menghapus produk ini dari keranjang?');

        if (!yakin) {
            event.preventDefault();   // batal kirim form
        }
    });
});
</script>
</body>
</html>