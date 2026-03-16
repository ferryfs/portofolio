<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik   = $_SESSION['ess_user'];
$nama  = $_SESSION['ess_name'];
$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

// CHECK IN
if (isset($_GET['type'])) {
    $type = sanitizeInput($_GET['type']);
    if (!in_array($type, ['WFO', 'WFH'])) { echo "<script>alert('Invalid!'); window.location='index.php';</script>"; exit(); }
    $cek = safeGetOne($pdo, "SELECT id FROM ess_attendance WHERE employee_id=? AND date_log=?", [$nik, $today]);
    if ($cek) {
        echo "<script>alert('Sudah absen masuk hari ini!'); window.location='index.php';</script>";
    } else {
        safeQuery($pdo, "INSERT INTO ess_attendance (employee_id,fullname,type,check_in_time,location_status,date_log) VALUES (?,?,?,?,'Terdeteksi (GPS)',?)", [$nik,$nama,$type,$now,$today]);
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())", [$nik,'checkin','Check-in '.$type.' berhasil pukul '.date('H:i')]);
        echo "<script>alert('Berhasil Check-in $type!'); window.location='index.php';</script>";
    }
}

// CHECK OUT — CSRF aktif sekarang (form sudah dipasang token)
if (isset($_POST['checkout'])) {
    if (!verifyCSRFToken()) { echo "<script>alert('Security Error!'); window.location='index.php';</script>"; exit(); }
    $tasks = sanitizeInput($_POST['tasks']);
    safeQuery($pdo, "UPDATE ess_attendance SET check_out_time=?, tasks=? WHERE employee_id=? AND date_log=?", [$now,$tasks,$nik,$today]);
    safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())", [$nik,'checkout','Check-out berhasil pukul '.date('H:i').'. Selamat beristirahat!']);
    echo "<script>alert('Berhasil Check-out!'); window.location='index.php';</script>";
}
?>
