<?php
$halamanAktif = $_GET['page'] ?? 'admin-dashboard';
$namaAdmin = $_SESSION['nama'] ?? 'Admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-main">
        <div class="sidebar-brand">
            <img class="brand-icon" src="/assets/images/logo.png" alt="Logo Griya Sprei">
            <div class="brand-text">
                <h2>Griya Sprei</h2>
                <p>Sistem Kelola Toko</p>
            </div>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">&times;</button>
        </div>

        <nav class="sidebar-nav" aria-label="Menu utama">
            <a class="nav-item <?= $halamanAktif === 'admin-dashboard' ? 'active' : '' ?>" href="/?page=admin-dashboard" <?= $halamanAktif === 'admin-dashboard' ? 'aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                <span>Dashboard</span>
            </a>
            <a class="nav-item <?= in_array($halamanAktif, ['admin-products', 'admin-add-product', 'admin-edit-product'], true) ? 'active' : '' ?>" href="/?page=admin-products" <?= in_array($halamanAktif, ['admin-products', 'admin-add-product', 'admin-edit-product'], true) ? 'aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/></svg>
                <span>CRUD Produk</span>
            </a>
            <a class="nav-item <?= $halamanAktif === 'admin-edit-home' ? 'active' : '' ?>" href="/?page=admin-edit-home" <?= $halamanAktif === 'admin-edit-home' ? 'aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 4v16M12 9h6M12 13h6M12 17h4"/></svg>
                <span>Edit Beranda</span>
            </a>
            <a class="nav-item <?= $halamanAktif === 'admin-register' ? 'active' : '' ?>" href="/?page=admin-register" <?= $halamanAktif === 'admin-register' ? 'aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M19 8v6M22 11h-6"/></svg>
                <span>Tambah Admin</span>
            </a>
            <span class="nav-item nav-item-disabled" aria-disabled="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6M9 10h6M9 14h6M9 18h3"/></svg>
                <span>Cek Pesanan</span>
            </span>
            <span class="nav-item nav-item-disabled" aria-disabled="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                <span>Cek Pembayaran</span>
            </span>
        </nav>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile">
            <span class="user-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1H5Z"/></svg>
            </span>
            <div>
                <strong>Admin</strong>
                <span><?= htmlspecialchars((string) $namaAdmin, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <form action="/?action=admin-logout" method="POST" class="sidebar-logout-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="logout-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
            <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>