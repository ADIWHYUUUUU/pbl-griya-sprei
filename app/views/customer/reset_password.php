<?php
$message = (string) ($_SESSION['password_reset_message'] ?? '');
$messageType = ($_SESSION['password_reset_message_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['password_reset_message'], $_SESSION['password_reset_message_type']);
$token = $_GET['token'] ?? '';
$validTokenFormat = is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Buat Kata Sandi Baru - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="page-login">
    <main class="login-card login-card--register">
        <header class="brand">
            <img class="brand__logo" src="/assets/images/logo.png" alt="Logo Griya Sprei">
            <div><h1 class="brand__name">Buat Kata Sandi Baru</h1><p class="brand__tagline">Kata sandi minimal 8 karakter.</p></div>
        </header>
        <?php if ($message !== ''): ?>
            <p class="flash-message<?= $messageType === 'success' ? ' flash-message--success' : '' ?>" role="<?= $messageType === 'success' ? 'status' : 'alert' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($validTokenFormat): ?>
            <form method="post" action="/?action=customer-reset-password">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label class="field__label" for="password">Kata Sandi Baru <span class="required">*</span></label>
                    <div class="input"><input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required></div>
                </div>
                <div class="field">
                    <label class="field__label" for="konfirmasi_password">Konfirmasi Kata Sandi <span class="required">*</span></label>
                    <div class="input"><input type="password" id="konfirmasi_password" name="konfirmasi_password" autocomplete="new-password" minlength="8" required></div>
                </div>
                <button type="submit" class="btn-submit">Simpan Kata Sandi</button>
            </form>
        <?php else: ?>
            <p class="flash-message" role="alert">Link reset tidak valid. Minta link baru untuk melanjutkan.</p>
            <a class="btn-submit" href="/?page=customer-forgot-password">Minta Link Reset</a>
        <?php endif; ?>
        <a class="btn-home" href="/?page=customer-login">Kembali ke Login</a>
    </main>
</body>
</html>
