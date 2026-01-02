<?php
session_start();

// PERBAIKAN 1: Include langsung nama filenya karena satu folder
include 'koneksi.php'; 

if(isset($_POST['btn_login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = md5($_POST['password']); 

    // Cek User
    $q = mysqli_query($conn, "SELECT * FROM wms_users WHERE username='$user' AND password='$pass'");
    $cek = mysqli_num_rows($q);

    if($cek > 0) {
        $data = mysqli_fetch_assoc($q);

        // SET SESSION
        $_SESSION['wms_login'] = true;
        $_SESSION['wms_user_id'] = $data['user_id'];
        $_SESSION['wms_fullname'] = $data['fullname'];
        $_SESSION['wms_role'] = $data['role'];
        $_SESSION['wms_count'] = $data['login_count'] + 1; 

        // UPDATE COUNTER
        mysqli_query($conn, "UPDATE wms_users SET login_count = login_count + 1, last_login = NOW() WHERE user_id = '{$data['user_id']}'");

        // PERBAIKAN 2: Redirect Sukses
        // Karena auth.php dan index.php (dashboard) ada di folder yang SAMA, langsung aja panggil index.php
        header("Location: index.php"); 
    } else {
        // PERBAIKAN 3: Redirect Gagal
        // Kalau gagal, harus mundur 2 folder ke belakang (ke root portofolio) buat balik ke login page
        header("Location: ../../index.php?err=1");
    }
}
?>