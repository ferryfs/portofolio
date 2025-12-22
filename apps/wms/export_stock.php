<?php
include '../../koneksi.php';
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Stock_Report_".date('Ymd').".xls");

echo "Product\tDescription\tTotal Qty\n";

$q = mysqli_query($conn, "SELECT p.product_code, p.description, SUM(q.qty) as total FROM wms_products p LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid GROUP BY p.product_uuid");

while($r = mysqli_fetch_assoc($q)) {
    echo "{$r['product_code']}\t{$r['description']}\t{$r['total']}\n";
}
?>