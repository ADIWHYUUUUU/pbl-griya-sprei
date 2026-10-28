<?php
require __DIR__ . '/config.php';

$all   = array_reverse(load_produk());            // ID terbaru di atas
$total = count($all);
$pages = max(1, (int)ceil($total / PER_PAGE));
$page  = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$rows  = array_slice($all, ($page - 1) * PER_PAGE, PER_PAGE);

$pageTitle = 'Katalog Produk';
$activeNav = 'products';
$crumbs = [['Admin', null], ['Katalog Produk', null]];
require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <h1>Katalog Produk</h1>
    <a href="Tambah.php" class="btn btn-primary"><?= icon('plus', 18) ?> Tambah Produk</a>
</div>

<div class="card table-card">
    <div class="table-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th><th>Gambar</th><th>Nama Produk</th><th>Kategori</th>
                    <th>Ukuran Standar</th><th>Harga Dasar</th><th>Stok</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="empty">Belum ada produk. Klik “Tambah Produk” untuk mulai mengisi katalog.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $p): $img = foto_url($p['foto']); ?>
                <tr>
                    <td><?= (int)$p['id'] ?></td>
                    <td>
                        <?php if ($img): ?>
                            <img class="thumb" src="<?= e($img) ?>" alt="<?= e($p['nama']) ?>">
                        <?php else: ?>
                            <span class="thumb thumb--empty"><?= icon('image', 24) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="td-name"><?= e($p['nama']) ?></td>
                    <td><?= e($p['kategori']) ?></td>
                    <td class="nowrap"><?= e(ukuran_text($p)) ?></td>
                    <td class="nowrap"><?= e(rupiah((int)$p['harga'])) ?></td>
                    <td><?= (int)$p['stok'] ?></td>
                    <td>
                        <div class="actions">
                            <a href="Edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <form method="post" action="Hapus.php" data-confirm="Hapus “<?= e($p['nama']) ?>” dari katalog? Tindakan ini tidak bisa dibatalkan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Halaman">
    <a class="pg<?= $page <= 1 ? ' is-disabled' : '' ?>" href="?page=<?= max(1, $page - 1) ?>" aria-label="Sebelumnya"><?= icon('chev-l', 16) ?></a>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a class="pg<?= $i === $page ? ' is-active' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a class="pg<?= $page >= $pages ? ' is-disabled' : '' ?>" href="?page=<?= min($pages, $page + 1) ?>" aria-label="Berikutnya"><?= icon('chev-r', 16) ?></a>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>