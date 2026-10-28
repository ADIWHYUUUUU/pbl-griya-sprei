<?php

require_once __DIR__ . '/ProductController.php';

class OrderController
{
    public function createOrder(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }
        $this->requireCustomer();

        $itemId = $_POST['item_id'] ?? '';
        if (!is_string($itemId) || !preg_match('/^[a-f0-9]{32}$/D', $itemId)) {
            $this->checkoutError('Item keranjang tidak valid.', '');
        }

        $cart = $_SESSION['cart'] ?? [];
        if (!is_array($cart) || !isset($cart[$itemId]) || !is_array($cart[$itemId])) {
            $this->checkoutError('Item tersebut tidak ditemukan di keranjang.', $itemId);
        }

        $item = $cart[$itemId];
        $productId = filter_var($item['id_produk'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $product = $productId === false ? false : (new ProductController())->getProductById($productId);
        $quantity = $this->readInteger($item['qtySprei'] ?? null);
        if (
            !$product || $quantity === null || $quantity < 1 ||
            $quantity > min(50, (int) $product['stok'])
        ) {
            $this->checkoutError('Produk atau stok sudah tidak tersedia. Periksa kembali keranjang Anda.', $itemId);
        }

        $shippingMethods = [
            'instan' => 'INSTANT_GOJEK',
            'jne' => 'EKSPEDISI_JNE',
            'jnt' => 'EKSPEDISI_JNT',
        ];
        $shippingMethod = $_POST['metode'] ?? '';
        if (!is_string($shippingMethod) || !isset($shippingMethods[$shippingMethod])) {
            $this->checkoutError('Pilih metode pengiriman yang valid.', $itemId);
        }
        if (($_POST['setuju'] ?? '') !== '1') {
            $this->checkoutError('Konfirmasi ukuran wajib disetujui sebelum pesanan dikirim.', $itemId);
        }

        $mode = $_POST['jenis'] ?? '';
        if (!in_array($mode, ['standar', 'custom'], true)) {
            $this->checkoutError('Tipe ukuran tidak valid.', $itemId);
        }

        $dimensions = [
            'panjang' => (int) $product['panjang_standar'],
            'lebar' => (int) $product['lebar_standar'],
            'tinggi' => (int) $product['tinggi_standar'],
        ];
        if ($mode === 'custom') {
            foreach (array_keys($dimensions) as $field) {
                $value = $this->readInteger($_POST[$field] ?? null);
                if ($value === null || $value < 1 || $value > 1000) {
                    $this->checkoutError('Ukuran custom harus berupa angka antara 1 dan 1000 cm.', $itemId);
                }
                $dimensions[$field] = $value;
            }
        }

        $pillows = $this->readInteger($_POST['bantal'] ?? null);
        $bolsters = $this->readInteger($_POST['guling'] ?? null);
        if (
            $pillows === null || $pillows < 0 || $pillows > 20 ||
            $bolsters === null || $bolsters < 0 || $bolsters > 20
        ) {
            $this->checkoutError('Jumlah sarung tambahan tidak valid.', $itemId);
        }

        $fields = [
            'alamat' => [2000, true],
            'catatan_alamat' => [1000, false],
            'catatan_kirim' => [1000, false],
            'catatan_produk' => [1000, false],
        ];
        $orderData = [];
        foreach ($fields as $field => [$maxLength, $required]) {
            $value = $_POST[$field] ?? '';
            if (!is_string($value) || !$this->isWithinLength($value, $maxLength)) {
                $this->checkoutError('Periksa kembali data pengiriman dan catatan pesanan Anda.', $itemId);
            }
            $value = trim($value);
            if ($required && $value === '') {
                $this->checkoutError('Alamat pengiriman wajib diisi.', $itemId);
            }
            $orderData[$field] = $value;
        }

        global $pdo;
        require_once __DIR__ . '/../models/OrderModel.php';
        $invoice = 'GS-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
        $orderId = (new OrderModel($pdo))->createOrder(
            [
                'id_user' => (int) $_SESSION['id_user'],
                'no_invoice' => $invoice,
                'alamat_pengiriman' => $orderData['alamat'],
                'catatan_alamat' => $orderData['catatan_alamat'],
                'metode_pengiriman' => $shippingMethods[$shippingMethod],
                'catatan_pengiriman' => $orderData['catatan_kirim'],
            ],
            [
                'id_produk' => (int) $product['id_produk'],
                'tipe_ukuran' => strtoupper($mode),
                'jumlah' => $quantity,
                'harga_satuan' => (float) $product['harga_dasar'],
                'panjang' => $dimensions['panjang'],
                'lebar' => $dimensions['lebar'],
                'tinggi' => $dimensions['tinggi'],
                'tambah_sarban' => $pillows,
                'tambah_sargul' => $bolsters,
                'catatan_pesanan' => $orderData['catatan_produk'],
            ]
        );

        unset($_SESSION['cart'][$itemId], $_SESSION['checkout_form'], $_SESSION['checkout_message']);
        $_SESSION['pesanan_terakhir'] = [
            'id' => $orderId,
            'kode' => $invoice,
            'produk' => ['nama' => (string) $product['nama_produk']],
        ];
        $this->redirect('/?page=customer-checkout&berhasil=1');
    }

    public function addToCart(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }

        $rawProductId = $_POST['id_produk'] ?? null;
        $productId = is_string($rawProductId) || is_int($rawProductId)
            ? filter_var($rawProductId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ])
            : false;

