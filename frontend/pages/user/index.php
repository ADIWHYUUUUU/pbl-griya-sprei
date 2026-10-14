
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$homepagePath = __DIR__ . '/../../../backend/storage/homepage.json';
$homepage = is_file($homepagePath)
  ? json_decode(file_get_contents($homepagePath), true)
  : [];
$homepage = is_array($homepage) ? $homepage : [];
$homepageValue = static function (string $key, string $fallback = '') use ($homepage): string {
  return htmlspecialchars((string) ($homepage[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};


$isLogin = (
    ($_SESSION['login'] ?? false) === true &&
    ($_SESSION['role'] ?? '') === 'CUSTOMER'
);

$namaUser = $isLogin
    ? ($_SESSION['nama'] ?? 'Pelanggan')
    : '';

// Mengambil nama customer dari session
$namaUser = $_SESSION['nama'] ?? 'Pelanggan';

?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Beranda Customer - Griya Sprei</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/frontend/assets/css/base.css">
  <link rel="stylesheet" href="/frontend/assets/css/beranda.css">

</head>

<body>

 

<?php require __DIR__ . '/../../components/header.php'; ?>


  <main class="container">

    <!-- HERO -->

    <section class="hero">

      <div class="hero__text">

        <span class="hero__pill">
          <?= $homepageValue('hero_pill', 'Griya Sprei Malang · Homemade sejak 2010'); ?>
        </span>

        <h2 class="hero__title">
          <?= $homepageValue('hero_title', 'Sprei dan bed cover pas di kasur Anda, ukuran standar maupun custom.'); ?>
        </h2>

        <p class="hero__desc">
          <?= $homepageValue('hero_description', 'Pilih model kain, tentukan ukuran panjang × lebar × tinggi kasur presisi cm, tambahkan sarung bantal guling, dan nikmati tidur nyaman dengan bahan MicroSilk & Katun Jepang halus.'); ?>
        </p>

        <a
          href="/frontend/pages/user/katalog.php  "
          class="btn btn--lg"
        >
          Lihat Katalog
        </a>

      </div>

      <div
        class="hero__image media"
        style="--ph:#9bb87a"
      ><?php if (!empty($homepage['hero_image'])): ?><img src="<?= $homepageValue('hero_image'); ?>" alt="Suasana produk Griya Sprei"><?php endif; ?></div>

    </section>


    <!-- ===== JELAJAHI KATALOG ===== -->

    <section class="section">

      <h2 class="section__title">
        <?= $homepageValue('explore_title', 'Jelajahi katalog'); ?>
      </h2>

      <div class="explore">

        <!-- SPREI -->

        <article class="explore__card">

          <div
            class="explore__image media"
            style="--ph:#4a74b5"
          ><?php if (!empty($homepage['sprei_image'])): ?><img src="<?= $homepageValue('sprei_image'); ?>" alt="Produk sprei"><?php endif; ?></div>

          <div class="explore__row">

            <div>
              <h3><?= $homepageValue('sprei_title', 'Sprei'); ?></h3>

              <p>
                <?= $homepageValue('sprei_description', 'Standar atau custom sesuai kasur.'); ?>
              </p>
            </div>

            <a
              href="/frontend/pages/katalog.php"
              class="btn"
            >
              Lihat
            </a>

          </div>

        </article>


        <!-- BED COVER -->

        <article class="explore__card">

          <div
            class="explore__image media"
            style="--ph:#a98466"
          ><?php if (!empty($homepage['bedcover_image'])): ?><img src="<?= $homepageValue('bedcover_image'); ?>" alt="Produk bed cover"><?php endif; ?></div>

          <div class="explore__row">

            <div>
              <h3><?= $homepageValue('bedcover_title', 'Bed Cover'); ?></h3>

              <p>
                <?= $homepageValue('bedcover_description', 'Pilih model dan ukuran.'); ?>
              </p>
            </div>

            <a
              href="/frontend/pages/katalog.php"
              class="btn"
            >
              Lihat
            </a>

          </div>

        </article>

      </div>

    </section>


    <!-- ===== PRODUK PILIHAN ===== -->

    <section class="section">

      <h2 class="section__title">
        <?= $homepageValue('featured_title', 'Produk Pilihan & Best Seller'); ?>
      </h2>

      <div
        class="featured"
        id="featuredGrid"
      ></div>

    </section>


    <!-- ===== CARA PEMESANAN ===== -->

    <section class="section">

      <h2 class="section__title">
        Cara Pemesanan Mudah
      </h2>

      <div class="steps">

        <article class="step">
          <div class="step__head">
            <span>1</span>
            <h3>Pilih &amp; Konfigurasi</h3>
          </div>

          <p>
            Tentukan model kain, ukuran,
            dan ekstra sarban/sargul.
          </p>
        </article>


        <article class="step">
          <div class="step__head">
            <span>2</span>
            <h3>Checkout Pesanan</h3>
          </div>

          <p>
            Isi alamat pengiriman dan pilih
            opsi pengiriman tujuan.
          </p>
        </article>


        <article class="step">
          <div class="step__head">
            <span>3</span>
            <h3>Admin Tinjau Harga</h3>
          </div>

          <p>
            Spesifikasi kasur dicek,
            tagihan resmi akan diterbitkan.
          </p>
        </article>


        <article class="step">
          <div class="step__head">
            <span>4</span>
            <h3>Transfer &amp; Unggah Bukti</h3>
          </div>

          <p>
            Bayar sesuai invoice via transfer bank
            dan unggah bukti transfer.
          </p>
        </article>


        <article class="step">
          <div class="step__head">
            <span>5</span>
            <h3>Jahit &amp; Pantau Status</h3>
          </div>

          <p>
            Pantau status pengerjaan secara real-time.
          </p>
        </article>

      </div>

    </section>

  </main>


  
<?php require __DIR__ . '/../../components/footer.php'; ?>



  <script src="/frontend/assets/js/data.js"></script>
  <script>window.homepageContent = <?= json_encode($homepage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}'; ?>;</script>
  <script src="/frontend/assets/js/beranda.js"></script>

</body>
</html>
