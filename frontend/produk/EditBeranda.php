<?php
require __DIR__ . '/config.php';

$data = load_beranda();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oldHeroFoto = $data['hero_foto'];
    $oldCategoryPhotos = array_column($data['kategori'], 'foto');
    $data['hero_label'] = trim($_POST['hero_label'] ?? '');
    $data['hero_judul'] = trim($_POST['hero_judul'] ?? '');
    $data['hero_deskripsi'] = trim($_POST['hero_deskripsi'] ?? '');
    $data['kategori'] = [];

    if ($data['hero_label'] === '' || $data['hero_judul'] === '' || $data['hero_deskripsi'] === '') {
        $errors[] = 'Label, judul, dan deskripsi hero wajib diisi.';
    }

    for ($i = 0; $i < 2; $i++) {
        $data['kategori'][$i] = [
            'nama' => trim($_POST['kategori_nama'][$i] ?? ''),
            'deskripsi' => trim($_POST['kategori_deskripsi'][$i] ?? ''),
            'foto' => $data['kategori'][$i]['foto'] ?? '',
        ];
        if ($data['kategori'][$i]['nama'] === '' || $data['kategori'][$i]['deskripsi'] === '') {
            $errors[] = 'Nama dan deskripsi setiap kategori wajib diisi.';
        }
    }

    $selectedProducts = array_map('intval', $_POST['produk_pilihan'] ?? []);
    $products = load_produk();
    $validIds = array_map(static fn(array $product): int => (int)$product['id'], $products);
    $data['produk_pilihan'] = array_values(array_filter($selectedProducts, static fn(int $id): bool => in_array($id, $validIds, true)));
    if (count($data['produk_pilihan']) !== 4) {
        $errors[] = 'Pilih tepat 4 produk untuk ditampilkan di beranda.';
    }
    $data['produk_badge'] = array_map('trim', $_POST['produk_badge'] ?? []);
    $data['produk_tampil'] = [];
    for ($i = 0; $i < 4; $i++) {
        $data['produk_tampil'][$i] = isset($_POST['produk_tampil'][$i]);
        if ($data['produk_badge'][$i] === '') $errors[] = 'Badge produk pada slot ' . ($i + 1) . ' wajib diisi.';
    }

    $heroFoto = handle_upload($_FILES['hero_foto'] ?? null, $uploadError);
    if ($uploadError) $errors[] = $uploadError;
    if ($heroFoto) $data['hero_foto'] = $heroFoto;

    $categoryPhotos = [];
    for ($i = 0; $i < 2; $i++) {
        $categoryFile = null;
        if (isset($_FILES['kategori_foto']['tmp_name'][$i])) {
            $categoryFile = [
                'name' => $_FILES['kategori_foto']['name'][$i] ?? '',
                'type' => $_FILES['kategori_foto']['type'][$i] ?? '',
                'tmp_name' => $_FILES['kategori_foto']['tmp_name'][$i],
                'error' => $_FILES['kategori_foto']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['kategori_foto']['size'][$i] ?? 0,
            ];
        }
        $categoryPhotos[$i] = handle_upload($categoryFile, $uploadError);
        if ($uploadError) $errors[] = $uploadError;
        if ($categoryPhotos[$i]) $data['kategori'][$i]['foto'] = $categoryPhotos[$i];
    }

    if (!$errors) {
        if ($heroFoto && $oldHeroFoto) hapus_foto($oldHeroFoto);
        foreach ($categoryPhotos as $i => $photo) {
            if ($photo && !empty($oldCategoryPhotos[$i])) hapus_foto($oldCategoryPhotos[$i]);
        }
        save_beranda($data);
        flash('success', 'Perubahan beranda berhasil disimpan.');
        redirect('EditBeranda.php');
    }
    if ($heroFoto) hapus_foto($heroFoto);
    foreach ($categoryPhotos as $photo) if ($photo) hapus_foto($photo);
}

$products = load_produk();
$productById = [];
foreach ($products as $product) $productById[(int)$product['id']] = $product;
$pageTitle = 'Edit Beranda';
$activeNav = 'home';
$crumbs = [['Admin', null], ['Edit Beranda', null]];
require __DIR__ . '/partials/header.php';
?>
<div class="page-head"><h1>Edit Beranda</h1><a class="btn btn-light" href="../pages/beranda.html" target="_blank" rel="noopener"><?= icon('eye', 16) ?> Pratinjau</a></div>

