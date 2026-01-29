<?php
// 1. ISOLASI SESSION
session_name("HRIS_APP_SESSION");
session_start();

// Koneksi Database
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// Fungsi Get Real IP (Biar tembus Proxy/Cloudflare kalau nanti hosting)
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) { return $_SERVER['HTTP_CLIENT_IP']; }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { return $_SERVER['HTTP_X_FORWARDED_FOR']; }
    else { return $_SERVER['REMOTE_ADDR']; }
}

// --- LOGIN HR ADMIN ---
if(isset($_POST['login_hr'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // Cek User
    $stmt = $conn->prepare("SELECT * FROM hris_admins WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Verifikasi Password (Bcrypt)
        if(password_verify($pass, $data['password'])) {
            
            // 🔥 FITUR BARU: CATAT IP & WAKTU LOGIN 🔥
            $ip_address = getUserIP();
            $user_id = $data['id'];
            
            // Update Database: Simpan IP dan Jam Login Sekarang
            $log_stmt = $conn->prepare("UPDATE hris_admins SET last_ip = ?, last_login = NOW() WHERE id = ?");
            $log_stmt->bind_param("si", $ip_address, $user_id);
            $log_stmt->execute();

            // Set Session Login
            $_SESSION['hris_status'] = "login_sukses";
            $_SESSION['hris_user'] = $data['username'];
            $_SESSION['hris_name'] = $data['fullname'];
            
            header("Location: index.php");
            exit();
        }
    }
    
    // Kalau Gagal
    echo "<script>alert('Akses Ditolak! Username/Password Salah.'); window.location='login.php';</script>";
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); 
}
?>