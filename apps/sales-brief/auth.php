<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

// --- REGISTER ---
if(isset($_POST['register'])) {
    $nama = $_POST['fullname'];
    $div  = $_POST['division'];
    $email= $_POST['email'];
    $user = $_POST['username'];
    $pass = md5($_POST['password']);

    $cek = mysqli_query($conn, "SELECT * FROM sales_brief_users WHERE username='$user'");
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah dipakai!'); window.location='landing.php';</script>";
    } else {
        $sql = "INSERT INTO sales_brief_users (fullname, division, email, username, password) 
                VALUES ('$nama', '$div', '$email', '$user', '$pass')";
        if(mysqli_query($conn, $sql)) {
            // REVISI 1: Redirect ke landing tapi bawa kode khusus (?open=login)
            echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='landing.php?open=login';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// --- LOGIN ---
if(isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = md5($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM sales_brief_users WHERE username='$user' AND password='$pass'");
    if(mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['sb_user'] = $data['username'];
        $_SESSION['sb_name'] = $data['fullname'];
        $_SESSION['sb_div']  = $data['division'];
        header("Location: index.php");
    } else {
        // Kalau login gagal, balikin ke pop-up login lagi
        echo "<script>alert('Username atau Password Salah!'); window.location='landing.php?open=login';</script>";
    }
}

// --- LOGOUT (REVISI 2) ---
if(isset($_GET['logout'])) {
    session_destroy();
    // Balik ke Halaman Utama Portofolio (Naik 2 folder)
    header("Location: ../../index.php"); 
}
?>