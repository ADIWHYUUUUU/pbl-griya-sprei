
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';

class ProductController
{
    private $model;

    public function __construct()
    {
        global $pdo;

        $this->model = new ProductModel($pdo);
    }

    // =========================================
    // PROTEKSI ADMIN DAN CSRF
    // =========================================
    private function authorizeAdminPost()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            ($_SESSION['login'] ?? false) !== true ||
            ($_SESSION['role'] ?? '') !== 'ADMIN'
        ) {
            http_response_code(403);
            exit('Akses ditolak.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method tidak diizinkan.');
        }

        $token = $_POST['csrf_token'] ?? '';

        if (
            !is_string($token) ||
            !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            http_response_code(403);
            exit('Token CSRF tidak valid.');
        }
    }

    // =========================================
    // READ - SEMUA PRODUK
    // =========================================
    public function getAllProducts()
    {
        return $this->model->getAllProducts();
    }

    // =========================================
    // READ - PRODUK BERDASARKAN ID
    // =========================================
    public function getProductById($id)
    {
        return $this->model->getProductById($id);
    }

    // =========================================
    // CREATE - TAMPILKAN FORM
    // =========================================
    public function create()
    {
        require_once __DIR__ . '/../../../frontend/pages/admin/produk/tambah.php';
    }

    // =========================================
    // CREATE - SIMPAN PRODUK
    // =========================================
    public function store()
    {
        $this->authorizeAdminPost();

        // Ambil input
        $nama = trim($_POST['nama_produk'] ?? '');
        $kategori = trim($_POST['kategori_produk'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        $panjang = filter_var(
            $_POST['panjang_standar'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $lebar = filter_var(
            $_POST['lebar_standar'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $tinggi = filter_var(
            $_POST['tinggi_standar'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $harga = filter_var(
            $_POST['harga_dasar'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $stok = filter_var(
            $_POST['stok'] ?? null,
            FILTER_VALIDATE_INT
        );

        // Validasi data
        if (
            $nama === '' ||
            strlen($nama) > 150 ||
            $kategori === '' ||
            $deskripsi === '' ||
            $panjang === false || $panjang <= 0 ||
            $lebar === false || $lebar <= 0 ||
            $tinggi === false || $tinggi <= 0 ||
            $harga === false || $harga < 0 ||
            $stok === false || $stok < 0
        ) {
            http_response_code(400);
            exit('Data produk tidak valid. Periksa kembali form.');
        }

        // Validasi upload gambar
        if (
            !isset($_FILES['gambar_produk']) ||
            $_FILES['gambar_produk']['error'] !== UPLOAD_ERR_OK
        ) {
            http_response_code(400);
            exit('Gambar produk wajib diunggah.');
        }

        $file = $_FILES['gambar_produk'];

        if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            exit('Ukuran gambar maksimal 2 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedMime[$mime])) {
            http_response_code(400);
            exit('Format gambar tidak didukung.');
        }

        // Folder penyimpanan gambar
        $uploadDir = dirname(__DIR__, 3)
            . '/frontend/uploads/produk/';

        if (
            !is_dir($uploadDir) &&
            !mkdir($uploadDir, 0755, true)
        ) {
            http_response_code(500);
            exit('Folder upload gagal dibuat.');
        }

        // Buat nama gambar acak
        $gambarName = bin2hex(random_bytes(16))
            . '.'
            . $allowedMime[$mime];

        $destination = $uploadDir . $gambarName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            exit('Gambar gagal diunggah.');
        }

        // Data untuk PostgreSQL
        $data = [
            'nama_produk'     => $nama,
            'kategori_produk' => $kategori,
            'panjang_standar' => $panjang,
            'lebar_standar'   => $lebar,
            'tinggi_standar'  => $tinggi,
            'harga_dasar'     => $harga,
            'stok'            => $stok,
            'deskripsi'       => $deskripsi,
            'gambar_produk'   => $gambarName
        ];

        try {
            $saved = $this->model->createProduct($data);

            if (!$saved) {
                throw new RuntimeException('Gagal menyimpan produk.');
            }
        } catch (Throwable $e) {
            // Jika database gagal, hapus gambar yang baru diupload
            if (is_file($destination)) {
                unlink($destination);
            }

            error_log($e->getMessage());

            http_response_code(500);
            exit('Produk gagal disimpan.');
        }

        header(
            'Location: /../../../frontend/pages/admin/produk/index.php?success=created'
        );
        exit;
    }

    // =========================================
    // UPDATE - TAMPILKAN FORM EDIT
    // =========================================
    public function edit($id)
    {
        $product = $this->model->getProductById($id);

        if (!$product) {
            http_response_code(404);
            exit('Produk tidak ditemukan.');
        }

        require_once __DIR__ . '/../../../frontend/pages/admin/produk/edit.php';
    }

    // =========================================
    // UPDATE - PROSES EDIT
    // =========================================
    public function update($id)
    {
        $this->authorizeAdminPost();

        $id = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($id === false) {
            http_response_code(400);
            exit('ID produk tidak valid.');
        }

        $nama = trim($_POST['nama_produk'] ?? '');
        $kategori = trim($_POST['kategori_produk'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        $panjang = filter_var($_POST['panjang_standar'] ?? null, FILTER_VALIDATE_FLOAT);
        $lebar = filter_var($_POST['lebar_standar'] ?? null, FILTER_VALIDATE_FLOAT);
        $tinggi = filter_var($_POST['tinggi_standar'] ?? null, FILTER_VALIDATE_FLOAT);
        $harga = filter_var($_POST['harga_dasar'] ?? null, FILTER_VALIDATE_FLOAT);
        $stok = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT);

        if (
            $nama === '' || strlen($nama) > 150 ||
            $kategori === '' || $deskripsi === '' ||
            $panjang === false || $panjang <= 0 ||
            $lebar === false || $lebar <= 0 ||
            $tinggi === false || $tinggi <= 0 ||
            $harga === false || $harga < 0 ||
            $stok === false || $stok < 0
        ) {
            http_response_code(400);
            exit('Data produk tidak valid.');
        }

        if (!$this->model->getProductById($id)) {
            http_response_code(404);
            exit('Produk tidak ditemukan.');
        }

        $data = [
            'nama_produk'     => $nama,
            'kategori_produk' => $kategori,
            'panjang_standar' => $panjang,
            'lebar_standar'   => $lebar,
            'tinggi_standar'  => $tinggi,
            'harga_dasar'     => $harga,
            'stok'            => $stok,
            'deskripsi'       => $deskripsi
        ];

        try {
            $saved = $this->model->updateProduct($id, $data);

            if (!$saved) {
                throw new RuntimeException('Update gagal.');
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());

            http_response_code(500);
            exit('Produk gagal diperbarui.');
        }

        header(
            'Location: /frontend/pages/admin/produk/index.php?success=updated'
        );
        exit;
    }

    // =========================================
    // DELETE - HAPUS PRODUK
    // =========================================
    public function delete($id)
    {
        $this->authorizeAdminPost();

        $id = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($id === false) {
            http_response_code(400);
            exit('ID produk tidak valid.');
        }

        $product = $this->model->getProductById($id);

        if (!$product) {
            http_response_code(404);
            exit('Produk tidak ditemukan.');
        }

        

        try {
            $deleted = $this->model->deleteProduct($id);

            if (!$deleted) {
                throw new RuntimeException('Delete gagal.');
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());

            http_response_code(500);
            exit('Produk gagal dihapus.');
        }

        header(
            'Location: /../../../frontend/pages/admin/produk/index.php?success=deleted'
        );
        exit;
    }
}
