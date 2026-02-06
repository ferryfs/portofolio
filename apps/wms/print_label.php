<?php
// apps/wms/print_label.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak.");
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Logic: Ambil HU dari URL kalau ada, kalau ga ada ambil Task terakhir
if(isset($_GET['hu_id'])) {
    $hu_id = sanitizeInput($_GET['hu_id']);
    $sql = "SELECT q.*, p.product_code, p.description 
            FROM wms_quants q 
            JOIN wms_products p ON q.product_uuid = p.product_uuid 
            WHERE q.hu_id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hu_id]);
} else {
    // Default: Last Task
    $sql = "SELECT t.*, p.product_code, p.description 
            FROM wms_warehouse_tasks t 
            JOIN wms_products p ON t.product_uuid = p.product_uuid 
            ORDER BY t.tanum DESC LIMIT 1";
    $stmt = $pdo->query($sql);
}

$d = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <title>Label Print</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #eee; }
        .label-container { 
            background: white; border: 2px solid #000; width: 10cm; height: 15cm; 
            margin: 20px auto; padding: 20px; text-align: left; box-sizing: border-box; 
        }
        h3 { margin-top: 0; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .barcode-box { text-align: center; margin: 20px 0; }
        .big-qty { font-size: 3rem; font-weight: bold; text-align: right; margin: 10px 0; }
        .meta { font-size: 1.2rem; line-height: 1.6; }
        @media print { 
            body { background: none; } 
            .no-print { display: none; } 
            .label-container { margin: 0; border: none; }
        }
        .btn-back { display:inline-block; margin-top:20px; text-decoration:none; padding:10px 20px; background:#333; color:#fff; border-radius:5px;}
    </style>
</head>
<body onload="window.print()">
    
    <?php if($d): 
        $hu = $d['hu_id'] ?? '-';
        $mat = $d['product_code'] ?? '-';
        $desc = $d['description'] ?? '-';
        $batch = $d['batch'] ?? '-';
        $qty = (float)($d['qty'] ?? 0);
        $bin = isset($d['dest_bin']) ? $d['dest_bin'] : ($d['lgpla'] ?? '-');
    ?>
    <div class="label-container">
        <h3>HANDLING UNIT (WMS)</h3>
        
        <div class="meta">
            <strong>Material:</strong><br>
            <span style="font-size: 1.5rem;"><?= $mat ?></span><br>
            <small><?= $desc ?></small>
        </div>
        
        <hr>

        <div class="meta">
            <div style="float:left;">
                <strong>Batch:</strong><br><?= $batch ?><br><br>
                <strong>Bin / Loc:</strong><br><?= $bin ?>
            </div>
            <div class="big-qty"><?= $qty ?> <span style="font-size:1rem;">PCS</span></div>
            <div style="clear:both;"></div>
        </div>

        <div class="barcode-box">
            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= $hu ?>&code=Code128&dpi=96&dataseparator=" alt="Barcode" style="width: 80%;">
            <br>
            <strong><?= $hu ?></strong>
        </div>
        
        <div style="text-align: center; font-size: 0.8rem; margin-top: 20px;">
            Printed: <?= date('d-M-Y H:i') ?>
        </div>
    </div>
    <?php else: ?>
        <p>Data Not Found</p>
    <?php endif; ?>
    
    <div class="no-print">
        <a href="index.php" class="btn-back">&laquo; Back to Dashboard</a>
    </div>
</body>
</html>