<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$cartCount = 0;
$cart = $_SESSION['cart'] ?? [];
if (is_array($cart)) {
    foreach ($cart as $cartItem) {
        if (is_array($cartItem)) {
            $cartCount += max(0, (int) ($cartItem['qtySprei'] ?? 0));
        }
    }
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

$currentPage = $_GET['page'] ?? basename($_SERVER['SCRIPT_NAME'] ?? '');
$activeNavPage = match ($currentPage) {
    'index.php', 'customer-home' => 'home',
    'customer-catalog', 'customer-detail' => 'catalog',
    'customer-cart' => '',
    'pesanan.php', 'pesananSaya.php', 'riwayatPesanan.php' => 'orders',
    default => '',
};

?>

<!-- ===== HEADER ===== -->
<header class="header">
    <div class="brand">
        <img
            class="brand__logo"
            src="/assets/images/logo.png"
            alt="Logo Griya Sprei">
        <div>
            <h1 class="brand__name">Griya Sprei</h1>
            <p class="brand__tagline">
                Homemade Pillow, Bolster, Sprei, Bedcover, Etc.
            </p>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav class="nav" aria-label="Navigasi utama">
        <a href="/?page=customer-home"
            class="nav__link<?= $activeNavPage === 'home' ? ' nav__link--active' : ''; ?>"
            <?= $activeNavPage === 'home' ? 'aria-current="page"' : ''; ?>>
            Beranda
        </a>
        <a href="/?page=customer-catalog"
            class="nav__link<?= $activeNavPage === 'catalog' ? ' nav__link--active' : ''; ?>"
            <?= $activeNavPage === 'catalog' ? 'aria-current="page"' : ''; ?>>
            Katalog
        </a>
        <span
            class="nav__link<?= $activeNavPage === 'orders' ? ' nav__link--active' : ''; ?>"
            aria-disabled="true">
            Pesanan Saya
        </span>
    </nav>

    <!-- HEADER ACTIONS -->
    <div class="header__actions">
        <!-- Keranjang -->
        <a class="cart" href="/?page=customer-cart" aria-label="Keranjang">
            <svg
                viewBox="0 0 24 24"
                width="24"
                height="24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6">
                <path d="M3 4h2l2.4 11h10.2L20 7H6.5" />
                <circle cx="9" cy="19" r="1.4" />
                <circle cx="17" cy="19" r="1.4" />
            </svg>
            <span class="cart__badge"<?= $cartCount === 0 ? ' hidden' : ''; ?>>
                <?= $cartCount ?>
            </span>
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
                        stroke-width="1.8">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7" />
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
                        stroke-width="2">
                        <path d="m6 9 6 6 6-6" />
                    </svg>

                </summary>
                <!-- Isi dropdown -->
                <div class="user-dropdown__menu">
                    <a
                        href="/?page=customer-home"
                        class="user-dropdown__item">
                        <?= htmlspecialchars(
                            $_SESSION['email'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </a>

                    <div class="user-dropdown__divider"></div>
                    <!-- Logout menggunakan POST -->
                    <form
                        action="/?action=customer-logout"
                        method="POST"
                        class="user-dropdown__form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button
                            type="submit"
                            class="user-dropdown__item user-dropdown__logout">
                            Logout
                        </button>
                    </form>
                </div>
            </details>
        <?php else: ?>

            <!-- CUSTOMER BELUM LOGIN -->
            <a
                href="/?page=customer-login"
                class="btn-login">
                <svg
                    viewBox="0 0 24 24"
                    width="16"
                    height="16"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7" />
                </svg>
                Masuk / Daftar
            </a>
        <?php endif; ?>
    </div>
</header>