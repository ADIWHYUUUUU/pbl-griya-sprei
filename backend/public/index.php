<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/controllers/AuthController.php';

$action = $_GET['action'] ?? '';

$authController = new AuthController();

switch ($action) {

    case 'login':
        $authController->login();
        break;

    case 'register':
        $authController->register();
        break;

    case 'logout':
        $authController->logout();
        break;

    default:
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'message' => 'Backend Griya Sprei aktif.'
        ]);
        break;
}