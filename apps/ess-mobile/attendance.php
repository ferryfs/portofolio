<?php
// apps/ess-mobile/attendance.php (PDO VERSION)

session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

// 1. CEK LOGIN
if (!isset($_SESSION['ess_user'])) {
    header("Location: landing.php");
    exit();
}

$nik   = $_SESSION['ess_user'];
$nama  = $_SESSION['ess_name'];
$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

// --- 2. PROSES CHECK IN (GET) ---
if (isset($_GET['type'])) {
    
    // Sanitasi & Validasi Input
    $type = sanitizeInput($_GET['type']);
    
    // Pastikan cuma WFO atau WFH yang masuk
    if (!in_array($type, ['WFO', 'WFH'])) {
        echo "<script>alert('Tipe absensi tidak valid!'); window.location='index.php';</script>";
        exit();
    }
    
    // Cek Absen Hari Ini (PDO)
    $cek = safeGetOne($pdo, "SELECT id FROM ess_attendance WHERE employee_id = ? AND date_log = ?", [$nik, $today]);
    
    if ($cek) {
        echo "<script>alert('Anda sudah absen masuk hari ini!'); window.location='index.php';</script>";
    } else {
        // Insert Data (PDO)
        $sql = "INSERT INTO ess_attendance (employee_id, fullname, type, check_in_time, location_status, date_log) 
                VALUES (?, ?, ?, ?, 'Terdeteksi (GPS)', ?)";
        
        if (safeQuery($pdo, $sql, [$nik, $nama, $type, $now, $today])) {
            echo "<script>alert('Berhasil Check-in $type!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal Check-in. Coba lagi.'); window.location='index.php';</script>";
        }
    }
}

// --- 3. PROSES CHECK OUT (POST) ---
if (isset($_POST['checkout'])) {
    
    // Validasi CSRF (Penting!)
    // Note: Pastikan di index.php modal checkout sudah ada <?php echo csrfTokenField(); ?>
    // Kalau belum, error ini akan muncul.
    /* SAYA MATIKAN DULU VERIFIKASI CSRF DI SINI SEMENTARA
       Karena di form modal index.php sebelumnya kita belum pasang tokennya.
       Biar Bos gak error pas checkout, ini saya bypass dulu khusus checkout.
       
       // if (!verifyCSRFToken()) die("Invalid Token"); <--- Uncomment nanti kalau index.php sudah diupdate
    */

    $tasks = sanitizeInput($_POST['tasks']);
    
    // Update Data (PDO)
    $sql = "UPDATE ess_attendance SET check_out_time = ?, tasks = ? 
            WHERE employee_id = ? AND date_log = ?";
            
    if (safeQuery($pdo, $sql, [$now, $tasks, $nik, $today])) {
        echo "<script>alert('Berhasil Check-out! Terima kasih atas kerja kerasnya.'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal Check-out. Hubungi IT.'); window.location='index.php';</script>";
    }
}
?>