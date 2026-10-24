<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: /?page=admin-login');
    exit;
}

$contentPath = __DIR__ . '/../../../storage/homepage.json';
$uploadDirectory = __DIR__ . '/../../../public/uploads/beranda/';
$uploadUrl = '/uploads/beranda/';
$defaults = [
    'hero_pill' => 'Griya Sprei Malang · Homemade sejak 2010',
    'hero_title' => 'Sprei dan bed cover pas di kasur Anda, ukuran standar maupun custom.',
    'hero_description' => 'Pilih model kain, tentukan ukuran kasur presisi, dan nikmati tidur nyaman.',
    'hero_image' => '',
    'explore_title' => 'Jelajahi katalog',
    'sprei_title' => 'Sprei',
    'sprei_description' => 'Standar atau custom sesuai kasur.',
    'sprei_image' => '',
    'bedcover_title' => 'Bed Cover',
    'bedcover_description' => 'Pilih model dan ukuran.',
    'bedcover_image' => '',
    'featured_title' => 'Produk Pilihan & Best Seller',
    'featured' => [],
];
$content = $defaults;
if (is_file($contentPath)) {
    $savedContent = json_decode((string) file_get_contents($contentPath), true);
    if (is_array($savedContent)) {
        $content = array_replace($defaults, $savedContent);
    }
}

$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$notice = '';
$error = '';
if (isset($_GET['saved'])) {
    $notice = 'Perubahan beranda berhasil disimpan.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $updated = [];
        foreach (array_keys($defaults) as $key) {
            if ($key !== 'featured') {
                $updated[$key] = trim((string) ($_POST[$key] ?? ''));
            }
        }

        try {
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }

            $saveUpload = static function (string $field, string $previous = '') use ($uploadDirectory, $uploadUrl): string {
                if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                    return $previous;
                }
                if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) {
                    throw new RuntimeException('Gambar harus berukuran maksimal 5 MB.');
                }

                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) {
                    throw new RuntimeException('Format gambar yang didukung: JPG, PNG, atau WebP.');
                }

                $filename = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDirectory . $filename)) {
                    throw new RuntimeException('Gambar gagal diunggah. Periksa izin folder uploads.');
                }
                return $uploadUrl . $filename;
            };

            foreach (['hero_image', 'sprei_image', 'bedcover_image'] as $imageKey) {
                $updated[$imageKey] = $saveUpload($imageKey, (string) ($content[$imageKey] ?? ''));
            }

            $updated['featured'] = [];
            foreach (($_POST['featured'] ?? []) as $index => $product) {
                if (!is_array($product)) {
                    continue;
                }
                $name = trim((string) ($product['name'] ?? ''));
                $tag = trim((string) ($product['tag'] ?? ''));
                $description = trim((string) ($product['description'] ?? ''));
                $price = filter_var($product['price'] ?? null, FILTER_VALIDATE_INT);
                if ($name === '' && $tag === '' && $description === '') {
                    continue;
                }
                if ($name === '' || $tag === '' || $description === '' || $price === false || $price < 0) {
                    throw new RuntimeException('Lengkapi nama, label, deskripsi, dan harga setiap produk pilihan.');
                }

                $imageField = 'featured_image_' . preg_replace('/[^0-9]/', '', (string) $index);
                $updated['featured'][] = [
                    'name' => $name,
                    'tag' => $tag,
                    'description' => $description,
                    'price' => $price,
                    'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($product['color'] ?? '')) ? $product['color'] : '#cccccc',
                    'image' => $saveUpload($imageField, (string) ($product['image'] ?? '')),
                ];
            }

            if (count($updated['featured']) > 12) {
                throw new RuntimeException('Produk pilihan dibatasi maksimal 12 item.');
            }

            $json = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false || file_put_contents($contentPath, $json . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Konten gagal disimpan. Periksa izin folder storage.');
            }

            header('Location: /?page=admin-edit-home&saved=1');
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $content = array_replace($content, $updated);
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Beranda - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin/edit_beranda.css">
</head>