<?php if ($errors): ?><div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<form class="home-editor" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <section class="card home-section">
        <div class="home-section-title"><span>1</span><div><h2>Foto &amp; Tulisan Utama Depan Toko (Hero)</h2><p>Konten yang tampil di bagian paling atas beranda.</p></div></div>
        <div class="field"><label for="hero_foto">Foto Banner Utama</label><div class="photo-box home-upload" data-photo><span class="photo-thumb photo-thumb--lg" data-photo-thumb><?php if (foto_url($data['hero_foto'])): ?><img src="<?= e(foto_url($data['hero_foto'])) ?>" alt="Banner saat ini"><?php else: ?><?= icon('image', 28) ?><?php endif; ?></span><span class="photo-info"><strong data-photo-name><?= e($data['hero_foto'] ?: 'Belum ada foto') ?></strong><small data-photo-meta>JPG, PNG, atau WebP · maksimal 5 MB</small></span><input type="file" id="hero_foto" name="hero_foto" accept="image/jpeg,image/png,image/webp" hidden><button type="button" class="btn btn-white" data-photo-pick><?= icon('camera', 16) ?> Unggah Foto</button></div></div>
        <div class="field"><label for="hero_label">Label Badge</label><input class="input" id="hero_label" name="hero_label" value="<?= e($data['hero_label']) ?>" maxlength="120"></div>
        <div class="field"><label for="hero_judul">Judul Beranda</label><textarea class="input" id="hero_judul" name="hero_judul" rows="2" maxlength="240"><?= e($data['hero_judul']) ?></textarea></div>
        <div class="field"><label for="hero_deskripsi">Subjudul / Penjelasan</label><textarea class="input" id="hero_deskripsi" name="hero_deskripsi" rows="3" maxlength="500"><?= e($data['hero_deskripsi']) ?></textarea></div>
    </section>

    <section class="card home-section">
        <div class="home-section-title"><span>2</span><div><h2>Kategori di Beranda</h2><p>Atur dua kartu kategori yang tampil setelah banner.</p></div></div>
        <div class="home-category-grid"><?php foreach ($data['kategori'] as $i => $category): ?><div class="home-category"><strong>Kategori <?= $i + 1 ?></strong><div class="field"><label for="kategori_nama_<?= $i ?>">Nama Kategori</label><input class="input" id="kategori_nama_<?= $i ?>" name="kategori_nama[]" value="<?= e($category['nama']) ?>"></div><div class="field"><label for="kategori_deskripsi_<?= $i ?>">Deskripsi</label><textarea class="input" id="kategori_deskripsi_<?= $i ?>" name="kategori_deskripsi[]" rows="2"><?= e($category['deskripsi']) ?></textarea></div><div class="field"><label for="kategori_foto_<?= $i ?>">Foto Kategori</label><input class="input" type="file" id="kategori_foto_<?= $i ?>" name="kategori_foto[]" accept="image/jpeg,image/png,image/webp"></div></div><?php endforeach; ?></div>
    </section>

    <section class="card home-section">
        <div class="home-section-title"><span>3</span><div><h2>Produk Pilihan &amp; Best Seller di Beranda</h2><p>Pilih empat produk yang ditampilkan pada beranda.</p></div></div>
        <div class="home-product-grid"><?php for ($slot = 0; $slot < 4; $slot++):
            $selected = $productById[(int)($data['produk_pilihan'][$slot] ?? 0)] ?? $products[0];
            $selectedPhoto = foto_url($selected['foto'] ?? '');
            ?>
            <article class="home-product-slot">
                <div class="home-product-slot-head">
                    <strong>SLOT <?= $slot + 1 ?><?= $slot === 0 ? ' (UTAMA)' : '' ?></strong>
                    <label class="home-toggle"><input type="checkbox" name="produk_tampil[<?= $slot ?>]" <?= !empty($data['produk_tampil'][$slot]) ? 'checked' : '' ?>><span></span> Tampil</label>
                </div>
                <div class="field"><label for="produk_<?= $slot ?>">Pilih Produk dari Katalog</label><div class="select-wrap"><select class="input" id="produk_<?= $slot ?>" name="produk_pilihan[]" required><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>" data-product-name="<?= e($product['nama']) ?>" data-product-price="<?= e(rupiah((int)$product['harga'])) ?>" data-product-photo="<?= e(foto_url($product['foto'] ?? '') ?: '') ?>" <?= (int)($data['produk_pilihan'][$slot] ?? 0) === (int)$product['id'] ? 'selected' : '' ?>><?= e($product['nama']) ?> · <?= e(rupiah((int)$product['harga'])) ?></option><?php endforeach; ?></select><?= icon('chev-d', 16) ?></div></div>
                <div class="field"><label for="produk_badge_<?= $slot ?>">Badge Khusus / Label Promo</label><input class="input" id="produk_badge_<?= $slot ?>" name="produk_badge[]" value="<?= e($data['produk_badge'][$slot] ?? '') ?>" maxlength="80"></div>
                <div class="home-product-preview">
                    <span class="home-product-thumb"><?php if ($selectedPhoto): ?><img src="<?= e($selectedPhoto) ?>" alt=""><?php else: ?><?= icon('image', 20) ?><?php endif; ?></span>
                    <span><strong data-product-preview-name><?= e($selected['nama']) ?></strong><small><span data-product-preview-price><?= e(rupiah((int)$selected['harga'])) ?></span> · <?= e(ucfirst(strtolower($selected['kategori'] ?? 'Produk'))) ?></small></span>
                </div>
            </article>
        <?php endfor; ?></div>
    </section>
    <div class="form-actions"><a class="btn btn-light" href="../pages/dasbord.php">Kembali ke Dashboard</a><button class="btn btn-primary btn-lg" type="submit"><?= icon('check', 18) ?> Simpan Perubahan Beranda</button></div>
</form>
<?php require __DIR__ . '/partials/footer.php'; ?>
