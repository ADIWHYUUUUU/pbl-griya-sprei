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

<!-- ===== HEADER ===== -->
 <?php require __DIR__ . '/../../components/header.php'; ?>

<!-- ================= ISI HALAMAN ================= -->
<main class="container">
<div class="isi">

    <!-- Pencarian + tombol tambah -->
    <div class="baris-atas">
        <form method="get" class="kotak-cari">
            <input type="text" name="cari" placeholder="🔍  Cari produk di keranjang..." value="">
        </form>
        <a href="katalog.html" class="tombol">+ Tambah Produk Lain</a>
    </div>

    <div class="dua-kolom">

        <!-- ===== KIRI: daftar produk ===== -->
        <section>

            <div class="kartu produk">
                <img src="../assets/img/sprei1.jpg" alt="Sprei katun jepang" class="foto-produk">
                <div class="detail">
                    <div class="judul-baris">
                        <h3>
                            Sprei katun jepang
                            <span class="label label-standar">STANDAR</span>
                        </h3>
                        <div>
                            <a href="edit_item.php?id=1" class="ikon" title="Ubah">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h12M3 12h8M3 18h5"/><path d="m14 19 6-6-2-2-6 6v2z"/></svg>
                            </a>
                            <form method="post" class="form-hapus" style="display:inline">
                                <button type="submit" class="ikon" title="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="spesifikasi">
                        <div>
                            <small>Ukuran (P × L × T)</small>
                            <p>200 × 160 × 30 cm</p>
                        </div>
                        <div>
                            <small>Tambahan</small>
                            <p>2 sarung bantal + 1 sarung guling</p>
                        </div>
                        <div class="penuh">
                            <small>Catatan</small>
                            <p>Karet keliling 4 sudut kencang, jahitan rapi untuk kasur busa</p>
                        </div>
                    </div>

                    <div class="bawah">
                        <div class="jumlah form-jumlah">
                            <button type="button">−</button>
                            <span>1</span>
                            <button type="button">+</button>
                        </div>
                        <a href="checkout.php?id=1" class="tombol">Checkout →</a>
                    </div>
                </div>
            </div>

            <div class="kartu produk">
                <img src="../assets/img/sprei2.jpg" alt="Sprei Katun Modal Hilton" class="foto-produk">
                <div class="detail">
                    <div class="judul-baris">
                        <h3>
                            Sprei Katun Modal Hilton
                            <span class="label label-custom">CUSTOM</span>
                        </h3>
                        <div>
                            <a href="edit_item.php?id=2" class="ikon" title="Ubah">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h12M3 12h8M3 18h5"/><path d="m14 19 6-6-2-2-6 6v2z"/></svg>
                            </a>
                            <form method="post" class="form-hapus" style="display:inline">
                                <button type="submit" class="ikon" title="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="spesifikasi">
                        <div>
                            <small>Ukuran (P × L × T)</small>
                            <p>200 × 160 × 30 cm</p>
                        </div>
                        <div>
                            <small>Tambahan</small>
                            <p>2 sarung bantal</p>
                        </div>
                        <div class="penuh">
                            <small>Catatan</small>
                            <p>Sudut fitted elastic, penambahan tinggi +10 cm (ekstra)</p>
                        </div>
                    </div>

                    <div class="bawah">
                        <div class="jumlah form-jumlah">
                            <button type="button">−</button>
                            <span>1</span>
                            <button type="button">+</button>
                        </div>
                        <a href="checkout.php?id=2" class="tombol">Checkout →</a>
                    </div>
                </div>
            </div>

            <!-- Paginasi -->
            <div class="paginasi">
                <a href="#" class="mati">‹</a>
                <a href="#" class="aktif">1</a>
                <a href="#">2</a>
                <a href="#">›</a>
            </div>

        </section>

        <!-- ===== KANAN: ringkasan ===== -->
        <aside class="kartu ringkasan">
            <h2>Ringkasan keranjang</h2>

            <div class="kotak-abu">
                <div class="baris">
                    <span>Produk di keranjang</span>
                    <b>5 item</b>
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

</div>
</main>

<!-- ===== FOOTER ===== -->
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