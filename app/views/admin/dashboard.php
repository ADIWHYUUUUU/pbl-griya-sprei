<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /?page=admin-login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Griya Sprei</title>
    <link rel="stylesheet" href="/assets/css/admin/dashboard.css">
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="dashboard-main">
            <button
                type="button"
                class="burger-btn"
                id="burgerBtn"
                aria-label="Buka menu"
                aria-controls="sidebar"
                aria-expanded="false"
            >&#9776;</button>

            <header class="dashboard-header">
                <div>
                    <p class="eyebrow">PANEL ADMIN</p>
                    <h1>Dashboard</h1>
                    <p class="dashboard-intro">Selamat datang, <?= htmlspecialchars((string) ($_SESSION['nama'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?>. Kelola toko Griya Sprei dari satu tempat.</p>
                </div>
            </header>

            <section class="dashboard-section" aria-labelledby="quick-actions-title">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">AKSES CEPAT</p>
                        <h2 id="quick-actions-title">Pengelolaan toko</h2>
                        <p>Pilih menu untuk melanjutkan pekerjaan Anda.</p>
                    </div>
                </div>

                <div class="action-grid">
                    <a class="action-card" href="/?page=admin-products">
                        <span class="action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/></svg>
                        </span>
                        <span class="action-card-content">
                            <strong>Kelola produk</strong>
                            <span>Lihat dan perbarui katalog produk.</span>
                        </span>
                        <span class="action-arrow" aria-hidden="true">&rarr;</span>
                    </a>

                    <a class="action-card" href="/?page=admin-add-product">
                        <span class="action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                        <span class="action-card-content">
                            <strong>Tambah produk</strong>
                            <span>Tambahkan produk baru ke katalog.</span>
                        </span>
                        <span class="action-arrow" aria-hidden="true">&rarr;</span>
                    </a>

                    <a class="action-card" href="/?page=admin-edit-home">
                        <span class="action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                        </span>
                        <span class="action-card-content">
                            <strong>Edit beranda</strong>
                            <span>Perbarui konten halaman utama toko.</span>
                        </span>
                        <span class="action-arrow" aria-hidden="true">&rarr;</span>
                    </a>

                    <a class="action-card" href="/?page=admin-register">
                        <span class="action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M19 8v6M22 11h-6"/></svg>
                        </span>
                        <span class="action-card-content">
                            <strong>Tambah admin</strong>
                            <span>Daftarkan akun admin untuk toko.</span>
                        </span>
                        <span class="action-arrow" aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </section>

            <section class="dashboard-note" aria-labelledby="dashboard-note-title">
                <span class="note-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                </span>
                <div>
                    <h2 id="dashboard-note-title">Informasi pengelolaan</h2>
                    <p>Gunakan menu di atas atau navigasi di samping untuk mengelola produk dan konten toko.</p>
                </div>
            </section>
        </main>
    </div>
    <script src="/assets/js/admin/sidebar.js"></script>
</body>
</html>
