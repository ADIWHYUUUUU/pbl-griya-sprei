<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';

class ProductController {
    private $model;

    public function __construct() {
        global $pdo; 
        $this->model = new ProductModel($pdo);
    }

    
    public function create() {
        require_once __DIR__ . '/../views/produk/tambah.php';
    }

   
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           
            $gambarName = null;
            if (isset($_FILES['gambar_produk']) && $_FILES['gambar_produk']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['gambar_produk']['tmp_name'];
                $fileName = time() . '_' . $_FILES['gambar_produk']['name'];
                $uploadFileDir = __DIR__ . '/../../public/uploads/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $fileName;
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $gambarName = $fileName;
                }
            }

            $data = [
                'nama_produk'     => $_POST['nama_produk'],
                'kategori_produk' => $_POST['kategori_produk'] ?? 'Sprei Standar', // sesuaikan enum
                'panjang_standar' => $_POST['panjang_standar'],
                'lebar_standar'   => $_POST['lebar_standar'],
                'tinggi_standar'  => $_POST['tinggi_standar'],
                'harga_dasar'     => $_POST['harga_dasar'],
                'stok'            => $_POST['stok'],
                'deskripsi'       => $_POST['deskripsi'],
                'gambar_produk'   => $gambarName
            ];

            $this->model->createProduct($data);
            
            
            header("Location: index.php?action=index");
            exit;
        }
    }

    // 1. Menampilkan halaman form edit beserta data lama produk
    public function edit($id) {
        // Ambil data produk berdasarkan id untuk ditampilkan di form
        $product = $this->model->getProductById($id); // (Pastikan nanti buat fungsi ini di Model jika belum ada)
        
        // Panggil view edit
        require_once __DIR__ . '/../views/produk/edit.php';
    }

    // 2. Memproses data perubahan dari form edit
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Ambil data yang dikirim dari form
            $data = [
                'nama_produk'     => $_POST['nama_produk'],
                'kategori_produk' => $_POST['kategori_produk'],
                'panjang_standar' => $_POST['panjang_standar'],
                'lebar_standar'   => $_POST['lebar_standar'],
                'tinggi_standar'  => $_POST['tinggi_standar'],
                'harga_dasar'     => $_POST['harga_dasar'],
                'stok'            => $_POST['stok'],
                'deskripsi'       => $_POST['deskripsi']
            ];

            // Panggil fungsi update dari Model yang baru saja kamu buat
            $this->model->updateProduct($id, $data);

            // Redirect kembali ke halaman daftar produk setelah berhasil update
            header("Location: index.php?action=index");
            exit;
        }
    }
}