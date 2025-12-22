<?php
include '../../koneksi.php';

// Logic: Ambil HU dari URL kalau ada (pilihan user), kalau ga ada ambil last task
if(isset($_GET['hu_id'])) {
    $hu_id = $_GET['hu_id'];
    $q = mysqli_query($conn, "SELECT q.*, p.product_code, p.description FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE q.hu_id = '$hu_id' LIMIT 1");
} else {
    // Default: Last Task
    $q = mysqli_query($conn, "SELECT t.*, p.product_code, p.description FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid ORDER BY t.tanum DESC LIMIT 1");
}
$d = mysqli_fetch_assoc($q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <title>Label</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        .label-container { border: 2px solid #000; width: 350px; margin: 20px auto; padding: 15px; text-align: left; }
        .barcode { height: 40px; background: repeating-linear-gradient(to right, black 0, black 2px, white 2px, white 5px); margin: 10px 0; }
        @media print { .no-print { display: none; } }
        .btn-back { display:inline-block; margin-top:20px; text-decoration:none; padding:10px; border:1px solid #333; color:#333; }
    </style>
</head>
<body onload="window.print()">
    <?php if($d): ?>
    <div class="label-container">
        <h3>HANDLING UNIT (SAP EWM)</h3>
        <strong>Material:</strong> <?= $d['product_code'] ?><br>
        <small><?= $d['description'] ?></small><br><br>
        <strong>HU ID:</strong> <?= $d['hu_id'] ?><br>
        <strong>Batch:</strong> <?= $d['batch'] ?><br>
        <strong>Bin:</strong> <?= isset($d['dest_bin']) ? $d['dest_bin'] : $d['lgpla'] ?><br>
        <h1 style="text-align: right; margin: 0;"><?= (float)$d['qty'] ?></h1>
        <div class="barcode"></div>
    </div>
    <?php else: ?>
        <p>Data Not Found</p>
    <?php endif; ?>
    
    <div class="no-print">
        <a href="index.php" class="btn-back">&laquo; Back to Monitor</a>
    </div>
</body>
</html>