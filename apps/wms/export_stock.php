<?php
// 🔥 1. PASANG SESSION DI PALING ATAS
session_name("WMS_APP_SESSION");
session_start();

// 🔥 2. CEK KEAMANAN (Opsional tapi PENTING)
// Biar orang gak bisa buka file ini langsung lewat URL tanpa login
if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}
include '../../koneksi.php';
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Stock_Report_".date('Ymd').".xls");

echo "Product\tDescription\tTotal Qty\n";

$stmt = $conn->prepare("SELECT p.product_code, p.description, SUM(q.qty) as total FROM wms_products p LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid GROUP BY p.product_uuid");
$stmt->execute();
$q = $stmt->get_result();
while($r = mysqli_fetch_assoc($q)) {
    echo "{$r['product_code']}\t{$r['description']}\t{$r['total']}\n";
}
$stmt->close();
?>