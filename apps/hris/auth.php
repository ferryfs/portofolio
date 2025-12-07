<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// --- LOGIN HR ADMIN ---
if(isset($_POST['login_hr'])) {
    $user = $_POST['username'];
    $pass = md5($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM hris_admins WHERE username='$user' AND password='$pass'");
    
    if(mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['hris_user'] = $data['username'];
        $_SESSION['hris_name'] = $data['fullname'];
        
        header("Location: index.php");
    } else {
        echo "<script>alert('Akses Ditolak! Username/Password Salah.'); window.location='login.php';</script>";
    }
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); // Balik ke Portofolio Utama
}
?>