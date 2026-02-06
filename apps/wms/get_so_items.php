<?php
// apps/wms/get_so_items.php
session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) exit(json_encode([]));
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if(isset($_GET['so'])) {
    $so = $_GET['so'];
    // Logic: Ambil item SO + Cek stok F1 di gudang (selain GI-ZONE)
    $sql = "SELECT 
            i.so_number, i.product_uuid, i.qty_ordered, 
            p.product_code, p.description, p.base_uom,
            COALESCE((SELECT SUM(q.qty) FROM wms_quants q WHERE q.product_uuid = p.product_uuid AND q.stock_type = 'F1' AND q.lgpla != 'GI-ZONE'), 0) as stock_available
            FROM wms_so_items i 
            JOIN wms_products p ON i.product_uuid = p.product_uuid 
            WHERE i.so_number = ? 
            GROUP BY i.so_item_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$so]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}
?>