
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method tidak diizinkan.');
}

// Hanya pengguna yang sudah login
if (
    ($_SESSION['login'] ?? false) !== true ||
    strtoupper((string) ($_SESSION['role'] ?? '')) !== 'USER'
) {
    header('Location: /frontend/pages/login.php');
    exit;
}

// Validasi CSRF
$token = (string) ($_POST['csrf_token'] ?? '');

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Token formulir tidak valid.');
}

// Ambil data pilihan dari halaman detail produk
$idProduk = filter_var(
    $_POST['id_produk'] ?? null,
    FILTER_VALIDATE_INT
);

$jumlah = filter_var(
    $_POST['jumlah'] ?? null,
    FILTER_VALIDATE_INT
);

$panjang = filter_var(
    $_POST['panjang'] ?? null,
    FILTER_VALIDATE_INT
);

$lebar = filter_var(
    $_POST['lebar'] ?? null,
    FILTER_VALIDATE_INT
);

$tinggi = filter_var(
    $_POST['tinggi'] ?? null,
    FILTER_VALIDATE_INT
);

$tambahan = trim((string) ($_POST['tambahan'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));

if (!$idProduk || !$jumlah || $jumlah < 1 || $jumlah > 100) {
    http_response_code(422);
    exit('Produk atau jumlah tidak valid.');
}

foreach ([$panjang, $lebar, $tinggi] as $ukuran) {
    if ($ukuran === false || $ukuran < 1 || $ukuran > 500) {
        http_response_code(422);
        exit('Ukuran produk tidak valid.');
    }
}

if (mb_strlen($tambahan) > 200 || mb_strlen($catatan) > 500) {
    http_response_code(422);
    exit('Tambahan atau catatan terlalu panjang.');
}

/*
 * Simpan ID produk dan spesifikasi pilihan pengguna.
 * Nama, gambar, dan harga produk nantinya diambil
 * dari database menggunakan ID ini.
 */

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$_SESSION['keranjang'][] = [
    'id_produk' => $idProduk,
    'jumlah' => $jumlah,
    'panjang' => $panjang,
    'lebar' => $lebar,
    'tinggi' => $tinggi,
    'tambahan' => $tambahan,
    'catatan' => $catatan
];

header('Location: /frontend/pages/user/keranjang.php?added=1');
exit;
