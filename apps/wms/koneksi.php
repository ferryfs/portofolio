<?php
// apps/wms/koneksi.php
// File ini menjembatani koneksi pusat dan fungsi log khusus WMS

require_once __DIR__ . '/../../config/database.php'; // Load $pdo dari config pusat

// FUNGSI PENCATAT LOG (AUDIT TRAIL)
// Menggunakan PDO Prepared Statement biar aman
function catat_log($pdo, $user, $type, $module, $desc) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $sql = "INSERT INTO wms_system_logs (log_date, user_id, action_type, module, description, ip_address) 
                VALUES (NOW(), ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user, $type, $module, $desc, $ip]);
    } catch (PDOException $e) {
        // Silent error: Jangan sampai error log bikin aplikasi macet
        error_log("WMS Log Error: " . $e->getMessage());
    }
}

// SESSION TIMEOUT: Check apakah session masih valid
// Dipanggil di halaman sensitif untuk prevent stale session
function wms_check_session_timeout($timeout_minutes = 480) {
    if (!isset($_SESSION['wms_login'])) return false;
    $last = $_SESSION['wms_last_activity'] ?? time();
    if ((time() - $last) > ($timeout_minutes * 60)) {
        session_destroy();
        return false;
    }
    $_SESSION['wms_last_activity'] = time();
    return true;
}

// Inisialisasi timestamp aktivitas saat login
if (isset($_SESSION['wms_login']) && !isset($_SESSION['wms_last_activity'])) {
    $_SESSION['wms_last_activity'] = time();
}

?>