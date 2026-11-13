<?php
$message = (string) ($_SESSION['password_reset_message'] ?? '');
$messageType = ($_SESSION['password_reset_message_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['password_reset_message'], $_SESSION['password_reset_message_type']);
$requestSubmitted = $messageType === 'success' && $message !== '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Kata Sandi - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="page-login">
    <main class="login-card login-card--register">
        <header class="brand">
            <img class="brand__logo" src="/assets/images/logo.png" alt="Logo Griya Sprei">
            <div>
                <h1 class="brand__name"><?= $requestSubmitted ? 'Cek email Anda' : 'Lupa Kata Sandi' ?></h1>
                <p class="brand__tagline"><?= $requestSubmitted ? 'Periksa kotak masuk email Anda.' : 'Kami akan mengirim link reset ke email Anda.' ?></p>
            </div>
        </header>
        <?php if ($requestSubmitted): ?>
            <section class="reset-confirmation" role="status" aria-labelledby="resetConfirmationTitle">
                <div class="reset-confirmation__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m3 7 9 6 9-6"></path>
                    </svg>
                </div>
                <h2 id="resetConfirmationTitle">Permintaan reset dikirim</h2>
                <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="reset-confirmation__hint">Cek juga folder spam. Jika email terdaftar, link reset berlaku selama <strong>10 menit</strong>.</p>
            </section>
        <?php else: ?>
            <?php if ($message !== ''): ?>
                <p class="flash-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" action="/?action=customer-forgot-password">
                <div class="field">
                    <label class="field__label" for="email">Email <span class="required">*</span></label>
                    <div class="input"><input type="email" id="email" name="email" placeholder="Masukkan email terdaftar" autocomplete="email" required></div>
                </div>
                <button type="submit" class="btn-submit">Kirim Link Reset</button>
            </form>
        <?php endif; ?>
        <a class="btn-home" href="/?page=customer-login">Kembali ke Login</a>
    </main>
</body>
</html>
