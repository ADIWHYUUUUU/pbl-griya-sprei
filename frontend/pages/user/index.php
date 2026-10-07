<?php

session_start();

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] !== 'CUSTOMER'
) {
    header('Location: ../pages/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Customer - Griya Sprei</title>
</head>

<body>

<h1>Halaman Customer</h1>

<p>
    Selamat datang,
    <?= htmlspecialchars($_SESSION['nama']); ?>
</p>

</body>
</html>