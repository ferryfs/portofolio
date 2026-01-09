<?php
session_start();

// 🔥 PERBAIKAN DISINI: Naik 2 folder ke atas (../../)
include '../../koneksi.php'; 

if (isset($_POST['btn_login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    // Cek ke tabel tms_users
    $query = "SELECT * FROM tms_users WHERE username = '$user' AND status = 'active'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Verifikasi Password (di database dummy tadi masih plain text 'admin123')
        if ($pass == $row['password']) {
            // Set Session Khusus TMS
            $_SESSION['tms_status'] = "login";
            $_SESSION['tms_user_id'] = $row['id'];
            $_SESSION['tms_role'] = $row['role'];
            $_SESSION['tms_fullname'] = $row['fullname'];
            $_SESSION['tms_tenant_id'] = $row['tenant_id'];

            // Login Sukses -> Lempar ke Dashboard
            header("Location: dashboard.php");
            exit();
        }
    }
    
    // Login Gagal
    header("Location: index.php?err=1");
    exit();
}
?>