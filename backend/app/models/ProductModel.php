<?php
class ProductModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    
    public function getAllProducts() {
        $stmt = $this->pdo->query("SELECT * FROM produk ORDER BY id_produk DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function createProduct($data) {
        $sql = "INSERT INTO produk (nama_produk, kategori_produk, panjang_standar, lebar_standar, tinggi_standar, harga_dasar, stok, deskripsi, gambar_produk) 
                VALUES (:nama_produk, :kategori_produk, :panjang_standar, :lebar_standar, :tinggi_standar, :harga_dasar, :stok, :deskripsi, :gambar_produk)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nama_produk'     => $data['nama_produk'],
            ':kategori_produk' => $data['kategori_produk'],
            ':panjang_standar' => $data['panjang_standar'],
            ':lebar_standar'   => $data['lebar_standar'],
            ':tinggi_standar'  => $data['tinggi_standar'],
            ':harga_dasar'     => $data['harga_dasar'],
            ':stok'            => $data['stok'],
            ':deskripsi'       => $data['deskripsi'],
            ':gambar_produk'   => $data['gambar_produk']
        ]);
    }


    public function updateProduct($id, $data) {
    $sql = "UPDATE produk SET 
            nama_produk = :nama_produk, 
            kategori_produk = :kategori_produk, 
            panjang_standar = :panjang_standar, 
            lebar_standar = :lebar_standar, 
            tinggi_standar = :tinggi_standar, 
            harga_dasar = :harga_dasar, 
            stok = :stok, 
            deskripsi = :deskripsi 
            WHERE id_produk = :id_produk"; // <-- id_produk dipakai di sini!
    
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
        ':id_produk'       => $id,
        ':nama_produk'     => $data['nama_produk'],
        ':kategori_produk' => $data['kategori_produk'],
        ':panjang_standar' => $data['panjang_standar'],
        ':lebar_standar'   => $data['lebar_standar'],
        ':tinggi_standar'  => $data['tinggi_standar'],
        ':harga_dasar'     => $data['harga_dasar'],
        ':stok'            => $data['stok'],
        ':deskripsi'       => $data['deskripsi']
    ]);


    



}
public function getProductById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM produk WHERE id_produk = :id_produk");
        $stmt->execute([':id_produk' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}




