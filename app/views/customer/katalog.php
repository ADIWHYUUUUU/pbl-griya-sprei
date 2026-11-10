<?php
require_once __DIR__ . '/../../controllers/ProductController.php';
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
      'SPREI' => 'Sprei',
      'BEDCOVER' => 'Bed Cover',
      'SARUNGBANTAL' => 'Sarung Bantal',
      'SARUNGGULING' => 'Sarung Guling',
      default => $category,
    };
    $categoryFilter = match ($categoryKey) {
      'BEDCOVER' => 'bedcover',
      'SARUNGBANTAL', 'SARUNGGULING' => 'sarung',
      default => 'sprei',
    };
    $fabric = (string) ($product['jenis_kain'] ?? '');
    $fabricLabel = match ($fabric) {
      'TENCEL' => 'Tencel',
      'KATUN_JEPANG' => 'Katun Jepang',
      'KATUN_LOKAL' => 'Katun lokal',
      'MICROTENCEL' => 'Microtencel',
      'MICROTEX' => 'Microtex',
      default => 'Belum diatur',
    };
    $imageName = (string) ($product['gambar_produk'] ?? '');
    return [
        // ID ASLI DATABASE
        'id' => (int) $product['id_produk'],
        // DATA PRODUK
        'name' => $product['nama_produk'] ?? '',
        'type' => $categoryLabel,
        'category' => $categoryFilter,
        'fabric' => $fabric,
        'fabricLabel' => $fabricLabel,
        'size' => implode(' × ', $dimensions),
        'price' => (float) ($product['harga_dasar'] ?? 0),
        'color' => '#e8e4d8',
        'desc' => $product['deskripsi'] ?? '',
        'stock' => (int) ($product['stok'] ?? 0),
        // GAMBAR HASIL UPLOAD ADMIN
        'image' => $imageName !== ''
            ? '/uploads/produk/' . rawurlencode(basename($imageName))
            : '',
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
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400&display=swap"
    rel="stylesheet"
  >
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/customer/katalog.css">
</head>
<body>
  <!-- HEADER -->
  <?php require __DIR__ . '/../layouts/header.php'; ?>
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
          placeholder="Cari produk, jenis kain, motif..."
        >
      </label>
      <select class="select filter-select" id="categoryFilter" aria-label="Filter kategori produk">
        <option value="all">Semua Kategori</option>
        <option value="sprei">Sprei</option>
        <option value="bedcover">Bed Cover</option>
        <option value="sarung">Sarung Bantal &amp; Guling</option>
      </select>
      <select class="select filter-select" id="fabricFilter" aria-label="Filter jenis kain">
        <option value="all">Semua Jenis Kain</option>
        <option value="TENCEL">Tencel</option>
        <option value="KATUN_JEPANG">Katun Jepang</option>
        <option value="KATUN_LOKAL">Katun lokal</option>
        <option value="MICROTENCEL">Microtencel</option>
        <option value="MICROTEX">Microtex</option>
      </select>
      <select class="select filter-select" id="priceSort" aria-label="Urutkan harga">
        <option value="default">Urutkan Harga</option>
        <option value="price-low">Harga Terendah</option>
        <option value="price-high">Harga Tertinggi</option>
      </select>
      <select class="select filter-select" id="sizeFilter" aria-label="Filter ukuran">
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
  <?php require __DIR__ . '/../layouts/footer.php'; ?>
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
    window.catalogCategory = <?= json_encode(
        $_GET['category'] ?? 'all',
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    ); ?>;
  </script>
  <script src="/assets/js/customer/katalog.js"></script>
</body>
</html>