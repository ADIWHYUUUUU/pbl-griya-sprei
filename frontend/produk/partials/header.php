<?php
$flashMessage = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sytle.css">
</head>
<body>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="../pages/dasbord.php">
        <span class="brand-logo"><?= icon('home', 24) ?></span>
        <span class="brand-text"><strong>Griya Sprei</strong><small>Sistem Kelola Toko</small></span>
    </a>
    <nav class="nav" aria-label="Navigasi admin">
        <a class="nav-item" href="../pages/dasbord.php"><?= icon('home', 18) ?> Dashboard</a>
        <a class="nav-item<?= ($activeNav ?? '') === 'products' ? ' is-active' : '' ?>" href="Katalog.php"><?= icon('box', 18) ?> Crud Produk</a>
        <a class="nav-item<?= ($activeNav ?? '') === 'orders' ? ' is-active' : '' ?>" href="CekPesanan.php"><?= icon('clipboard', 18) ?> Cek Pesanan</a>
        <a class="nav-item<?= ($activeNav ?? '') === 'payments' ? ' is-active' : '' ?>" href="CekPembayaran.php"><?= icon('card', 18) ?> Cek Pembayaran</a>
        <a class="nav-item<?= ($activeNav ?? '') === 'home' ? ' is-active' : '' ?>" href="EditBeranda.php"><?= icon('layout', 18) ?> Edit Beranda</a>
    </nav>
    <div class="sidebar-foot">
        <div class="user">
            <span class="avatar"><?= icon('user', 18) ?></span>
            <span><strong>Admin</strong><small>Ibu Nita</small></span>
        </div>
        <a class="nav-item logout" href="../pages/beranda.html"><?= icon('logout', 18) ?> Keluar</a>
    </div>
</aside>
<div class="scrim" id="scrim"></div>
<div class="main">
    <header class="topbar">
        <button class="burger" id="burger" type="button" aria-label="Buka menu"><?= icon('menu', 20) ?></button>
        <nav class="crumbs" aria-label="Breadcrumb">
            <?php foreach ($crumbs as $index => [$label, $url]): ?>
                <?php if ($index > 0): ?><span aria-hidden="true">›</span><?php endif; ?>
                <?php if ($url): ?>
                    <a href="<?= e($url) ?>"><?= e($label) ?></a>
                <?php else: ?>
                    <span class="current"><?= e($label) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="content">
        <?php if ($flashMessage): ?>
            <div class="alert alert--<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['msg']) ?></div>
        <?php endif; ?>
