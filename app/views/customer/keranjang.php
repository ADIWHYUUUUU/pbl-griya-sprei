<?php
$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart)) {
    $cart = [];
}

$cartMessage = $_SESSION['cart_message'] ?? '';
$cartMessageType = $_SESSION['cart_message_type'] ?? 'error';
unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);

$cartItems = array_filter($cart, static function ($item, $itemId) {
    return is_array($item) && preg_match('/^[a-f0-9]{32}$/D', (string) $itemId);
}, ARRAY_FILTER_USE_BOTH);

$escapeHtml = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/customer/cart.css">
</head>
<body>
    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <main class="container cart-page">
        <h2 class="cart-page__title">Keranjang</h2>

        <?php if ($cartMessage !== ''): ?>
            <p class="cart-message cart-message--<?= $cartMessageType === 'success' ? 'success' : 'error' ?>" role="status">
                <?= $escapeHtml($cartMessage) ?>
            </p>
        <?php endif; ?>

        <?php if ($cartItems === []): ?>
            <section class="cart-empty">
                <p>Keranjang masih kosong.</p>
                <a class="cart-action" href="/?page=customer-catalog">Lihat katalog</a>
            </section>
        <?php else: ?>
            <div class="cart-list">
                <?php foreach ($cartItems as $itemId => $item): ?>
                    <article class="cart-item">
                        <?php if (!empty($item['gambar_produk'])): ?>
                            <img
                                class="cart-item__image"
                                src="/uploads/produk/<?= rawurlencode(basename((string) $item['gambar_produk'])) ?>"
                                alt="<?= $escapeHtml($item['nama_produk'] ?? 'Produk') ?>">
                        <?php endif; ?>

                        <div class="cart-item__body">
                            <h3><?= $escapeHtml($item['nama_produk'] ?? 'Produk') ?></h3>
                            <p>
                                Ukuran <?= ($item['mode'] ?? '') === 'custom' ? 'custom' : 'standar' ?>:
                                <?= (int) ($item['panjang'] ?? 0) ?> ×
                                <?= (int) ($item['lebar'] ?? 0) ?> ×
                                <?= (int) ($item['tinggi'] ?? 0) ?> cm
                            </p>
                            <p>Jumlah sprei: <?= (int) ($item['qtySprei'] ?? 0) ?></p>
                            <p>
                                Tambahan sarung bantal: <?= (int) ($item['qtyBantal'] ?? 0) ?>,
                                sarung guling: <?= (int) ($item['qtyGuling'] ?? 0) ?>
                            </p>
                            <?php if (!empty($item['catatan'])): ?>
                                <p>Catatan: <?= nl2br($escapeHtml($item['catatan'])) ?></p>
                            <?php endif; ?>
                            <p class="cart-item__price">
                                Harga dasar: Rp <?= number_format((float) ($item['harga_dasar'] ?? 0), 0, ',', '.') ?>
                            </p>
                        </div>

                        <form action="/?action=customer-cart-remove" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $escapeHtml($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="item_id" value="<?= $escapeHtml($itemId) ?>">
                            <button class="cart-remove" type="submit">Hapus</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="cart-note">
                Keranjang menyimpan konfigurasi sementara di sesi browser. Fitur checkout belum tersedia.
            </p>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
