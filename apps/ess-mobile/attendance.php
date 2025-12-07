<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

if(!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik  = $_SESSION['ess_user'];
$nama = $_SESSION['ess_name'];
$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

// --- 1. PROSES CHECK IN ---
if(isset($_GET['type'])) {
    $type = $_GET['type']; // WFO atau WFH
    
    // Cek dulu udah absen belum hari ini?
    $cek = mysqli_query($conn, "SELECT * FROM ess_attendance WHERE employee_id='$nik' AND date_log='$today'");
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Anda sudah absen masuk hari ini!'); window.location='index.php';</script>";
    } else {
        $sql = "INSERT INTO ess_attendance (employee_id, fullname, type, check_in_time, location_status, date_log) 
                VALUES ('$nik', '$nama', '$type', '$now', 'Terdeteksi (GPS)', '$today')";
        if(mysqli_query($conn, $sql)) {
            echo "<script>alert('Berhasil Check-in $type!'); window.location='index.php';</script>";
        }
    }
}

// --- 2. PROSES CHECK OUT ---
if(isset($_POST['checkout'])) {
    $tasks = $_POST['tasks'];
    
    // Update data absen hari ini, isi jam pulang & tasks
    $sql = "UPDATE ess_attendance SET check_out_time='$now', tasks='$tasks' 
            WHERE employee_id='$nik' AND date_log='$today'";
            
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Berhasil Check-out! Terima kasih atas kerja kerasnya.'); window.location='index.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>