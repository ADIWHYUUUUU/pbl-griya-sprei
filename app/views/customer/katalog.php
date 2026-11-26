<?php
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../helpers/product_pricing.php';
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
      'SARUNGBANTAL', 'SARUNGGULING', 'SARUNGBANTALGULING' => 'Sarung Bantal & Guling',
      default => $category,
    };
    $categoryFilter = match ($categoryKey) {
      'BEDCOVER' => 'bedcover',
      'SARUNGBANTAL', 'SARUNGGULING', 'SARUNGBANTALGULING' => 'sarung',
      default => 'sprei',
    };
    $fabric = (string) ($product['jenis_kain'] ?? '');
    $fabricLabel = product_fabric_label($fabric);
    $price = product_minimum_price($category)
      ?? (int) ($product['harga_dasar'] ?? 0);
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
        'price' => $price,
        'color' => '#e8e4d8',
        'desc' => $product['deskripsi'] ?? '',
        'stock' => (int) ($product['stok'] ?? 0),
        // GAMBAR HASIL UPLOAD ADMIN
        'image' => $imageName !== ''
            ? product_image_url($imageName)
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
    <section class="catalog-heading">
      <h1>Katalog</h1>
      <p>Klik produk untuk melihat detail dan memesan. Isi ukuran kasur Anda saat memesan. Ukuran produk ditulis P × L × T (cm).</p>
    </section>
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
    </section>
    <div class="catalog-categories" aria-label="Filter kategori produk">
      <span class="catalog-categories__label">Kategori</span>
      <div class="catalog-categories__options" id="categoryFilters">
        <button class="category-chip" type="button" data-category="all">Semua</button>
        <button class="category-chip" type="button" data-category="sprei">Sprei</button>
        <button class="category-chip" type="button" data-category="bedcover">Bed Cover</button>
        <button class="category-chip" type="button" data-category="sarung">Sarung Bantal &amp; Guling</button>
      </div>
    </div>
    <div class="catalog-results">
      <p id="catalogSummary" aria-live="polite"></p>
      <button class="catalog-reset" type="button" id="resetFilters">Reset filter</button>
    </div>
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