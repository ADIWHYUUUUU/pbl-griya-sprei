<?php

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$actions = [
	'customer-login' => 'login',
	'customer-register' => 'register',
	'admin-login' => 'loginAdmin',
	'customer-logout' => 'logoutCustomer',
	'admin-logout' => 'logoutAdmin',
];
$action = $_GET['action'] ?? '';

if ($action !== '') {
	$isLogout = in_array($action, ['customer-logout', 'admin-logout'], true);
	$isProductStore = $action === 'admin-product-store';
	if (!$isLogout && !$isProductStore) {
		header('Content-Type: application/json; charset=utf-8');
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		header('Allow: POST');
		http_response_code(405);
		echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
		exit;
	}

	if (!isset($actions[$action]) && !$isProductStore) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan.']);
		exit;
	}

	try {
		if ($isProductStore) {
			require_once __DIR__ . '/../app/controllers/ProductController.php';
			$controller = new ProductController();
			$controller->store();
		} else {
			require_once __DIR__ . '/../app/controllers/AuthController.php';
			$controller = new AuthController(!$isLogout);
			$controller->{$actions[$action]}();
		}
	} catch (Throwable $error) {
		error_log($error->getMessage());
		http_response_code(500);
		if ($isProductStore) {
			echo 'Produk gagal disimpan. Silakan coba lagi.';
		} else {
			echo json_encode(['success' => false, 'message' => 'Login gagal diproses. Silakan coba lagi.']);
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
