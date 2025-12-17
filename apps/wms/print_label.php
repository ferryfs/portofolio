<?php
include '../../koneksi.php';
// Ambil data WT terakhir (Simulasi aja, biasanya by ID)
$q = mysqli_query($conn, "SELECT t.*, p.product_code, p.description FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid ORDER BY t.tanum DESC LIMIT 1");
$d = mysqli_fetch_assoc($q);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print HU Label</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eee; }
        .label-container { 
            width: 400px; height: 250px; background: white; border: 1px solid #000; 
            padding: 20px; margin: 50px auto; box-shadow: 5px 5px 15px rgba(0,0,0,0.2);
        }
        .header { font-size: 24px; font-weight: bold; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 10px; }
        .barcode { height: 50px; background: repeating-linear-gradient(to right, black 0, black 2px, white 2px, white 4px); margin: 10px 0; width: 80%; }
        .details { font-size: 14px; line-height: 1.5; }
        .big-qty { font-size: 40px; font-weight: bold; float: right; border: 2px solid black; padding: 5px 15px; }
    </style>
</head>
<body onload="window.print()">

    <div class="label-container">
        <div class="header">HANDLING UNIT (HU)</div>
        <div class="details">
            <strong>Product:</strong> <?= $d['product_code'] ?><br>
            <span><?= $d['description'] ?></span><br>
            <br>
            <strong>Batch:</strong> BATCH-<?= date('ymd') ?><br>
            <strong>Bin:</strong> <?= $d['dest_bin'] ?>
        </div>
        
        <span class="big-qty"><?= number_format($d['qty'],0) ?></span>
        
        <div style="clear:both;"></div>
        <br>
        <div class="barcode"></div>
        <small style="font-family: monospace;">HU-<?= rand(100000,999999) ?></small>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: blue; text-decoration: none;">&laquo; Back to Monitor</a>
    </div>

</body>
</html>