<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Beranda - Griya Sprei</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/beranda.css">
</head>
<body>

  <!-- ===== HEADER ===== -->
  <header class="header">
    <div class="brand">
      <img class="brand__logo" src="../assets/images/image1.png" alt="Logo Griya Sprei">
      <div>
        <h1 class="brand__name">Griya Sprei</h1>
        <p class="brand__tagline">Homemade Pillow, Bolster, Sprei, Bedcover, Etc.</p>
      </div>
    </div>
    <nav class="nav" aria-label="Navigasi utama">
      <a href="beranda.html" class="nav__link nav__link--active">Beranda</a>
      <a href="katalog.html" class="nav__link">Katalog</a>
      <a href="#" class="nav__link">Pembayaran</a>
      <a href="#" class="nav__link">Riwayat Pesanan</a>
    </nav>
    <div class="header__actions">
      <a href="#" class="cart" aria-label="Keranjang">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 4h2l2.4 11h10.2L20 7H6.5"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg>
        <span class="cart__badge">3</span>
      </a>
      <a href="login.html" class="btn-login">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        Masuk / Daftar
      </a>
    </div>
  </header>

  <main class="container">

    <!-- ===== HERO ===== -->
    <section class="hero">
      <div class="hero__text">
        <span class="hero__pill">Griya Sprei Malang · Homemade sejak 2010</span>
        <h2 class="hero__title">Sprei dan bed cover pas di kasur Anda, ukuran standar maupun custom.</h2>
        <p class="hero__desc">Pilih model kain, tentukan ukuran panjang × lebar × tinggi kasur presisi cm, tambahkan sarung bantal guling, dan nikmati tidur nyaman dengan bahan MicroSilk &amp; Katun Jepang halus.</p>
        <a href="katalog.html" class="btn btn--lg">Lihat Katalog</a>
      </div>
      <!-- Ganti dengan foto: <img src="../assets/images/hero.png" alt="..."> -->
      <div class="hero__image media" style="--ph:#9bb87a"></div>
    </section>

    <!-- ===== JELAJAHI KATALOG ===== -->
    <section class="section">
      <h2 class="section__title">Jelajahi katalog</h2>
      <div class="explore">
        <article class="explore__card">
          <div class="explore__image media" style="--ph:#4a74b5"></div>
          <div class="explore__row">
            <div><h3>Sprei</h3><p>Standar atau custom sesuai kasur.</p></div>
            <a href="katalog.html" class="btn">Lihat</a>
          </div>
        </article>
        <article class="explore__card">
          <div class="explore__image media" style="--ph:#a98466"></div>
          <div class="explore__row">
            <div><h3>Bed Cover</h3><p>Pilih model dan ukuran.</p></div>
            <a href="katalog.html" class="btn">Lihat</a>
          </div>
        </article>
      </div>
    </section>

    <!-- ===== PRODUK PILIHAN (diisi beranda.js) ===== -->
    <section class="section">
      <h2 class="section__title">Produk Pilihan &amp; Best Seller</h2>
      <div class="featured" id="featuredGrid"></div>
    </section>

    <!-- ===== CARA PEMESANAN ===== -->
    <section class="section">
      <h2 class="section__title">Cara Pemesanan Mudah</h2>
      <div class="steps">
        <article class="step"><div class="step__head"><span>1</span><h3>Pilih &amp; Konfigurasi</h3></div><p>Tentukan model kain, ukuran, dan ekstra sarban/sargul.</p></article>
        <article class="step"><div class="step__head"><span>2</span><h3>Checkout Pesanan</h3></div><p>Isi alamat pengiriman dan pilih opsi pengiriman tujuan.</p></article>
        <article class="step"><div class="step__head"><span>3</span><h3>Admin Tinjau Harga</h3></div><p>Spesifikasi kasur dicek, tagihan resmi akan diterbitkan.</p></article>
        <article class="step"><div class="step__head"><span>4</span><h3>Transfer &amp; Unggah Bukti</h3></div><p>Bayar sesuai invoice via transfer bank dan unggah bukti transfer.</p></article>
        <article class="step"><div class="step__head"><span>5</span><h3>Jahit &amp; Pantau Status</h3></div><p>Pantau status pengerjaan secara real-time.</p></article>
      </div>
    </section>
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

  <script src="../assets/js/data.js"></script>
  <script src="../assets/js/beranda.js"></script>
</body>
</html>