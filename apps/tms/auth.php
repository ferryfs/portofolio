<?php
// 🔥 SESSION KHUSUS TMS
session_name("TMS_APP_SESSION");
session_start();

// Koneksi Database
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if (!$conn) { die("Koneksi Database Gagal: " . mysqli_connect_error()); }

// Helper IP
function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

if (isset($_POST['btn_login'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // Pakai Prepared Statement (Anti SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM tms_users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $db_pass = $row['password'];
        $login_sukses = false;

        // Cek Password (Bcrypt Prioritas, MD5 Fallback, Plain Text Fallback)
        if (password_verify($pass, $db_pass)) {
            $login_sukses = true; // Match Bcrypt
        } elseif (md5($pass) === $db_pass) {
            $login_sukses = true; // Match MD5
        } elseif ($pass === $db_pass) {
            $login_sukses = true; // Match Plain (Legacy)
        }

        if ($login_sukses) {
            // 🔥 CATAT IP & WAKTU LOGIN
            $ip = getUserIP();
            $uid = $row['id'];
            $conn->query("UPDATE tms_users SET last_ip='$ip', last_login=NOW() WHERE id='$uid'");

            // Set Session Khusus TMS
            $_SESSION['tms_status']    = "login";
            $_SESSION['tms_user_id']   = $row['id'];
            $_SESSION['tms_role']      = $row['role'];
            $_SESSION['tms_fullname']  = $row['fullname'];
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

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); 
}
?>