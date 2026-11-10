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

$cart = $_SESSION['cart'] ?? [];
$cart = is_array($cart) ? $cart : [];
$products = new ProductController();
$items = [];
$totalConfigurations = 0;
$selectableCartIds = [];
$cartQuantities = [];
$cartProductsByItem = [];

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
    if ($product && $quantity > 0 && $productId !== false) {
        $cartQuantities[$productId] = ($cartQuantities[$productId] ?? 0) + $quantity;
    }
    $cartProductsByItem[$itemId] = [
        'product' => $product,
        'product_id' => $productId,
        'quantity' => $quantity,
    ];

    $items[] = [
        'id' => $itemId,
        'data' => $item,
        'product' => $product,
        'name' => $name,
        'quantity' => $quantity,
        'product_id' => $productId,
    ];
}

foreach ($cartProductsByItem as $itemId => $cartItem) {
    $productId = $cartItem['product_id'];
    if (
        $cartItem['product']
        && $cartItem['quantity'] > 0
        && $productId !== false
        && $cartQuantities[$productId] <= min(50, (int) $cartItem['product']['stok'])
    ) {
        $selectableCartIds[] = $itemId;
    }
}

foreach ($items as &$entry) {
    $entry['checkout_available'] = in_array($entry['id'], $selectableCartIds, true);
}
unset($entry);

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

    <?php if ($cartMessage !== ''): ?>
        <p class="cart-toast cart-toast--<?= $cartMessageType ?>" role="status">
            <?php if ($cartMessageType === 'success'): ?>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
            <?php else: ?>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v6M12 16.5v.5" /></svg>
            <?php endif; ?>
            <span><?= aman($cartMessage) ?></span>
        </p>
    <?php endif; ?>

    <main class="container">
        <div class="isi">
            <div class="kepala-halaman">
                <div>
                    <h1>Keranjang Saya</h1>
                    <p><?= $totalConfigurations ?> produk di keranjang</p>
                </div>
                <a href="/?page=customer-catalog" class="tombol-garis">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Tambah Produk Lain
                </a>
            </div>

            <div class="dua-kolom" data-cart-selectable-ids="<?= aman(json_encode($selectableCartIds)) ?>">
                <section class="daftar-produk" aria-label="Daftar produk keranjang">
                    <div class="kartu pilih-semua">
                        <span id="cartSelectionCount" aria-live="polite">0 produk dipilih</span>
                        <button type="button" class="tombol-garis tombol-kecil" id="cartSelectAll" aria-pressed="false" <?= $selectableCartIds === [] ? 'disabled' : '' ?>>
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3.5" y="3.5" width="17" height="17" rx="3" />
                                <path d="m8 12 3 3 5-6" />
                            </svg>
                            <span data-select-all-label>Pilih Semua</span>
                        </button>
                    </div>
                    <?php if ($visibleItems === []): ?>
                        <div class="kartu kosong">
                            <h2>Keranjang masih kosong.</h2>
                            <p>Pilih produk dari katalog untuk mulai memesan.</p>
                            <a href="/?page=customer-catalog" class="tombol">Lihat Katalog</a>
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
                            $isCustom = ($item['mode'] ?? '') === 'custom';
                            $itemLength = (int) ($item['panjang'] ?? 0);
                            $itemWidth = (int) ($item['lebar'] ?? 0);
                            $itemHeight = (int) ($item['tinggi'] ?? 0);
                            $bolehTambahSarung = ($product['kategori_produk'] ?? '') === 'SPREI';
                            $itemPillows = $bolehTambahSarung ? (int) ($item['qtyBantal'] ?? 0) : 0;
                            $itemBolsters = $bolehTambahSarung ? (int) ($item['qtyGuling'] ?? 0) : 0;
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
                                <label class="cart-item-select">
                                    <input
                                        type="checkbox"
                                        class="cart-item-checkbox"
                                        value="<?= aman($entry['id']) ?>"
                                        <?= !$entry['checkout_available'] ? 'disabled' : '' ?>
                                    >
                                    <span class="sr-only">Pilih <?= aman($entry['name']) ?> untuk diajukan</span>
                                </label>

                                <?php if ($imageUrl !== ''): ?>
                                    <img class="foto-produk" src="<?= aman($imageUrl) ?>" alt="<?= aman($entry['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="foto-produk foto-placeholder">[Foto produk]</div>
                                <?php endif; ?>

                                <div class="detail">
                                    <h2><?= aman($entry['name']) ?></h2>
                                    <span class="label <?= $isCustom ? 'label-custom' : 'label-standar' ?>">Tipe: <?= $isCustom ? 'Custom' : 'Standar' ?></span>

                                    <div class="aksi-produk">
                                        <?php if ($product && $maxQuantity > 0): ?>
                                            <button type="button" class="tombol-aksi" data-edit-cart-open>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z" />
                                                </svg>
                                                Ubah
                                            </button>
                                            <dialog class="edit-cart__dialog">
                                                <form action="/?action=customer-cart-update" method="POST" class="edit-cart__form">
                                                    <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="item_id" value="<?= aman($entry['id']) ?>">
                                                    <input type="hidden" name="edit_cart" value="1">
                                                    <input type="hidden" name="mode" value="<?= $isCustom ? 'custom' : 'standard' ?>">
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
                                                                <button type="button" class="<?= !$isCustom ? 'is-active' : '' ?>" data-edit-mode="standard" aria-pressed="<?= !$isCustom ? 'true' : 'false' ?>">Standar</button>
                                                                <button type="button" class="<?= $isCustom ? 'is-active' : '' ?>" data-edit-mode="custom" aria-pressed="<?= $isCustom ? 'true' : 'false' ?>">Custom</button>
                                                            </div>
                                                        </fieldset>

                                                        <fieldset class="edit-cart__section">
                                                            <legend>Dimensi kasur</legend>
                                                            <div class="edit-cart__dimensions">
                                                                <label class="edit-cart__field">Panjang
                                                                    <span class="edit-cart__input-wrap"><input type="number" name="panjang" min="1" step="1" value="<?= $itemLength ?>" data-standard="<?= aman($product['panjang_standar']) ?>" <?= !$isCustom ? 'disabled' : '' ?> required><span>cm</span></span>
                                                                </label>
                                                                <label class="edit-cart__field">Lebar
                                                                    <span class="edit-cart__input-wrap"><input type="number" name="lebar" min="1" step="1" value="<?= $itemWidth ?>" data-standard="<?= aman($product['lebar_standar']) ?>" <?= !$isCustom ? 'disabled' : '' ?> required><span>cm</span></span>
                                                                </label>
                                                                <label class="edit-cart__field">Tinggi kasur
                                                                    <span class="edit-cart__input-wrap"><input type="number" name="tinggi" min="1" step="1" value="<?= $itemHeight ?>" data-standard="<?= aman($product['tinggi_standar']) ?>" <?= !$isCustom ? 'disabled' : '' ?> required><span>cm</span></span>
                                                                </label>
                                                            </div>
                                                            <p class="edit-cart__hint" data-edit-dimension-hint>Dimensi standar produk. Ukuran standar tidak dapat diubah.</p>
                                                        </fieldset>

                                                        <?php if ($bolehTambahSarung): ?>
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
                                                        <?php endif; ?>

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
                                            <button type="submit" class="tombol-aksi tombol-aksi--hapus">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>

                                    <dl class="spesifikasi">
                                        <div><dt>Ukuran (P × L × T)</dt><dd><strong><?= aman(formatUkuran($itemLength)) ?> × <?= aman(formatUkuran($itemWidth)) ?> × <?= aman(formatUkuran($itemHeight)) ?> cm</strong></dd></div>
                                        <div><dt>Tambahan</dt><dd><strong><?= aman($extras === [] ? 'Tanpa tambahan' : implode(' + ', $extras)) ?></strong></dd></div>
                                        <div class="penuh"><dt>Catatan</dt><dd><?= aman(trim($itemNote) ?: 'Tidak ada catatan') ?></dd></div>
                                    </dl>

                                    <div class="bawah">
                                        <?php if ($product && $maxQuantity > 0): ?>
                                            <div class="jumlah">
                                                <span>Jumlah</span>
                                                <form action="/?action=customer-cart-update" method="POST" class="qty-stepper">
                                                    <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="item_id" value="<?= aman($entry['id']) ?>">
                                                    <button type="submit" name="qtySprei" value="<?= max(1, min($entry['quantity'] - 1, $maxQuantity)) ?>" aria-label="Kurangi jumlah" <?= $entry['quantity'] <= 1 ? 'disabled' : '' ?>>−</button>
                                                    <output aria-label="Jumlah pesanan"><?= $entry['quantity'] ?></output>
                                                    <button type="submit" name="qtySprei" value="<?= $entry['quantity'] + 1 ?>" aria-label="Tambah jumlah" <?= $entry['quantity'] >= $maxQuantity ? 'disabled' : '' ?>>+</button>
                                                </form>
                                            </div>
                                            <small class="catatan-harga">Harga dihitung admin sesuai ukuran</small>
                                        <?php else: ?>
                                            <span class="cart-error">Item tidak tersedia; hapus dari keranjang untuk melanjutkan.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <?php if ($totalPages > 1): ?>
                            <nav class="paginasi" aria-label="Halaman keranjang">
                                <a href="/?page=customer-cart&amp;page_num=<?= max(1, $page - 1) ?>" aria-label="Halaman sebelumnya" <?= $page === 1 ? 'aria-disabled="true"' : '' ?>>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="m15 18-6-6 6-6" />
                                    </svg>
                                </a>
                                <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                                    <a href="/?page=customer-cart&amp;page_num=<?= $number ?>" class="<?= $page === $number ? 'aktif' : '' ?>" <?= $page === $number ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                                <?php endfor; ?>
                                <a href="/?page=customer-cart&amp;page_num=<?= min($totalPages, $page + 1) ?>" aria-label="Halaman berikutnya" <?= $page === $totalPages ? 'aria-disabled="true"' : '' ?>>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </a>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <aside class="kartu ringkasan">
                    <h2>Ringkasan keranjang</h2>
                    <div class="kotak-abu">
                        <div class="baris"><span>Produk dipilih</span><b><span id="summarySelectedCount">0</span> item</b></div>
                        <div class="baris"><span>Total tagihan</span><b>Menunggu pengecekan admin</b></div>
                        <small>Anda belum perlu membayar sekarang.</small>
                    </div>

                    <h3>Apa yang terjadi setelah ini?</h3>
                    <ol class="langkah">
                        <li>Anda mengajukan pesanan</li>
                        <li>Admin memeriksa spesifikasi (status: Menunggu Verifikasi)</li>
                        <li>Total tagihan muncul di halaman <a href="/?page=customer-orders">Pesanan Saya</a></li>
                    </ol>

                    <form id="checkoutSelectionForm" action="/?action=customer-checkout-start" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_token']) ?>">
                        <div id="selectedCartItems"></div>
                        <button type="submit" class="checkout-button" id="checkoutSelectedButton" disabled>
                            Ajukan Pesanan (<span id="checkoutSelectedCount">0</span> produk) <span aria-hidden="true">→</span>
                        </button>
                    </form>
                    <p class="catatan-pilihan">Pilihan produk tersimpan saat Anda berpindah halaman.</p>
                </aside>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../layouts/footer.php'; ?>
    <script src="/assets/js/customer/keranjang.js"></script>
</body>
</html>
