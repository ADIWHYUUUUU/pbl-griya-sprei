<?php
require __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p  = find_produk($id);
if (!$p) {
    flash('error', 'Produk tidak ditemukan.');
    redirect('Katalog.php');
}

$errors = [];
$d = $p;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = input_produk($_POST) + ['id' => $id, 'foto' => $p['foto']];
    $errors = validate_produk($d);
    $foto = handle_upload($_FILES['foto'] ?? null, $upErr);
    if ($upErr) $errors[] = $upErr;

    if (!$errors) {
        $list = load_produk();
        foreach ($list as &$row) {
            if ((int)$row['id'] !== $id) continue;
            if ($foto) {
                hapus_foto($row['foto']);
                $row['foto'] = $foto;
            }
            $row = array_merge($row, array_diff_key($d, ['id' => 1, 'foto' => 1]));
        }
        unset($row);
        save_produk($list);
        flash('success', "Perubahan “{$d['nama']}” berhasil disimpan.");
        redirect('Katalog.php');
    }
    if (!empty($foto)) hapus_foto($foto);
}

$img  = foto_url($p['foto']);
$meta = foto_meta($p['foto']);
$presets = [
    ['Single', 120, 200],
    ['Queen', 160, 200],
    ['King', 180, 200],
    ['Super King', 200, 200],
];

$pageTitle = 'Edit Produk';
$crumbs = [['Admin', null], ['Katalog Produk', 'Katalog.php'], ['Edit Produk', null]];
require __DIR__ . '/partials/header.php';
?>
<a href="Katalog.php" class="btn btn-light btn-back"><?= icon('arrow-l', 16) ?> Kembali ke Katalog</a>

<form class="card form-card" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="card-head">
        <h1>Edit Katalog Produk</h1>
    </div>

    <div class="form-body">
        <?php if ($errors): ?>
            <div class="alert alert--error" role="alert">
                <ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <section class="sec">
            <h2 class="sec-title"><?= icon('info', 18) ?> Informasi Utama Produk</h2>
            <div class="field">
                <label for="nama">Nama Produk / Sprei <span class="req">*</span></label>
                <input class="input input--soft" type="text" id="nama" name="nama" value="<?= e($d['nama']) ?>" required>
            </div>
            <div class="field">
                <label for="kategori">Kategori Produk <span class="req">*</span></label>
                <div class="select-wrap">
                    <select class="input input--soft" id="kategori" name="kategori" required>
                        <?php foreach (KATEGORI as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $d['kategori'] === $val ? 'selected' : '' ?>><?= e($val) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= icon('chev-d', 16) ?>
                </div>
            </div>
        </section>

        <section class="sec">
            <h2 class="sec-title"><?= icon('ruler', 18) ?> Ukuran Standar Kasur (cm)</h2>
            <div class="grid-3">
                <div>
                    <label class="label-sm" for="panjang">Panjang Kasur</label>
                    <div class="input-group input-group--lg"><input type="number" min="1" id="panjang" name="panjang" value="<?= (int)$d['panjang'] ?>"><span class="affix">P<br>(cm)</span></div>
                </div>
                <div>
                    <label class="label-sm" for="lebar">Lebar Kasur</label>
                    <div class="input-group input-group--lg"><input type="number" min="1" id="lebar" name="lebar" value="<?= (int)$d['lebar'] ?>"><span class="affix">L<br>(cm)</span></div>
                </div>
                <div>
                    <label class="label-sm" for="tinggi">Tinggi Karet</label>
                    <div class="input-group input-group--lg"><input type="number" min="1" id="tinggi" name="tinggi" value="<?= (int)$d['tinggi'] ?>"><span class="affix">T<br>(Karet)</span></div>
                </div>
            </div>
            <div class="chips" role="group" aria-label="Ukuran cepat">
                <?php foreach ($presets as [$nm, $l, $pj]): ?>
                    <button type="button" class="chip" data-lebar="<?= $l ?>" data-panjang="<?= $pj ?>"><?= e($nm) ?> (<?= $l ?>×<?= $pj ?>)</button>
                <?php endforeach; ?>
            </div>

            <div class="grid-2 mt">
                <div>
                    <label for="harga">Harga</label>
                    <div class="input-group input-group--lg"><span class="affix affix--left">Rp</span><input type="text" inputmode="numeric" id="harga" name="harga" data-rupiah value="<?= e(angka((int)$d['harga'])) ?>"></div>
                </div>
                <div>
                    <label for="stok">Edit Stock</label>
                    <div class="input-group input-group--lg"><input type="number" min="0" id="stok" name="stok" value="<?= (int)$d['stok'] ?>"><span class="affix affix--sm">Set</span></div>
                </div>
            </div>
        </section>

        <section class="sec">
            <h2 class="sec-title"><?= icon('image', 18) ?> Foto Produk Katalog</h2>
            <div class="photo-box" data-photo>
                <span class="photo-thumb photo-thumb--lg" data-photo-thumb>
                    <?php if ($img): ?><img src="<?= e($img) ?>" alt=""><?php else: ?><?= icon('image', 28) ?><?php endif; ?>
                </span>
                <div class="photo-info">
                    <div class="photo-title">
                        <strong data-photo-name><?= $img ? e($p['foto']) : 'Belum ada foto' ?></strong>
                        <?php if ($img): ?><span class="badge" data-photo-badge>Foto Utama</span><?php endif; ?>
                    </div>
                    <small data-photo-meta>Format: JPG, PNG, atau WebP<?= $meta ? " • {$meta['w']} x {$meta['h']} px ({$meta['mb']} MB)" : '' ?></small>
                    <small class="ok" data-photo-ok <?= ($meta && min($meta['w'], $meta['h']) >= 800) ? '' : 'hidden' ?>><?= icon('check-c', 14) ?> Sesuai standar resolusi marketplace &amp; etalase kasir</small>
                </div>
                <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
                <button type="button" class="btn btn-white" data-photo-pick><?= icon('camera', 16) ?> Ubah Foto</button>
            </div>
        </section>

        <section class="sec">
            <div class="desc-head">
                <label for="deskripsi" class="sec-label"><?= icon('text', 18) ?> Deskripsi Singkat (Opsional)</label>
                <span class="counter" data-counter-for="deskripsi"><?= mb_strlen($d['deskripsi']) ?> / 500 Karakter</span>
            </div>
            <textarea class="input input--soft" id="deskripsi" name="deskripsi" rows="4" maxlength="500"><?= e($d['deskripsi']) ?></textarea>
        </section>
    </div>

    <div class="form-foot">
        <button type="submit" form="formHapus" class="link-danger"><?= icon('trash', 16) ?> Hapus dari Katalog</button>
        <div class="foot-right">
            <a href="Katalog.php" class="btn btn-white">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg"><?= icon('check', 18) ?> Simpan Perubahan</button>
        </div>
    </div>
</form>

<form id="formHapus" method="post" action="Hapus.php" data-confirm="Hapus “<?= e($p['nama']) ?>” dari katalog? Tindakan ini tidak bisa dibatalkan.">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
</form>
<?php require __DIR__ . '/partials/footer.php'; ?>