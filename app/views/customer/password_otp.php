<?php $csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Kode - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="page-login">
    <main class="login-card login-card--reset">
        <header class="brand">
            <img class="brand__logo" src="/assets/images/logo.png" alt="Logo Griya Sprei">
            <div><h1 class="brand__name">Griya Sprei</h1><p class="brand__tagline">Homemade Pillow, Bolster, Sprei, Bedcover, Etc.</p></div>
        </header>
        <div class="reset-intro"><h2>Verifikasi email</h2><p>Masukkan kode 6 digit yang dikirim ke email Anda.</p></div>
        <p class="flash-message flash-message--success" id="resetMessage" role="status">Jika email terdaftar, kode verifikasi akan dikirim.</p>
        <form data-reset-form action="/?action=customer-password-reset-verify" method="post">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="field">
                <label class="field__label" for="otp">Kode verifikasi</label>
                <div class="input"><input type="text" id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="6 digit kode" required autofocus></div>
            </div>
            <button type="submit" class="btn-submit">Verifikasi</button>
        </form>
        <form id="resendCodeForm" action="/?action=customer-password-reset-resend" method="post">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit" class="btn-home" id="resendCodeButton">Kirim ulang kode</button>
        </form>
        <a class="reset-back" href="/?page=customer-password-forgot">Gunakan email lain</a>
    </main>
    <script src="/assets/js/customer/password-reset.js"></script>
</body>
</html>
