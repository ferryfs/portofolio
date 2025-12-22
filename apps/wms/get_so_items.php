<?php
include '../../koneksi.php';

if(isset($_GET['so'])) {
    $so = $_GET['so'];
    
    // Ambil Item SO + Cek Total Stok Tersedia (Available Stock F1)
    $query = "
        SELECT 
            i.so_number, i.product_uuid, i.qty_ordered, 
            p.product_code, p.description, p.base_uom,
            COALESCE(SUM(q.qty), 0) as stock_available
        FROM wms_so_items i
        JOIN wms_products p ON i.product_uuid = p.product_uuid
        LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid AND q.stock_type = 'F1'
        WHERE i.so_number = '$so'
        GROUP BY i.so_item_id
    ";
    
    $result = mysqli_query($conn, $query);
    $items = [];
    
    while($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    echo json_encode($items);
}
?>