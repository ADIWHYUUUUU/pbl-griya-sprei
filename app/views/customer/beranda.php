<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$homepagePath = __DIR__ . '/../../../storage/homepage.json';
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

// Popup update pesanan: tampil sekali per sesi untuk setiap update baru dari Admin
$popupOrders = [];
$popupCustomerId = (int) ($_SESSION['id_user'] ?? 0);
if ($isLogin && $popupCustomerId > 0) {
    require_once __DIR__ . '/../../helpers/order_updates.php';
    $popupShown = $_SESSION['popup_update_pesanan'] ?? [];
    $popupShown = is_array($popupShown) ? $popupShown : [];
    foreach (orderUpdatesUnseen($popupCustomerId) as $updatedOrder) {
        $orderKey = (string) $updatedOrder['id_pesanan'];
        if (($popupShown[$orderKey] ?? null) !== $updatedOrder['signature']) {
            $popupOrders[] = $updatedOrder;
            $popupShown[$orderKey] = $updatedOrder['signature'];
        }
    }
    $_SESSION['popup_update_pesanan'] = $popupShown;
}
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
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/customer/beranda.css">
</head>
<body>
<?php require __DIR__ . '/../layouts/header.php'; ?>
  <main class="container">
    <!-- HERO -->
    <section class="hero">
      <div class="hero__text">
        <span class="hero__pill">
          <?= $homepageValue('hero_pill', 'Griya Sprei Malang · Homemade sejak 2010'); ?>
        </span>
        <h2 class="hero__title">
          <?= $homepageValue('hero_title', 'Griya Sprei adalah usaha bedding lokal yang fokus pada pembuatan sprei custom dengan kualitas jahitan rapi dan bahan berkualitas.'); ?>
        </h2>
        <p class="hero__desc">
          <?= $homepageValue('hero_description', 'Pilih model kain, tentukan ukuran panjang × lebar × tinggi kasur presisi cm, tambahkan sarung bantal guling, dan nikmati tidur nyaman dengan bahan MicroSilk & Katun Jepang halus.'); ?>
        </p>
        <div class="hero__actions">
          <a
            href="/?page=customer-catalog"
            class="btn btn--lg"
          >
            Lihat Katalog
          </a>
          <button type="button" class="btn btn--lg btn--outline" data-buka-panduan>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 16.5L16.5 3 21 7.5 7.5 21z" /><path d="M7.5 12l2 2M10.5 9l2 2M13.5 6l2 2" />
            </svg>
            Cara Ukur Kasur
          </button>
        </div>
      </div>
      <div
        class="hero__image media"
        style="--ph:#9bb87a"
      ><?php if (!empty($homepage['hero_image'])): ?><img src="<?= $homepageValue('hero_image'); ?>" alt="Suasana produk Griya Sprei"><?php endif; ?></div>
    </section>
    <!-- ===== KATEGORI PRODUK ===== -->
    <section class="section" id="explore">
      <h2 class="section__title">
        <?= $homepageValue('explore_title', 'Kategori produk'); ?>
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
              href="/?page=customer-catalog&category=sprei"
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
              href="/?page=customer-catalog&category=bedcover"
              class="btn"
            >
              Lihat
            </a>
          </div>
        </article>
        <!-- SARUNG BANTAL & GULING -->
        <article class="explore__card">
          <div
            class="explore__image media"
            style="--ph:#9bb87a"
          ><?php if (!empty($homepage['sarung_image'])): ?><img src="<?= $homepageValue('sarung_image'); ?>" alt="Sarung bantal dan guling"><?php endif; ?></div>
          <div class="explore__row">
            <div>
              <h3><?= $homepageValue('sarung_title', 'Sarung Bantal & Guling'); ?></h3>
              <p>
                <?= $homepageValue('sarung_description', 'Bisa ditambahkan saat memesan sprei.'); ?>
              </p>
            </div>
            <a
              href="/?page=customer-catalog&category=sprei"
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
            <h3>Pilih bahan &amp; motif</h3>
          </div>
          <p>
            Tentukan bahan kain, ukuran, dan ekstra 
            sarbangul dengan cara melihat katalog
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
            <h3>Menentukan Harga</h3>
          </div>
          <p>
            Ukuran dan jenis kasur sedang diperiksa,
            harga akan diberikan setelah pengecekan selesai.
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
            <h3> Status </h3>
          </div>
          <p>
            Pantau status pengerjaan secara real-time.
          </p>
        </article>
      </div>
    </section>
    <!-- ===== CARA MENGUKUR KASUR (popup, dibuka dari tombol di hero) ===== -->
    <dialog class="guide-modal" id="panduanUkuran" aria-labelledby="panduanUkuranJudul">
      <header class="guide-modal__head">
        <div>
          <h2 id="panduanUkuranJudul">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l4-1 11-11-3-3L5 16z" /><path d="M14 6l3 3" /></svg>
            Cara Mengukur Kasur
          </h2>
          <p>Ukur kasur tanpa sprei, pakai meteran kain</p>
        </div>
        <button type="button" class="guide-modal__close" data-tutup-panduan aria-label="Tutup">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
      </header>

      <div class="guide-modal__body">
        <svg class="guide-modal__diagram" viewBox="0 0 345 130" role="img" aria-label="Ilustrasi kasur: P panjang, L lebar, T tinggi">
          <path d="M60 30 L90 8 L320 8 L290 30 Z" fill="#f1f2ee" stroke="#4f6350" stroke-width="1.2" stroke-linejoin="round" />
          <path d="M290 30 L320 8 L320 62 L290 84 Z" fill="#d4dccf" stroke="#4f6350" stroke-width="1.2" stroke-linejoin="round" />
          <rect x="60" y="30" width="230" height="54" rx="3" fill="#e6ebe2" stroke="#4f6350" stroke-width="1.2" />
          <line x1="40" y1="30" x2="40" y2="84" stroke="#a07f3c" stroke-width="2" />
          <text x="18" y="62" font-size="12" font-weight="600" fill="#a07f3c">T</text>
          <line x1="296" y1="94" x2="328" y2="70" stroke="#3f5a7a" stroke-width="2" />
          <text x="332" y="96" font-size="12" font-weight="600" fill="#3f5a7a">L</text>
          <line x1="60" y1="100" x2="290" y2="100" stroke="#b0583f" stroke-width="2" />
          <text x="175" y="120" font-size="12" font-weight="600" fill="#b0583f" text-anchor="middle">P = Panjang</text>
        </svg>

        <ol class="guide-modal__steps">
          <li><b>Panjang (P):</b> ukur dari kepala sampai kaki kasur.</li>
          <li><b>Lebar (L):</b> ukur sisi kiri ke kanan.</li>
          <li><b>Tinggi (T):</b> ukur ketebalan kasur dari atas ke bawah.</li>
          <li>Pakai ukuran bulat dalam cm. Kalau ragu antara dua angka, pilih yang lebih besar.</li>
        </ol>

        <div class="guide-modal__sizes">
          <h3>Ukuran standar di Indonesia</h3>
          <dl>
            <div><dt>Single</dt><dd>120 × 200 cm</dd></div>
            <div><dt>Queen</dt><dd>160 × 200 cm</dd></div>
            <div><dt>King</dt><dd>180 × 200 cm</dd></div>
            <div><dt>Super King</dt><dd>200 × 200 cm</dd></div>
          </dl>
        </div>
      </div>

      <footer class="guide-modal__foot">
        <button type="button" class="btn" data-tutup-panduan>Mengerti</button>
      </footer>
    </dialog>

    <?php if ($popupOrders !== []): ?>
    <!-- ===== POPUP UPDATE PESANAN (muncul otomatis saat ada update dari Admin) ===== -->
    <dialog class="guide-modal" id="popupUpdatePesanan" aria-labelledby="popupUpdatePesananJudul">
      <header class="guide-modal__head">
        <div>
          <h2 id="popupUpdatePesananJudul">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" /><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" /></svg>
            Ada update pesanan
          </h2>
          <p>Admin telah memperbarui <?= count($popupOrders) ?> pesanan Anda</p>
        </div>
        <button type="button" class="guide-modal__close" data-tutup-update aria-label="Tutup">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
      </header>

      <div class="guide-modal__body">
        <ul class="order-update-list">
          <?php foreach ($popupOrders as $updatedOrder): ?>
            <?php $updateInfo = orderUpdateInfo($updatedOrder); ?>
            <li class="order-update order-update--<?= htmlspecialchars($updateInfo['tone'], ENT_QUOTES, 'UTF-8') ?>">
              <div class="order-update__head">
                <b><?= htmlspecialchars($updatedOrder['kode'], ENT_QUOTES, 'UTF-8') ?></b>
                <span class="order-update__tag"><?= htmlspecialchars($updateInfo['label'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <p><?= htmlspecialchars($updateInfo['message'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php if ($updateInfo['action_label'] !== ''): ?>
                <a class="order-update__action" href="<?= htmlspecialchars($updateInfo['action_url'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($updateInfo['action_label'], ENT_QUOTES, 'UTF-8') ?> →
                </a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <footer class="guide-modal__foot">
        <button type="button" class="btn btn--outline" data-tutup-update>Nanti</button>
        <a class="btn" href="/?page=customer-orders">Lihat Pesanan</a>
      </footer>
    </dialog>
    <?php endif; ?>
    <!-- ===== FAQ ===== -->
    <section class="section">
      <h2 class="section__title">Pertanyaan yang sering diajukan</h2>
      <p class="section__desc">Jawaban singkat seputar pemesanan di Griya Sprei.</p>
      <div class="faq">
        <details class="faq__item">
          <summary>Apakah saya harus membayar saat memesan?</summary>
          <p>Tidak. Setelah Anda mengajukan pesanan, Admin memeriksa spesifikasi kasur terlebih dahulu. Tagihan resmi muncul di halaman Pesanan Saya, lalu Anda bisa membayar.</p>
        </details>
        <details class="faq__item">
          <summary>Bagaimana jika saya tidak yakin dengan ukuran kasur?</summary>
          <p>Ukur panjang, lebar, dan tinggi kasur sesuai panduan di atas. Jika masih ragu, hubungi kami lewat WhatsApp dan kami bantu memastikan ukurannya.</p>
        </details>
        <details class="faq__item">
          <summary>Bagaimana cara membayar?</summary>
          <p>Transfer ke rekening bank sesuai nominal di tagihan, lalu unggah bukti transfer di halaman pembayaran. Admin akan memverifikasi pembayaran Anda.</p>
        </details>
        <details class="faq__item">
          <summary>Bisa memesan dalam jumlah besar untuk kos, homestay, atau rumah sakit?</summary>
          <p>Bisa. Hubungi kami lewat WhatsApp agar kebutuhan jumlah dan ukurannya bisa dibicarakan bersama.</p>
        </details>
        <details class="faq__item">
          <summary>Bagaimana saya tahu pesanan sudah dikirim?</summary>
          <p>Status pesanan dan nomor resi tampil di halaman Pesanan Saya setelah pesanan dikirim.</p>
        </details>
      </div>
    </section>
    <!-- ===== CTA WHATSAPP ===== -->
    <section class="section cta">
      <div>
        <h2>Butuh ukuran khusus atau pesanan dalam jumlah besar?</h2>
        <p>Hubungi kami dan kami bantu menentukan spesifikasinya.</p>
      </div>
      <a class="btn btn--lg cta__btn" href="https://wa.me/+62111122223333" target="_blank" rel="noopener">Hubungi via WhatsApp</a>
    </section>
  </main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
  <script>window.homepageContent = <?= json_encode($homepage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}'; ?>;</script>
  <script src="/assets/js/customer/beranda.js"></script>
  <script>
    (function () {
      var modal = document.getElementById('panduanUkuran');
      if (!modal || typeof modal.showModal !== 'function') return;
      document.querySelectorAll('[data-buka-panduan]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          modal.showModal();
          document.body.style.overflow = 'hidden';
        });
      });
      modal.querySelectorAll('[data-tutup-panduan]').forEach(function (btn) {
        btn.addEventListener('click', function () { modal.close(); });
      });
      // Klik di luar kotak popup untuk menutup
      modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.close();
      });
      modal.addEventListener('close', function () {
        document.body.style.overflow = '';
      });
    })();

    // Popup update pesanan: langsung dibuka saat halaman dimuat
    (function () {
      var popup = document.getElementById('popupUpdatePesanan');
      if (!popup || typeof popup.showModal !== 'function') return;
      popup.showModal();
      document.body.style.overflow = 'hidden';
      popup.querySelectorAll('[data-tutup-update]').forEach(function (btn) {
        btn.addEventListener('click', function () { popup.close(); });
      });
      popup.addEventListener('click', function (e) {
        if (e.target === popup) popup.close();
      });
      popup.addEventListener('close', function () {
        document.body.style.overflow = '';
      });
    })();
  </script>
</body>
</html>