
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman khusus ADMIN
if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /frontend/pages/login.php');
    exit;
}

// Panggil ProductController
require_once __DIR__ . '/../../../backend/app/controllers/ProductController.php';

$controller = new ProductController();
$products = $controller->getAllProducts();

// Token untuk form penghapusan produk
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manajemen Produk - Griya Sprei</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #252525;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .admin-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        h1 {
            margin: 0;
            font-size: 26px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-add {
            background: #26734d;
            color: white;
        }

        .btn-edit {
            background: #e9a23b;
            color: #222;
        }

        .btn-delete {
            background: #c43d3d;
            color: white;
        }

        .table-wrap {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f0f4f1;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions form {
            margin: 0;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 700px) {
            .topbar { align-items: flex-start; flex-direction: column; }
            .admin-actions { justify-content: flex-start; }
        }
    </style>
</head>

<body>

<main class="container">

    <div class="topbar">
        <div>
            <h1>Manajemen Produk</h1>
            <p>Kelola produk Griya Sprei melalui dashboard Admin.</p>
        </div>

        <div class="admin-actions">
            <a href="/frontend/pages/admin/beranda.php" class="btn btn-edit">Edit Beranda</a>
            <a href="register.php" class="btn btn-add">Tambah Admin</a>
            <a href="tambah.php" class="btn btn-add">+ Tambah Produk</a>
        </div>
    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Ukuran Standar</th>
                    <th>Harga Dasar</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($products)): ?>

                <tr>
                    <td colspan="8" class="empty">
                        Belum ada produk yang ditambahkan.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($products as $product): ?>

                    <tr>

                        <td>
                            <?= (int) $product['id_produk'] ?>
                        </td>

                        <td>
                            <?php if (!empty($product['gambar_produk'])): ?>

                                <img
                                    src="/frontend/uploads/produk/<?= rawurlencode(basename($product['gambar_produk'])) ?>"
                                    class="product-image"
                                    alt="<?= e($product['nama_produk']) ?>"
                                >

                            <?php else: ?>

                                <span>Belum ada gambar</span>

                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($product['nama_produk']) ?>
                        </td>

                        <td>
                            <?= e($product['kategori_produk']) ?>
                        </td>

                        <td>
                            <?= e($product['panjang_standar']) ?>
                            ×
                            <?= e($product['lebar_standar']) ?>
                            ×
                            <?= e($product['tinggi_standar']) ?>
                            cm
                        </td>

                        <td>
                            Rp <?= number_format(
                                (float) $product['harga_dasar'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <?= (int) $product['stok'] ?>
                        </td>

                        <td>
                            <div class="actions">

                                <a
                                    class="btn btn-edit"
                                    href="/frontend/pages/admin/produk/edit.php?id=<?= (int) $product['id_produk'] ?>"
                                >
                                    Edit
                                </a>

                                <form
                                    action="/../../../backend/actions/delete.php"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                >

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int) $product['id_produk'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-delete"
                                    >
                                        Hapus
                                    </button>

                                </form>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>
        </table>

    </div>

</main>

</body>
</html>
