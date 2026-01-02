<?php
// SESUAIKAN DENGAN SERVER LO
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portofolio_db"; // Ganti nama database lo kalo beda

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// ==========================================
// FUNGSI PENCATAT LOG (AUDIT TRAIL)
// ==========================================
// Dipanggil setiap ada aksi penting (Create/Update/Delete)
// ==========================================
function catat_log($conn, $user, $type, $module, $desc) {
    // Ambil IP Address User
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Bersihkan input biar aman dari SQL Injection
    $user   = mysqli_real_escape_string($conn, $user);
    $type   = mysqli_real_escape_string($conn, $type);
    $module = mysqli_real_escape_string($conn, $module);
    $desc   = mysqli_real_escape_string($conn, $desc);
    
    // Insert ke tabel wms_system_logs
    // Pastikan tabel wms_system_logs SUDAH DIBUAT di database lo!
    $sql = "INSERT INTO wms_system_logs (log_date, user_id, action_type, module, description, ip_address) 
            VALUES (NOW(), '$user', '$type', '$module', '$desc', '$ip')";
            
    // Eksekusi (Silent error, biar gak ganggu user kalau log gagal)
    mysqli_query($conn, $sql);
}
?>