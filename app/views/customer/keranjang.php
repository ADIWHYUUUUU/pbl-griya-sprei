<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controllers/ProductController.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function aman($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatUkuran($value): string
{
    return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0';
}

$cartMessage = (string) ($_SESSION['cart_message'] ?? '');
$cartMessageType = ($_SESSION['cart_message_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);

$search = trim((string) ($_GET['cari'] ?? ''));
$cart = $_SESSION['cart'] ?? [];
$cart = is_array($cart) ? $cart : [];
$products = new ProductController();
$items = [];
$totalConfigurations = 0;

foreach ($cart as $itemId => $item) {
    if (!is_string($itemId) || !preg_match('/^[a-f0-9]{32}$/D', $itemId) || !is_array($item)) {
        continue;
    }

    $productId = filter_var($item['id_produk'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $totalConfigurations++;
    $product = $productId === false ? false : $products->getProductById($productId);
    $name = (string) ($product['nama_produk'] ?? $item['nama_produk'] ?? 'Produk tidak tersedia');
    $quantity = max(0, (int) ($item['qtySprei'] ?? 0));
    if ($search !== '' && stripos($name, $search) === false) {
        continue;
    }

    $items[] = [
        'id' => $itemId,
        'data' => $item,
        'product' => $product,
        'name' => $name,
        'quantity' => $quantity,
    ];
}

$perPage = 3;
$totalPages = max(1, (int) ceil(count($items) / $perPage));
$page = filter_var($_GET['page_num'] ?? 1, FILTER_VALIDATE_INT);
$page = max(1, min($totalPages, $page ?: 1));
$visibleItems = array_slice($items, ($page - 1) * $perPage, $perPage);

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
    <link rel="stylesheet" href="/assets/css/customer/keranjang.css">
</head>
<body>
    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="isi">
            <h1 class="sr-only">Keranjang Belanja</h1>

            <?php if ($cartMessage !== ''): ?>
                <p class="cart-message cart-message--<?= $cartMessageType ?>" role="status">
                    <?= aman($cartMessage) ?>
                </p>
            <?php endif; ?>

            <div class="baris-atas">
                <form method="GET" action="/" class="kotak-cari">
                    <input type="hidden" name="page" value="customer-cart">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input type="search" name="cari" placeholder="Cari produk di keranjang..." value="<?= aman($search) ?>" aria-label="Cari produk dalam keranjang">
                </form>
                <a href="/?page=customer-catalog" class="tombol">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Tambah Produk Lain
                </a>
            </div>

            <div class="dua-kolom">
                <section aria-label="Daftar produk keranjang">
                    <?php if ($visibleItems === []): ?>
                        <div class="kartu kosong">
                            <h2><?= $search !== '' ? 'Produk tidak ditemukan.' : 'Keranjang masih kosong.' ?></h2>
                            <p><?= $search !== '' ? 'Coba kata pencarian lain.' : 'Pilih produk dari katalog untuk mulai memesan.' ?></p>
                            <?php if ($search !== ''): ?>
                                <a href="/?page=customer-cart" class="tombol">Tampilkan Semua</a>
                            <?php else: ?>
                                <a href="/?page=customer-catalog" class="tombol">Lihat Katalog</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($visibleItems as $entry): ?>
                            <?php
                            $item = $entry['data'];
                            $product = $entry['product'];
                            $stock = (int) ($product['stok'] ?? 0);
                            $otherQuantity = 0;
                            foreach ($cart as $otherId => $otherItem) {
                                if ($otherId !== $entry['id'] && is_array($otherItem) && (int) ($otherItem['id_produk'] ?? 0) === (int) ($item['id_produk'] ?? 0)) {
                                    $otherQuantity += max(0, (int) ($otherItem['qtySprei'] ?? 0));
                                }
                            }
                            $maxQuantity = min(50, max(0, $stock - $otherQuantity));
                            $image = trim((string) ($product['gambar_produk'] ?? $item['gambar_produk'] ?? ''));
                            $imageUrl = $image !== '' ? '/uploads/produk/' . rawurlencode(basename($image)) : '';
                            $mode = ($item['mode'] ?? '') === 'custom' ? 'CUSTOM' : 'STANDAR';
                            $itemLength = (int) ($item['panjang'] ?? 0);
                            $itemWidth = (int) ($item['lebar'] ?? 0);
                            $itemHeight = (int) ($item['tinggi'] ?? 0);
                            $itemPillows = (int) ($item['qtyBantal'] ?? 0);
                            $itemBolsters = (int) ($item['qtyGuling'] ?? 0);
                            $itemNote = (string) ($item['catatan'] ?? '');
                            $extras = [];
                            if ($itemPillows > 0) {
                                $extras[] = $itemPillows . ' sarung bantal';
                            }
                            if ($itemBolsters > 0) {
                                $extras[] = $itemBolsters . ' sarung guling';
                            }
                            ?>
                            <article class="kartu produk">
                                <?php if ($imageUrl !== ''): ?>
                                    <img class="foto-produk" src="<?= aman($imageUrl) ?>" alt="<?= aman($entry['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="foto-produk foto-placeholder" aria-label="Gambar tidak tersedia">Gambar tidak tersedia</div>
                                <?php endif; ?>

                                <div class="detail">
                                    <div class="judul-baris">
                                        <h2><?= aman($entry['name']) ?> <span class="label <?= $mode === 'CUSTOM' ? 'label-custom' : 'label-standar' ?>"><?= aman($mode) ?></span></h2>
                                        <div class="aksi-produk">
                                            <?php if ($product && $maxQuantity > 0): ?>
                                                <button type="button" class="ikon edit-cart__trigger" aria-label="Edit <?= aman($entry['name']) ?>" title="Edit pesanan" data-edit-cart-open>
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M4 5h12M4 10h9M4 15h6" />
                                                            <path d="m14 17 5.5-5.5a1.4 1.4 0 0 1 2 2L16 19l-3 1z" />
                                                        </svg>
                                                </button>
                                                <dialog class="edit-cart__dialog">
                                                    <form action="/?action=customer-cart-update" method="POST" class="edit-cart__form">
                                                        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']) ?>">
                                                        <input type="hidden" name="item_id" value="<?= aman($entry['id']) ?>">
                                                        <input type="hidden" name="edit_cart" value="1">
                                                        <input type="hidden" name="mode" value="<?= $mode === 'CUSTOM' ? 'custom' : 'standard' ?>">
                                                        <input type="hidden" name="qtySprei" value="<?= $entry['quantity'] ?>">
                                                        <header class="edit-cart__header">
                                                            <div>
                                                                <h3>Ubah Spesifikasi Pesanan</h3>
                                                                <p><?= aman($entry['name']) ?></p>
                                                            </div>
                                                            <button type="button" class="edit-cart__close" aria-label="Tutup" data-edit-cart-close>×</button>
                                                        </header>

                                                        <div class="edit-cart__body">
                                                            <fieldset class="edit-cart__section edit-cart__type">
                                                                <legend>Tipe ukuran</legend>
                                                                <div class="edit-cart__segments" aria-label="Tipe ukuran pesanan">
                                                                    <button type="button" class="<?= $mode === 'STANDAR' ? 'is-active' : '' ?>" data-edit-mode="standard" aria-pressed="<?= $mode === 'STANDAR' ? 'true' : 'false' ?>">Standar</button>
                                                                    <button type="button" class="<?= $mode === 'CUSTOM' ? 'is-active' : '' ?>" data-edit-mode="custom" aria-pressed="<?= $mode === 'CUSTOM' ? 'true' : 'false' ?>">Custom</button>
                                                                </div>
                                                            </fieldset>

                                                            <fieldset class="edit-cart__section">
                                                                <legend>Dimensi kasur</legend>
                                                                <div class="edit-cart__dimensions">
                                                                    <label class="edit-cart__field">Panjang
                                                                        <span class="edit-cart__input-wrap"><input type="number" name="panjang" min="1" step="1" value="<?= $itemLength ?>" data-standard="<?= aman($product['panjang_standar']) ?>" <?= $mode === 'STANDAR' ? 'disabled' : '' ?> required><span>cm</span></span>
                                                                    </label>
                                                                    <label class="edit-cart__field">Lebar
                                                                        <span class="edit-cart__input-wrap"><input type="number" name="lebar" min="1" step="1" value="<?= $itemWidth ?>" data-standard="<?= aman($product['lebar_standar']) ?>" <?= $mode === 'STANDAR' ? 'disabled' : '' ?> required><span>cm</span></span>
                                                                    </label>
                                                                    <label class="edit-cart__field">Tinggi kasur
                                                                        <span class="edit-cart__input-wrap"><input type="number" name="tinggi" min="1" step="1" value="<?= $itemHeight ?>" data-standard="<?= aman($product['tinggi_standar']) ?>" <?= $mode === 'STANDAR' ? 'disabled' : '' ?> required><span>cm</span></span>
                                                                    </label>
                                                                </div>
                                                                <p class="edit-cart__hint" data-edit-dimension-hint>Dimensi standar produk. Ukuran standar tidak dapat diubah.</p>
                                                            </fieldset>

                                                            <fieldset class="edit-cart__section">
                                                                <legend>Tambahkan sarung bantal dan guling</legend>
                                                                <div class="edit-cart__extras">
                                                                    <label class="edit-cart__extra">
                                                                        <span>Sarung Bantal</span>
                                                                        <span class="edit-cart__stepper">
                                                                            <button type="button" aria-label="Kurangi sarung bantal" data-quantity-step="-1">−</button>
                                                                            <input type="number" name="qtyBantal" min="0" max="20" value="<?= $itemPillows ?>" required>
                                                                            <button type="button" aria-label="Tambah sarung bantal" data-quantity-step="1">+</button>
                                                                        </span>
                                                                    </label>
                                                                    <label class="edit-cart__extra">
                                                                        <span>Sarung Guling</span>
                                                                        <span class="edit-cart__stepper">
                                                                            <button type="button" aria-label="Kurangi sarung guling" data-quantity-step="-1">−</button>
                                                                            <input type="number" name="qtyGuling" min="0" max="20" value="<?= $itemBolsters ?>" required>
                                                                            <button type="button" aria-label="Tambah sarung guling" data-quantity-step="1">+</button>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </fieldset>

                                                            <label class="edit-cart__field edit-cart__note">Catatan jahitan khusus workshop
                                                                <textarea name="catatan" maxlength="1000" rows="3"><?= aman($itemNote) ?></textarea>
                                                            </label>
                                                        </div>

                                                        <footer class="edit-cart__actions">
                                                            <button type="button" class="edit-cart__cancel" data-edit-cart-close>Batal</button>
                                                            <button type="submit" class="tombol">✓ &nbsp; Simpan Spesifikasi</button>
                                                        </footer>
                                                    </form>
                                                </dialog>
                                            <?php endif; ?>
                                        <form action="/?action=customer-cart-remove" method="POST" onsubmit="return confirm('Hapus produk ini dari keranjang?')">
                                            <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="item_id" value="<?= aman($entry['id']) ?>">
                                            <button type="submit" class="ikon" aria-label="Hapus <?= aman($entry['name']) ?>" title="Hapus produk">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                                </svg>
                                            </button>
                                        </form>
                                        </div>
                                    </div>

                                    <div class="spesifikasi">
                                        <div><small>Ukuran (P × L × T)</small><p><?= aman(formatUkuran($itemLength)) ?> × <?= aman(formatUkuran($itemWidth)) ?> × <?= aman(formatUkuran($itemHeight)) ?> cm</p></div>
                                        <div><small>Tambahan</small><p><?= aman($extras === [] ? 'Tanpa tambahan' : implode(' + ', $extras)) ?></p></div>
                                        <div class="penuh"><small>Catatan</small><p><?= aman(trim($itemNote) ?: '-') ?></p></div>
                                    </div>

                                    <div class="bawah">
                                        <?php if ($product && $maxQuantity > 0): ?>
                                            <form action="/?action=customer-cart-update" method="POST" class="qty-stepper">
                                                <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="item_id" value="<?= aman($entry['id']) ?>">
                                                <button type="submit" name="qtySprei" value="<?= max(1, min($entry['quantity'] - 1, $maxQuantity)) ?>" aria-label="Kurangi jumlah" <?= $entry['quantity'] <= 1 ? 'disabled' : '' ?>>−</button>
                                                <output aria-label="Jumlah pesanan"><?= $entry['quantity'] ?></output>
                                                <button type="submit" name="qtySprei" value="<?= $entry['quantity'] + 1 ?>" aria-label="Tambah jumlah" <?= $entry['quantity'] >= $maxQuantity ? 'disabled' : '' ?>>+</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="cart-error">Item tidak tersedia; hapus dari keranjang untuk melanjutkan.</span>
                                        <?php endif; ?>
                                        <button type="button" class="checkout-button" disabled title="Checkout belum tersedia">Checkout <span aria-hidden="true">→</span></button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <nav class="paginasi" aria-label="Halaman keranjang">
                            <a href="/?page=customer-cart&amp;page_num=<?= max(1, $page - 1) ?>&amp;cari=<?= rawurlencode($search) ?>" aria-label="Halaman sebelumnya" <?= $page === 1 ? 'aria-disabled="true"' : '' ?>>‹</a>
                            <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                                <a href="/?page=customer-cart&amp;page_num=<?= $number ?>&amp;cari=<?= rawurlencode($search) ?>" class="<?= $page === $number ? 'aktif' : '' ?>" <?= $page === $number ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                            <?php endfor; ?>
                            <a href="/?page=customer-cart&amp;page_num=<?= min($totalPages, $page + 1) ?>&amp;cari=<?= rawurlencode($search) ?>" aria-label="Halaman berikutnya" <?= $page === $totalPages ? 'aria-disabled="true"' : '' ?>>›</a>
                        </nav>
                    <?php endif; ?>
                </section>

                <aside class="kartu ringkasan">
                    <h2>Ringkasan keranjang</h2>
                    <div class="kotak-abu">
                        <div class="baris"><span>Produk di keranjang</span><b><?= $totalConfigurations ?> item</b></div>
                        <div class="baris"><span>Total tagihan</span><b>Dikonfirmasi admin</b></div>
                    </div>
                    <p>Tekan Checkout pada produk yang ingin dipesan. Alamat dan metode pengiriman diisi pada langkah checkout untuk item tersebut.</p>
                    <p>Setelah checkout, pesanan berstatus <strong>Menunggu Verifikasi</strong>. Total tagihan akan muncul di halaman Pesanan Saya setelah admin memeriksa spesifikasi pesanan.</p>
                </aside>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../layouts/footer.php'; ?>
    <script src="/assets/js/customer/keranjang.js"></script>
</body>
</html>
