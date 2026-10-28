<?php

/**
 * Griya Sprei - konfigurasi & fungsi bantu
 * Penyimpanan data memakai file JSON (data/produk.json) supaya langsung jalan tanpa database.
 */
session_start();

define('DATA_FILE', __DIR__ . '/data/produk.json');
define('HOME_DATA_FILE', __DIR__ . '/data/beranda.json');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
const PER_PAGE = 4;
const MAX_FOTO = 5 * 1024 * 1024; // 5 MB

const KATEGORI = [
    'SPREI'         => 'Sprei (Fitted Sheet)',
    'BEDCOVER'      => 'Bedcover',
    'SARUNG BANTAL' => 'Sarung Bantal & Guling',
];

/* ---------- Util ---------- */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function rupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function angka(int $n): string
{
    return $n > 0 ? number_format($n, 0, ',', '.') : '';
}
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(?string $type = null, ?string $msg = null): ?array
{
    if ($type !== null) {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- Data produk ---------- */
function seed_produk(): array
{
    $nama = [
        1 => 'Rosaline - Dusty Pink',
        2 => 'Rosaline - Ivory',
        3 => 'Calista - Navy Stripe',
        4 => 'Calista - Soft Grey',
        5 => 'Verona - Cream',
        6 => 'Verona - Mocha',
        7 => 'Luna - Sky Blue',
        8 => 'Luna - Lilac',
        9 => 'Nordic - Charcoal',
        10 => 'Asterina - Sage',
        11 => 'Asterina - Burgundy',
        12 => 'Beverly - Roland (Grey & Navy)',
        13 => 'Beverly - Beckham (Light Grey)',
    ];
    $out = [];
    foreach ($nama as $id => $n) {
        $out[] = [
            'id' => $id,
            'nama' => $n,
            'kategori' => 'SPREI',
            'panjang' => $id === 10 ? 200 : 200,
            'lebar' => $id === 10 ? 160 : 200,
            'tinggi' => $id === 10 ? 30 : 200,
            'harga' => $id === 10 ? 1000000 : 200000,
            'stok' => $id === 10 ? 9 : 10,
            'foto' => '',
            'deskripsi' => '',
        ];
    }
    return $out;
}

function load_produk(): array
{
    if (!is_dir(dirname(DATA_FILE))) mkdir(dirname(DATA_FILE), 0755, true);
    if (!file_exists(DATA_FILE)) save_produk(seed_produk());
    $d = json_decode((string)file_get_contents(DATA_FILE), true);
    return is_array($d) ? $d : [];
}
function save_produk(array $list): void
{
    file_put_contents(DATA_FILE, json_encode(array_values($list), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function default_beranda(): array
{
    return [
        'hero_foto' => '',
        'hero_label' => 'Griya Sprei Malang · Homemade sejak 2010',
        'hero_judul' => 'Sprei dan bed cover pas di kasur Anda, ukuran standar maupun custom.',
        'hero_deskripsi' => 'Pilih model kain, tentukan ukuran panjang × lebar × tinggi kasur presisi cm, tambahkan sarung bantal guling, dan nikmati tidur nyaman dengan bahan MicroSilk & Katun Jepang halus.',
        'kategori' => [
            ['nama' => 'Sprei', 'deskripsi' => 'Standar atau custom sesuai kasur.', 'foto' => ''],
            ['nama' => 'Bed Cover', 'deskripsi' => 'Pilih model dan ukuran.', 'foto' => ''],
        ],
        'produk_pilihan' => [1, 9, 2, 10],
        'produk_badge' => ['Paling Laris Minggu Ini', 'Set Hemat Lengkap', '100% Katun Adem', 'Rekomendasi Favorit'],
        'produk_tampil' => [true, true, true, true],
    ];
}

function load_beranda(): array
{
    if (!is_dir(dirname(HOME_DATA_FILE))) mkdir(dirname(HOME_DATA_FILE), 0755, true);
    if (!file_exists(HOME_DATA_FILE)) save_beranda(default_beranda());
    $data = json_decode((string)file_get_contents(HOME_DATA_FILE), true);
    return is_array($data) ? array_replace_recursive(default_beranda(), $data) : default_beranda();
}

function save_beranda(array $data): void
{
    file_put_contents(HOME_DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
function find_produk(int $id): ?array
{
    foreach (load_produk() as $p) if ((int)$p['id'] === $id) return $p;
    return null;
}
function next_id(array $list): int
{
    return $list ? max(array_column($list, 'id')) + 1 : 1;
}
function ukuran_text(array $p): string
{
    return "{$p['panjang']} x {$p['lebar']} x {$p['tinggi']} cm";
}

function input_produk(array $src): array
{
    return [
        'nama'      => trim($src['nama'] ?? ''),
        'kategori'  => $src['kategori'] ?? 'SPREI',
        'panjang'   => (int)($src['panjang'] ?? 0),
        'lebar'     => (int)($src['lebar'] ?? 0),
        'tinggi'    => (int)($src['tinggi'] ?? 0),
        'harga'     => (int)preg_replace('/\D/', '', $src['harga'] ?? ''),
        'stok'      => (int)($src['stok'] ?? 0),
        'deskripsi' => trim($src['deskripsi'] ?? ''),
    ];
}

function validate_produk(array $d): array
{
    $err = [];
    if ($d['nama'] === '') $err[] = 'Nama produk wajib diisi.';
    if (!isset(KATEGORI[$d['kategori']])) $err[] = 'Kategori tidak valid.';
    if ($d['panjang'] < 1 || $d['lebar'] < 1 || $d['tinggi'] < 1) $err[] = 'Panjang, lebar, dan tinggi kasur wajib diisi dengan angka lebih dari 0.';
    if ($d['harga'] < 1) $err[] = 'Harga dasar wajib diisi.';
    if ($d['stok'] < 0) $err[] = 'Stok tidak boleh negatif.';
    if (mb_strlen($d['deskripsi']) > 500) $err[] = 'Deskripsi maksimal 500 karakter.';
    return $err;
}

/* ---------- Foto ---------- */
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? $s : 'produk';
}

function handle_upload(?array $file, ?string &$err): ?string
{
    $err = null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err = 'Upload foto gagal. Coba lagi.';
        return null;
    }
    if ($file['size'] > MAX_FOTO) {
        $err = 'Ukuran foto maksimal 5 MB.';
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $ok = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ok[$info[2]])) {
        $err = 'Format foto harus JPG, PNG, atau WebP.';
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.' . $ok[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        $err = 'Foto tidak dapat disimpan di server.';
        return null;
    }
    return $name;
}
function hapus_foto(?string $name): void
{
    if ($name && is_file(UPLOAD_DIR . basename($name))) @unlink(UPLOAD_DIR . basename($name));
}
function foto_url(?string $name): ?string
{
    return ($name && is_file(UPLOAD_DIR . basename($name))) ? UPLOAD_URL . rawurlencode(basename($name)) : null;
}
function foto_meta(?string $name): ?array
{
    if (!foto_url($name)) return null;
    $path = UPLOAD_DIR . basename($name);
    $i = @getimagesize($path);
    return ['w' => $i[0] ?? 0, 'h' => $i[1] ?? 0, 'mb' => round(filesize($path) / 1048576, 1)];
}

/* ---------- Ikon (SVG inline) ---------- */
function icon(string $n, int $s = 20): string
{
    static $p = [
        'home'      => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'box'       => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3h6v3H9zM9 12h6M9 16h6"/>',
        'card'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'layout'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 10v10"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'ruler'     => '<rect x="2" y="8" width="20" height="8" rx="1.5"/><path d="M6 8v3M10 8v4M14 8v3M18 8v4"/>',
        'image'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'text'      => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'camera'    => '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/>',
        'trash'     => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'check-c'   => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/>',
        'arrow-l'   => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'chev-l'    => '<path d="m15 6-6 6 6 6"/>',
        'chev-r'    => '<path d="m9 6 6 6-6 6"/>',
        'chev-d'    => '<path d="m6 9 6 6 6-6"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
        'logout'    => '<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'eye'       => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/><circle cx="12" cy="12" r="2.5"/>',
        'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
    ];
    return '<svg class="ico" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}
