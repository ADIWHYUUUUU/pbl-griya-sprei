
<?php

// ==========================================
// 1. SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 2. KONEKSI DATABASE
// ==========================================
require_once __DIR__ . '/../../../backend/app/config/database.php';

// ==========================================
// 3. CART CONTROLLER
// ==========================================
require_once __DIR__ . '/../../../backend/app/controllers/cart_controller.php';

// ==========================================
// 4. CEK LOGIN CUSTOMER
// ==========================================
if (!CartController::isCustomer()) {
    header('Location: /frontend/pages/login.php');
    exit;
}

$namaUser = $_SESSION['nama'] ?? 'Pelanggan';

// ==========================================
// 5. TOKEN CSRF
// ==========================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================
// 6. INISIALISASI CONTROLLER
// ==========================================
$cart = new CartController($pdo);

// ==========================================
// 7. HELPER HTML
// ==========================================
function aman($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ==========================================
// 8. FORMAT UKURAN
// ==========================================
function formatUkuran($value): string
{
    $formatted = rtrim(
        rtrim(
            number_format((float) $value, 2, '.', ''),
            '0'
        ),
        '.'
    );

    return $formatted === '' ? '0' : $formatted;
}

// ==========================================
// 9. NOTIFIKASI SESSION
// ==========================================
$cartSuccess = (string) ($_SESSION['cart_success'] ?? '');
$cartError = (string) ($_SESSION['cart_error'] ?? '');

// Flash message hanya ditampilkan satu kali.
unset(
    $_SESSION['cart_success'],
    $_SESSION['cart_error']
);

// ==========================================
// 10. PENCARIAN
// ==========================================
$cari = trim((string) ($_GET['cari'] ?? ''));

// ==========================================
// 11. AMBIL KERANJANG MELALUI CONTROLLER
// ==========================================
$result = $cart->getItems($cari);

// Seluruh produk yang sesuai pencarian
$semuaProduk = $result['rows'];

// Total set dari seluruh isi keranjang
$totalItem = $result['totalItem'];

// Badge header menghitung konfigurasi keranjang
$jumlahKeranjang = $cart->countConfigurations();

// ==========================================
// 12. PAGINATION
// ==========================================
$perPage = 5;

$totalData = count($semuaProduk);

$totalPages = max(
    1,
    (int) ceil($totalData / $perPage)
);

$page = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT
);

$page = max(
    1,
    min($totalPages, $page ?: 1)
);

// Ambil produk sesuai halaman aktif
$daftarProduk = array_slice(
    $semuaProduk,
    ($page - 1) * $perPage,
    $perPage
);

