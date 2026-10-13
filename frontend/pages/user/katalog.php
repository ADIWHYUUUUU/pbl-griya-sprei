<?php
require_once __DIR__ . '/../../../backend/app/controllers/ProductController.php';

$productController = new ProductController();
$catalogProducts = array_map(static function ($product) {
  $dimensions = array_filter([
    $product['panjang_standar'] ?? null,
    $product['lebar_standar'] ?? null,
    $product['tinggi_standar'] ?? null,
  ], static fn ($dimension) => $dimension !== null && $dimension !== '');
  $category = (string) ($product['kategori_produk'] ?? '');
  $imageName = $product['gambar_produk'] ?? '';

  return [
    'name' => $product['nama_produk'] ?? '',
    'type' => $category,
    'category' => stripos($category, 'bedcover') !== false ? 'bedcover' : 'sprei',
    'size' => implode(' × ', $dimensions),
    'price' => (float) ($product['harga_dasar'] ?? 0),
    'color' => '#e8e4d8',
    'desc' => $product['deskripsi'] ?? '',
    'image' => $imageName !== ''
      ? '/backend/public/uploads/' . rawurlencode(basename($imageName))
      : '',
  ];
}, $productController->getAllProducts());

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
  
  <link rel="stylesheet" href="../../assets/css/base.css">
  <link rel="stylesheet" href="../../assets/css/katalog.css">
</head>
<body>

 <?php require __DIR__ . '/../../components/header.php'; ?>

  <main class="container">

    

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

  <!-- ===== FOOTER COMPONENT ===== -->

<?php require __DIR__ . '/../../components/footer.php'; ?>

  <script>
    window.catalogProducts = <?= json_encode(
      $catalogProducts,
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ); ?>;
  </script>
  <script src="../../assets/js/app.js"></script>
</body>
</html>