<body>
    <header class="admin-header">
        <a href="/?page=admin-dashboard" class="back-link" aria-label="Kembali ke dashboard">← Dashboard</a>
        <div><span class="eyebrow">PENGELOLAAN TOKO</span>
            <h1>Konten beranda</h1>
        </div>
        <a href="/?page=customer-home" target="_blank" rel="noopener" class="preview-link">Lihat beranda ↗</a>
    </header>

    <main class="editor-shell">
        <?php if ($notice !== ''): ?><p class="alert alert--success" role="status"><?= $escape($notice); ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="alert alert--error" role="alert"><?= $escape($error); ?></p><?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="editor-form">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']); ?>">

            <section class="editor-section">
                <div class="section-heading">
                    <div><span class="eyebrow">01 / PEMBUKA</span>
                        <h2>Hero beranda</h2>
                    </div>
                </div>
                <div class="form-grid">
                    <label class="field field--wide">Label kecil<input name="hero_pill" maxlength="120" value="<?= $escape($content['hero_pill']); ?>"></label>
                    <label class="field field--wide">Judul utama<textarea name="hero_title" rows="2" maxlength="240" required><?= $escape($content['hero_title']); ?></textarea></label>
                    <label class="field field--wide">Deskripsi<textarea name="hero_description" rows="3" maxlength="600" required><?= $escape($content['hero_description']); ?></textarea></label>
                    <?php renderImageField('hero_image', 'Gambar utama', $content['hero_image'] ?? '', $escape); ?>
                </div>
            </section>

            <section class="editor-section">
                <div class="section-heading">
                    <div><span class="eyebrow">02 / KATEGORI</span>
                        <h2>Jelajahi katalog</h2>
                    </div>
                </div>
                <div class="form-grid">
                    <label class="field">Judul bagian<input name="explore_title" maxlength="100" value="<?= $escape($content['explore_title']); ?>"></label>
                    <span></span>
                    <label class="field">Nama kategori<input name="sprei_title" maxlength="80" value="<?= $escape($content['sprei_title']); ?>"></label>
                    <label class="field">Nama kategori<input name="bedcover_title" maxlength="80" value="<?= $escape($content['bedcover_title']); ?>"></label>
                    <label class="field">Deskripsi sprei<textarea name="sprei_description" rows="2" maxlength="180"><?= $escape($content['sprei_description']); ?></textarea></label>
                    <label class="field">Deskripsi bed cover<textarea name="bedcover_description" rows="2" maxlength="180"><?= $escape($content['bedcover_description']); ?></textarea></label>
                    <?php renderImageField('sprei_image', 'Gambar kategori sprei', $content['sprei_image'] ?? '', $escape); ?>
                    <?php renderImageField('bedcover_image', 'Gambar kategori bed cover', $content['bedcover_image'] ?? '', $escape); ?>
                </div>
            </section>

            <section class="editor-section">
                <div class="section-heading section-heading--products">
                    <div><span class="eyebrow">03 / PILIHAN TOKO</span>
                        <h2>Produk pilihan</h2>
                        <p class="section-note">Atur produk yang muncul di halaman beranda.</p>
                    </div>
                    <button type="button" class="button button--secondary" id="add-product">＋ Tambah produk</button>
                </div>
                <label class="field field--title">Judul bagian<input name="featured_title" maxlength="100" value="<?= $escape($content['featured_title']); ?>"></label>
                <div id="featured-products" class="product-list">
                    <?php foreach (($content['featured'] ?? []) as $index => $product): ?>
                        <?php renderProductFields($index, $product, $escape); ?>
                    <?php endforeach; ?>
                </div>
                <template id="product-template">
                    <?php renderProductFields('__INDEX__', [], $escape); ?>
                </template>
            </section>

            <div class="save-bar"><a href="/?page=admin-dashboard" class="cancel-link">Batal</a><button type="submit" class="button button--primary">Simpan perubahan</button></div>
        </form>
    </main>
    <script>
        const productList = document.getElementById('featured-products');
        const productTemplate = document.getElementById('product-template');
        let nextProductIndex = <?= count($content['featured'] ?? []); ?>;
        document.getElementById('add-product').addEventListener('click', () => {
            if (productList.children.length >= 12) return;
            const wrapper = document.createElement('div');
            wrapper.innerHTML = productTemplate.innerHTML.replaceAll('__INDEX__', String(nextProductIndex++));
            productList.append(wrapper.firstElementChild);
        });
        productList.addEventListener('click', (event) => {
            if (event.target.matches('[data-remove-product]')) event.target.closest('.product-card').remove();
        });
        document.querySelector('.editor-form').addEventListener('change', (event) => {
            const input = event.target;
            if (!input.matches('input[type="file"]') || !input.files?.[0]) return;

            const file = input.files[0];
            const imageField = input.closest('.image-field');
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
                alert('Pilih gambar JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.');
                input.value = '';
                return;
            }

            let preview = imageField.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'image-preview';
                preview.alt = 'Pratinjau gambar';
                imageField.insertBefore(preview, imageField.querySelector('small'));
            }

            if (preview.dataset.previewUrl) URL.revokeObjectURL(preview.dataset.previewUrl);
            preview.dataset.previewUrl = URL.createObjectURL(file);
            preview.src = preview.dataset.previewUrl;
        });
    </script>
