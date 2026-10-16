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
    $categoryKey = strtoupper(str_replace(['_', ' '], '', $category));
    $categoryLabel = match ($categoryKey) {
      'SPREI' => 'Sprei Katun',
      'BEDCOVER' => 'Bedcover Set',
      default => $category,
    };
    $imageName = (string) ($product['gambar_produk'] ?? '');
    return [
        // ID ASLI DATABASE
        'id' => (int) $product['id_produk'],
        // DATA PRODUK
        'name' => $product['nama_produk'] ?? '',
        'type' => $categoryLabel,
        'category' => $categoryKey === 'BEDCOVER'
            ? 'bedcover'
            : 'sprei',
        'size' => implode(' × ', $dimensions),
        'price' => (float) ($product['harga_dasar'] ?? 0),
        'color' => '#e8e4d8',
        'desc' => $product['deskripsi'] ?? '',
        'stock' => (int) ($product['stok'] ?? 0),
        // GAMBAR HASIL UPLOAD ADMIN
        'image' => $imageName !== ''
            ? '/frontend/uploads/produk/' . rawurlencode(basename($imageName))
            : '',
        // LINK DETAIL BERDASARKAN ID PRODUK
        'detailUrl' => 'detailproduk.php?id=' . (int) $product['id_produk'],
    ];
}, $productController->getAllProducts());
// =========================================
// INFORMASI TOKO
// =========================================
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
  <title>
    Katalog Produk - <?= htmlspecialchars($nama_toko, ENT_QUOTES, 'UTF-8') ?>
  </title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400&display=swap"
    rel="stylesheet"
  >
  <link rel="stylesheet" href="../../assets/css/base.css">
  <link rel="stylesheet" href="../../assets/css/katalog.css">
</head>
<body>
  <!-- HEADER -->
  <?php require __DIR__ . '/../../components/header.php'; ?>
  <main class="container">
    <!-- TOOLBAR -->
    <section class="toolbar">
      <label class="search">
        <svg
          viewBox="0 0 24 24"
          width="16"
          height="16"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="11" cy="11" r="7"/>
          <path d="m20 20-3.5-3.5"/>
        </svg>
        <input
          type="search"
          id="searchInput"
          placeholder="Cari sprei, bedcover, motif..."
        >
      </label>
      <button class="chip chip--active" data-category="all">
        Semua Produk
      </button>
      <button class="chip" data-category="sprei">
        Sprei Katun
      </button>
      <button class="chip" data-category="bedcover">
        Bedcover Set
      </button>
      <select
        class="select"
        id="sizeFilter"
        aria-label="Filter ukuran"
      >
        <option value="all">Filter Ukuran (Semua)</option>
        <option value="160 × 200 × 30">160 × 200 × 30</option>
        <option value="180 × 200 × 30">180 × 200 × 30</option>
        <option value="180 × 200 × 35">180 × 200 × 35</option>
        <option value="200 × 200 × 35">200 × 200 × 35</option>
      </select>
    </section>
    <!-- GRID PRODUK -->
    <section
      class="grid"
      id="productGrid"
      aria-live="polite"
    ></section>
    <!-- PAGINATION -->
    <nav
      class="pagination"
      id="pagination"
      aria-label="Halaman"
    ></nav>
  </main>
  <!-- FOOTER -->
  <?php require __DIR__ . '/../../components/footer.php'; ?>
  <!-- DATA POSTGRESQL UNTUK JAVASCRIPT -->
  <script>
    window.catalogProducts = <?= json_encode(
        $catalogProducts,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_INVALID_UTF8_SUBSTITUTE
    ); ?>;
  </script>
  <script src="../../assets/js/app.js"></script>
</body>
</html>
