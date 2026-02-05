<?php
// 🔥 SESSION KHUSUS TMS
session_name("TMS_APP_SESSION");
session_start();

// Load security helpers
require_once __DIR__ . '/../../config/security.php';

// Koneksi Database
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if (!$conn) { die("Koneksi Database Gagal: " . mysqli_connect_error()); }

// Helper IP
function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

if (isset($_POST['btn_login'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        header("Location: index.php?err=csrf");
        exit();
    }
    
    $user = sanitizeInput($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($user) || empty($pass)) {
        header("Location: index.php?err=empty");
        exit();
    }
    
    // Rate limiting
    if (!checkRateLimit($user, 5, 300)) {
        logSecurityEvent('Rate limit exceeded for user: ' . $user, 'WARNING');
        header("Location: index.php?err=ratelimit");
        exit();
    }

    // Pakai Prepared Statement (Anti SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM tms_users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $db_pass = $row['password'];
        $login_sukses = false;

        // Cek Password (Bcrypt Prioritas, MD5 Fallback untuk migrasi)
        if (password_verify($pass, $db_pass)) {
            $login_sukses = true; // Match Bcrypt
        } elseif (md5($pass) === $db_pass) {
            // Auto-upgrade MD5 ke Bcrypt
            $new_hash = hashPassword($pass);
            $stmt_upgrade = $conn->prepare("UPDATE tms_users SET password = ? WHERE id = ?");
            $stmt_upgrade->bind_param("si", $new_hash, $row['id']);
            $stmt_upgrade->execute();
            $login_sukses = true; // Match MD5
        }

        if ($login_sukses) {
            regenerateSessionId();
            
            // CATAT IP dengan prepared statement
            $ip = getUserIP();
            $uid = $row['id'];
            $stmt_update = $conn->prepare("UPDATE tms_users SET last_ip = ?, last_login = NOW() WHERE id = ?");
            $stmt_update->bind_param("si", $ip, $uid);
            $stmt_update->execute();

            // Set Session Khusus TMS
            $_SESSION['tms_status']    = "login";
            $_SESSION['tms_user_id']   = $row['id'];
            $_SESSION['tms_role']      = $row['role'];
            $_SESSION['tms_fullname']  = $row['fullname'];
            $_SESSION['tms_tenant_id'] = $row['tenant_id'];

            logSecurityEvent('TMS user logged in: ' . $user, 'INFO');
            // Login Sukses -> Lempar ke Dashboard
            header("Location: dashboard.php");
            exit();
        }
    }
    
    logSecurityEvent('Failed TMS login attempt for user: ' . $user, 'WARNING');
    // Login Gagal
    header("Location: index.php?err=1");
    exit();
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    logSecurityEvent('TMS user logged out: ' . ($_SESSION['tms_fullname'] ?? 'unknown'), 'INFO');
    destroySession();
    header("Location: ../../index.php");
    exit();
}
?>