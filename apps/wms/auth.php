<?php
// apps/wms/auth.php
session_name("WMS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// --- PROSES LOGIN ---
if(isset($_POST['btn_login'])) {
    
    // 1. Cek CSRF Token
    if (!verifyCSRFToken()) {
        header("Location: login.php?err=csrf");
        exit();
    }
    
    // 2. Sanitasi Input
    $user = sanitizeInput($_POST['username']);
    $pass = $_POST['password']; // Password tidak perlu disanitasi (biar karakter unik bisa masuk)

    if (empty($user) || empty($pass)) {
        header("Location: login.php?err=empty");
        exit();
    }
    
    // 3. Rate Limiting (Mencegah Brute Force)
    if (!checkRateLimit($user, 5, 300)) {
        logSecurityEvent('Rate limit exceeded for WMS user: ' . $user, 'WARNING');
        header("Location: login.php?err=ratelimit");
        exit();
    }

    // 4. Cek User di Database (PDO)
    $stmt = $pdo->prepare("SELECT * FROM wms_users WHERE username = ?");
    $stmt->execute([$user]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $login_sukses = false;

        // Cek Password (Prioritas Bcrypt, Fallback ke MD5 untuk auto-update)
        if (verifyPassword($pass, $data['password'])) {
            $login_sukses = true;
        } elseif (md5($pass) === $data['password']) {
            // Jika masih MD5, update ke Bcrypt sekarang juga
            $new_hash = hashPassword($pass);
            $upd = $pdo->prepare("UPDATE wms_users SET password = ? WHERE user_id = ?");
            $upd->execute([$new_hash, $data['user_id']]);
            $login_sukses = true;
        }

        if ($login_sukses) {
            // Regenerate Session ID untuk keamanan
            session_regenerate_id(true);
            
            // Catat Waktu & IP Login Terakhir
            $ip = $_SERVER['REMOTE_ADDR'];
            $log = $pdo->prepare("UPDATE wms_users SET login_count = login_count + 1, last_login = NOW(), last_ip = ? WHERE user_id = ?");
            $log->execute([$ip, $data['user_id']]);

            // Set Session Variables
            $_SESSION['wms_login']    = true;
            $_SESSION['wms_user_id']  = $data['user_id'];
            $_SESSION['wms_fullname'] = $data['fullname'];
            $_SESSION['wms_role']     = $data['role'];
            $_SESSION['wms_count']    = $data['login_count'] + 1;

            logSecurityEvent('WMS Login Success: ' . $user);
            header("Location: index.php");
            exit();
        }
    }
    
    logSecurityEvent('WMS Login Failed: ' . $user, 'WARNING');
    header("Location: login.php?err=1");
    exit();
}
?>