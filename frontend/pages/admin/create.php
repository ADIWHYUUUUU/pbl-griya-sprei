
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /frontend/pages/login.php');
    exit;
}

if (empty($_SESSION['csrf_produk'])) {
    $_SESSION['csrf_produk'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_produk'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Tambah Produk - Griya Sprei</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            background: #f5f5f2;
            font-family: Arial, sans-serif;
            color: #252525;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 32px;
            border-radius: 14px;
            border: 1px solid #e5e5e5;
        }

        h1 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font: inherit;
        }

        button {
            padding: 13px 20px;
            border: none;
            border-radius: 8px;
            background: #506b50;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #3d563d;
        }

        #preview {
            display: none;
            width: 200px;
            height: 200px;
            object-fit: cover;
            margin-top: 15px;
            border-radius: 10px;
        }

        .hint {
            font-size: 13px;
            color: #666;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Tambah Produk</h1>

    <p>Masukkan data dan gambar produk Griya Sprei.</p>

    <form
        action="/backend/actions/produk/simpan.php"
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
        >

        <div class="form-group">

            <label>Nama Produk</label>

            <input
                type="text"
                name="nama_produk"
                placeholder="Contoh: Sprei Katun Jepang"
                maxlength="150"
                required
            >

        </div>

        <div class="form-group">

            <label>Harga Produk</label>

            <input
                type="number"
                name="harga"
                placeholder="Contoh: 150000"
                min="0"
                step="0.01"
                required
            >

        </div>

        <div class="form-group">

            <label>Gambar Produk</label>

            <input
                type="file"
                name="gambar"
                id="gambar"
                accept="image/jpeg,image/png,image/webp"
                required
            >

            <p class="hint">
                Format JPG, PNG, atau WEBP. Maksimal 5 MB.
            </p>

            <img id="preview" alt="Preview gambar produk">

        </div>

        <button type="submit">
            Simpan Produk
        </button>

    </form>

</div>

<script>
const inputGambar = document.getElementById('gambar');
const preview = document.getElementById('preview');

let previewUrl = null;

inputGambar.addEventListener('change', function () {

    const file = this.files[0];

    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }

    if (!file) {
        preview.style.display = 'none';
        preview.removeAttribute('src');
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Pilih gambar JPG, PNG, atau WEBP.');
        this.value = '';
        preview.style.display = 'none';
        return;
    }

    previewUrl = URL.createObjectURL(file);
    preview.src = previewUrl;
    preview.style.display = 'block';

});
</script>

</body>
</html>