// ==========================================
// 13. LINK PAGINATION
// ==========================================
function linkPage(int $page, string $cari): string
{
    return '?' . http_build_query([
        'page' => $page,
        'cari' => $cari
    ]);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Keranjang - Griya Sprei</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/keranjang.css"
    >

</head>

<body>

<!-- ==========================================
     HEADER
========================================== -->
<?php require __DIR__ . '/../../components/header.php'; ?>


<!-- ==========================================
     ISI HALAMAN KERANJANG
========================================== -->
<main class="container">

    <div class="isi">

        <!-- ==================================
             NOTIFIKASI TAMBAH PRODUK
        =================================== -->
        <?php if (($_GET['added'] ?? '') === '1'): ?>

            <p class="cart-success" role="status">
                Produk berhasil ditambahkan ke keranjang.
            </p>

        <?php endif; ?>


        <!-- ==================================
             NOTIFIKASI UPDATE / HAPUS
        =================================== -->
        <?php if ($cartSuccess !== ''): ?>

            <p class="cart-success" role="status">
                <?= aman($cartSuccess) ?>
            </p>

        <?php endif; ?>


        <!-- ==================================
             NOTIFIKASI ERROR
        =================================== -->
        <?php if ($cartError !== ''): ?>

            <p class="cart-error" role="alert">
                <?= aman($cartError) ?>
            </p>

        <?php endif; ?>


        <!-- ==================================
             PENCARIAN + TAMBAH PRODUK
        =================================== -->
        <div class="baris-atas">

            <!-- PENCARIAN -->
            <form
                method="GET"
                action="keranjang.php"
                class="kotak-cari"
            >

                <input
                    type="text"
                    name="cari"
                    placeholder="🔍 Cari produk di keranjang..."
                    value="<?= aman($cari) ?>"
                    aria-label="Cari produk dalam keranjang"
                >

            </form>

            <!-- KEMBALI KE KATALOG -->
            <a
                href="katalog.php"
                class="tombol"
            >
                + Tambah Produk Lain
            </a>

        </div>


        <!-- ==================================
             LAYOUT DUA KOLOM
        =================================== -->
        <div class="dua-kolom">

            <!-- ==================================
                 KIRI: DAFTAR KERANJANG
            =================================== -->
            <section aria-label="Daftar produk keranjang">

                <?php if (empty($daftarProduk)): ?>

                    <!-- ==========================
                         KERANJANG KOSONG
                    =========================== -->
                    <div class="kartu produk">

                        <div class="detail">

                            <h3>

                                <?= $cari !== ''
                                    ? 'Produk tidak ditemukan.'
                                    : 'Keranjang masih kosong.' ?>

                            </h3>

                            <p>

                                <?php if ($cari !== ''): ?>

                                    Tidak ada produk dalam keranjang
                                    yang sesuai dengan pencarian Anda.

                                <?php else: ?>

                                    Belum ada sprei atau bedcover
                                    yang ditambahkan ke keranjang.
                                    Silakan pilih produk dari katalog.

                                <?php endif; ?>

                            </p>

                            <?php if ($cari !== ''): ?>

                                <a
                                    href="keranjang.php"
                                    class="tombol"
                                >
                                    Tampilkan Semua
                                </a>

                            <?php else: ?>

                                <a
                                    href="katalog.php"
                                    class="tombol"
                                >
                                    Lihat Katalog
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php else: ?>

                    <!-- ==========================
                         PERULANGAN PRODUK
                    =========================== -->
                    <?php foreach ($daftarProduk as $baris): ?>

                        <?php

                        // Data yang diberikan CartController
                        $produk = $baris['produk'];

                        $item = $baris['item'];

                        $itemId = $baris['item_id'];

                        $gambar = $baris['gambar'];

                        $jumlah = (int) $baris['jumlah'];

                        // =========================
                        // TIPE PRODUK
                        // =========================
                        $mode = strtolower(
                            (string) ($item['mode'] ?? 'standard')
                        ) === 'custom'
                            ? 'CUSTOM'
                            : 'STANDAR';

                        // =========================
                        // UKURAN PRODUK
                        // =========================
                        $panjang = formatUkuran(
                            $item['length'] ?? 0
                        );

                        $lebar = formatUkuran(
                            $item['width'] ?? 0
                        );

                        $tinggi = formatUkuran(
                            $item['height'] ?? 0
                        );

                        // =========================
                        // TAMBAHAN SARUNG
                        // =========================
                        $tambahan = [];

                        $qtyBantal = (int) (
                            $item['qtyBantal'] ?? 0
                        );

                        $qtyGuling = (int) (
                            $item['qtyGuling'] ?? 0
                        );

                        if ($qtyBantal > 0) {

                            $tambahan[] =
                                $qtyBantal . ' sarung bantal';

                        }

                        if ($qtyGuling > 0) {

                            $tambahan[] =
                                $qtyGuling . ' sarung guling';

                        }

                        $teksTambahan = empty($tambahan)
                            ? 'Tanpa tambahan'
                            : implode(' + ', $tambahan);

                        // =========================
                        // CATATAN
                        // =========================
                        $catatan = trim(
                            (string) ($item['catatan'] ?? '')
                        );

                        if ($catatan === '') {
                            $catatan = '-';
                        }

                        // =========================
                        // STOK PRODUK
                        // =========================
                        $stok = (int) ($produk['stok'] ?? 0);

                        $maxJumlah = max(
                            1,
                            min(50, $stok)
                        );

                        ?>


                        <!-- ======================
                             KARTU PRODUK
                        ======================= -->
                        <div class="kartu produk">

                            <!-- GAMBAR PRODUK -->
                            <?php if ($gambar !== ''): ?>

                                <img
                                    src="<?= aman($gambar) ?>"
                                    alt="<?= aman($produk['nama_produk']) ?>"
                                    class="foto-produk"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="foto-produk">
                                    Gambar belum tersedia
                                </div>

                            <?php endif; ?>


                            <!-- DETAIL PRODUK -->
                            <div class="detail">

                                <!-- ==================
                                     NAMA + LABEL + HAPUS
                                =================== -->
                                <div class="judul-baris">

                                    <h3>

                                        <?= aman($produk['nama_produk']) ?>

                                        <span
                                            class="label <?= $mode === 'CUSTOM'
                                                ? 'label-custom'
                                                : 'label-standar' ?>"
                                        >
                                            <?= aman($mode) ?>
                                        </span>

                                    </h3>


                                    <!-- FORM HAPUS -->
                                    <div>

                                        <form
                                            action="/backend/actions/user/hapus-keranjang.php"
                                            method="POST"
                                            class="form-hapus"
                                            onsubmit="return confirm('Hapus produk ini dari keranjang?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= aman($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="item_id"
                                                value="<?= aman($itemId) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="ikon"
                                                title="Hapus produk"
                                                aria-label="Hapus produk dari keranjang"
                                            >

                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                >

                                                    <path
                                                        d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"
                                                    />

                                                </svg>

                                            </button>

                                        </form>

                                    </div>

                                </div>


                                <!-- ==================
                                     SPESIFIKASI
                                =================== -->
                                <div class="spesifikasi">

                                    <!-- UKURAN -->
                                    <div>

                                        <small>
                                            Ukuran (P × L × T)
                                        </small>

                                        <p>

                                            <?= aman($panjang) ?>

                                            ×

                                            <?= aman($lebar) ?>

                                            ×

                                            <?= aman($tinggi) ?>

                                            cm

                                        </p>

                                    </div>


                                    <!-- TAMBAHAN SARUNG -->
                                    <div>

                                        <small>Tambahan</small>

                                        <p>
                                            <?= aman($teksTambahan) ?>
                                        </p>

                                    </div>


                                    <!-- JUMLAH DALAM SET -->
                                    <div>

                                        <small>Jumlah Pesanan</small>

                                        <p>
                                            <?= $jumlah ?> set
                                        </p>

                                    </div>


                                    <!-- TIPE UKURAN -->
                                    <div>

                                        <small>Tipe Ukuran</small>

                                        <p>
                                            <?= aman($mode) ?>
                                        </p>

                                    </div>


                                    <!-- CATATAN -->
                                    <div class="penuh">

                                        <small>Catatan</small>

                                        <p>
                                            <?= aman($catatan) ?>
                                        </p>

                                    </div>

                                </div>


                                <!-- ==================
                                     JUMLAH + CHECKOUT
                                =================== -->
                                <div class="bawah">

                                    <!-- FORM UPDATE JUMLAH -->
                                    <form
                                        action="/backend/actions/user/update-keranjang.php"
                                        method="POST"
                                        class="jumlah form-jumlah"
                                    >

                                        <!-- TOKEN CSRF -->
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= aman($_SESSION['csrf_token']) ?>"
                                        >

                                        <!-- ID KONFIGURASI -->
                                        <input
                                            type="hidden"
                                            name="item_id"
                                            value="<?= aman($itemId) ?>"
                                        >

                                        <!-- JUMLAH SET -->
                                        <input
                                            type="number"
                                            name="qtySprei"
                                            min="1"
                                            max="<?= $maxJumlah ?>"
                                            value="<?= $jumlah ?>"
                                            aria-label="Jumlah set produk"
                                            style="width:65px;"
                                            required
                                        >

                                        <span>set</span>

                                        <!-- SIMPAN JUMLAH -->
                                        <button
                                            type="submit"
                                            class="ikon"
                                            title="Simpan perubahan jumlah"
                                            aria-label="Perbarui jumlah produk"
                                        >

                                            <svg
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >

                                                <path
                                                    d="M20 11a8 8 0 0 0-14-5L4 8"
                                                />

                                                <path d="M4 4v4h4"/>

                                                <path
                                                    d="M4 13a8 8 0 0 0 14 5l2-2"
                                                />

                                                <path d="M20 20v-4h-4"/>

                                            </svg>

                                        </button>

                                    </form>


                                    <!-- CHECKOUT SATU ITEM -->
                                    <a
                                        href="checkout.php?item=<?= rawurlencode($itemId) ?>"
                                        class="tombol"
                                    >
                                        Checkout →
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <!-- ==========================
                         PAGINATION
                    =========================== -->
                    <?php if ($totalPages > 1): ?>

                        <div class="paginasi">

                            <!-- SEBELUMNYA -->
                            <?php if ($page > 1): ?>

                                <a
                                    href="<?= aman(
                                        linkPage($page - 1, $cari)
                                    ) ?>"
                                    aria-label="Halaman sebelumnya"
                                >
                                    ‹
                                </a>

                            <?php endif; ?>


                            <!-- NOMOR HALAMAN -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                                <a
                                    href="<?= aman(
                                        linkPage($i, $cari)
                                    ) ?>"
                                    class="<?= $page === $i ? 'aktif' : '' ?>"
                                    <?= $page === $i
                                        ? 'aria-current="page"'
                                        : '' ?>
                                >
                                    <?= $i ?>
                                </a>

                            <?php endfor; ?>


                            <!-- SELANJUTNYA -->
                            <?php if ($page < $totalPages): ?>

                                <a
                                    href="<?= aman(
                                        linkPage($page + 1, $cari)
                                    ) ?>"
                                    aria-label="Halaman selanjutnya"
                                >
                                    ›
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </section>


            <!-- ==================================
                 KANAN: RINGKASAN KERANJANG
            =================================== -->
            <aside class="kartu ringkasan">

                <h2>Ringkasan keranjang</h2>

                <div class="kotak-abu">

                    <!-- TOTAL KONFIGURASI -->
                    <div class="baris">

                        <span>
                            Konfigurasi produk
                        </span>

                        <b>
                            <?= $jumlahKeranjang ?>
                        </b>

                    </div>


                    <!-- TOTAL SET -->
                    <div class="baris">

                        <span>
                            Total jumlah pesanan
                        </span>

                        <b>
                            <?= $totalItem ?> set
                        </b>

                    </div>


                    <!-- TOTAL TAGIHAN -->
                    <div class="baris">

                        <span>
                            Total tagihan
                        </span>

                        <b>
                            Ditetapkan Admin
                        </b>

                    </div>

                </div>


                <!-- INFORMASI CHECKOUT -->
                <p>

                    Tekan <b>Checkout</b> pada produk
                    yang ingin dipesan.

                    Alamat dan metode pengiriman
                    diisi pada langkah checkout
                    untuk item tersebut.

                </p>


                <!-- INFORMASI VERIFIKASI -->
                <p>

                    Setelah checkout, pesanan
                    berstatus <b>Menunggu Verifikasi</b>.

                    Total tagihan akan muncul di halaman
                    Pesanan Saya setelah Admin
                    memeriksa spesifikasi pesanan.

                </p>

            </aside>

        </div>

    </div>

</main>


<!-- ==========================================
     FOOTER
========================================== -->
<?php require __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
