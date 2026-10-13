<?php
session_start();

$namaUser        = isset($_SESSION["nama"]) ? $_SESSION["nama"] : "";
$jumlahKeranjang = isset($_SESSION["keranjang"]) ? count($_SESSION["keranjang"]) : 0;

$daftarProduk = [
    1 => [
        "nama"      => "Sprei Katun Jepang",
        "varian"    => "Sage Luxury",
        "gambar"    => "sprei-katun-jepang.png",
        "panjang"   => 200,
        "lebar"     => 160,
        "tinggi"    => 25,
        "deskripsi" => "Sprei katun Jepang asli dengan kerapatan tenun halus, serat alami dingin menyerap keringat, dan jahitan sudut elastis presisi. Cocok untuk kasur ukuran standar maupun kustom. Tekstur satin stripe memberikan kilau elegan berserat lembut yang tahan dicuci berulang kali tanpa memudar atau berbulu.",
    ],
];
$id = isset($_GET["id"]) ? (int) $_GET["id"] : 1;
if (!isset($daftarProduk[$id])) {
    $id = 1;
}
$produk = $daftarProduk[$id];

$dimensi = [
    ["id" => "length", "label" => "Panjang", "min" => 100, "max" => 300, "nilai" => $produk["panjang"]],
    ["id" => "width",  "label" => "Lebar",   "min" => 80,  "max" => 260, "nilai" => $produk["lebar"]],
    ["id" => "height", "label" => "Tinggi",  "min" => 10,  "max" => 50,  "nilai" => $produk["tinggi"]],
];

$tambahan = [
    ["id" => "qtyBantal", "judul" => "Tambah Sarung Bantal"],
    ["id" => "qtyGuling", "judul" => "Tambah Sarung Guling"],
];
$menu = [
    ["label" => "Beranda",         "link" => "beranda.php"],
    ["label" => "Katalog",         "link" => "katalog.php"],
    ["label" => "Pembayaran",      "link" => "#"],
    ["label" => "Riwayat Pesanan", "link" => "#"],
];

$tautan = [
    ["label" => "Katalog",         "link" => "katalog.php"],
    ["label" => "Pembayaran",      "link" => "#"],
    ["label" => "Riwayat Pesanan", "link" => "#"],
];

