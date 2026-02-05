<?php
// 🔥 SESSION KHUSUS WMS
session_name("WMS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/security.php';
include 'koneksi.php'; 

// Helper IP
function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

if(isset($_POST['btn_login'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        header("Location: login.php?err=csrf");
        exit();
    }
    
    $user = sanitizeInput($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($user) || empty($pass)) {
        header("Location: login.php?err=empty");
        exit();
    }
    
    // Rate limiting
    if (!checkRateLimit($user, 5, 300)) {
        logSecurityEvent('Rate limit exceeded for WMS user: ' . $user, 'WARNING');
        header("Location: login.php?err=ratelimit");
        exit();
    }

    // Pakai Prepared Statement (Anti SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM wms_users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $db_pass = $data['password'];
        $login_sukses = false;

        // Cek Password (Prioritas Bcrypt, fallback MD5)
        if (password_verify($pass, $db_pass)) {
            $login_sukses = true;
        } elseif (md5($pass) === $db_pass) {
            // Auto-upgrade MD5 ke Bcrypt
            $new_hash = hashPassword($pass);
            $stmt_upgrade = $conn->prepare("UPDATE wms_users SET password = ? WHERE user_id = ?");
            $stmt_upgrade->bind_param("si", $new_hash, $data['user_id']);
            $stmt_upgrade->execute();
            $login_sukses = true; // Match MD5
        }

        if ($login_sukses) {
            regenerateSessionId();
            
            // CATAT IP dengan prepared statement
            $ip = getUserIP();
            $uid = $data['user_id'];
            $stmt_update = $conn->prepare("UPDATE wms_users SET login_count = login_count + 1, last_login = NOW(), last_ip = ? WHERE user_id = ?");
            $stmt_update->bind_param("si", $ip, $uid);
            $stmt_update->execute();

            // SET SESSION
            $_SESSION['wms_login']    = true;
            $_SESSION['wms_user_id']  = $data['user_id'];
            $_SESSION['wms_fullname'] = $data['fullname'];
            $_SESSION['wms_role']     = $data['role'];
            $_SESSION['wms_count']    = $data['login_count'] + 1;

            logSecurityEvent('WMS user logged in: ' . $user, 'INFO');
            header("Location: index.php");
            exit();
        }
    }
    
    logSecurityEvent('Failed WMS login attempt for user: ' . $user, 'WARNING');
    // Login Gagal
    header("Location: login.php?err=1");
    exit();
}
?>