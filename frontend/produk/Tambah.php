<?php
require __DIR__ . '/config.php';

$errors = [];
$d = ['nama' => '', 'kategori' => 'SPREI', 'panjang' => 200, 'lebar' => 160, 'tinggi' => 30,
      'harga' => 0, 'stok' => 0, 'deskripsi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = input_produk($_POST);
    $errors = validate_produk($d);
    $foto = handle_upload($_FILES['foto'] ?? null, $upErr);
    if ($upErr) $errors[] = $upErr;

    if (!$errors) {
        $list = load_produk();
        $list[] = ['id' => next_id($list)] + $d + ['foto' => $foto ?? ''];
        save_produk($list);
        flash('success', "Produk “{$d['nama']}” berhasil ditambahkan.");
        redirect('Katalog.php');
    }
    if (!empty($foto)) hapus_foto($foto); // form gagal: jangan menyisakan file yatim
}

$pageTitle = 'Tambah Produk Baru';
$crumbs = [['Admin', null], ['Katalog Produk', 'Katalog.php'], ['Tambah Produk Baru', null]];
require __DIR__ . '/partials/header.php';
?>
<div class="card-head card-head--plain"><h1>Tambah Katalog Produk</h1></div>

<form class="card form-card form-card--simple" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <?php if ($errors): ?>
        <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="form-intro">
        <h2>Informasi Produk</h2>
        <p>Masukkan data sprei</p>
    </div>

    <div class="field">
        <label for="nama">Nama Produk <span class="req">*</span></label>
        <input class="input" type="text" id="nama" name="nama" value="<?= e($d['nama']) ?>" placeholder="Contoh: Asterina - Sage Green Tencel" required>
    </div>

    <div class="field">
        <label for="kategori">Kategori <span class="req">*</span></label>
        <div class="select-wrap">
            <select class="input" id="kategori" name="kategori" required>
                <?php foreach (KATEGORI as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $d['kategori'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= icon('chev-d', 16) ?>
        </div>
    </div>

    <div class="field">
        <label>Ukuran Kasur (cm) <span class="req">*</span></label>
        <div class="grid-3">
            <div class="input-group"><input type="number" min="1" id="panjang" name="panjang" value="<?= (int)$d['panjang'] ?>" aria-label="Panjang kasur"><span class="affix affix--plain">P</span></div>
            <div class="input-group"><input type="number" min="1" id="lebar" name="lebar" value="<?= (int)$d['lebar'] ?>" aria-label="Lebar kasur"><span class="affix affix--plain">L</span></div>
            <div class="input-group"><input type="number" min="1" id="tinggi" name="tinggi" value="<?= (int)$d['tinggi'] ?>" aria-label="Tinggi karet"><span class="affix affix--plain">T (Karet)</span></div>
        </div>
    </div>

    <div class="grid-2 field">
        <div>
            <label for="harga">Harga Dasar Retail (Rp) <span class="req">*</span></label>
            <div class="input-group"><span class="affix affix--plain affix--left">Rp</span><input type="text" inputmode="numeric" id="harga" name="harga" data-rupiah value="<?= e(angka((int)$d['harga'])) ?>" placeholder="185.000"></div>
        </div>
        <div>
            <label for="stok">Stok</label>
            <div class="input-group"><input type="number" min="0" id="stok" name="stok" value="<?= (int)$d['stok'] ?>"><span class="affix affix--plain">Set</span></div>
        </div>
    </div>

    <div class="field">
        <label>Foto Produk</label>
        <div class="photo-box photo-box--simple" data-photo>
            <span class="photo-thumb" data-photo-thumb><?= icon('image', 22) ?></span>
            <div class="photo-info">
                <strong data-photo-name>Belum ada foto dipilih</strong>
                <small>Format JPG, PNG, atau WebP</small>
            </div>
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
            <button type="button" class="btn btn-light" data-photo-pick><?= icon('camera', 16) ?> Ubah Foto</button>
        </div>
    </div>

    <div class="field">
        <label for="deskripsi">Deskripsi Singkat (Opsional)</label>
        <textarea class="input" id="deskripsi" name="deskripsi" rows="3" maxlength="500"><?= e($d['deskripsi']) ?></textarea>
    </div>

    <div class="form-actions">
        <a href="Katalog.php" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary btn-lg"><?= icon('check', 18) ?> Simpan Perubahan</button>
    </div>
</form>
<?php require __DIR__ . '/partials/footer.php'; ?>