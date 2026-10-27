<?php
$halamanAktif = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-main">
        <div class="sidebar-brand">
            <img class="brand-icon" src="../assets/images/image1.png" alt="Logo Griya Sprei">
            <div class="brand-text">
                <h2>Griya Sprei</h2>
                <p>Sistem Kelola Toko</p>
            </div>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">&times;</button>
        </div>

        <nav class="sidebar-nav" aria-label="Menu utama">
            <div class="nav-item <?= in_array($halamanAktif, ['dasbord.php', 'dashboard.php'], true) ? 'active' : '' ?>" <?= in_array($halamanAktif, ['dasbord.php', 'dashboard.php'], true) ? 'aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                <span>Dashboard</span>
            </div>
            <div class="nav-item" aria-disabled="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/></svg>
                <span>CRUD Produk</span>
            </div>
            <div class="nav-item" aria-disabled="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6M9 10h6M9 14h6M9 18h3"/></svg>
                <span>Cek Pesanan</span>
            </div>
            <div class="nav-item" aria-disabled="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                <span>Cek Pembayaran</span>
            </div>
            <div class="nav-item nav-item-home" aria-disabled="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 10h18M8 10v9"/></svg>
                <span>Edit Beranda</span>
            </div>
        </nav>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile">
            <span class="user-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1H5Z"/></svg>
            </span>
            <div>
                <strong>Admin</strong>
                <span>Ibu Nita</span>
            </div>
        </div>
        <div class="logout-link" aria-disabled="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
            <span>Keluar</span>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
