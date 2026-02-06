<?php
// apps/wms/get_po_items.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if(isset($_GET['po'])) {
    $po = $_GET['po'];
    
    // Ambil Item PO + Join ke Master Product
    // Di real case, tabelnya wms_po_items. 
    // Tapi karena ini simulasi dan tabel PO mungkin belum diisi, kita return array kosong atau data dummy kalau perlu.
    
    // VERSI QUERY NYATA (Aktifkan kalau tabel wms_po_items sudah ada isinya):
    /*
    $stmt = $pdo->prepare("
        SELECT i.*, p.product_code, p.description, p.base_uom 
        FROM wms_po_items i
        JOIN wms_products p ON i.product_uuid = p.product_uuid
        WHERE i.po_number = ?
    ");
    $stmt->execute([$po]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */

    // VERSI SIMULASI (Biar fitur jalan walau tabel kosong)
    // Kita ambil random product dari master data
    $stmt = $pdo->query("SELECT * FROM wms_products LIMIT 2");
    $items = [];
    $no = 1;
    while($prod = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = [
            'uuid' => $prod['product_uuid'],
            'code' => $prod['product_code'],
            'name' => $prod['description'],
            'qty_ord' => rand(10, 100), // Random Qty PO
            'uom' => $prod['base_uom']
        ];
    }
    
    echo json_encode($items);
} else {
    echo json_encode([]);
}
?>