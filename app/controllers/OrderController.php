<?php

require_once __DIR__ . '/ProductController.php';

class OrderController
{
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
            $pendingCart[$itemId] = $item;
            $_SESSION['pending_cart'] = $pendingCart;
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

    private function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}