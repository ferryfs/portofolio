<?php
// apps/hris/auth.php (PDO VERSION)

session_name("HRIS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

// --- LOGIN HR ADMIN ---
if(isset($_POST['login_hr'])) {
    
    // Validasi CSRF
    if (!verifyCSRFToken()) {
        die("<h3>Akses Ditolak: Token Invalid.</h3><br><a href='login.php'>Kembali</a>");
    }

    $user = sanitizeInput($_POST['username']);
    $pass = $_POST['password'];

    // Rate Limiting
    if (!checkRateLimit($user, 5, 300)) {
        echo "<script>alert('Terlalu banyak percobaan! Tunggu 5 menit.'); window.location='login.php';</script>";
        exit();
    }

    // Ambil User (PDO)
    $data = safeGetOne($pdo, "SELECT * FROM hris_admins WHERE username = ?", [$user]);

    if ($data) {
        // Verifikasi Password (Bcrypt)
        if(verifyPassword($pass, $data['password'])) {
            session_regenerate_id(true);
            
            // Catat Login
            safeQuery($pdo, "UPDATE hris_admins SET last_ip = ?, last_login = NOW() WHERE id = ?", [$_SERVER['REMOTE_ADDR'], $data['id']]);

            // Set Session
            $_SESSION['hris_status'] = "login_sukses";
            $_SESSION['hris_user'] = $data['username'];
            $_SESSION['hris_name'] = $data['fullname'];
            $_SESSION['hris_id'] = $data['id'];
            
            logSecurityEvent("HRIS Login: $user");
            header("Location: index.php");
            exit();
        }
    }
    
    logSecurityEvent("HRIS Login Failed: $user", "WARNING");
    echo "<script>alert('Username atau Password Salah!'); window.location='login.php';</script>";
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}
?>