<?php

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$actions = [
	'customer-login' => 'login',
	'customer-register' => 'register',
	'admin-login' => 'loginAdmin',
	'admin-register' => 'registerAdmin',
	'customer-logout' => 'logoutCustomer',
	'admin-logout' => 'logoutAdmin',
];
$action = $_GET['action'] ?? '';

if ($action !== '') {
	$isLogout = in_array($action, ['customer-logout', 'admin-logout'], true);
	$productActions = [
		'admin-product-store',
		'admin-product-update',
		'admin-product-delete',
	];
	$isProductAction = in_array($action, $productActions, true);
	$isCartAction = in_array($action, ['customer-cart-add', 'customer-cart-update', 'customer-cart-remove'], true);
	if (!$isLogout && !$isProductAction && !$isCartAction) {
		header('Content-Type: application/json; charset=utf-8');
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		header('Allow: POST');
		http_response_code(405);
		echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
		exit;
	}

	if (!isset($actions[$action]) && !$isProductAction && !$isCartAction) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan.']);
		exit;
	}

	try {
		if ($isCartAction) {
			require_once __DIR__ . '/../app/controllers/OrderController.php';
			$controller = new OrderController();
			if ($action === 'customer-cart-add') {
				$controller->addToCart();
			} elseif ($action === 'customer-cart-update') {
				$controller->updateCart();
			} else {
				$controller->removeFromCart();
			}
		} elseif ($isProductAction) {
			require_once __DIR__ . '/../app/controllers/ProductController.php';
			$controller = new ProductController();
			if ($action === 'admin-product-store') {
				$controller->store();
			} elseif ($action === 'admin-product-update') {
				$controller->update($_POST['id_produk'] ?? null);
			} else {
				$controller->delete($_POST['id_produk'] ?? null);
			}
		} else {
			require_once __DIR__ . '/../app/controllers/AuthController.php';
			$controller = new AuthController(!$isLogout);
			$controller->{$actions[$action]}();
		}
	} catch (Throwable $error) {
		error_log($error->getMessage());
		http_response_code(500);
		if ($isCartAction) {
			echo 'Keranjang gagal diproses. Silakan coba lagi.';
		} elseif ($isProductAction) {
			echo 'Produk gagal disimpan. Silakan coba lagi.';
		} else {
			echo json_encode(['success' => false, 'message' => 'Permintaan gagal diproses. Silakan coba lagi.']);
		}
	}
	exit;
}

$pages = require __DIR__ . '/../routes/web.php';
$page = $_GET['page'] ?? 'customer-home';

if (!isset($pages[$page])) {
	http_response_code(404);
	echo 'Halaman tidak ditemukan.';
	exit;
}

require $pages[$page];
