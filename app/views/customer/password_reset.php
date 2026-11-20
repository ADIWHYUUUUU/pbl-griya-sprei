<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Baru - Griya Sprei</title>
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
        <div class="reset-intro"><h2>Buat password baru</h2><p>Password minimal 8 karakter.</p></div>
        <p class="flash-message" id="resetMessage" role="alert" hidden></p>
        <form data-reset-form action="/?action=customer-password-reset-save" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="field"><label class="field__label" for="password">Password baru</label><div class="input"><input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required></div></div>
            <div class="field"><label class="field__label" for="konfirmasi_password">Konfirmasi password</label><div class="input"><input type="password" id="konfirmasi_password" name="konfirmasi_password" autocomplete="new-password" minlength="8" required></div></div>
            <button type="submit" class="btn-submit">Simpan password baru</button>
        </form>
    </main>
    <script src="/assets/js/customer/password-reset.js"></script>
</body>
</html>
