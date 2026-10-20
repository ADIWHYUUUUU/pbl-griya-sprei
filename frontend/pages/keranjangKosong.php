<?php
// =====================================================
// HALAMAN KERANJANG KOSONG - GRIYA SPREI
// Letak file: pbl-griya-sprei/frontend/pages/keranjangKosong.php
// CSS: frontend/assets/css/keranjangKosong.css
// JS : frontend/assets/js/keranjangKosong.js
//
// Halaman ini dibuka otomatis dari keranjang.php
// kalau keranjang tidak berisi produk.
// =====================================================

// Teks yang tampil di halaman (mudah diubah di sini)
$judul     = 'KERANJANG SAAT INI KOSONG';
$deskripsi = 'Belum ada sprei atau bed cover yang ditambahkan ke keranjang.
              Jelajahi katalog produk kami untuk memilih motif dan ukuran kasur
              standar maupun custom yang sesuai dengan kebutuhan Anda.';
$linkKatalog = 'katalog.html';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Kosong - Griya Sprei</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="../assets/css/keranjangKosong.css">
</head>
<body>

<main class="isi">

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

        <h2><?= $judul ?></h2>
        <p><?= $deskripsi ?></p>

        <a href="<?= $linkKatalog ?>" class="tombol">Jelajahi Katalog &nbsp;→</a>
    </div>

</main>

<script src="../assets/js/keranjangKosong.js"></script>
</body>
</html>