</body>

</html>

<?php
function renderImageField(string $name, string $label, string $image, callable $escape): void
{
?>
    <div class="image-field">
        <span class="field-label"><?= $escape($label); ?></span>
        <label class="upload-control"><span>Pilih gambar</span><input type="file" name="<?= $escape($name); ?>" accept="image/jpeg,image/png,image/webp"></label>
        <?php if ($image !== ''): ?><img class="image-preview" src="<?= $escape($image); ?>" alt="Gambar saat ini"><?php endif; ?>
        <small>JPG, PNG, WebP · Maksimal 5 MB</small>
    </div>
<?php
}

function renderProductFields($index, array $product, callable $escape): void
{
    $prefix = 'featured[' . $index . ']';
    $imageIndex = $index === '__INDEX__' ? '__INDEX__' : preg_replace('/[^0-9]/', '', (string) $index);
?>
    <article class="product-card">
        <div class="product-card__heading">
            <h3>Produk pilihan</h3><button type="button" class="remove-button" data-remove-product aria-label="Hapus produk">Hapus</button>
        </div>
        <div class="form-grid">
            <label class="field">Nama produk<input name="<?= $escape($prefix); ?>[name]" maxlength="120" required value="<?= $escape($product['name'] ?? ''); ?>"></label>
            <label class="field">Label<input name="<?= $escape($prefix); ?>[tag]" maxlength="60" required value="<?= $escape($product['tag'] ?? ''); ?>"></label>
            <label class="field field--wide">Deskripsi<textarea name="<?= $escape($prefix); ?>[description]" rows="2" maxlength="220" required><?= $escape($product['description'] ?? ''); ?></textarea></label>
            <label class="field">Harga (Rp)<input type="number" name="<?= $escape($prefix); ?>[price]" min="0" step="1000" required value="<?= $escape($product['price'] ?? ''); ?>"></label>
            <label class="field">Warna placeholder<input type="color" name="<?= $escape($prefix); ?>[color]" value="<?= $escape($product['color'] ?? '#9bb87a'); ?>"></label>
            <div class="image-field field--wide">
                <span class="field-label">Foto produk</span>
                <label class="upload-control"><span>Pilih gambar</span><input type="file" name="featured_image_<?= $escape($imageIndex); ?>" accept="image/jpeg,image/png,image/webp"></label>
                <input type="hidden" name="<?= $escape($prefix); ?>[image]" value="<?= $escape($product['image'] ?? ''); ?>">
                <?php if (!empty($product['image'])): ?><img class="image-preview" src="<?= $escape($product['image']); ?>" alt="Foto produk saat ini"><?php endif; ?>
                <small>JPG, PNG, WebP · Maksimal 5 MB</small>
            </div>
        </div>
    </article>
<?php
}
?>