<?php

session_start();

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] !== 'ADMIN'
) {
    header('Location: ../pages/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Griya Sprei</title>
</head>

<body>

<h1>Dashboard Admin</h1>

<p>
    Selamat datang,
    <?= htmlspecialchars($_SESSION['nama']); ?>
</p>

</body>
</html>