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
?>