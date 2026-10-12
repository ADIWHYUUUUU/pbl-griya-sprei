
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah customer sudah login
$isLogin = (
    ($_SESSION['login'] ?? false) === true &&
    ($_SESSION['role'] ?? '') === 'CUSTOMER'
);

// Ambil nama customer
$namaUser = $isLogin
    ? ($_SESSION['nama'] ?? 'Pelanggan')
    : '';

?>

<!-- ===== HEADER ===== -->

<header class="header">

  <div class="brand">

    <img
      class="brand__logo"
      src="/frontend/assets/images/image1.png"
      alt="Logo Griya Sprei"
    >

    <div>
      <h1 class="brand__name">Griya Sprei</h1>

      <p class="brand__tagline">
        Homemade Pillow, Bolster, Sprei, Bedcover, Etc.
      </p>
    </div>

  </div>

  <!-- NAVBAR -->

  <nav class="nav" aria-label="Navigasi utama">

    <a href="/frontend/pages/user/index.php"
   class="nav__link nav__link--active">
    Beranda
</a>
    <a href="/frontend/pages/user/katalog.php" class="nav__link">
      Katalog
    </a>

    <a href="#" class="nav__link">
      Pembayaran
    </a>

    <a href="#" class="nav__link">
      Riwayat Pesanan
    </a>

  </nav>

  <!-- HEADER ACTIONS -->

  <div class="header__actions">

    <!-- Keranjang -->

    <a href="#" class="cart" aria-label="Keranjang">

      <svg
        viewBox="0 0 24 24"
        width="24"
        height="24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
      >
        <path d="M3 4h2l2.4 11h10.2L20 7H6.5"/>
        <circle cx="9" cy="19" r="1.4"/>
        <circle cx="17" cy="19" r="1.4"/>
      </svg>

      <span class="cart__badge">3</span>

    </a>

    <?php if ($isLogin): ?>

      <!-- CUSTOMER SUDAH LOGIN -->

      <details class="user-dropdown">

        <!-- Tombol nama customer -->
        <summary class="btn-login user-dropdown__trigger">

          <svg
            viewBox="0 0 24 24"
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
          </svg>

          <span>
            
            <?= htmlspecialchars(
                $namaUser,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
          </span>

          <!-- Icon panah dropdown -->
          <svg
            class="dropdown-arrow"
            viewBox="0 0 24 24"
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="m6 9 6 6 6-6"/>
          </svg>

        </summary>

        <!-- Isi dropdown -->
        <div class="user-dropdown__menu">

          <a
            href="/frontend/pages/user/index.php"
            class="user-dropdown__item"
          >
            <?= htmlspecialchars(
                $_SESSION['email'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
          </a>

          <div class="user-dropdown__divider"></div>

          <!-- Logout menggunakan POST -->
          <form
            action="/backend/actions/logout.php"
            method="POST"
            class="user-dropdown__form"
          >
            <button
              type="submit"
              class="user-dropdown__item user-dropdown__logout"
            >
              Logout
            </button>
          </form>

        </div>

      </details>

    <?php else: ?>

      <!-- CUSTOMER BELUM LOGIN -->

      <a
        href="/frontend/pages/login.php"
        class="btn-login"
      >

        <svg
          viewBox="0 0 24 24"
          width="16"
          height="16"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
        </svg>

        Masuk / Daftar

      </a>

    <?php endif; ?>

  </div>

</header>
