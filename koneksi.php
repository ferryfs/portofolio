<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP emang kosong
$db   = "portofolio_db"; // Pastikan ejaannya sama persis dgn di DBeaver

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}
?>