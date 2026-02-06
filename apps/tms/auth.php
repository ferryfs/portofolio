<?php
// apps/tms/auth.php (FIXED PDO)

session_name("TMS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// --- LOGIN PROSES ---
if (isset($_POST['btn_login'])) {
    
    // 1. Cek CSRF
    if (!verifyCSRFToken()) {
        header("Location: index.php?err=csrf");
        exit();
    }
    
    // 2. Sanitasi Input
    $user = sanitizeInput($_POST['username']);
    $pass = $_POST['password'];

    if (empty($user) || empty($pass)) {
        header("Location: index.php?err=empty");
        exit();
    }
    
    // 3. Rate Limiting
    if (!checkRateLimit($user, 5, 300)) {
        logSecurityEvent('Rate limit exceeded for user: ' . $user, 'WARNING');
        header("Location: index.php?err=ratelimit");
        exit();
    }

    // 4. Cek User (PDO)
    $row = safeGetOne($pdo, "SELECT * FROM tms_users WHERE username = ? AND status = 'active'", [$user]);

    if ($row) {
        $login_sukses = false;

        // Cek Password (Bcrypt > MD5)
        if (verifyPassword($pass, $row['password'])) {
            $login_sukses = true;
        } elseif (md5($pass) === $row['password']) {
            // Auto-upgrade MD5 ke Bcrypt
            $new_hash = hashPassword($pass);
            safeQuery($pdo, "UPDATE tms_users SET password = ? WHERE id = ?", [$new_hash, $row['id']]);
            $login_sukses = true;
        }

        if ($login_sukses) {
            session_regenerate_id(true);
            
            // Catat IP Login
            $ip = $_SERVER['REMOTE_ADDR'];
            safeQuery($pdo, "UPDATE tms_users SET last_ip = ?, last_login = NOW() WHERE id = ?", [$ip, $row['id']]);

            // Set Session
            $_SESSION['tms_status']    = "login";
            $_SESSION['tms_user_id']   = $row['id'];
            $_SESSION['tms_role']      = $row['role'];
            $_SESSION['tms_fullname']  = $row['fullname'];
            $_SESSION['tms_tenant_id'] = $row['tenant_id'];

            logSecurityEvent('TMS Login Success: ' . $user);
            header("Location: dashboard.php");
            exit();
        }
    }
    
    logSecurityEvent('TMS Login Failed: ' . $user, 'WARNING');
    header("Location: index.php?err=1");
    exit();
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); // Balik ke Portofolio Utama
    exit();
}
?>