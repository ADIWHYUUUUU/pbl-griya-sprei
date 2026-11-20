<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$hasAdminSession = ($_SESSION['login'] ?? false) === true && ($_SESSION['role'] ?? '') === 'ADMIN';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftarkan Admin - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="page-login page-register">
<main class="login-card login-card--register">
    <header class="brand">
        <img class="brand__logo" src="/assets/images/logo.png" alt="Logo Griya Sprei">
        <div>
            <h1 class="brand__name">Griya Sprei</h1>
            <p class="brand__tagline">Akses pengelolaan toko</p>
        </div>
    </header>
    <div style="margin-bottom: 24px;">
        <h2 style="margin-bottom: 5px;">Buat Akun Admin</h2>
        <p style="font-size: 14px; color: #777;">Daftarkan akun untuk mengelola toko Griya Sprei</p>
    </div>
    <form id="adminRegisterForm" action="/?action=admin-register" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!$hasAdminSession): ?>
            <p class="field__hint" style="margin: 0 0 14px;">Verifikasi dengan akun admin yang sudah aktif.</p>
            <div class="field">
                <label class="field__label" for="admin_email">Email admin aktif <span class="required">*</span></label>
                <div class="input">
                    <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    <input type="email" id="admin_email" name="admin_email" placeholder="Email admin yang memberi akses" autocomplete="username" required>
                </div>
            </div>
            <div class="field">
                <label class="field__label" for="admin_password">Kata sandi admin aktif <span class="required">*</span></label>
                <div class="input">
                    <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    <input type="password" id="admin_password" name="admin_password" placeholder="Kata sandi admin yang memberi akses" autocomplete="current-password" required>
                </div>
            </div>
        <?php endif; ?>
        <div class="field">
            <label class="field__label" for="nama">Nama Lengkap <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" autocomplete="name" required>
            </div>
        </div>
        <div class="field">
            <label class="field__label" for="email">Email <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <input type="email" id="email" name="email" placeholder="Masukkan email admin" autocomplete="email" required>
            </div>
        </div>
        <div class="field">
            <label class="field__label" for="no_hp">Nomor HP <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 18h4"/></svg>
                <input type="tel" id="no_hp" name="no_hp" placeholder="Contoh: 081234567890" autocomplete="tel" required>
            </div>
        </div>
        <div class="field">
            <label class="field__label" for="password">Kata Sandi <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" minlength="8" autocomplete="off" required>
            </div>
        </div>
        <div class="field">
            <label class="field__label" for="konfirmasi_password">Konfirmasi Kata Sandi <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi kata sandi" minlength="8" autocomplete="off" required>
            </div>
        </div>
        <button type="submit" class="btn-submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            Daftar Akun Admin
        </button>
    </form>
    <p class="register"><a href="<?= $hasAdminSession ? '/?page=admin-dashboard' : '/?page=admin-login'; ?>"><?= $hasAdminSession ? 'Kembali ke dashboard' : 'Kembali ke login admin'; ?></a></p>
</main>
<script src="/assets/js/admin/register.js"></script>
</body>
</html>