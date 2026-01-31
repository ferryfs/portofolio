<?php
// ⚠️ DEPRECATED BUT MAINTAINED FOR BACKWARD COMPATIBILITY
// File ini masih digunakan oleh apps/ (TMS, WMS, dll)
// Jangan akses langsung, gunakan melalui include/require saja

if (basename($_SERVER['PHP_SELF']) === 'koneksi.php') {
    // Direct access di-block
    header("Location: index.php");
    exit();
}

// Load PDO configuration
require_once __DIR__ . '/config/database.php';

// 🛡️ BACKWARD COMPATIBILITY LAYER
// Untuk legacy code yang masih pakai MySQLi-style:
// Gunakan $pdo untuk prepared statements, bukan $conn

// Jangan buat $conn variable untuk avoid confusion
// Apps harus upgrade ke PDO prepared statements

error_log("DEPRECATED: koneksi.php masih diakses oleh: " . $_SERVER['PHP_SELF']);
?>
