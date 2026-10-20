
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman khusus ADMIN
if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /frontend/pages/login.php');
    exit;
}

// Token CSRF untuk logout
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
    <link rel="stylesheet" href="../../assets/css/admin-index.css">
</head>

<body>
<main class="container">
    <div class="topbar">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Selamat datang di panel pengelolaan Griya Sprei.</p>
        </div>
    </div>

    <nav class="admin-actions dashboard-actions" aria-label="Navigasi admin">
        <a href="produk.php" class="btn btn-add">Kelola Produk</a>

        <a href="tambah.php" class="btn btn-edit">Tambah Produk</a>

        <a href="beranda.php" class="btn btn-edit">Edit Beranda</a>

        <a href="register.php" class="btn btn-add">Tambah Admin</a>

        <!-- TOMBOL LOGOUT ADMIN -->
        <form
            action="/backend/actions/admin/logout.php"
            method="POST"
            class="logout-form"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >

            <button type="submit" class="btn btn-logout">
                Keluar
            </button>
        </form>
    </nav>

    <div class="dashboard-note">
        <h2>Pengelolaan Toko</h2>
        <p>Pilih salah satu menu untuk mengelola katalog dan konten toko.</p>
    </div>
</main>
</body>
</html>
