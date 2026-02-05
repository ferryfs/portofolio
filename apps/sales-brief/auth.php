<?php
// apps/sales-brief/auth.php (FULL VERSION)

// 1. Session Khusus Sales Brief
session_name("SB_APP_SESSION");
session_start();

// 2. Load Config Pusat
require_once __DIR__ . '/../../config/database.php'; // Memuat $pdo
require_once __DIR__ . '/../../config/security.php'; // Memuat helper security

// --- REGISTER USER BARU ---
if(isset($_POST['register'])) {
    
    // Cek Token Keamanan
    if (!verifyCSRFToken()) {
        die("Security Alert: Invalid CSRF Token. Silakan refresh halaman.");
    }
    
    // Sanitasi Input
    $nama  = sanitizeInput($_POST['fullname']);
    $div   = sanitizeInput($_POST['division']);
    $email = sanitizeInput($_POST['email']);
    $user  = sanitizeInput($_POST['username']);
    $pass  = $_POST['password']; // Password jangan disanitasi (biar karakter unik aman)
    
    // Validasi Dasar
    if (empty($nama) || empty($user) || empty($pass)) {
        echo "<script>alert('Data tidak lengkap!'); window.location='landing.php?tab=register';</script>";
        exit();
    }

    // Cek Duplikat Username (Pakai PDO)
    $cek = safeGetOne($pdo, "SELECT id FROM sales_brief_users WHERE username = ?", [$user]);
    
    if($cek) {
        echo "<script>alert('Username sudah dipakai! Ganti yang lain.'); window.location='landing.php?tab=register';</script>";
    } else {
        // Hash Password & Insert
        $hash = hashPassword($pass);
        $sql = "INSERT INTO sales_brief_users (fullname, division, email, username, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        
        if(safeQuery($pdo, $sql, [$nama, $div, $email, $user, $hash])) {
            logSecurityEvent("SB Register Success: $user");
            echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='landing.php?tab=login';</script>";
        } else {
            logSecurityEvent("SB Register Error: $user", "ERROR");
            echo "<script>alert('Gagal Daftar! Hubungi IT.'); window.location='landing.php?tab=register';</script>";
        }
    }
}

// --- LOGIN PROSES ---
if(isset($_POST['login'])) {
    
    // Cek Token Keamanan
    if (!verifyCSRFToken()) {
        die("Security Alert: Invalid CSRF Token.");
    }
    
    $user = sanitizeInput($_POST['username']);
    $pass = $_POST['password'];

    // Rate Limiting (Cegah Brute Force)
    if (!checkRateLimit($user, 5, 300)) {
        echo "<script>alert('Terlalu banyak percobaan login! Tunggu 5 menit.'); window.location='landing.php?tab=login';</script>";
        exit();
    }

    // Ambil Data User (PDO)
    $data = safeGetOne($pdo, "SELECT * FROM sales_brief_users WHERE username = ?", [$user]);

    if ($data) {
        $valid = false;

        // 1. Cek Password Modern (Bcrypt)
        if (verifyPassword($pass, $data['password'])) {
            $valid = true;
        } 
        // 2. Fallback: Cek Password Lama (MD5) -> Jika cocok, update ke Bcrypt
        elseif (md5($pass) === $data['password']) {
            $newHash = hashPassword($pass);
            safeQuery($pdo, "UPDATE sales_brief_users SET password = ? WHERE id = ?", [$newHash, $data['id']]);
            $valid = true;
        }

        if ($valid) {
            // Login Sukses
            session_regenerate_id(true); // Ganti ID Session biar aman
            
            // Catat IP & Waktu Login
            safeQuery($pdo, "UPDATE sales_brief_users SET last_ip = ?, last_login = NOW() WHERE id = ?", [$_SERVER['REMOTE_ADDR'], $data['id']]);

            // Set Session Variable
            $_SESSION['sb_user'] = $data['username'];
            $_SESSION['sb_name'] = $data['fullname'];
            $_SESSION['sb_div']  = $data['division'];
            $_SESSION['sb_id']   = $data['id'];
            
            logSecurityEvent("SB Login Success: $user");
            header("Location: index.php");
            exit();
        }
    }
    
    // Login Gagal
    logSecurityEvent("SB Login Failed: $user", "WARNING");
    echo "<script>alert('Username atau Password Salah!'); window.location='landing.php?tab=login';</script>";
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); // Balik ke Portfolio Utama
    exit();
}
?>