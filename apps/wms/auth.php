<?php
// 🔥 SESSION KHUSUS WMS
session_name("WMS_APP_SESSION");
session_start();

include 'koneksi.php'; 

// Helper IP
function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

if(isset($_POST['btn_login'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // Pakai Prepared Statement (Anti SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM wms_users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $db_pass = $data['password'];
        $login_sukses = false;

        // Cek Password (Prioritas Bcrypt)
        if (password_verify($pass, $db_pass)) {
            $login_sukses = true;
        } elseif (md5($pass) === $db_pass) {
            $login_sukses = true; // Fallback untuk user lama (jika ada)
        }

        if ($login_sukses) {
            // 🔥 CATAT IP & UPDATE LOGIN
            $ip = getUserIP();
            $uid = $data['user_id'];
            $conn->query("UPDATE wms_users SET login_count = login_count + 1, last_login = NOW(), last_ip = '$ip' WHERE user_id = '$uid'");

            // SET SESSION
            $_SESSION['wms_login']    = true;
            $_SESSION['wms_user_id']  = $data['user_id'];
            $_SESSION['wms_fullname'] = $data['fullname'];
            $_SESSION['wms_role']     = $data['role'];
            $_SESSION['wms_count']    = $data['login_count'] + 1;

            header("Location: index.php");
            exit();
        }
    }
    
    // Login Gagal
    header("Location: login.php?err=1");
    exit();
}
?>