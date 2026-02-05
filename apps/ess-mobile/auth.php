<?php
// apps/ess-mobile/auth.php

session_name("ESS_PORTAL_SESSION");
session_start();

// Load Config Pusat
require_once __DIR__ . '/../../config/database.php'; // Memuat $pdo
require_once __DIR__ . '/../../config/security.php'; // Memuat fungsi security

// --- 1. REGISTER ---
if(isset($_POST['register'])) {
    if (!verifyCSRFToken()) die("CSRF Token Invalid");

    $nama  = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $role  = sanitizeInput($_POST['role']);
    $div   = sanitizeInput($_POST['division'] ?? 'General');
    $nik   = sanitizeInput($_POST['employee_id']);
    $pass  = $_POST['password'];

    // Cek duplikat pakai PDO
    $cek = safeGetOne($pdo, "SELECT id FROM ess_users WHERE employee_id = ?", [$nik]);
    
    if($cek) {
        echo "<script>alert('ID Karyawan sudah terdaftar!'); window.location='landing.php';</script>";
    } else {
        $hash = hashPassword($pass);
        $sql = "INSERT INTO ess_users (fullname, email, division, employee_id, password, role, annual_leave_quota, created_at) VALUES (?, ?, ?, ?, ?, ?, 12, NOW())";
        
        if(safeQuery($pdo, $sql, [$nama, $email, $div, $nik, $hash, $role])) {
            logSecurityEvent("Register Success: $nik");
            echo "<script>alert('Registrasi Berhasil!'); window.location='landing.php';</script>";
        } else {
            echo "<script>alert('Gagal Daftar!'); window.location='landing.php';</script>";
        }
    }
}

// --- 2. LOGIN (FIXED) ---
if(isset($_POST['login'])) {
    // Validasi CSRF
    if (!verifyCSRFToken()) {
        die("<h3>Security Alert: Invalid Token.</h3><br><a href='landing.php'>Kembali</a>");
    }

    $nik  = sanitizeInput($_POST['employee_id']);
    $pass = $_POST['password'];

    // Rate Limit
    if (!checkRateLimit($nik)) {
        echo "<script>alert('Terlalu banyak percobaan! Tunggu 5 menit.'); window.location='landing.php';</script>";
        exit();
    }

    // Ambil User pakai PDO
    $user = safeGetOne($pdo, "SELECT * FROM ess_users WHERE employee_id = ? LIMIT 1", [$nik]);

    if ($user) {
        // Cek Password (Verify Hash)
        if (verifyPassword($pass, $user['password'])) {
            
            // Login Sukses
            session_regenerate_id(true);
            $_SESSION['ess_user'] = $user['employee_id'];
            $_SESSION['ess_name'] = $user['fullname'];
            $_SESSION['ess_role'] = $user['role'];
            $_SESSION['ess_div']  = $user['division'];
            $_SESSION['ess_id']   = $user['id'];
            $_SESSION['LAST_ACTIVITY'] = time();

            // Update Log Login
            safeQuery($pdo, "UPDATE ess_users SET last_ip = ?, last_login = NOW() WHERE id = ?", [$_SERVER['REMOTE_ADDR'], $user['id']]);
            
            logSecurityEvent("Login Success: $nik");
            header("Location: index.php");
            exit();
        }
    }

    logSecurityEvent("Login Failed: $nik", "WARNING");
    echo "<script>alert('ID atau Password Salah!'); window.location='landing.php';</script>";
}

// --- 3. LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); // Balik ke Portfolio Utama
    exit();
}
?>