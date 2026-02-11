<?php
// apps/wms/get_so_items.php
// V9: OPTIMIZED AJAX HELPER (Left Join + Security)

// 1. Matikan Error Text (Biar JSON gak rusak)
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

// 2. Security Check & Header
if(!isset($_SESSION['wms_login'])) { 
    http_response_code(403); 
    exit(json_encode(['error' => 'Unauthorized'])); 
}

// 3. Clear Buffer (PENTING: Biar gak ada whitespace sebelum {json})
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// 4. Main Logic
if(isset($_GET['so'])) {
    $so = sanitizeInput($_GET['so']); // Sanitasi Input

    try {
        // Query Smart: Join SO Items dengan Stok Fisik
        // Logikanya: Ambil Item SO -> Cek ke Gudang -> Jumlahkan Stok yang F1 & Bukan di GI-ZONE
        $sql = "SELECT 
                    i.so_item_id,
                    i.product_uuid, 
                    i.qty_ordered, 
                    p.product_code, 
                    p.description,
                    COALESCE(SUM(q.qty), 0) as stock_available
                FROM wms_so_items i
                JOIN wms_products p ON i.product_uuid = p.product_uuid
                LEFT JOIN wms_quants q ON i.product_uuid = q.product_uuid 
                    AND q.stock_type = 'F1' 
                    AND q.lgpla != 'GI-ZONE' -- Jangan hitung stok yg sudah di area pengiriman/terbooking
                WHERE i.so_number = ?
                GROUP BY i.so_item_id -- Group per baris item SO
                ORDER BY p.product_code ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$so]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($data);

    } catch (Exception $e) {
        // Kalau error database, kirim array kosong biar UI gak hang
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>