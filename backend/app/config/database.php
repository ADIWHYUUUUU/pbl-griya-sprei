<?php

/**
 * Fungsi sederhana untuk membaca file .env secara manual
 */
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Lewati komentar
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
        $_ENV[trim($name)] = trim($value);
    }
}

// Arahkan path ke file .env di root folder (sesuaikan jumlah '../../../../' dengan kedalaman folder file ini)
loadEnv(__DIR__ . '/../../.env'); 

// Ambil data dari .env
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_NAME') ?: 'pbl_siprei';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: ''; // Password aman tersembunyi di .env!

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<h3>Koneksi ke PostgreSQL Berhasil! 🎉</h3>";
    
    // Tes ambil versi PostgreSQL
    $stmt = $pdo->query("SELECT version()");
    $row = $stmt->fetch();
    echo "<p>Info Server: " . $row['version'] . "</p>";

} catch (PDOException $e) {
    echo "<h3>Koneksi Gagal: </h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}