        if ($productId === false || $productId === null) {
            $this->setCartMessage('Produk tidak valid.', 'error');
            $this->redirect('/?page=customer-catalog');
        }

        $product = (new ProductController())->getProductById($productId);
        if (!$product) {
            http_response_code(404);
            $this->setCartMessage('Produk tidak ditemukan.', 'error');
            $this->redirect('/?page=customer-catalog');
        }

        $stock = (int) $product['stok'];
        $quantity = $this->readInteger($_POST['qtySprei'] ?? null);
        $mode = $_POST['mode'] ?? '';

        if ($stock < 1 || $quantity === null || $quantity < 1 || $quantity > min(50, $stock)) {
            $this->setCartMessage('Jumlah produk melebihi stok yang tersedia.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        if (!in_array($mode, ['standard', 'custom'], true)) {
            $this->setCartMessage('Tipe ukuran tidak valid.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        $dimensions = [
            'length' => (int) $product['panjang_standar'],
            'width' => (int) $product['lebar_standar'],
            'height' => (int) $product['tinggi_standar'],
        ];

        if ($mode === 'custom') {
            foreach (['length', 'width', 'height'] as $field) {
                $value = $this->readInteger($_POST[$field] ?? null);
                if ($value === null || $value < 1) {
                    $this->setCartMessage('Ukuran custom tidak valid. Periksa kembali dimensi yang dimasukkan.', 'error');
                    $this->redirect('/?page=customer-detail&id=' . $productId);
                }
                $dimensions[$field] = $value;
            }
        }

        $quantityPillow = $this->readInteger($_POST['qtyBantal'] ?? null);
        $quantityBolster = $this->readInteger($_POST['qtyGuling'] ?? null);
        if (
            $quantityPillow === null || $quantityPillow < 0 || $quantityPillow > 20 ||
            $quantityBolster === null || $quantityBolster < 0 || $quantityBolster > 20
        ) {
            $this->setCartMessage('Jumlah sarung tambahan tidak valid.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        $note = $_POST['catatan'] ?? '';
        $noteCharacterCount = is_string($note)
            ? preg_match_all('/./us', $note, $matches)
            : false;
        if (
            !is_string($note) ||
            strlen($note) > 4000 ||
            $noteCharacterCount === false ||
            $noteCharacterCount > 1000
        ) {
            $this->setCartMessage('Catatan terlalu panjang.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        $cart = $_SESSION['cart'] ?? [];
        if (!is_array($cart)) {
            $cart = [];
        }

        $existingQuantity = 0;
        foreach ($cart as $cartItem) {
            if (is_array($cartItem) && (int) ($cartItem['id_produk'] ?? 0) === $productId) {
                $existingQuantity += max(0, (int) ($cartItem['qtySprei'] ?? 0));
            }
        }

        if ($existingQuantity + $quantity > $stock) {
            $this->setCartMessage('Jumlah pesanan di keranjang melebihi stok yang tersedia.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        $itemId = bin2hex(random_bytes(16));
        $item = [
            'id_produk' => $productId,
            'nama_produk' => (string) $product['nama_produk'],
            'gambar_produk' => (string) ($product['gambar_produk'] ?? ''),
            'harga_dasar' => (float) $product['harga_dasar'],
            'mode' => $mode,
            'panjang' => $dimensions['length'],
            'lebar' => $dimensions['width'],
            'tinggi' => $dimensions['height'],
            'qtySprei' => $quantity,
            'qtyBantal' => $quantityPillow,
            'qtyGuling' => $quantityBolster,
            'catatan' => trim($note),
        ];

        if (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'CUSTOMER') {
            $pendingCart = $_SESSION['pending_cart'] ?? [];
            if (!is_array($pendingCart)) {
                $pendingCart = [];
            }
            $isDuplicate = false;
            foreach ($pendingCart as $pendingItem) {
                if ($pendingItem === $item) {
                    $isDuplicate = true;
                    break;
                }
            }
            if (!$isDuplicate) {
                $pendingCart[$itemId] = $item;
            }
            $_SESSION['pending_cart'] = $pendingCart;
            $_SESSION['login_return_url'] = '/?page=customer-detail&id=' . $productId;
            $this->setCartMessage('Silakan login dengan akun customer terlebih dahulu. Pesanan Anda akan otomatis masuk ke keranjang setelah login.', 'error');
            $this->redirect('/?page=customer-login');
        }

        $cart[$itemId] = $item;
        $_SESSION['cart'] = $cart;

        $this->setCartMessage('Produk berhasil ditambahkan ke keranjang.', 'success');
        $this->redirect('/?page=customer-cart');
    }

    public function updateCart(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }
        $this->requireCustomer();

        $itemId = $_POST['item_id'] ?? '';
        if (!is_string($itemId) || !preg_match('/^[a-f0-9]{32}$/D', $itemId)) {
            $this->setCartMessage('Item keranjang tidak valid.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        $cart = $_SESSION['cart'] ?? [];
        if (!is_array($cart) || !isset($cart[$itemId]) || !is_array($cart[$itemId])) {
            $this->setCartMessage('Item tersebut tidak ditemukan di keranjang.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        $productId = filter_var($cart[$itemId]['id_produk'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $quantity = $this->readInteger($_POST['qtySprei'] ?? null);
        $product = $productId === false ? false : (new ProductController())->getProductById($productId);

        if (!$product || $quantity === null || $quantity < 1) {
            $this->setCartMessage('Produk atau jumlah pesanan tidak valid.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        $stock = (int) $product['stok'];
        $otherQuantity = 0;
        foreach ($cart as $otherId => $cartItem) {
            if ($otherId !== $itemId && is_array($cartItem) && (int) ($cartItem['id_produk'] ?? 0) === $productId) {
                $otherQuantity += max(0, (int) ($cartItem['qtySprei'] ?? 0));
            }
        }

        if ($quantity > min(50, $stock) || $otherQuantity + $quantity > $stock) {
            $this->setCartMessage('Jumlah pesanan melebihi stok yang tersedia.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        if (($_POST['edit_cart'] ?? '') === '1') {
            $mode = $_POST['mode'] ?? '';
            if (!in_array($mode, ['standard', 'custom'], true)) {
                $this->setCartMessage('Tipe ukuran tidak valid.', 'error');
                $this->redirect('/?page=customer-cart');
            }

            $dimensions = [
                'panjang' => (int) $product['panjang_standar'],
                'lebar' => (int) $product['lebar_standar'],
                'tinggi' => (int) $product['tinggi_standar'],
            ];
            if ($mode === 'custom') {
                foreach (['panjang', 'lebar', 'tinggi'] as $field) {
                    $value = $this->readInteger($_POST[$field] ?? null);
                    if ($value === null || $value < 1) {
                        $this->setCartMessage('Ukuran custom tidak valid. Periksa kembali dimensi yang dimasukkan.', 'error');
                        $this->redirect('/?page=customer-cart');
                    }
                    $dimensions[$field] = $value;
                }
            }

            $quantityPillow = $this->readInteger($_POST['qtyBantal'] ?? null);
            $quantityBolster = $this->readInteger($_POST['qtyGuling'] ?? null);
            if (
                $quantityPillow === null || $quantityPillow < 0 || $quantityPillow > 20 ||
                $quantityBolster === null || $quantityBolster < 0 || $quantityBolster > 20
            ) {
                $this->setCartMessage('Jumlah sarung tambahan tidak valid.', 'error');
                $this->redirect('/?page=customer-cart');
            }

            $note = $_POST['catatan'] ?? '';
            $noteCharacterCount = is_string($note)
                ? preg_match_all('/./us', $note, $matches)
                : false;
            if (
                !is_string($note) ||
                strlen($note) > 4000 ||
                $noteCharacterCount === false ||
                $noteCharacterCount > 1000
            ) {
                $this->setCartMessage('Catatan terlalu panjang.', 'error');
                $this->redirect('/?page=customer-cart');
            }

            $cart[$itemId]['mode'] = $mode;
            $cart[$itemId]['panjang'] = $dimensions['panjang'];
            $cart[$itemId]['lebar'] = $dimensions['lebar'];
            $cart[$itemId]['tinggi'] = $dimensions['tinggi'];
            $cart[$itemId]['qtyBantal'] = $quantityPillow;
            $cart[$itemId]['qtyGuling'] = $quantityBolster;
            $cart[$itemId]['catatan'] = trim($note);
        }

        $cart[$itemId]['qtySprei'] = $quantity;
        $_SESSION['cart'] = $cart;
        $this->setCartMessage(
            ($_POST['edit_cart'] ?? '') === '1'
                ? 'Pesanan berhasil diperbarui.'
                : 'Jumlah pesanan berhasil diperbarui.',
            'success'
        );
        $this->redirect('/?page=customer-cart');
    }

    public function removeFromCart(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }
        $this->requireCustomer();

        $itemId = $_POST['item_id'] ?? '';
        if (!is_string($itemId) || !preg_match('/^[a-f0-9]{32}$/D', $itemId)) {
            $this->setCartMessage('Item keranjang tidak valid.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        if (!isset($_SESSION['cart'][$itemId])) {
            $this->setCartMessage('Item tersebut tidak ditemukan di keranjang.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        unset($_SESSION['cart'][$itemId]);
        $this->setCartMessage('Item berhasil dihapus dari keranjang.', 'success');
        $this->redirect('/?page=customer-cart');
    }

    private function readInteger($value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer === false ? null : $integer;
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    private function requireCustomer(): void
    {
        if (($_SESSION['login'] ?? false) === true && ($_SESSION['role'] ?? '') === 'CUSTOMER') {
            return;
        }

        $this->setCartMessage('Silakan login terlebih dahulu untuk mengakses keranjang.', 'error');
        $this->redirect('/?page=customer-login');
    }

    private function setCartMessage(string $message, string $type): void
    {
        $_SESSION['cart_message'] = $message;
        $_SESSION['cart_message_type'] = $type;
    }

    private function isWithinLength(string $value, int $maxLength): bool
    {
        $characterCount = preg_match_all('/./us', $value, $matches);
        return strlen($value) <= $maxLength * 4
            && $characterCount !== false
            && $characterCount <= $maxLength;
    }

    private function checkoutError(string $message, string $itemId): void
    {
        $_SESSION['checkout_message'] = $message;
        $formFields = [
            'alamat', 'catatan_alamat', 'metode',
            'catatan_kirim', 'jenis', 'panjang', 'lebar', 'tinggi',
            'bantal', 'guling', 'catatan_produk',
        ];
        $_SESSION['checkout_form'] = [];
        foreach ($formFields as $field) {
            if (isset($_POST[$field]) && is_string($_POST[$field])) {
                $_SESSION['checkout_form'][$field] = $_POST[$field];
            }
        }

        $url = '/?page=customer-cart';
        if (preg_match('/^[a-f0-9]{32}$/D', $itemId)) {
            $url = '/?page=customer-checkout&item_id=' . rawurlencode($itemId);
        }
        $this->redirect($url);
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}
