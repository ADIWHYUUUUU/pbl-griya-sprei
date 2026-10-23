<?php

require_once 'database.php';

if (isset($pdo)) {
    echo "<h3>Koneksi ke database PostgreSQL Berhasil! 🎉</h3>";

    $stmt = $pdo->query("SELECT version()");
    $row = $stmt->fetch();

    echo "<p>Info Server: " . $row['version'] . "</p>";
} else {
    echo "<h3>Koneksi Gagal.</h3>";
}
