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

if (isset($_GET['berhasil'], $_SESSION['pesanan_terakhir'])) {
    $pesananBaru = $_SESSION['pesanan_terakhir'];
} else {
    $itemId = $_GET['item_id'] ?? '';
    $cart = $_SESSION['cart'] ?? [];
    if (
        !is_string($itemId)
        || !preg_match('/^[a-f0-9]{32}$/D', $itemId)
        || !is_array($cart)
        || !isset($cart[$itemId])
        || !is_array($cart[$itemId])
    ) {
        $_SESSION['cart_message'] = 'Pilih item keranjang yang ingin di-checkout.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: /?page=customer-cart', true, 303);
        exit;
    }

    require_once __DIR__ . '/../../controllers/ProductController.php';
    $productController = new ProductController();
    $cartItem = $cart[$itemId];
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

    $formValues = $_SESSION['checkout_form'] ?? [];
    $formValues = is_array($formValues) ? $formValues : [];
    unset($_SESSION['checkout_form']);
    $checkoutMessage = (string) ($_SESSION['checkout_message'] ?? '');
    unset($_SESSION['checkout_message']);

    $pemesan = [
        'nama' => (string) ($_SESSION['nama'] ?? ''),
        'telepon' => (string) ($_SESSION['no_hp'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
    ];
    $alamatAwal = (string) ($formValues['alamat'] ?? '');
    $catatanAwal = (string) ($formValues['catatan_alamat'] ?? '');
    $infoReguler = 'Pengiriman reguler ke seluruh area di Indonesia.';
    $metodeKirim = [
        'instan' => ['kelompok' => 'Kurir Instan', 'jenis' => 'instan', 'nama' => 'Gojek / Grab Express', 'info' => 'Khusus area Kota Malang', 'waktu' => 'Dikirim setelah produksi selesai'],
        'jnt' => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'J&T Express', 'info' => $infoReguler, 'waktu' => 'Perkiraan: 1–3 hari'],
        'jne' => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'JNE Express', 'info' => $infoReguler, 'waktu' => 'Perkiraan: 1–3 hari'],
    ];

    $dimensions = [
        'panjang' => (int) $cartItem['panjang'],
        'lebar' => (int) $cartItem['lebar'],
        'tinggi' => (int) $cartItem['tinggi'],
    ];
    foreach (array_keys($dimensions) as $field) {
        if (isset($formValues[$field]) && ctype_digit((string) $formValues[$field])) {
            $dimensions[$field] = (int) $formValues[$field];
        }
    }

    $quantityPillow = (int) ($cartItem['qtyBantal'] ?? 0);
    $quantityBolster = (int) ($cartItem['qtyGuling'] ?? 0);
    if (isset($formValues['bantal']) && ctype_digit((string) $formValues['bantal'])) {
        $quantityPillow = (int) $formValues['bantal'];
    }
    if (isset($formValues['guling']) && ctype_digit((string) $formValues['guling'])) {
        $quantityBolster = (int) $formValues['guling'];
    }

    $extras = [];
    if ($quantityPillow > 0) {
        $extras[] = $quantityPillow . ' sarung bantal';
    }
    if ($quantityBolster > 0) {
        $extras[] = $quantityBolster . ' sarung guling';
    }
    $mode = (string) ($formValues['jenis'] ?? $cartItem['mode'] ?? 'standard');
    $produk = [
        'nama' => (string) $product['nama_produk'],
        'gambar' => !empty($product['gambar_produk'])
            ? '/uploads/produk/' . rawurlencode(basename($product['gambar_produk']))
            : '',
        'jenis' => $mode === 'custom' ? 'custom' : 'standar',
        'ukuran' => implode(' × ', $dimensions) . ' cm',
        'jumlah' => (int) ($cartItem['qtySprei'] ?? 0),
        'tambahan' => $extras === [] ? '-' : implode(' + ', $extras),
        'catatan' => (string) ($formValues['catatan_produk'] ?? $cartItem['catatan'] ?? ''),
    ];
    $dimensi = $dimensions;
    $jumlahSarung = ['bantal' => $quantityPillow, 'guling' => $quantityBolster];
}

