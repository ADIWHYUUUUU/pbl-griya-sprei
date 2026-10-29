<?php
$halamanAktif = 'produk';

// Data contoh — ganti dengan query database (mis. SELECT ... ORDER BY id DESC)
$produk = [
    ['id' => 13, 'gambar' => 'assets/img/produk/beverly-beckham.jpg', 'nama' => 'Beverly - Beckham (Light Grey)', 'kategori' => 'SPREI', 'ukuran' => '200 x 200 x 200 cm', 'harga' => 200000,  'stok' => 10],
    ['id' => 12, 'gambar' => 'assets/img/produk/beverly-roland.jpg',   'nama' => 'Beverly - Roland (Grey & Navy)', 'kategori' => 'SPREI', 'ukuran' => '200 x 200 x 200 cm', 'harga' => 200000,  'stok' => 10],
    ['id' => 11, 'gambar' => 'assets/img/produk/asterina-burgundy.jpg','nama' => 'Asterina - Burgundy',            'kategori' => 'SPREI', 'ukuran' => '200 x 200 x 200 cm', 'harga' => 200000,  'stok' => 10],
    ['id' => 10, 'gambar' => 'assets/img/produk/asterina-sage.jpg',    'nama' => 'Asterina - Sage',                'kategori' => 'SPREI', 'ukuran' => '160 x 200 x 30 cm',  'harga' => 1000000, 'stok' => 9],
];

// Pagination
$perHalaman   = 4;
$totalHalaman = max(1, (int) ceil(count($produk) / $perHalaman));
$halaman      = isset($_GET['page']) ? max(1, min($totalHalaman, (int) $_GET['page'])) : 1;
$tampil       = array_slice($produk, ($halaman - 1) * $perHalaman, $perHalaman);

function rupiah(int $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Produk - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <span class="crumb">Admin</span>
            <span class="crumb-sep">&gt;</span>
            <span class="crumb crumb-now">Katalog Produk</span>
        </header>

        <main class="content">
            <div class="page-head">
                <h1>Katalog Produk</h1>
                <a href="tambah-produk.php" class="btn btn-primary">+ Tambah Produk</a>
            </div>

            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="kolom-id">ID</th>
                            <th class="kolom-gambar">Gambar</th>
                            <th class="kolom-nama">Nama Produk</th>
                            <th class="kolom-kategori">Kategori</th>
                            <th class="kolom-ukuran">Ukuran Standar</th>
                            <th class="kolom-harga">Harga Dasar</th>
                            <th class="kolom-stok">Stok</th>
                            <th class="kolom-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tampil as $p): ?>
                            <tr>
                                <td data-label="ID"><?= (int) $p['id'] ?></td>
                                <td data-label="Gambar">
                                    <span class="thumb">
                                        <img src="<?= htmlspecialchars($p['gambar']) ?>"
                                             alt="<?= htmlspecialchars($p['nama']) ?>"
                                             onerror="this.style.display='none'">
                                    </span>
                                </td>
                                <td data-label="Nama Produk"><?= htmlspecialchars($p['nama']) ?></td>
                                <td data-label="Kategori"><?= htmlspecialchars($p['kategori']) ?></td>
                                <td data-label="Ukuran"><?= htmlspecialchars($p['ukuran']) ?></td>
                                <td data-label="Harga Dasar"><?= rupiah($p['harga']) ?></td>
                                <td data-label="Stok"><?= (int) $p['stok'] ?></td>
                                <td data-label="Aksi">
                                    <div class="aksi">
                                        <a href="edit-produk.php?id=<?= (int) $p['id'] ?>" class="btn btn-edit">Edit</a>
                                        <a href="hapus-produk.php?id=<?= (int) $p['id'] ?>"
                                           class="btn btn-hapus"
                                           data-nama="<?= htmlspecialchars($p['nama']) ?>">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="pagination" aria-label="Halaman">
                <?php if ($halaman > 1): ?>
                    <a href="?page=<?= $halaman - 1 ?>" class="page" aria-label="Sebelumnya">&lsaquo;</a>
                <?php else: ?>
                    <span class="page is-disabled" aria-hidden="true">&lsaquo;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                    <a href="?page=<?= $i ?>" class="page<?= $i === $halaman ? ' is-active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($halaman < $totalHalaman): ?>
                    <a href="?page=<?= $halaman + 1 ?>" class="page" aria-label="Berikutnya">&rsaquo;</a>
                <?php else: ?>
                    <span class="page is-disabled" aria-hidden="true">&rsaquo;</span>
                <?php endif; ?>
            </nav>
        </main>
    </div>
</div>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>
