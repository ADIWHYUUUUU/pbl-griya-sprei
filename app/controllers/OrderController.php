<?php

require_once __DIR__ . '/ProductController.php';
require_once __DIR__ . '/../models/CartModel.php';
require_once __DIR__ . '/../helpers/product_pricing.php';

class OrderController
{
    public function saveShippingAddress(): void
    {
        if (!$this->hasValidCsrfToken()) {
            $_SESSION['checkout_message'] = 'Sesi keamanan berakhir. Muat ulang halaman dan coba lagi.';
            $_SESSION['checkout_message_type'] = 'error';
            $this->redirect('/?page=customer-checkout');
        }
        $this->requireCustomer();

        $address = $_POST['alamat'] ?? null;
        $addressNote = $_POST['catatan_alamat'] ?? '';
        if (
            !is_string($address)
            || !is_string($addressNote)
            || !$this->isWithinLength($address, 2000)
            || !$this->isWithinLength($addressNote, 1000)
            || trim($address) === ''
        ) {
            $_SESSION['checkout_message'] = 'Alamat lengkap wajib diisi dengan benar.';
            $_SESSION['checkout_message_type'] = 'error';
            $_SESSION['checkout_form'] = [
                'alamat' => is_string($address) ? $address : '',
                'catatan_alamat' => is_string($addressNote) ? $addressNote : '',
                'metode' => is_string($_POST['metode'] ?? null) ? $_POST['metode'] : '',
                'catatan_kirim' => is_string($_POST['catatan_kirim'] ?? null) ? $_POST['catatan_kirim'] : '',
            ];
            $this->redirect('/?page=customer-checkout');
        }

        $userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($userId === false || $userId === null) {
            throw new UnexpectedValueException('ID customer tidak valid saat menyimpan alamat.');
        }

        global $pdo;
        require_once __DIR__ . '/../models/UserModel.php';
        (new UserModel($pdo))->updateShippingAddress((int) $userId, trim($address));

        $_SESSION['checkout_message'] = 'Alamat utama berhasil disimpan.';
        $_SESSION['checkout_message_type'] = 'success';
        $_SESSION['checkout_form'] = [
            'alamat' => trim($address),
            'catatan_alamat' => trim($addressNote),
            'metode' => is_string($_POST['metode'] ?? null) ? $_POST['metode'] : '',
            'catatan_kirim' => is_string($_POST['catatan_kirim'] ?? null) ? $_POST['catatan_kirim'] : '',
        ];
        $this->redirect('/?page=customer-checkout');
    }

    public function startCheckout(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }
        $this->requireCustomer();