$ikonCentang = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
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
    <link rel="stylesheet" href="/assets/css/customer/checkout.css">
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
            <p class="kode-pesanan">Kode pesanan: <b><?= checkoutEscape($pesananBaru['kode']) ?></b> · <?= checkoutEscape($pesananBaru['produk']['nama']) ?></p>
            <div class="aksi-sukses">
                <a href="/?page=customer-catalog" class="tombol tombol-lebar">Lanjut Belanja</a>
                <a href="/?page=customer-cart" class="tombol tombol-abu">Tutup</a>
            </div>
        </div>
    </div>

<?php } else { ?>

    <?php if ($checkoutMessage !== ''): ?>
        <p class="checkout-message" role="alert"><?= checkoutEscape($checkoutMessage) ?></p>
    <?php endif; ?>

    <form method="post" action="/?action=customer-order-create" id="formCheckout">
        <input type="hidden" name="csrf_token" value="<?= checkoutEscape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="item_id" value="<?= checkoutEscape($itemId) ?>">

        <!-- ================= DATA & ALAMAT ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                </span>
                <div>
                    <h2>Data &amp; Alamat Pengiriman</h2>
                    <small>Informasi pemesan dan tujuan pengiriman</small>
                </div>
                <button type="button" class="tombol-kecil" data-buka-modal="modalAlamat">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m14 5 5 5-10 10H4v-5z"/></svg>
                    Ubah Alamat
                </button>
            </div>

            <div class="dua-kolom">
                <div>
                    <small class="judul-kecil">IDENTITAS PEMESAN</small>
                    <p class="baris-ikon"><b id="teksNama"><?= checkoutEscape($pemesan['nama']) ?></b></p>
                    <p class="baris-ikon">📞 <span id="teksTelepon"><?= checkoutEscape($pemesan['telepon']) ?></span></p>
                    <p class="baris-ikon">✉️ <?= checkoutEscape($pemesan['email']) ?></p>
                </div>
                <div class="kotak-alamat">
                    <small class="judul-kecil">ALAMAT PENGIRIMAN LENGKAP</small>
                    <p id="teksAlamat"><?= checkoutEscape($alamatAwal) ?></p>
                    <p class="catatan-alamat" id="kotakCatatanAlamat">ⓘ <b>Catatan alamat:</b> <span id="teksCatatanAlamat"><?= checkoutEscape($catatanAwal) ?></span></p>
                </div>
            </div>
        </section>

        <!-- ================= DETAIL PESANAN ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                </span>
                <div>
                    <h2>Detail Pesanan</h2>
                    <small>Konfirmasi detail spesifikasi pesanan</small>
                </div>
                <button type="button" class="tombol-kecil" data-buka-modal="modalSpesifikasi">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20 20 4M7 4h-3v3M20 17v3h-3"/></svg>
                    Ubah Spesifikasi
                </button>
            </div>

            <div class="produk">
                <img src="<?= checkoutEscape($produk['gambar']) ?>" alt="<?= checkoutEscape($produk['nama']) ?>" class="foto-produk">
                <div class="detail">
                    <h3>
                        <?= checkoutEscape($produk['nama']) ?>
                        <span class="label label-<?= checkoutEscape($produk['jenis']) ?>" id="teksJenis"><?= strtoupper(checkoutEscape($produk['jenis'])) ?></span>
                    </h3>
                    <p><small>Ukuran:</small> <span id="teksUkuran"><?= checkoutEscape($produk['ukuran']) ?></span> &nbsp;&nbsp; <small>Jumlah:</small> <?= (int) $produk['jumlah'] ?> pcs</p>
                    <p><small>Tambahan:</small> <span id="teksTambahan"><?= checkoutEscape($produk['tambahan']) ?></span></p>
                    <p class="catatan-produk"><b>Catatan:</b> <span id="teksCatatanProduk"><?= checkoutEscape($produk['catatan'] ?: '-') ?></span></p>
                </div>
            </div>
        </section>

        <!-- ================= METODE PENGIRIMAN ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>
                </span>
                <div>
                    <h2>Metode Pengiriman</h2>
                    <small>Pilih preferensi pengiriman produk</small>
                </div>
            </div>

            <div class="pilihan-kirim">
                <?php $selectedMethod = $formValues['metode'] ?? 'instan'; foreach ($metodeKirim as $kode => $m) { ?>
                <label class="opsi <?= $selectedMethod === $kode ? 'terpilih' : '' ?>">
                    <input type="radio" name="metode" value="<?= $kode ?>" <?= $selectedMethod === $kode ? 'checked' : '' ?>>
                    <span class="kelompok kelompok-<?= checkoutEscape($m['jenis']) ?>"><?= checkoutEscape($m['kelompok']) ?></span>
                    <b><?= checkoutEscape($m['nama']) ?></b>
                    <small><?= checkoutEscape($m['info']) ?></small>
                    <small class="waktu"><?= checkoutEscape($m['waktu']) ?></small>
                </label>
                <?php } ?>
            </div>

            <label class="label-catatan">Catatan pengiriman <small>(Opsional)</small>
                <textarea name="catatan_kirim" rows="2" maxlength="1000" placeholder="Contoh: Titipkan di satpam jika rumah sedang kosong, hubungi nomor ini sebelum datang"><?= checkoutEscape($formValues['catatan_kirim'] ?? '') ?></textarea>
            </label>
        </section>

        <!-- ================= ALUR & PERSETUJUAN ================= -->
        <section class="kartu kartu-info">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <div>
                    <h2>Alur Penetapan Total &amp; Validasi Admin</h2>
                    <p class="teks-alur">
                        Setelah Anda menekan tombol <b>"Kirim Pesanan"</b> di bawah, pesanan akan diteruskan ke sistem
                        produksi dengan status <span class="status">MENUNGGU VERIFIKASI</span>. Admin akan segera
                        memverifikasi pesanan dan menghitung total biaya serta ongkos kirim sesuai alamat Anda,
                        kemudian menerbitkan invoice resmi.
                    </p>
                </div>
            </div>

            <label class="persetujuan">
                <input type="checkbox" name="setuju" id="cekSetuju" value="1" required>
                <span>Saya mengonfirmasi bahwa data ukuran kasur (P × L × T) sudah diukur secara akurat. Saya mengerti
                      bahwa pesanan yang telah masuk tahap pemotongan kain tidak dapat dibatalkan atau diubah.</span>
            </label>
        </section>

        <!-- ================= TOMBOL KIRIM ================= -->
        <div class="bagian-kirim">
            <button type="submit" class="tombol tombol-besar" id="tombolKirim" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                Kirim Pesanan
            </button>
            <small>🕓 Verifikasi Admin: Maks 1×24 Jam</small>
        </div>


        <!-- ================= MODAL: UBAH ALAMAT PENGIRIMAN ================= -->
        <div class="modal-latar" id="modalAlamat">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulModalAlamat">
                <div class="modal-kepala">
                    <h2 id="judulModalAlamat">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        Ubah Alamat Pengiriman
                    </h2>
                    <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
                </div>

                <div class="modal-isi">
                    <p class="catatan-alamat">Nama dan nomor WhatsApp pelanggan diambil dari data akun yang sedang login.</p>
                    <label class="isian">Alamat Lengkap &amp; Kota
                        <textarea name="alamat" id="inputAlamat" rows="3" maxlength="2000"><?= checkoutEscape($alamatAwal) ?></textarea>
                    </label>
                    <label class="isian">Catatan Khusus Pengiriman <small>(Opsional)</small>
                        <input type="text" name="catatan_alamat" id="inputCatatanAlamat" maxlength="1000" value="<?= checkoutEscape($catatanAwal) ?>">
                    </label>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="tombol tombol-abu" data-tutup-modal>Batal</button>
                    <button type="button" class="tombol" id="simpanAlamat"><?= $ikonCentang ?> Simpan Perubahan</button>
                </div>
            </div>
        </div>


        <!-- ================= MODAL: UBAH SPESIFIKASI PESANAN ================= -->
        <div class="modal-latar" id="modalSpesifikasi">
            <div class="modal modal-lebar" role="dialog" aria-modal="true" aria-labelledby="judulModalSpek">
                <div class="modal-kepala">
                    <div>
                        <h2 id="judulModalSpek">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17 17 3l4 4L7 21H3z"/><path d="m8 12 2 2M11 9l2 2M14 6l2 2"/></svg>
                            Ubah Spesifikasi Pesanan
                        </h2>
                        <small><?= checkoutEscape($produk['nama']) ?></small>
                    </div>
                    <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
                </div>

                <div class="modal-isi">
                    <small class="judul-bagian">TIPE UKURAN</small>
                    <div class="pilih-tipe">
                        <?php foreach (['standar' => 'Standar', 'custom' => 'Custom'] as $nilai => $teks) { ?>
                        <label>
                            <input type="radio" name="jenis" value="<?= $nilai ?>" <?= $produk['jenis'] === $nilai ? 'checked' : '' ?>>
                            <span><?= $teks ?></span>
                        </label>
                        <?php } ?>
                    </div>

                    <small class="judul-bagian">DIMENSI KASUR</small>
                    <div class="tiga-kolom">
                        <?php foreach (['panjang' => 'Panjang', 'lebar' => 'Lebar', 'tinggi' => 'Tinggi Kasur'] as $nama => $teks) { ?>
                        <label class="isian"><?= $teks ?>
                            <?php $standardDimension = ['panjang' => 'panjang_standar', 'lebar' => 'lebar_standar', 'tinggi' => 'tinggi_standar'][$nama]; ?>
                            <span class="input-satuan"><input type="number" min="1" max="1000" name="<?= $nama ?>" id="input<?= ucfirst($nama) ?>" value="<?= checkoutEscape($dimensi[$nama]) ?>" data-standard="<?= (int) $product[$standardDimension] ?>" <?= $produk['jenis'] === 'standar' ? 'disabled' : '' ?>><i>cm</i></span>
                        </label>
                        <?php } ?>
                    </div>
                    <p class="info-tinggi">ⓘ Tinggi standar produk: <?= (int) $product['tinggi_standar'] ?> cm. Harga akhir dikonfirmasi oleh admin.</p>

                    <small class="judul-bagian">TAMBAHKAN SARUNG BANTAL DAN GULING</small>
                    <div class="dua-penghitung">
                        <?php foreach ($jumlahSarung as $nama => $jumlah) { $idInput = 'input' . ucfirst($nama); ?>
                        <div class="penghitung-kotak">
                            <div>
                                <b>Sarung <?= ucfirst($nama) ?></b>
                                <small>+Rp 25.000 / pcs</small>
                            </div>
                            <div class="penghitung">
                                <button type="button" data-kurang="<?= $idInput ?>">−</button>
                                <input type="number" min="0" max="20" name="<?= $nama ?>" id="<?= $idInput ?>" value="<?= $jumlah ?>" readonly>
                                <button type="button" data-tambah="<?= $idInput ?>">+</button>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <small class="judul-bagian">CATATAN JAHITAN KHUSUS WORKSHOP</small>
                    <textarea class="isian-lepas" name="catatan_produk" id="inputCatatanProduk" rows="3" maxlength="1000"><?= checkoutEscape($produk['catatan']) ?></textarea>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="tombol tombol-garis" data-tutup-modal>Batal</button>
                    <button type="button" class="tombol" id="simpanSpesifikasi"><?= $ikonCentang ?> Simpan Spesifikasi</button>
                </div>
            </div>
        </div>

    </form>

<?php } ?>

</div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
<script src="/assets/js/customer/checkout.js"></script>
</body>
</html>