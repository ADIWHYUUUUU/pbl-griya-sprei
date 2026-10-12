<?php

$nama_toko = "Griya Sprei";
$tagline   = "Homemade Pillow, Bolster, Sprei, Bedcover, Etc.";
$alamat    = "Jl. Kanjuruhan Asri No. 8, Tlogomas, Kec. Lowokwaru, Kota Malang";
$instagram = "@produksispreikatun";
$email     = "griyasprei@gmail.com";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Katalog Produk - <?php echo $nama_toko; ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/base.css">
</head>
<body>

 <?php require __DIR__ . '/../../components/header.php'; ?>

  <main class="container">

    <section class="hero">
      <h2 class="hero__title">Sprei dan Bedcover</h2>
      <p class="hero__text">Tersedia pilihan ukuran dan berbagai macam motif yang sesuai dengan keinginan anda.</p>
    </section>

    <section class="toolbar">
      <label class="search">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" id="searchInput" placeholder="Cari sprei, bedcover, motif...">
      </label>

      <button class="chip chip--active" data-category="all">Semua Produk</button>
      <button class="chip" data-category="sprei">Sprei Katun</button>
      <button class="chip" data-category="bedcover">Bedcover Set</button>

      <select class="select" id="sizeFilter" aria-label="Filter ukuran">
        <option value="all">Filter Ukuran (Semua)</option>
        <option value="160 × 200 × 30">160 × 200 × 30</option>
        <option value="180 × 200 × 30">180 × 200 × 30</option>
        <option value="180 × 200 × 35">180 × 200 × 35</option>
        <option value="200 × 200 × 35">200 × 200 × 35</option>
      </select>
    </section>

    <section class="grid" id="productGrid" aria-live="polite"></section>

    <nav class="pagination" id="pagination" aria-label="Halaman"></nav>
  </main>

  <footer class="footer">
    <div class="footer__item">
      <span class="footer__icon">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
      </span>
      <?php echo $alamat; ?>
    </div>
    <div class="footer__item">
      <span class="footer__icon">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
      </span>
      <?php echo $instagram; ?>
    </div>
    <div class="footer__item">
      <span class="footer__icon">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
      </span>
      <?php echo $email; ?>
    </div>
  </footer>

  <script src="assets/js/app.js"></script>
</body>
</html>

