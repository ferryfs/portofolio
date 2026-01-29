<?php
// DETEKSI SERVER
$server_name = $_SERVER['SERVER_NAME'];

if ($server_name == 'localhost') {
    // 🏠 SETTINGAN LOCALHOST (XAMPP)
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "portofolio_db"; // Sesuaikan nama DB lokal
} else {
    // 🌐 SETTINGAN HOSTING (Nanti diisi pas udah beli hosting)
    $host = "localhost"; // Biasanya tetap localhost
    $user = "u123456_user_hosting"; // Nanti dapet dari cPanel
    $pass = "password_hosting_yang_rumit";
    $db   = "u123456_nama_db_hosting";
}

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// DEFINISI BASE URL (PENTING BUAT LINK & GAMBAR)
// Biar gak usah ngetik http://localhost/portofolio manual
if ($server_name == 'localhost') {
    define('BASE_URL', 'http://localhost/portofolio/');
} else {
    define('BASE_URL', 'https://ferryfernando.com/'); // Ganti nama domain lu nanti
}
?>