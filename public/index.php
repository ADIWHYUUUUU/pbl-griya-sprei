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
	$isCartAction = in_array($action, ['customer-cart-add', 'customer-cart-update', 'customer-cart-remove', 'customer-checkout-start'], true);
	$isOrderAction = $action === 'customer-order-create';
	$isPaymentAction = $action === 'customer-payment-submit';
	$adminOrderActions = ['admin-order-verify-save'];
	$isAdminOrderAction = in_array($action, $adminOrderActions, true);
	$adminPaymentActions = ['admin-payment-verify-save'];
	$isAdminPaymentAction = in_array($action, $adminPaymentActions, true);
	if (!$isLogout && !$isProductAction && !$isCartAction && !$isOrderAction && !$isAdminOrderAction && !$isPaymentAction && !$isAdminPaymentAction) {
		header('Content-Type: application/json; charset=utf-8');
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		header('Allow: POST');
		http_response_code(405);
		echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
		exit;
	}

	if (!isset($actions[$action]) && !$isProductAction && !$isCartAction && !$isOrderAction && !$isAdminOrderAction && !$isPaymentAction && !$isAdminPaymentAction) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan.']);
		exit;
	}

	try {
		if ($isAdminPaymentAction) {
			require_once __DIR__ . '/../app/controllers/AdminPaymentController.php';
			(new AdminPaymentController())->verifySave();
		} elseif ($isPaymentAction) {
			require_once __DIR__ . '/../app/controllers/PaymentController.php';
			(new PaymentController())->submit();
		} elseif ($isAdminOrderAction) {
			require_once __DIR__ . '/../app/controllers/AdminOrderController.php';
			(new AdminOrderController())->verifySave();
		} elseif ($isCartAction || $isOrderAction) {
			require_once __DIR__ . '/../app/controllers/OrderController.php';
			$controller = new OrderController();
			if ($isOrderAction) {
				$controller->createOrder();
			} elseif ($action === 'customer-checkout-start') {
				$controller->startCheckout();
			} elseif ($action === 'customer-cart-add') {
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
		} elseif ($isOrderAction) {
			$_SESSION['checkout_message'] = 'Pesanan gagal disimpan. Silakan coba lagi.';
			header('Location: /?page=customer-checkout', true, 303);
		} elseif ($isProductAction) {
			echo 'Produk gagal disimpan. Silakan coba lagi.';
		} elseif ($isPaymentAction) {
			$_SESSION['payment_message'] = 'Pembayaran gagal diproses. Silakan coba lagi.';
			$_SESSION['payment_message_type'] = 'error';
			$_SESSION['payment_form'] = $_POST;
			$query = ['page' => 'customer-payment'];
			if (isset($_POST['id_pesanan']) && is_string($_POST['id_pesanan'])) {
				$query['id'] = $_POST['id_pesanan'];
			}
			header('Location: /?' . http_build_query($query), true, 303);
		} elseif ($action === 'admin-order-verify-save') {
			$_SESSION['admin_order_message'] = [
				'type' => 'error',
				'text' => 'Verifikasi pesanan gagal disimpan. Silakan coba lagi.',
			];
			$query = ['page' => 'admin-order-verification'];
			if (isset($_POST['id_pesanan']) && is_string($_POST['id_pesanan'])) {
				$query['id'] = $_POST['id_pesanan'];
			} else {
				$query['page'] = 'admin-orders';
			}
			header('Location: /?' . http_build_query($query), true, 303);
		} elseif ($isAdminPaymentAction) {
			$_SESSION['admin_payment_message'] = 'Pembayaran gagal diproses. Silakan coba lagi.';
			$_SESSION['admin_payment_message_type'] = 'error';
			header('Location: /?page=admin-payments', true, 303);
		} else {
			echo json_encode(['success' => false, 'message' => 'Permintaan gagal diproses. Silakan coba lagi.']);
		}
	}
	exit;
}

$pages = require __DIR__ . '/../routes/web.php';
$page = $_GET['page'] ?? 'customer-home';

$orderPages = ['customer-orders', 'pesanan.php', 'pesananSaya.php', 'riwayatPesanan.php'];
if (
	in_array($page, $orderPages, true)
	&& (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'CUSTOMER')
) {
	$_SESSION['cart_message'] = 'Silakan login dengan akun customer untuk mengakses Pesanan Saya.';
	$_SESSION['cart_message_type'] = 'error';
	header('Location: /?page=customer-login', true, 303);
	exit;
}

if (!isset($pages[$page])) {
	http_response_code(404);
	echo 'Halaman tidak ditemukan.';
	exit;
}

if (
	in_array($page, ['customer-cart', 'customer-checkout', 'customer-payment'], true)
	&& (($_SESSION['login'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'CUSTOMER')
) {
	$_SESSION['cart_message'] = 'Silakan login dengan akun customer terlebih dahulu untuk melanjutkan pesanan.';
	$_SESSION['cart_message_type'] = 'error';
	header('Location: /?page=customer-login', true, 303);
	exit;
}

require $pages[$page];