        $cart = $_SESSION['cart'] ?? [];
        $selectedIds = $_POST['item_ids'] ?? null;
        if (!is_array($cart) || !is_array($selectedIds) || $selectedIds === []) {
            $this->setCartMessage('Pilih setidaknya satu produk untuk checkout.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        $validatedIds = [];
        foreach ($selectedIds as $itemId) {
            if (
                !is_string($itemId)
                || !preg_match('/^[a-f0-9]{32}$/D', $itemId)
                || !isset($cart[$itemId])
                || !is_array($cart[$itemId])
            ) {
                $this->setCartMessage('Pilihan produk tidak valid. Pilih kembali dari keranjang.', 'error');
                $this->redirect('/?page=customer-cart');
            }
            $validatedIds[$itemId] = true;
        }

        $_SESSION['checkout_item_ids'] = array_keys($validatedIds);
        $this->redirect('/?page=customer-checkout');
    }

    public function createOrder(): void
    {
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
        }
        $this->requireCustomer();

        $fullCart = $_SESSION['cart'] ?? [];
        $selectedIds = $_SESSION['checkout_item_ids'] ?? [];
        if (!is_array($fullCart) || !is_array($selectedIds) || $selectedIds === []) {
            $this->checkoutError('Pilih produk dari keranjang sebelum checkout.');
        }

        $cart = [];
        foreach ($selectedIds as $itemId) {
            if (
                !is_string($itemId)
                || !preg_match('/^[a-f0-9]{32}$/D', $itemId)
                || !isset($fullCart[$itemId])
                || !is_array($fullCart[$itemId])
            ) {
                $this->checkoutError('Pilihan produk tidak valid. Pilih kembali dari keranjang.');
            }
            $cart[$itemId] = $fullCart[$itemId];
        }

        $productController = new ProductController();
        $items = [];
        $quantitiesByProduct = [];
        $standardQuantitiesByProduct = [];
        foreach ($cart as $itemId => $item) {
            if (!is_string($itemId) || !preg_match('/^[a-f0-9]{32}$/D', $itemId) || !is_array($item)) {
                $this->checkoutError('Data keranjang tidak valid. Periksa kembali keranjang Anda.');
            }

            $productId = filter_var($item['id_produk'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $product = $productId === false ? false : $productController->getProductById($productId);
            $quantity = $this->readInteger($item['qtySprei'] ?? null);
            if (!$product || $quantity === null || $quantity < 1 || $quantity > 50) {
                $this->checkoutError('Produk atau jumlah pesanan tidak valid. Periksa kembali keranjang Anda.');
            }

            $isPillowBolster = product_is_pillow_bolster_category((string) $product['kategori_produk']);
            $mode = $isPillowBolster ? 'standard' : ($item['mode'] ?? '');
            if (!in_array($mode, ['standard', 'custom'], true)) {
                $this->checkoutError('Tipe ukuran tidak valid. Periksa kembali keranjang Anda.');
            }

            $dimensions = [
                'panjang' => (int) $product['panjang_standar'],
                'lebar' => (int) $product['lebar_standar'],
                'tinggi' => $isPillowBolster ? null : (int) $product['tinggi_standar'],
            ];
            $fabric = is_string($item['jenis_kain'] ?? null)
                ? $item['jenis_kain']
                : (string) ($product['jenis_kain'] ?? '');
            if ($mode === 'custom' && !$isPillowBolster) {
                foreach (array_keys($dimensions) as $field) {
                    $value = $this->readInteger($item[$field] ?? null);
                    if ($value === null || $value < 1 || $value > 1000) {
                        $this->checkoutError('Ukuran custom harus berupa angka antara 1 dan 1000 cm.');
                    }
                    $dimensions[$field] = $value;
                }
            } elseif (($pricingOptions = product_pricing_options((string) $product['kategori_produk'])) !== []) {
                $standardWidth = (int) ($item['lebar'] ?? 0);
                $usesCatalogSize = $standardWidth === (int) $product['lebar_standar'];
                $standardPrice = product_price_for(
                    (string) $product['kategori_produk'],
                    $fabric,
                    $standardWidth
                );
                if (!isset($pricingOptions[$fabric]) || ($standardPrice === null && !$usesCatalogSize)) {
                    $this->checkoutError('Pilihan kain atau ukuran standar tidak valid. Pilih kembali dari halaman produk.');
                }
                $dimensions = $usesCatalogSize
                    ? [
                        'panjang' => (int) $product['panjang_standar'],
                        'lebar' => $standardWidth,
                        'tinggi' => (int) $product['tinggi_standar'],
                    ]
                    : [
                        'panjang' => 200,
                        'lebar' => $standardWidth,
                        'tinggi' => (int) $pricingOptions[$fabric]['height'],
                    ];
            }

            $allowsExtras = ($product['kategori_produk'] ?? '') === 'SPREI';
            $pillows = $allowsExtras ? $this->readInteger($item['qtyBantal'] ?? 0) : 0;
            $bolsters = $allowsExtras ? $this->readInteger($item['qtyGuling'] ?? 0) : 0;
            $note = $item['catatan'] ?? '';
            if (
                $pillows === null || $pillows < 0 || $pillows > 20 ||
                $bolsters === null || $bolsters < 0 || $bolsters > 20 ||
                !is_string($note) || !$this->isWithinLength($note, 1000)
            ) {
                $this->checkoutError('Spesifikasi produk tidak valid. Periksa kembali keranjang Anda.');
            }

            $quantitiesByProduct[$productId] = ($quantitiesByProduct[$productId] ?? 0) + $quantity;
            if ($mode === 'standard') {
                $standardQuantitiesByProduct[$productId] = ($standardQuantitiesByProduct[$productId] ?? 0) + $quantity;
            }
            $items[] = [
                'id_produk' => (int) $product['id_produk'],
                'nama_produk' => (string) $product['nama_produk'],
                'kategori_produk' => (string) $product['kategori_produk'],
                'stok' => (int) $product['stok'],
                'mode' => $mode,
                'jumlah' => $quantity,
                'panjang' => $dimensions['panjang'],
                'lebar' => $dimensions['lebar'],
                'tinggi' => $dimensions['tinggi'],
                'bantal' => $pillows,
                'guling' => $bolsters,
                'catatan' => trim($note),
                'harga_satuan' => $mode === 'standard'
                    ? (float) (product_price_for((string) $product['kategori_produk'], $fabric, $dimensions['lebar']) ?? $product['harga_dasar'])
                    : (float) $product['harga_dasar'],
                'jenis_kain' => $fabric !== '' ? $fabric : null,
            ];
        }

        foreach ($items as $item) {
            if (
                $quantitiesByProduct[$item['id_produk']] > 50
                || ($standardQuantitiesByProduct[$item['id_produk']] ?? 0) > $item['stok']
            ) {
                $this->checkoutError('Jumlah pesanan melebihi stok yang tersedia. Periksa kembali keranjang Anda.');
            }
        }

        $shippingMethods = [
            'instan' => 'INSTANT_GOJEK',
            'jne' => 'EKSPEDISI_JNE',
            'jnt' => 'EKSPEDISI_JNT',
        ];
        $shippingMethod = $_POST['metode'] ?? '';
        if (!is_string($shippingMethod) || !isset($shippingMethods[$shippingMethod])) {
            $this->checkoutError('Pilih metode pengiriman yang valid.');
        }
        if (($_POST['setuju'] ?? '') !== '1') {
            $this->checkoutError('Konfirmasi ukuran wajib disetujui sebelum pesanan dikirim.');
        }

        $fields = [
            'alamat' => [2000, true],
            'catatan_alamat' => [1000, false],
            'catatan_kirim' => [1000, false],
        ];
        $orderData = [];
        foreach ($fields as $field => [$maxLength, $required]) {
            $value = $_POST[$field] ?? '';
            if (!is_string($value) || !$this->isWithinLength($value, $maxLength)) {
                $this->checkoutError('Periksa kembali data pengiriman dan catatan pesanan Anda.');
            }
            $value = trim($value);
            if ($required && $value === '') {
                $this->checkoutError('Alamat pengiriman wajib diisi.');
            }
            $orderData[$field] = $value;
        }

        global $pdo;
        require_once __DIR__ . '/../models/OrderModel.php';
        $createdOrder = (new OrderModel($pdo))->createOrder(
            [
                'id_user' => (int) $_SESSION['id_user'],
                'alamat_pengiriman' => $orderData['alamat'],
                'catatan_alamat' => $orderData['catatan_alamat'],
                'metode_pengiriman' => $shippingMethods[$shippingMethod],
                'catatan_pengiriman' => $orderData['catatan_kirim'],
                'cart_item_ids' => array_keys($cart),
            ],
            array_map(static function (array $item): array {
                return [
                    'id_produk' => $item['id_produk'],
                    'kategori_produk' => $item['kategori_produk'],
                    'tipe_ukuran' => match ($item['mode']) {
                        'standard' => 'STANDAR',
                        'custom' => 'CUSTOM',
                    },
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'panjang' => $item['panjang'],
                    'lebar' => $item['lebar'],
                    'tinggi' => $item['tinggi'],
                    'tambah_sarban' => $item['bantal'],
                    'tambah_sargul' => $item['guling'],
                    'catatan_pesanan' => $item['catatan'],
                ];
            }, $items)
        );
        $orderId = $createdOrder['id_pesanan'];

        foreach (array_keys($cart) as $itemId) {
            unset($_SESSION['cart'][$itemId]);
        }
        if (($_SESSION['cart'] ?? []) === []) {
            unset($_SESSION['cart']);
        }
        unset(
            $_SESSION['checkout_item_ids'],
            $_SESSION['checkout_form'],
            $_SESSION['checkout_message'],
            $_SESSION['checkout_message_type']
        );
        $_SESSION['pesanan_terakhir'] = [
            'id' => $orderId,
            'kode' => $createdOrder['no_invoice'],
            'produk' => array_column($items, 'nama_produk'),
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
        $isPillowBolster = product_is_pillow_bolster_category((string) $product['kategori_produk']);
        $mode = $isPillowBolster ? 'standard' : ($_POST['mode'] ?? '');
        if (!in_array($mode, ['standard', 'custom'], true)) {
            $this->setCartMessage('Tipe ukuran tidak valid.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        if (
            $quantity === null
            || $quantity < 1
            || $quantity > 50
            || ($mode === 'standard' && ($stock < 1 || $quantity > $stock))
        ) {
            $this->setCartMessage('Jumlah produk melebihi stok yang tersedia.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        $dimensions = [
            'length' => (int) $product['panjang_standar'],
            'width' => (int) $product['lebar_standar'],
            'height' => $isPillowBolster ? null : (int) $product['tinggi_standar'],
        ];
        $pricingOptions = product_pricing_options((string) $product['kategori_produk']);
        $fabric = (string) ($product['jenis_kain'] ?? '');
        if ($pricingOptions !== []) {
            $width = $this->readInteger($_POST['width'] ?? null);
            if (!isset($pricingOptions[$fabric])) {
                $this->setCartMessage('Jenis kain produk belum memiliki pilihan harga.', 'error');
                $this->redirect('/?page=customer-detail&id=' . $productId);
            }
            if ($mode === 'standard') {
                $usesCatalogSize = $width === (int) $product['lebar_standar'];
                if (
                    $width === null ||
                    (!isset($pricingOptions[$fabric]['prices'][$width]) && !$usesCatalogSize)
                ) {
                    $this->setCartMessage('Pilih ukuran standar yang tersedia untuk produk ini.', 'error');
                    $this->redirect('/?page=customer-detail&id=' . $productId);
                }
                $dimensions = $width === (int) $product['lebar_standar']
                    ? [
                        'length' => (int) $product['panjang_standar'],
                        'width' => $width,
                        'height' => (int) $product['tinggi_standar'],
                    ]
                    : [
                        'length' => 200,
                        'width' => $width,
                        'height' => $pricingOptions[$fabric]['height'],
                    ];
            }
        }

        if ($mode === 'custom' && !$isPillowBolster) {
            foreach (['length', 'width', 'height'] as $field) {
                $value = $this->readInteger($_POST[$field] ?? null);
                if ($value === null || $value < 1) {
                    $this->setCartMessage('Ukuran custom tidak valid. Periksa kembali dimensi yang dimasukkan.', 'error');
                    $this->redirect('/?page=customer-detail&id=' . $productId);
                }
                $dimensions[$field] = $value;
            }
        }

        $allowsExtras = ($product['kategori_produk'] ?? '') === 'SPREI';
        $quantityPillow = $allowsExtras ? $this->readInteger($_POST['qtyBantal'] ?? 0) : 0;
        $quantityBolster = $allowsExtras ? $this->readInteger($_POST['qtyGuling'] ?? 0) : 0;
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
        if ($mode === 'standard') {
            foreach ($cart as $cartItem) {
                if (
                    is_array($cartItem)
                    && (int) ($cartItem['id_produk'] ?? 0) === $productId
                    && ($cartItem['mode'] ?? 'standard') === 'standard'
                ) {
                    $existingQuantity += max(0, (int) ($cartItem['qtySprei'] ?? 0));
                }
            }
        }

        if ($mode === 'standard' && $existingQuantity + $quantity > $stock) {
            $this->setCartMessage('Jumlah pesanan di keranjang melebihi stok yang tersedia.', 'error');
            $this->redirect('/?page=customer-detail&id=' . $productId);
        }

        $itemId = bin2hex(random_bytes(16));
        $item = [
            'id_produk' => $productId,
            'nama_produk' => (string) $product['nama_produk'],
            'gambar_produk' => (string) ($product['gambar_produk'] ?? ''),
            'harga_dasar' => (float) $product['harga_dasar'],
            'harga_satuan' => $mode === 'standard'
                ? (float) (product_price_for((string) $product['kategori_produk'], $fabric, $dimensions['width']) ?? $product['harga_dasar'])
                : (float) $product['harga_dasar'],
            'jenis_kain' => $fabric !== '' ? $fabric : null,
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
        $this->persistCartItem($itemId, $item);
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
        $isEdit = ($_POST['edit_cart'] ?? '') === '1';

        if (!$product || $quantity === null || $quantity < 1) {
            $this->setCartMessage('Produk atau jumlah pesanan tidak valid.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        $isPillowBolster = product_is_pillow_bolster_category((string) $product['kategori_produk']);
        $mode = $isPillowBolster
            ? 'standard'
            : ($isEdit ? ($_POST['mode'] ?? '') : ($cart[$itemId]['mode'] ?? 'standard'));
        if ($isEdit && !in_array($mode, ['standard', 'custom'], true)) {
            $this->setCartMessage('Tipe ukuran tidak valid.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        $stock = (int) $product['stok'];
        $otherStandardQuantity = 0;
        foreach ($cart as $otherId => $cartItem) {
            if (
                $otherId !== $itemId
                && is_array($cartItem)
                && (int) ($cartItem['id_produk'] ?? 0) === $productId
                && ($cartItem['mode'] ?? 'standard') !== 'custom'
            ) {
                $otherStandardQuantity += max(0, (int) ($cartItem['qtySprei'] ?? 0));
            }
        }

        if (
            $quantity > 50
            || ($mode !== 'custom' && (
                $stock < 1
                || $quantity > $stock
                || $otherStandardQuantity + $quantity > $stock
            ))
        ) {
            $this->setCartMessage('Jumlah pesanan melebihi stok yang tersedia.', 'error');
            $this->redirect('/?page=customer-cart');
        }

        if ($isEdit) {
            $keepStandardSize = ($cart[$itemId]['mode'] ?? 'standard') === 'standard';
            $dimensions = [
                'panjang' => (int) ($isPillowBolster
                    ? $product['panjang_standar']
                    : ($keepStandardSize ? ($cart[$itemId]['panjang'] ?? $product['panjang_standar']) : $product['panjang_standar'])),
                'lebar' => (int) ($isPillowBolster
                    ? $product['lebar_standar']
                    : ($keepStandardSize ? ($cart[$itemId]['lebar'] ?? $product['lebar_standar']) : $product['lebar_standar'])),
                'tinggi' => $isPillowBolster
                    ? null
                    : (int) ($keepStandardSize ? ($cart[$itemId]['tinggi'] ?? $product['tinggi_standar']) : $product['tinggi_standar']),
            ];
            if ($mode === 'custom' && !$isPillowBolster) {
                foreach (['panjang', 'lebar', 'tinggi'] as $field) {
                    $value = $this->readInteger($_POST[$field] ?? null);
                    if ($value === null || $value < 1) {
                        $this->setCartMessage('Ukuran custom tidak valid. Periksa kembali dimensi yang dimasukkan.', 'error');
                        $this->redirect('/?page=customer-cart');
                    }
                    $dimensions[$field] = $value;
                }
            }

            $allowsExtras = ($product['kategori_produk'] ?? '') === 'SPREI';
            $quantityPillow = $allowsExtras ? $this->readInteger($_POST['qtyBantal'] ?? 0) : 0;
            $quantityBolster = $allowsExtras ? $this->readInteger($_POST['qtyGuling'] ?? 0) : 0;
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
        $cart[$itemId]['harga_satuan'] = ($cart[$itemId]['mode'] ?? 'standard') === 'standard'
            ? (float) (product_price_for(
                (string) $product['kategori_produk'],
                (string) ($cart[$itemId]['jenis_kain'] ?? $product['jenis_kain'] ?? ''),
                (int) ($cart[$itemId]['lebar'] ?? $product['lebar_standar'])
            ) ?? $product['harga_dasar'])
            : (float) $product['harga_dasar'];
        $this->persistCartItem($itemId, $cart[$itemId]);
        $_SESSION['cart'] = $cart;
        $this->setCartMessage(
            $isEdit
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

        global $pdo;
        $cartModel = new CartModel($pdo);
        $cartModel->removeItem((int) $_SESSION['id_user'], $itemId);
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

    private function persistCartItem(string $itemId, array $item): void
    {
        global $pdo;

        $userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($userId === false || $userId === null) {
            throw new UnexpectedValueException('ID customer tidak valid saat menyimpan keranjang.');
        }

        $cartModel = new CartModel($pdo);
        $cartModel->saveItem($userId, $itemId, $item);
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

    private function checkoutError(string $message): void
    {
        $_SESSION['checkout_message'] = $message;
        $_SESSION['checkout_message_type'] = 'error';
        $formFields = ['alamat', 'catatan_alamat', 'metode', 'catatan_kirim'];
        $_SESSION['checkout_form'] = [];
        foreach ($formFields as $field) {
            if (isset($_POST[$field]) && is_string($_POST[$field])) {
                $_SESSION['checkout_form'][$field] = $_POST[$field];
            }
        }

        $this->redirect('/?page=customer-checkout');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}