function aman($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo aman($produk["nama"]); ?> - Griya Sprei</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/frontend/assets/css/base.css">
  <link rel="stylesheet" href="/fronend/assets/css/detail.css">
</head>
<body>

  <!-- ===== HEADER ===== -->
  <header class="header">
    <div class="brand">
      <img class="brand__logo" src="/frontend/assets/images/image1.png" alt="Logo Griya Sprei">
      <div>
        <h1 class="brand__name">Griya Sprei</h1>
        <p class="brand__tagline">Homemade Pillow, Bolster, Sprei, Bedcover, Etc.</p>
      </div>
    </div>
    <nav class="nav" aria-label="Navigasi utama">
<?php foreach ($menu as $m) { ?>
      <a href="<?php echo $m["link"]; ?>" class="nav__link"><?php echo $m["label"]; ?></a>
<?php } ?>
    </nav>
    <div class="header__actions">
      <a href="#" class="cart" aria-label="Keranjang">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 4h2l2.4 11h10.2L20 7H6.5"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg>
        <span class="cart__badge" id="cartBadge"<?php if ($jumlahKeranjang == 0) echo " hidden"; ?>><?php echo $jumlahKeranjang; ?></span>
      </a>
      <!-- Kalau sudah login tampil nama user, kalau belum tampil "Masuk / Daftar" -->
      <a href="<?php echo $namaUser != "" ? "#" : "login.php"; ?>" class="btn-login" id="userButton">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        <span id="userName"><?php echo $namaUser != "" ? aman($namaUser) : "Masuk / Daftar"; ?></span>
      </a>
    </div>
  </header>

  <main class="container">

    <!-- ===== BREADCRUMB ===== -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="beranda.php">Beranda</a> <span>›</span>
      <a href="katalog.php">Katalog</a> <span>›</span>
      <strong id="crumbName"><?php echo aman($produk["nama"] . " (" . $produk["varian"] . ")"); ?></strong>
    </nav>

    <div class="detail">

      <!-- ===== KOLOM KIRI: FOTO & INFO ===== -->
      <div class="detail__left">
        <div class="panel panel--image">
          <div class="product-image media" id="productImage">
            <img src="../assets/images/<?php echo aman($produk["gambar"]); ?>" alt="<?php echo aman($produk["nama"]); ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
          </div>
        </div>

        <div class="panel panel--info">
          <h2 class="product-title" id="productTitle"><?php echo aman($produk["nama"]); ?></h2>
          <p class="product-size">Ukuran Standar Kasur: <strong id="standardSizeText"><?php echo $produk["panjang"] . " × " . $produk["lebar"] . " × " . $produk["tinggi"]; ?> cm</strong></p>
          <p class="product-desc" id="productDesc"><?php echo aman($produk["deskripsi"]); ?></p>

          <div class="notice">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
            <div>
              <h3>Pemberitahuan Tagihan &amp; Verifikasi</h3>
              <p>Harga dan tagihan final akan dikonfirmasi oleh Admin setelah spesifikasi pesanan Anda diverifikasi.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== KOLOM KANAN: FORM PESANAN ===== -->
      <form class="panel panel--form" id="orderForm" novalidate>
        <input type="hidden" name="id_produk" value="<?php echo $id; ?>">

        <h2 class="form__title">Konfigurasi Ukuran &amp; Spesifikasi</h2>

        <!-- Tipe ukuran -->
        <p class="form__label">Pilih Tipe Ukuran</p>
        <div class="segmented" role="tablist">
          <button type="button" class="segmented__btn segmented__btn--active" data-mode="standard" role="tab">Standar</button>
          <button type="button" class="segmented__btn" data-mode="custom" role="tab">Custom Ukuran</button>
        </div>

        <!-- Dimensi kasur -->
        <fieldset class="dimension">
          <legend class="dimension__title">Dimensi Kasur (cm)</legend>
          <div class="dimension__grid">
<?php foreach ($dimensi as $d) { ?>
            <div class="dim">
              <label for="<?php echo $d["id"]; ?>"><?php echo $d["label"]; ?></label>
              <div class="dim__input"><input type="number" id="<?php echo $d["id"]; ?>" name="<?php echo $d["id"]; ?>" min="<?php echo $d["min"]; ?>" max="<?php echo $d["max"]; ?>" value="<?php echo $d["nilai"]; ?>"><span>cm</span></div>
              <p class="dim__hint" id="<?php echo $d["id"]; ?>Hint">Standar katalog</p>
            </div>
<?php } ?>
          </div>
        </fieldset>

        <!-- Jumlah sprei -->
        <div class="row-box">
          <div>
            <h3>Jumlah Sprei</h3>
            <p>Set standar termasuk 2 sarung bantal + 2 sarung guling</p>
          </div>
          <div class="stepper" data-stepper data-min="1" data-max="50">
            <button type="button" data-step="-1" aria-label="Kurangi">−</button>
            <output id="qtySprei">1</output>
            <button type="button" data-step="1" aria-label="Tambah">+</button>
          </div>
        </div>

        <!-- Tambahan sarung -->
        <p class="form__label">Tambahan Sarung Bantal &amp; Guling</p>
        <div class="extras">
<?php foreach ($tambahan as $t) { ?>
          <div class="extra">
            <div class="extra__head"><h3><?php echo $t["judul"]; ?></h3><span>Opsional</span></div>
            <div class="extra__row">
              <span>Jumlah:</span>
              <div class="stepper stepper--sm" data-stepper data-min="0" data-max="20">
                <button type="button" data-step="-1" aria-label="Kurangi">−</button>
                <output id="<?php echo $t["id"]; ?>">0</output>
                <button type="button" data-step="1" aria-label="Tambah">+</button>
              </div>
            </div>
          </div>
<?php } ?>
        </div>

        <!-- Catatan -->
        <label class="form__label" for="note">Catatan :</label>
        <textarea id="note" name="catatan" rows="4" placeholder="Contoh: Karet keliling penuh, resleting bawah, atau instruksi khusus penjahit..."></textarea>

        <p class="form__message" id="formMessage" hidden></p>

        <button type="submit" class="btn-cart">
          Tambahkan ke Keranjang
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m-5-6 6 6-6 6"/></svg>
        </button>
      </form>
    </div>
  </main>

  <?php require __DIR__ . '/../../components/footer.php'; ?>

  <script src="../assets/js/data.js"></script>
  <script src="../assets/js/detail.js"></script>
</body>
</html>