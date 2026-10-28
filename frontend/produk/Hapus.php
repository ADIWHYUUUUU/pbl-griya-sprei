<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('Katalog.php');
}

csrf_check();
$id = (int)($_POST['id'] ?? 0);
$list = load_produk();
$found = false;

foreach ($list as $index => $product) {
    if ((int)$product['id'] !== $id) {
        continue;
    }

    hapus_foto($product['foto'] ?? null);
    unset($list[$index]);
    $found = true;
    break;
}

if (!$found) {
    flash('error', 'Produk tidak ditemukan.');
    redirect('Katalog.php');
}

save_produk($list);
flash('success', 'Produk berhasil dihapus dari katalog.');
redirect('Katalog.php');
