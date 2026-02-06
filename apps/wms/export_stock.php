<?php
// apps/wms/export_stock.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak.");
}

require_once __DIR__ . '/../../config/database.php';

// Set Header untuk Download Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=WMS_Stock_Report_".date('Ymd_His').".xls");
header("Pragma: no-cache"); 
header("Expires: 0");

// Header Kolom (Pakai Tab sebagai delimiter)
echo "Product Code\tDescription\tUoM\tAvailable (F1)\tQuality (Q4)\tBlocked (B6)\tTotal Qty\n";

// Query Pivot Stok (Sama kayak di stock_master.php)
$sql = "SELECT 
            p.product_code, p.description, p.base_uom,
            SUM(CASE WHEN q.stock_type = 'F1' THEN q.qty ELSE 0 END) as stock_f1,
            SUM(CASE WHEN q.stock_type = 'Q4' THEN q.qty ELSE 0 END) as stock_q4,
            SUM(CASE WHEN q.stock_type = 'B6' THEN q.qty ELSE 0 END) as stock_b6,
            SUM(q.qty) as total_stock
        FROM wms_products p
        LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid
        GROUP BY p.product_uuid 
        ORDER BY p.product_code ASC";

$stmt = $pdo->query($sql);

while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Pastikan angka formatnya benar
    $f1 = (float)$r['stock_f1'];
    $q4 = (float)$r['stock_q4'];
    $b6 = (float)$r['stock_b6'];
    $tt = (float)$r['total_stock'];
    
    echo "{$r['product_code']}\t{$r['description']}\t{$r['base_uom']}\t$f1\t$q4\t$b6\t$tt\n";
}
?>