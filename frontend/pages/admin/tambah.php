
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
    <title>Tambah Produk - Griya Sprei</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #252525;
        }

        .container {
            max-width: 760px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        h1 { margin-top: 0; }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .sizes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            padding: 12px 18px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-save {
            background: #26734d;
            color: white;
        }

        .btn-back {
            background: #e8e8e8;
            color: #252525;
        }

        .hint {
            font-size: 13px;
            color: #777;
        }

        @media (max-width: 540px) {
            .sizes { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<main class="container">

    <div class="form-card">

        <h1>Tambah Produk</h1>
        <p>Masukkan informasi produk baru Griya Sprei.</p>

        <form
            action="/../../../backend/actions/store.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <div class="form-group">
                <label for="nama_produk">Nama Produk</label>
                <input
                    type="text"
                    id="nama_produk"
                    name="nama_produk"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="kategori_produk">Kategori Produk</label>

                <select
                    id="kategori_produk"
                    name="kategori_produk"
                    required
                >
                    <option value="">-- Pilih Kategori --</option>
                    <option value="SPREI">Sprei</option>
                    <option value="BEDCOVER">Bedcover</option>
                </select>
            </div>

            <div class="form-group">
                <label>Ukuran Standar (cm)</label>

                <div class="sizes">

                    <div>
                        <label for="panjang_standar">Panjang</label>
                        <input
                            type="number"
                            id="panjang_standar"
                            name="panjang_standar"
                            min="1"
                            step="0.01"
                            required
                        >
                    </div>

                    <div>
                        <label for="lebar_standar">Lebar</label>
                        <input
                            type="number"
                            id="lebar_standar"
                            name="lebar_standar"
                            min="1"
                            step="0.01"
                            required
                        >
                    </div>

                    <div>
                        <label for="tinggi_standar">Tinggi</label>
                        <input
                            type="number"
                            id="tinggi_standar"
                            name="tinggi_standar"
                            min="1"
                            step="0.01"
                            required
                        >
                    </div>

                </div>
            </div>

            <div class="form-group">
                <label for="harga_dasar">Harga Dasar (Rp)</label>
                <input
                    type="number"
                    id="harga_dasar"
                    name="harga_dasar"
                    min="0"
                    step="1"
                    required
                >
            </div>

            <div class="form-group">
                <label for="stok">Stok</label>
                <input
                    type="number"
                    id="stok"
                    name="stok"
                    min="0"
                    step="1"
                    required
                >
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi Produk</label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label for="gambar_produk">Gambar Produk</label>

                <input
                    type="file"
                    id="gambar_produk"
                    name="gambar_produk"
                    accept=".jpg,.jpeg,.png,.webp"
                    required
                >

                <p class="hint">
                    Format JPG, PNG, atau WEBP. Maksimal 2 MB.
                </p>
            </div>

            <div class="actions">

                <button type="submit" class="btn btn-save">
                    Simpan Produk
                </button>

                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    Kembali
                </a>

            </div>

        </form>

    </div>

</main>

</body>
</html>
