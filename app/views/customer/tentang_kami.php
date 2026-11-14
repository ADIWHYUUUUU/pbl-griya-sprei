<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$alamatToko = 'Jl. Kanjuruhan Asri No. 8, Tlogomas, Lowokwaru, Kota Malang';
$petaUrl = 'https://www.google.com/maps?q=' . rawurlencode($alamatToko) . '&output=embed';
$petaLink = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($alamatToko);

// Foto toko / produksi: cukup simpan file bernama foto-toko.jpg (atau .jpeg/.png/.webp)
// di folder public/uploads/tentang/. Selama file belum ada, kotaknya tampil kosong.
$fotoToko = '';
foreach (['jpg', 'jpeg', 'png', 'webp'] as $ekstensi) {
    $lokasiFoto = __DIR__ . '/../../../public/uploads/tentang/foto-toko.' . $ekstensi;
    if (is_file($lokasiFoto)) {
        $fotoToko = '/uploads/tentang/foto-toko.' . $ekstensi . '?v=' . filemtime($lokasiFoto);
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tentang Kami - Griya Sprei</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/customer/tentang_kami.css">
</head>
<body>
<?php require __DIR__ . '/../layouts/header.php'; ?>
  <main>
    <!-- ===== HERO ===== -->
    <section class="about-hero">
      <div class="container about-hero__inner">
        <div class="about-hero__text">
          <span class="about-eyebrow">Tentang Kami</span>
          <h2 class="about-hero__title">Dari usaha rumahan menjadi toko sprei di Malang</h2>
          <p class="about-hero__desc">Kenali siapa di balik Griya Sprei dan di mana Anda bisa menemukan kami.</p>
          <a href="/?page=customer-catalog" class="btn btn--lg">Lihat Katalog</a>
        </div>
        <!-- Foto toko / produksi: kosong sampai file public/uploads/tentang/foto-toko.* tersedia -->
        <?php if ($fotoToko !== ''): ?>
          <div class="about-hero__image">
            <img src="<?= htmlspecialchars($fotoToko, ENT_QUOTES, 'UTF-8') ?>" alt="Toko dan tempat produksi Griya Sprei">
          </div>
        <?php else: ?>
          <div class="about-hero__image" aria-hidden="true"></div>
        <?php endif; ?>
      </div>
    </section>

    <div class="container">
      <!-- ===== CERITA KAMI ===== -->
      <section class="about-section about-story">
        <div>
          <h2 class="about-title">Cerita kami</h2>
          <p class="about-text">
            Griya Sprei berdiri di Malang pada 2010 dan berawal dari usaha rumahan.
            Kami memproduksi sprei, bed cover, sarung bantal, dan sarung guling
            ukuran standar dan custom sesuai kebutuhan customer.
          </p>
        </div>
        <div class="about-stat">
          <strong>2010</strong>
          <span>Mulai beroperasi di Malang</span>
        </div>
      </section>

      <!-- ===== YANG KAMI PEGANG ===== -->
      <section class="about-section">
        <h2 class="about-title">Yang kami pegang</h2>
        <p class="about-subtitle">Hal-hal yang membedakan cara kami bekerja.</p>
        <div class="about-values">
          <article class="about-card">
            <h3>Ukuran sesuai kebutuhan</h3>
            <p>Isi panjang, lebar, dan tinggi kasur saat memesan. Tidak terpaku pada ukuran standar.</p>
          </article>
          <article class="about-card">
            <h3>Dibuat sendiri</h3>
            <p>Produk kami buat sendiri di Malang, bukan sekadar menjual ulang.</p>
          </article>
          <article class="about-card">
            <h3>Untuk banyak kebutuhan</h3>
            <p>Melayani rumah tangga, kos, homestay, hingga rumah sakit.</p>
          </article>
        </div>
      </section>

      <!-- ===== KAMI MELAYANI ===== -->
      <section class="about-section">
        <h2 class="about-title">Kami melayani</h2>
        <ul class="about-card about-services">
          <li>Custom segala ukuran kasur (single, double, king, ukuran custom)</li>
          <li>Pilihan bahan premium: Tencel, katun jepang &amp; katun lokal yang adem dan tidak luntur</li>
          <li>Motif aesthetic terbaru, cocok untuk kamar kost, homestay, dan rumah tangga</li>
          <li>Harga spesial grosir untuk kost, homestay, guesthouse, rumah sakit &amp; daycare</li>
        </ul>
      </section>

      <!-- ===== KUNJUNGI KAMI ===== -->
      <section class="about-section about-visit">
        <div class="about-card about-visit__info">
          <h2 class="about-title">Kunjungi kami</h2>
          <dl>
            <dt>Alamat</dt>
            <dd>
              <a href="<?= htmlspecialchars($petaLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($alamatToko, ENT_QUOTES, 'UTF-8') ?>
              </a>
            </dd>
            <dt>Jam layanan</dt>
            <dd>Hubungi kami lewat WhatsApp sebelum berkunjung agar kami bisa melayani anda.</dd>
            <dt>Kontak</dt>
            <dd>
              <a href="https://wa.me/+62111122223333" target="_blank" rel="noopener">WhatsApp +62 1111-2222-3333</a>
            </dd>
          </dl>
        </div>
        <div class="about-visit__map">
          <iframe
            src="<?= htmlspecialchars($petaUrl, ENT_QUOTES, 'UTF-8') ?>"
            title="Peta lokasi Griya Sprei"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </section>
    </div>
  </main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
