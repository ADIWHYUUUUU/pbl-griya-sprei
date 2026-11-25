<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function checkoutEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../../helpers/product_pricing.php';

if (isset($_GET['berhasil'], $_SESSION['pesanan_terakhir'])) {
    $pesananBaru = $_SESSION['pesanan_terakhir'];
} else {
    $cart = $_SESSION['cart'] ?? [];    
    if (!is_array($cart) || $cart === []) {
        $_SESSION['cart_message'] = 'Keranjang Anda masih kosong.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: /?page=customer-cart', true, 303);
        exit;
    }
    $selectedCartIds = $_SESSION['checkout_item_ids'] ?? [];
    if (!is_array($selectedCartIds) || $selectedCartIds === []) {
        $_SESSION['cart_message'] = 'Pilih produk dari keranjang sebelum checkout.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: /?page=customer-cart', true, 303);
        exit;
    }

    $checkoutCart = [];
    foreach ($selectedCartIds as $itemId) {
        if (
            !is_string($itemId)
            || !preg_match('/^[a-f0-9]{32}$/D', $itemId)
            || !isset($cart[$itemId])
            || !is_array($cart[$itemId])
        ) {
            unset($_SESSION['checkout_item_ids']);
            $_SESSION['cart_message'] = 'Pilihan produk tidak valid. Pilih kembali dari keranjang.';
            $_SESSION['cart_message_type'] = 'error';
            header('Location: /?page=customer-cart', true, 303);
            exit;
        }
        $checkoutCart[$itemId] = $cart[$itemId];
    }

    require_once __DIR__ . '/../../controllers/ProductController.php';
    require_once __DIR__ . '/../../models/UserModel.php';
    $productController = new ProductController();
    $formValues = $_SESSION['checkout_form'] ?? [];
    $formValues = is_array($formValues) ? $formValues : [];
    unset($_SESSION['checkout_form']);
    $checkoutMessage = (string) ($_SESSION['checkout_message'] ?? '');
    $checkoutMessageType = ($_SESSION['checkout_message_type'] ?? '') === 'success' ? 'success' : 'error';
    unset($_SESSION['checkout_message'], $_SESSION['checkout_message_type']);
    $produkPesanan = [];
    foreach ($checkoutCart as $itemId => $cartItem) {
        if (!is_string($itemId) || !preg_match('/^[a-f0-9]{32}$/D', $itemId) || !is_array($cartItem)) {
            $_SESSION['cart_message'] = 'Data keranjang tidak valid. Periksa kembali keranjang Anda.';
            $_SESSION['cart_message_type'] = 'error';
            header('Location: /?page=customer-cart', true, 303);
            exit;
        }

        $productId = filter_var($cartItem['id_produk'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $product = $productId === false ? false : $productController->getProductById($productId);
        if (!$product) {
            $_SESSION['cart_message'] = 'Produk sudah tidak tersedia. Hapus item tersebut dari keranjang.';
            $_SESSION['cart_message_type'] = 'error';
            header('Location: /?page=customer-cart', true, 303);
            exit;
        }

        $quantityPillow = (int) ($cartItem['qtyBantal'] ?? 0);
        $quantityBolster = (int) ($cartItem['qtyGuling'] ?? 0);
        $extras = [];
        if ($quantityPillow > 0) {
            $extras[] = $quantityPillow . ' sarung bantal';
        }
        if ($quantityBolster > 0) {
            $extras[] = $quantityBolster . ' sarung guling';
        }
        $isPillowBolster = product_is_pillow_bolster_category((string) $product['kategori_produk']);
        $dimensions = [
            (int) ($isPillowBolster ? $product['panjang_standar'] : ($cartItem['panjang'] ?? $product['panjang_standar'])),
            (int) ($isPillowBolster ? $product['lebar_standar'] : ($cartItem['lebar'] ?? $product['lebar_standar'])),
        ];
        if (!$isPillowBolster) {
            $dimensions[] = (int) ($cartItem['tinggi'] ?? $product['tinggi_standar']);
        }
        $isCustom = !$isPillowBolster && ($cartItem['mode'] ?? '') === 'custom';
        $produkPesanan[] = [
            'nama' => (string) $product['nama_produk'],
            'gambar' => !empty($product['gambar_produk'])
                ? product_image_url($product['gambar_produk'])
                : '',
            'jenis' => $isCustom ? 'custom' : 'standar',
            'kain' => product_fabric_label((string) ($cartItem['jenis_kain'] ?? $product['jenis_kain'] ?? '')),
            'ukuran' => implode(' × ', $dimensions) . ' cm',
            'jumlah' => (int) ($cartItem['qtySprei'] ?? 0),
            'harga_satuan' => $isCustom
                ? null
                : (product_price_for(
                    (string) $product['kategori_produk'],
                    (string) ($cartItem['jenis_kain'] ?? $product['jenis_kain'] ?? ''),
                    (int) $dimensions[1]
                ) ?? (float) $product['harga_dasar']),
            'tambahan' => $extras === [] ? '-' : implode(' + ', $extras),
            'catatan' => (string) ($cartItem['catatan'] ?? ''),
        ];
    }

    $pemesan = [
        'nama' => (string) ($_SESSION['nama'] ?? ''),
        'telepon' => (string) ($_SESSION['no_hp'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
    ];
    $customerId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if ($customerId === false || $customerId === null) {
        http_response_code(403);
        exit('Akun customer tidak valid.');
    }
    $alamatDefault = (new UserModel($GLOBALS['pdo']))->getShippingAddress($customerId);
    $alamatAwal = (string) ($formValues['alamat'] ?? $alamatDefault);
    $catatanAwal = (string) ($formValues['catatan_alamat'] ?? '');
    $alamatTersimpan = trim($alamatDefault) !== '';
    $alamatEditorAktif = !$alamatTersimpan
        || ($formValues['edit_alamat'] ?? '') === '1'
        || (isset($formValues['alamat']) && $formValues['alamat'] !== $alamatDefault);
    $jumlahProduk = count($produkPesanan);
    $jumlahItem = array_sum(array_column($produkPesanan, 'jumlah'));
    $infoReguler = 'Pengiriman reguler ke seluruh area di Indonesia.';
    $metodeKirim = [
        'instan' => ['kelompok' => 'Kurir Instan', 'jenis' => 'instan', 'nama' => 'Gojek / Grab / Maxim', 'logo' => 'GGM', 'info' => 'Khusus area Kota Malang', 'waktu' => 'Dikirim setelah produksi selesai'],
        'jnt' => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'J&T Express', 'logo' => 'JT', 'info' => $infoReguler, 'waktu' => 'Perkiraan 1–3 hari'],
        'jne' => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'JNE Express', 'logo' => 'JNE', 'info' => $infoReguler, 'waktu' => 'Perkiraan 1–3 hari'],
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/customer/checkout.css?v=<?= (int) filemtime(__DIR__ . '/../../../public/assets/css/customer/checkout.css') ?>">
</head>
<body>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
<div class="isi">

    <nav class="breadcrumb">
        <a href="/?page=customer-home">Beranda</a> /
        <a href="/?page=customer-cart">Keranjang</a> /
        <b>Checkout</b>
    </nav>
    <h1 class="checkout-page-title">Checkout</h1>

<?php if (isset($pesananBaru)) { ?>

    <!-- ================= PESANAN BERHASIL DIKIRIM ================= -->
    <div class="modal-latar tampil" id="modalSukses">
        <div class="modal modal-sukses" role="dialog" aria-modal="true" aria-labelledby="judulSukses">
            <div class="ikon-sukses">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
            </div>
            <h2 id="judulSukses">Pesanan Berhasil Dikirim!</h2>
            <p>Pesanan Anda telah diterima oleh sistem kami. Admin sedang memverifikasi pesanan dan
               menghitung total biaya + ongkos kirim.</p>
            <p class="kode-pesanan">
                Kode pesanan: <b><?= checkoutEscape($pesananBaru['kode']) ?></b><br>
                Produk: <?= checkoutEscape(implode(', ', $pesananBaru['produk'] ?? [])) ?>
            </p>
            <div class="aksi-sukses">
                <a href="/?page=customer-catalog" class="tombol tombol-lebar">Lanjut Belanja</a>
                <a href="/?page=customer-cart" class="tombol tombol-abu">Tutup</a>
            </div>
        </div>
    </div>

<?php } else { ?>

    <?php if ($checkoutMessage !== ''): ?>
        <p class="checkout-message checkout-message--<?= checkoutEscape($checkoutMessageType) ?>" role="<?= $checkoutMessageType === 'success' ? 'status' : 'alert' ?>"><?= checkoutEscape($checkoutMessage) ?></p>
    <?php endif; ?>

    <form method="post" action="/?action=customer-order-create" id="formCheckout">
        <input type="hidden" name="csrf_token" value="<?= checkoutEscape($_SESSION['csrf_token']) ?>">

        <div class="checkout-layout" data-address-saved="<?= $alamatTersimpan ? 'true' : 'false' ?>">
            <div class="checkout-main-column">
                <section class="kartu address-card" aria-labelledby="addressTitle">
                    <div class="section-title-row">
                        <div>
                            <h2 id="addressTitle">1. Alamat Pengiriman</h2>
                            <p>Pastikan alamat sudah benar. Admin menghitung ongkir dari alamat ini.</p>
                        </div>
                    </div>

                    <div class="customer-identity">
                        <span class="eyebrow">Pemesan</span>
                        <strong><?= checkoutEscape($pemesan['nama']) ?></strong>
                        <span><?= checkoutEscape($pemesan['telepon']) ?> · <?= checkoutEscape($pemesan['email']) ?></span>
                    </div>

                    <div class="address-saved" id="addressSaved" <?= $alamatEditorAktif ? 'hidden' : '' ?>>
                        <div class="saved-address-copy">
                            <span class="eyebrow">Alamat tersimpan</span>
                            <p><?= nl2br(checkoutEscape($alamatDefault)) ?></p>
                            <?php if ($catatanAwal !== ''): ?>
                                <span class="saved-address-note">Patokan: <?= checkoutEscape($catatanAwal) ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button button-outline" id="tombolUbahAlamat" aria-controls="addressEditor">Edit</button>
                    </div>
                    <input type="hidden" name="alamat" id="alamatTersimpanInput" value="<?= checkoutEscape($alamatTersimpan ? $alamatDefault : '') ?>" <?= $alamatEditorAktif ? 'disabled' : '' ?>>

                    <div class="address-editor" id="addressEditor" <?= !$alamatEditorAktif ? 'hidden' : '' ?>>
                        <?php if (!$alamatTersimpan): ?>
                            <div class="address-notice">Anda belum punya alamat tersimpan. Isi alamat dan patokan di bawah, lalu tekan <strong>Simpan Alamat</strong>. Data ini menjadi alamat utama akun Anda.</div>
                        <?php endif; ?>
                        <label class="field-label" for="inputAlamat">Alamat lengkap <span>*</span></label>
                        <textarea name="alamat" id="inputAlamat" rows="3" maxlength="2000" required placeholder="Nama jalan, nomor, RT/RW, kelurahan, kecamatan, kota" <?= !$alamatEditorAktif ? 'disabled' : '' ?>><?= checkoutEscape($alamatAwal) ?></textarea>
                        <label class="field-label" for="inputCatatanAlamat">Patokan alamat <span class="optional">(opsional)</span></label>
                        <input type="text" name="catatan_alamat" id="inputCatatanAlamat" maxlength="1000" value="<?= checkoutEscape($catatanAwal) ?>" placeholder="Contoh: patokan rumah atau warna pagar" <?= !$alamatEditorAktif ? 'disabled' : '' ?>>
                        <p class="field-help">Alamat disimpan di akun. Patokan alamat hanya dipakai untuk pesanan ini.</p>
                        <div class="address-actions">
                            <button type="submit" class="button button-primary" formaction="/?action=customer-shipping-address-save" formnovalidate data-save-address>Simpan Alamat</button>
                            <?php if ($alamatTersimpan): ?>
                                <button type="button" class="button button-outline" id="tombolBatalAlamat">Batal</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!$alamatEditorAktif): ?>
                        <input type="hidden" name="catatan_alamat" value="<?= checkoutEscape($catatanAwal) ?>">
                    <?php endif; ?>
                </section>

                <section class="kartu order-card" aria-labelledby="orderTitle">
                    <div class="section-title-row">
                        <div>
                            <h2 id="orderTitle">2. Pesanan Anda <span class="title-meta">(<?= $jumlahProduk ?> produk)</span></h2>
                        </div>
                        <a class="text-link" href="/?page=customer-cart">Ubah di keranjang</a>
                    </div>

                    <div class="checkout-products">
                        <?php foreach ($produkPesanan as $produk): ?>
                            <article class="checkout-product">
                                <?php if ($produk['gambar'] !== ''): ?>
                                    <img src="<?= checkoutEscape($produk['gambar']) ?>" alt="<?= checkoutEscape($produk['nama']) ?>" class="product-thumb">
                                <?php else: ?>
                                    <div class="product-thumb product-thumb-placeholder" aria-hidden="true"></div>
                                <?php endif; ?>
                                <div class="product-copy">
                                    <h3><?= checkoutEscape($produk['nama']) ?> <span class="label label-<?= checkoutEscape($produk['jenis']) ?>"><?= strtoupper(checkoutEscape($produk['jenis'])) ?></span></h3>
                                    <p><?= checkoutEscape($produk['kain']) ?> · <?= checkoutEscape($produk['ukuran']) ?> · <?= (int) $produk['jumlah'] ?> pcs</p>
                                    <p><?= $produk['harga_satuan'] === null ? 'Harga dikonfirmasi Admin' : 'Rp ' . number_format($produk['harga_satuan'], 0, ',', '.') . ' / pcs' ?></p>
                                    <?php if ($produk['tambahan'] !== '-'): ?>
                                        <p>Tambahan: <?= checkoutEscape($produk['tambahan']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($produk['catatan'] !== ''): ?>
                                        <p>Catatan: <?= checkoutEscape($produk['catatan']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="kartu shipping-card" aria-labelledby="shippingTitle">
                    <div class="section-title-row">
                        <div>
                            <h2 id="shippingTitle">3. Metode Pengiriman <span>*</span></h2>
                            <p>Kurir instan hanya untuk alamat di Kota Malang.</p>
                        </div>
                    </div>

                    <?php
                    $selectedMethod = is_string($formValues['metode'] ?? null) && isset($metodeKirim[$formValues['metode']])
                        ? $formValues['metode']
                        : 'instan';
                    ?>
                    <div class="shipping-options">
                        <h3 class="shipping-group-title">Kurir instan</h3>
                        <label class="opsi <?= $selectedMethod === 'instan' ? 'terpilih' : '' ?>">
                            <input type="radio" name="metode" value="instan" <?= $selectedMethod === 'instan' ? 'checked' : '' ?>>
                            <span class="courier-logo courier-logo-gojek">GGM</span>
                            <span class="courier-copy"><strong>Gojek / Grab / Maxim</strong><small>Khusus area Kota Malang</small></span>
                            <small class="waktu">Dikirim setelah<br>produksi selesai</small>
                        </label>

                        <h3 class="shipping-group-title">Ekspedisi reguler</h3>
                        <?php foreach (['jnt', 'jne'] as $kode):
                            $m = $metodeKirim[$kode];
                        ?>
                            <label class="opsi <?= $selectedMethod === $kode ? 'terpilih' : '' ?>">
                                <input type="radio" name="metode" value="<?= $kode ?>" <?= $selectedMethod === $kode ? 'checked' : '' ?>>
                                <span class="courier-logo"><?= checkoutEscape($m['logo']) ?></span>
                                <span class="courier-copy"><strong><?= checkoutEscape($m['nama']) ?></strong><small><?= checkoutEscape($m['info']) ?></small></span>
                                <small class="waktu"><?= checkoutEscape($m['waktu']) ?></small>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label class="field-label shipping-note-label" for="catatanKirim">Catatan untuk kurir <span class="optional">(opsional)</span></label>
                    <textarea class="shipping-note" id="catatanKirim" name="catatan_kirim" rows="2" maxlength="1000" placeholder="Contoh: titipkan di satpam jika rumah kosong"><?= checkoutEscape($formValues['catatan_kirim'] ?? '') ?></textarea>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="summaryTitle">
                <h2 id="summaryTitle">Ringkasan</h2>
                <dl class="summary-totals">
                    <div><dt>Produk</dt><dd><?= $jumlahProduk ?> item · <?= $jumlahItem ?> pcs</dd></div>
                    <div><dt>Pengiriman</dt><dd id="summaryShipping"><?= checkoutEscape($metodeKirim[$selectedMethod]['nama'] ?? $metodeKirim['instan']['nama']) ?></dd></div>
                    <div><dt>Total tagihan</dt><dd>Ditetapkan Admin</dd></div>
                </dl>

                <div class="summary-process">
                    <h3>Setelah Anda kirim pesanan</h3>
                    <ol>
                        <li><span>1</span><p>Admin memeriksa pesanan dan menetapkan total, diskon, dan ongkir (maks. 1×24 jam).</p></li>
                        <li><span>2</span><p>Anda menerima tagihan dan membayar dalam 2×24 jam, jika tidak pesanan dibatalkan otomatis.</p></li>
                        <li><span>3</span><p>Pesanan diproses dan dikirim.</p></li>
                    </ol>
                </div>

                <label class="persetujuan">
                    <input type="checkbox" name="setuju" id="cekSetuju" value="1" required>
                    <span>Ukuran kasur (P × L × T) sudah saya ukur dengan akurat. Pesanan yang sudah masuk tahap pemotongan kain tidak dapat dibatalkan atau diubah.</span>
                </label>

                <ul class="checkout-checklist" aria-live="polite">
                    <li id="checkAddress" class="<?= $alamatTersimpan ? 'complete' : '' ?>"><?= $alamatTersimpan ? 'Alamat tersimpan' : 'Alamat belum disimpan' ?></li>
                    <li id="checkShipping" class="complete">Metode pengiriman dipilih</li>
                    <li id="checkAgreement">Konfirmasi ukuran</li>
                </ul>

                <button type="submit" class="tombol tombol-besar" id="tombolKirim" disabled>
                    Kirim Pesanan
                </button>
                <small class="summary-footnote">Isi alamat dan centang konfirmasi ukuran untuk mengirim pesanan.</small>
            </aside>
        </div>

    </form>

<?php } ?>

</div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
<script src="/assets/js/customer/checkout.js?v=<?= (int) filemtime(__DIR__ . '/../../../public/assets/js/customer/checkout.js') ?>"></script>
</body>
</html>