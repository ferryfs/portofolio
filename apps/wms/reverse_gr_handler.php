<?php
// apps/wms/reverse_gr_handler.php
// V1.0: THE VOID ENGINE - GR Reversal System

session_name("WMS_APP_SESSION");
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['wms_login'])) {
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized access.']); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user_id = $_SESSION['wms_fullname'] ?? 'System';
$gr_number = sanitizeInput($_POST['gr_number'] ?? '');

if(empty($gr_number)) {
    echo json_encode(['status'=>'error', 'message'=>'GR Number is required.']); exit;
}

try {
    $pdo->beginTransaction();

    // 1. CEK VALIDITAS & KEAMANAN
    // Kita ambil data items dari GR ini
    $gr_items = safeGetAll($pdo, "SELECT * FROM wms_gr_items WHERE gr_number = ?", [$gr_number]);
    
    if(!$gr_items) throw new Exception("Document $gr_number not found in records.");

    // 🔥 SECURITY GUARD: Cek apakah operator sudah kerja
    foreach($gr_items as $item) {
        if($item['qty_actual_good'] > 0) {
            throw new Exception("CRITICAL: Operator has already started Putaway for this GR. Reversal blocked.");
        }
    }

    // 2. PROSES ROLLBACK PO
    foreach($gr_items as $item) {
        $total_to_void = (float)$item['qty_good'] + (float)$item['qty_damaged'];
        
        // Kurangi received_qty di PO Line
        safeQuery($pdo, "UPDATE wms_po_items 
                         SET received_qty = received_qty - ?, 
                             status = 'OPEN' 
                         WHERE po_item_id = ?", [$total_to_void, $item['po_item_id']]);
    }

    // Ambil nomor PO untuk balikin status Header
    $gr_header = safeGetOne($pdo, "SELECT po_number FROM wms_gr_header WHERE gr_number = ?", [$gr_number]);
    $po_number = $gr_header['po_number'];
    
    safeQuery($pdo, "UPDATE wms_po_header SET status = 'OPEN' WHERE po_number = ?", [$po_number]);

    // 3. WIPE OUT PHYSICAL RECORDS (Cleanup)
    
    // A. Hapus Task Putaway yang masih OPEN
    safeQuery($pdo, "DELETE FROM wms_warehouse_tasks 
                     WHERE hu_id IN (SELECT hu_id FROM wms_quants WHERE gr_ref = ?) 
                     AND status = 'OPEN'", [$gr_number]);

    // B. Hapus Stock di Staging (GR-ZONE & BLOCK-ZONE)
    safeQuery($pdo, "DELETE FROM wms_quants WHERE gr_ref = ?", [$gr_number]);

    // C. Hapus Data GR (Header & Items)
    safeQuery($pdo, "DELETE FROM wms_gr_items WHERE gr_number = ?", [$gr_number]);
    safeQuery($pdo, "DELETE FROM wms_gr_header WHERE gr_number = ?", [$gr_number]);

    // 4. AUDIT LOGGING
    $desc = "REVERSE GR: $gr_number for PO $po_number. All staging stock and tasks destroyed.";
    safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) 
                     VALUES (?, 'INBOUND', 'REVERSE', ?, ?, NOW())", 
                     [$user_id, $desc, $_SERVER['REMOTE_ADDR']]);

    // Beri notif ke Admin Feed
    safeQuery($pdo, "INSERT INTO wms_inbound_notif (po_number, message, severity, created_at) 
                     VALUES (?, ?, 'DANGER', NOW())", 
                     [$po_number, "WARNING: GR $gr_number was REVERSED by $user_id. Check PO balance!"]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => "Document $gr_number successfully voided."]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}