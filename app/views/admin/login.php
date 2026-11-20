<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="page-login">
<main class="login-card">
    <header class="brand">
        <img class="brand__logo" src="/assets/images/logo.png" alt="Logo Griya Sprei">
        <div>
            <h1 class="brand__name">Griya Sprei</h1>
            <p class="brand__tagline">Akses pengelolaan toko</p>
        </div>
    </header>
    <div style="margin-bottom: 24px;">
        <h2 style="margin-bottom: 5px;">Masuk Admin</h2>
        <p style="font-size: 14px; color: #777;">Masuk untuk mengelola toko Griya Sprei</p>
    </div>
    <form id="adminLoginForm" action="/?action=admin-login" method="post" novalidate>
        <p class="flash-message" id="loginMessage" role="alert" hidden></p>
        <div class="field" id="emailField">
            <label class="field__label" for="email">Email <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <input type="email" id="email" name="email" placeholder="Masukkan email admin" autocomplete="email">
                <button type="button" class="input__action" id="clearEmail" aria-label="Hapus email" hidden>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
                </button>
            </div>
            <p class="field__hint" id="emailHint"><span>Masukkan alamat email admin yang valid</span></p>
        </div>
        <div class="field" id="passwordField">
            <label class="field__label" for="password">Kata sandi <span class="required">*</span></label>
            <div class="input">
                <svg class="input__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password">
                <button type="button" class="input__action" id="togglePassword" aria-label="Tampilkan kata sandi">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <p class="field__hint field__hint--error" id="passwordHint" hidden><span>Kata sandi wajib diisi</span></p>
        </div>
        <label class="remember">
            <input type="checkbox" id="remember" name="remember">
            <span class="remember__box"></span>
            Ingat saya
        </label>
        <button type="submit" class="btn-submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M10 8l4 4-4 4M14 12H3"/></svg>
            Masuk ke Dashboard
        </button>
    </form>
    <p class="register">Belum punya akun admin? <a href="/?page=admin-register">Daftar akun</a></p>
</main>
<script src="/assets/js/admin/login.js"></script>
</body>
</html>