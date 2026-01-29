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

if(isset($_GET['po'])) {
    $po = $_GET['po'];
    
    // Ambil Item PO + Join ke Master Product biar dapet Nama & Kode
    $query = "
        SELECT i.*, p.product_code, p.description, p.base_uom 
        FROM wms_po_items i
        JOIN wms_products p ON i.product_uuid = p.product_uuid
        WHERE i.po_number = '$po'
    ";
    
    $result = mysqli_query($conn, $query);
    $items = [];
    
    while($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    // Kirim data JSON ke Frontend
    echo json_encode($items);
}
?>