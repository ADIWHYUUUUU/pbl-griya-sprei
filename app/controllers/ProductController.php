<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../models/ProductModel.php';

class ProductController
{
    private $model;

    public function __construct()
    {
        global $pdo;

        $this->model = new ProductModel($pdo);
    }

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

    private function normalizeProductCategory($category)
    {
        if ($category === 'BEDCOVER') {
            return 'BED_COVER';
        }
        if (in_array($category, ['SARUNG_BANTAL', 'SARUNG_GULING'], true)) {
            return 'SARUNG_BANTAL_GULING';
        }
        return $category;
    }


    public function getAllProducts()
    {
        return $this->model->getAllProducts();
    }

    public function getFeaturedProducts()
    {
        return $this->model->getFeaturedProducts();
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
        $kategori = $this->normalizeProductCategory(
            trim($_POST['kategori_produk'] ?? '')
        );
        $jenisKain = trim($_POST['jenis_kain'] ?? '');
        $motifInput = $_POST['motif'] ?? '';
        if (!is_string($motifInput)) {
            http_response_code(400);
            exit('Motif produk tidak valid.');
        }
        $motif = trim($motifInput);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        $panjang = filter_var(
            $_POST['panjang_standar'] ?? null,
            FILTER_VALIDATE_INT
        );

        $lebar = filter_var(
            $_POST['lebar_standar'] ?? null,
            FILTER_VALIDATE_INT
        );

        $tinggi = filter_var(
            $_POST['tinggi_standar'] ?? null,
            FILTER_VALIDATE_INT
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
            !in_array($kategori, ['SPREI', 'BED_COVER', 'SARUNG_BANTAL_GULING'], true) ||
            !in_array($jenisKain, ['TENCEL', 'KATUN_JEPANG', 'KATUN_LOKAL', 'MICROTENCEL', 'MICROTEX'], true) ||
            $panjang === false || $panjang <= 0 ||
            $lebar === false || $lebar <= 0 ||
            $tinggi === false || $tinggi <= 0 ||
            $harga === false || !is_finite($harga) || $harga <= 0 ||
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

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
        ];

        if (@getimagesize($file['tmp_name']) === false) {
            http_response_code(400);
            exit('File bukan gambar yang valid.');
        }

        try {
            $upload = save_upload(
                $file,
                SupabaseStorageService::productBucket(),
                2 * 1024 * 1024,
                $allowedMime
            );
        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            exit($error->getMessage());
        } catch (Throwable $error) {
            error_log('[CREATE PRODUCT IMAGE UPLOAD] ' . $error->getMessage());
            http_response_code(502);
            exit(
                'Gambar gagal diunggah ke Supabase Storage. Detail: '
                . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }
        $gambarName = $upload['path'];

        // Data untuk PostgreSQL
        $data = [
            'nama_produk'     => $nama,
            'kategori_produk' => $kategori,
            'jenis_kain'      => $jenisKain,
            'motif'           => $motif,
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

            try {
                delete_uploaded_file($gambarName, 'produk');
            } catch (Throwable $cleanupError) {
                error_log('[CREATE PRODUCT IMAGE CLEANUP] ' . $cleanupError->getMessage());
            }

            error_log('[CREATE PRODUCT] ' . $e->getMessage());

            http_response_code(500);
            exit('Produk gagal disimpan.');
        }

        header('Location: /?page=admin-products&success=created', true, 303);
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

        require_once __DIR__ . '/../views/admin/edit.php';
    }

    // =========================================
    // UPDATE - PROSES EDIT PRODUK + GAMBAR
    // =========================================
    public function update($id)
    {
        $this->authorizeAdminPost();

        // Validasi ID
        $id = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($id === false || $id === null) {
            http_response_code(400);
            exit('ID produk tidak valid.');
        }

        // Ambil data produk lama
        $product = $this->model->getProductById($id);

        if (!$product) {
            http_response_code(404);
            exit('Produk tidak ditemukan.');
        }

        // =====================================
        // AMBIL INPUT FORM
        // =====================================

        $nama = trim($_POST['nama_produk'] ?? '');
        $kategori = $this->normalizeProductCategory(
            trim($_POST['kategori_produk'] ?? '')
        );
        $jenisKain = trim($_POST['jenis_kain'] ?? '');
        $motifInput = $_POST['motif'] ?? '';
        if (!is_string($motifInput)) {
            http_response_code(400);
            exit('Motif produk tidak valid.');
        }
        $motif = trim($motifInput);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        $panjang = filter_var(
            $_POST['panjang_standar'] ?? null,
            FILTER_VALIDATE_INT
        );

        $lebar = filter_var(
            $_POST['lebar_standar'] ?? null,
            FILTER_VALIDATE_INT
        );

        $tinggi = filter_var(
            $_POST['tinggi_standar'] ?? null,
            FILTER_VALIDATE_INT
        );

        $harga = filter_var(
            $_POST['harga_dasar'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $stok = filter_var(
            $_POST['stok'] ?? null,
            FILTER_VALIDATE_INT
        );

        // =====================================
        // VALIDASI INPUT
        // =====================================

        if (
            $nama === '' ||
            strlen($nama) > 150 ||
            !in_array($kategori, ['SPREI', 'BED_COVER', 'SARUNG_BANTAL_GULING'], true) ||
            !in_array($jenisKain, ['TENCEL', 'KATUN_JEPANG', 'KATUN_LOKAL', 'MICROTENCEL', 'MICROTEX'], true) ||
            $panjang === false || $panjang <= 0 ||
            $lebar === false || $lebar <= 0 ||
            $tinggi === false || $tinggi <= 0 ||
            $harga === false || !is_finite($harga) || $harga <= 0 ||
            $stok === false || $stok < 0
        ) {
            http_response_code(400);
            exit('Data produk tidak valid. Periksa kembali form.');
        }

        // =====================================
        // PROSES GAMBAR PRODUK
        // =====================================

        // Secara default, gunakan gambar lama
        $gambarName = $product['gambar_produk'];

        $newUploadPath = null;

        // Jika admin mengunggah gambar baru
        if (isset($_FILES['gambar_produk'])) {

            $file = $_FILES['gambar_produk'];

            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    exit('Gambar gagal diunggah.');
                }
                if ($file['size'] > 2 * 1024 * 1024) {
                    http_response_code(400);
                    exit('Ukuran gambar maksimal 2 MB.');
                }

                $allowedMime = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                ];

                if (@getimagesize($file['tmp_name']) === false) {
                    http_response_code(400);
                    exit('File bukan gambar yang valid.');
                }

                try {
                    $upload = save_upload(
                        $file,
                        SupabaseStorageService::productBucket(),
                        2 * 1024 * 1024,
                        $allowedMime
                    );
                } catch (InvalidArgumentException $error) {
                    http_response_code(400);
                    exit($error->getMessage());
                } catch (Throwable $error) {
                    error_log('[UPDATE PRODUCT IMAGE UPLOAD] ' . $error->getMessage());
                    http_response_code(502);
                    exit(
                        'Gambar gagal diunggah ke Supabase Storage. Detail: '
                        . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    );
                }

                $gambarName = $upload['path'];
                $newUploadPath = $upload['path'];
            }
        }

        // =====================================
        // DATA UNTUK MODEL
        // =====================================

        $data = [
            'nama_produk'     => $nama,
            'kategori_produk' => $kategori,
            'jenis_kain'      => $jenisKain,
            'motif'           => $motif,
            'panjang_standar' => $panjang,
            'lebar_standar'   => $lebar,
            'tinggi_standar'  => $tinggi,
            'harga_dasar'     => $harga,
            'stok'            => $stok,
            'deskripsi'       => $deskripsi,

            // Wajib dikirim agar tidak terjadi
            // Undefined array key "gambar_produk"
            'gambar_produk'   => $gambarName
        ];

        // =====================================
        // UPDATE DATABASE
        // =====================================

        try {

            $saved = $this->model->updateProduct($id, $data);

            if (!$saved) {
                throw new RuntimeException('Update produk gagal.');
            }
        } catch (Throwable $e) {

            // Hapus gambar baru jika database gagal diperbarui
            if ($newUploadPath !== null) {
                try {
                    delete_uploaded_file($newUploadPath, 'produk');
                } catch (Throwable $cleanupError) {
                    error_log('[UPDATE PRODUCT IMAGE CLEANUP] ' . $cleanupError->getMessage());
                }
            }

            error_log('[UPDATE PRODUCT] ' . $e->getMessage());

            http_response_code(500);
            exit('Produk gagal diperbarui. Periksa terminal PHP.');
        }

        // Gambar lama tidak dihapus otomatis.

        // =====================================
        // REDIRECT SETELAH BERHASIL
        // =====================================

        header('Location: /?page=admin-products&success=updated', true, 303);
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

        if ($id === false || $id === null) {
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

            error_log('[DELETE PRODUCT] ' . $e->getMessage());

            http_response_code(500);
            exit('Produk gagal dihapus.');
        }

        try {
            delete_uploaded_file((string) ($product['gambar_produk'] ?? ''), 'produk');
        } catch (Throwable $cleanupError) {
            error_log('[DELETE PRODUCT IMAGE CLEANUP] ' . $cleanupError->getMessage());
        }

        header('Location: /?page=admin-products&success=deleted', true, 303);
        exit;
    }
}
