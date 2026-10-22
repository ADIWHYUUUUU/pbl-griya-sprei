
<?php

class CartController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['keranjang']) ||
            !is_array($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }
    }

    public static function isCustomer(): bool
    {
        return ($_SESSION['login'] ?? false) === true
            && ($_SESSION['role'] ?? '') === 'CUSTOMER';
    }

    public static function validateCsrf(): bool
    {
        $saved = $_SESSION['csrf_token'] ?? '';
        $sent = $_POST['csrf_token'] ?? '';

        return is_string($saved)
            && is_string($sent)
            && $saved !== ''
            && hash_equals($saved, $sent);
    }

    private function getProduct(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id_produk,
                nama_produk,
                kategori_produk,
                gambar_produk,
                panjang_standar,
                lebar_standar,
                tinggi_standar,
                harga_dasar,
                stok
            FROM produk
            WHERE id_produk = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        return $product ?: null;
    }

    private function validInt(
        mixed $value,
        int $min,
        int $max,
        string $label
    ): int {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false || $number < $min || $number > $max) {
            throw new InvalidArgumentException(
                "$label harus antara $min sampai $max."
            );
        }

        return $number;
    }

    private function validDimension(
        mixed $value,
        float $min,
        float $max,
        string $label
    ): float {
        if (!is_scalar($value) || !is_numeric($value)) {
            throw new InvalidArgumentException(
                "$label harus berupa angka."
            );
        }

        $number = (float) $value;

        if (!is_finite($number) ||
            $number < $min ||
            $number > $max) {
            throw new InvalidArgumentException(
                "$label harus antara $min sampai $max cm."
            );
        }

        return round($number, 2);
    }

    private function getReservedQuantity(int $productId): int
    {
        $total = 0;

        foreach ($_SESSION['keranjang'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ((int) ($item['id_produk'] ?? 0) === $productId) {
                $total += (int) ($item['qtySprei'] ?? 0);
            }
        }

        return $total;
    }

    // =====================================
    // TAMBAH PRODUK KE KERANJANG
    // =====================================
    public function add(array $data): string
    {
        $id = $this->validInt(
            $data['id_produk'] ?? null,
            1,
            PHP_INT_MAX,
            'ID produk'
        );

        $product = $this->getProduct($id);

        if (!$product) {
            throw new InvalidArgumentException(
                'Produk tidak ditemukan.'
            );
        }

        $stock = (int) $product['stok'];

        if ($stock <= 0) {
            throw new InvalidArgumentException(
                'Stok produk sedang habis.'
            );
        }

        $mode = strtolower(trim((string) ($data['mode'] ?? 'standard')));

        if (!in_array($mode, ['standard', 'custom'], true)) {
            throw new InvalidArgumentException(
                'Tipe ukuran tidak valid.'
            );
        }

        $qtySprei = $this->validInt(
            $data['qtySprei'] ?? 1,
            1,
            min(50, $stock),
            'Jumlah sprei'
        );

        $qtyBantal = $this->validInt(
            $data['qtyBantal'] ?? 0,
            0,
            20,
            'Jumlah tambahan sarung bantal'
        );

        $qtyGuling = $this->validInt(
            $data['qtyGuling'] ?? 0,
            0,
            20,
            'Jumlah tambahan sarung guling'
        );

        // Produk STANDARD selalu menggunakan ukuran database,
        // bukan mempercayai ukuran yang dikirim browser.
        if ($mode === 'standard') {
            $length = (float) $product['panjang_standar'];
            $width = (float) $product['lebar_standar'];
            $height = (float) $product['tinggi_standar'];
        } else {
            // Rentang mengikuti form detail_produk.php.
            $length = $this->validDimension(
                $data['length'] ?? null,
                100,
                max(300, (float) $product['panjang_standar']),
                'Panjang'
            );

            $width = $this->validDimension(
                $data['width'] ?? null,
                80,
                max(260, (float) $product['lebar_standar']),
                'Lebar'
            );

            $height = $this->validDimension(
                $data['height'] ?? null,
                10,
                max(50, (float) $product['tinggi_standar']),
                'Tinggi'
            );
        }

        $note = trim((string) ($data['catatan'] ?? ''));

        if (mb_strlen($note) > 1000) {
            throw new InvalidArgumentException(
                'Catatan maksimal 1000 karakter.'
            );
        }

        // Total produk yang sudah masuk keranjang juga diperiksa.
        $reserved = $this->getReservedQuantity($id);

        if ($reserved + $qtySprei > $stock) {
            throw new InvalidArgumentException(
                'Jumlah pesanan melebihi stok yang tersedia. ' .
                'Produk ini sudah ada di keranjang sebanyak ' .
                $reserved . ' set.'
            );
        }

        // Satu konfigurasi = satu item unik.
        $itemId = bin2hex(random_bytes(12));

        $_SESSION['keranjang'][$itemId] = [
            'id_produk' => $id,
            'mode' => $mode,

            'length' => $length,
            'width' => $width,
            'height' => $height,

            'qtySprei' => $qtySprei,
            'qtyBantal' => $qtyBantal,
            'qtyGuling' => $qtyGuling,

            'catatan' => $note
        ];

        return $itemId;
    }

    // =====================================
    // HAPUS SATU KONFIGURASI KERANJANG
    // =====================================
    public function remove(string $itemId): void
    {
        if (!preg_match('/^[a-f0-9]{24}$/D', $itemId)) {
            throw new InvalidArgumentException(
                'ID item keranjang tidak valid.'
            );
        }

        if (!isset($_SESSION['keranjang'][$itemId])) {
            throw new InvalidArgumentException(
                'Item keranjang tidak ditemukan.'
            );
        }

        unset($_SESSION['keranjang'][$itemId]);
    }

    // =====================================
    // UPDATE JUMLAH SPREI
    // =====================================
    public function updateQuantity(
        string $itemId,
        mixed $quantity
    ): void {
        if (!preg_match('/^[a-f0-9]{24}$/D', $itemId) ||
            !isset($_SESSION['keranjang'][$itemId])) {
            throw new InvalidArgumentException(
                'Item keranjang tidak ditemukan.'
            );
        }

        $item = $_SESSION['keranjang'][$itemId];

        $productId = (int) $item['id_produk'];
        $product = $this->getProduct($productId);

        if (!$product) {
            throw new InvalidArgumentException(
                'Produk sudah tidak tersedia.'
            );
        }

        $stock = (int) $product['stok'];

        if ($stock < 1) {
            throw new InvalidArgumentException(
                'Stok produk sedang habis.'
            );
        }

        $newQuantity = $this->validInt(
            $quantity,
            1,
            min(50, $stock),
            'Jumlah sprei'
        );

        // Hitung semua konfigurasi produk yang sama,
        // kecuali item yang sedang diperbarui.
        $reserved = 0;

        foreach ($_SESSION['keranjang'] as $key => $other) {
            if ($key === $itemId || !is_array($other)) {
                continue;
            }

            if ((int) ($other['id_produk'] ?? 0) === $productId) {
                $reserved += (int) ($other['qtySprei'] ?? 0);
            }
        }

        if ($reserved + $newQuantity > $stock) {
            throw new InvalidArgumentException(
                'Jumlah melebihi stok produk yang tersedia.'
            );
        }

        $_SESSION['keranjang'][$itemId]['qtySprei'] = $newQuantity;
    }

    // =====================================
    // AMBIL DATA UNTUK HALAMAN KERANJANG
    // =====================================
    public function getItems(string $search = ''): array
    {
        $rows = [];
        $totalQuantity = 0;

        $search = trim($search);

        foreach ($_SESSION['keranjang'] as $itemId => $item) {
            if (!is_array($item) || empty($item['id_produk'])) {
                continue;
            }

            // Lewati data session dari format keranjang demo lama.
            if (!preg_match('/^[a-f0-9]{24}$/D', (string) $itemId)) {
                continue;
            }

            $product = $this->getProduct(
                (int) $item['id_produk']
            );

            if (!$product) {
                continue;
            }

            $quantity = max(1, (int) ($item['qtySprei'] ?? 1));

            // Ringkasan menghitung seluruh isi keranjang,
            // bukan hanya hasil pencarian.
            $totalQuantity += $quantity;

            if ($search !== '' &&
                mb_stripos(
                    (string) $product['nama_produk'],
                    $search
                ) === false) {
                continue;
            }

            $imageName = (string) ($product['gambar_produk'] ?? '');

            $image = $imageName !== ''
                ? '/frontend/uploads/produk/' .
                  rawurlencode(basename($imageName))
                : '';

            $rows[] = [
                'item_id' => (string) $itemId,
                'produk' => $product,
                'item' => $item,
                'gambar' => $image,
                'jumlah' => $quantity
            ];
        }

        return [
            'rows' => $rows,
            'totalItem' => $totalQuantity
        ];
    }

    public function countConfigurations(): int
    {
        $count = 0;

        foreach ($_SESSION['keranjang'] as $key => $item) {
            if (is_array($item) &&
                preg_match('/^[a-f0-9]{24}$/D', (string) $key) &&
                !empty($item['id_produk'])) {
                $count++;
            }
        }

        return $count;
    }
}
