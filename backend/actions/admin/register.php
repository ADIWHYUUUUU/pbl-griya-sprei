<?php

require_once __DIR__ . '/../../app/controllers/auth_controller.php';

$auth = new AuthController();
$auth->registerAdmin();