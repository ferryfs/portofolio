<?php
// apps/wms/print_label.php
// V9: MASS LABEL PRINTING & AUDIT LOG
// Features: Support printing by HU, Task, or Full GR. Added System Audit Log.

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { exit("Access Denied."); }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$user = $_SESSION['wms_fullname'];
$labels = []; // Container untuk array data
$print_desc = "";

// 1. LOGIC PENGAMBILAN DATA (Flexible & Mass Print)
if(isset($_GET['gr'])) {
    // Ambil SEMUA HU dari satu dokumen GR
    $grNum = sanitizeInput($_GET['gr']);
    $sql = "SELECT q.hu_id, q.batch, q.qty, q.lgpla as bin, 
                   p.product_code, p.description, p.base_uom 
            FROM wms_quants q 
            JOIN wms_products p ON q.product_uuid = p.product_uuid 
            WHERE q.gr_ref = ?";
    $labels = safeGetAll($pdo, $sql, [$grNum]);
    $print_desc = "Mass printed " . count($labels) . " labels for GR: $grNum";

} elseif(isset($_GET['hu'])) {
    // Ambil dari Stok Fisik (wms_quants) 1 item
    $hu = sanitizeInput($_GET['hu']);
    $sql = "SELECT q.hu_id, q.batch, q.qty, q.lgpla as bin, 
                   p.product_code, p.description, p.base_uom 
            FROM wms_quants q 
            JOIN wms_products p ON q.product_uuid = p.product_uuid 
            WHERE q.hu_id = ?";
    $data = safeGetOne($pdo, $sql, [$hu]);
    if($data) $labels[] = $data;
    $print_desc = "Printed single label for HU: $hu";

} elseif(isset($_GET['task'])) {
    // Ambil dari Tugas Gudang (wms_warehouse_tasks) 1 item
    $task_id = sanitizeInput($_GET['task']);
    $sql = "SELECT t.hu_id, t.batch, t.qty, t.dest_bin as bin, 
                   p.product_code, p.description, p.base_uom 
            FROM wms_warehouse_tasks t 
            JOIN wms_products p ON t.product_uuid = p.product_uuid 
            WHERE t.tanum = ?";
    $data = safeGetOne($pdo, $sql, [$task_id]);
    if($data) $labels[] = $data;
    $print_desc = "Printed label from Task ID: $task_id";
}

// 2. LOG AUDIT PRINTING (Keamanan biar gak ada yang duplikat barcode sembarangan)
if(!empty($labels)) {
    safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) 
                     VALUES (?, 'PRINT_LABEL', 'PRINT', ?, ?, NOW())", 
                     [$user, $print_desc, $_SERVER['REMOTE_ADDR']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Label</title>
    <style>
        /* CSS KHUSUS PRINTER LABEL (4x6 Inch / 10x15 cm) */
        @page { size: 100mm 150mm; margin: 0; }
        
        body { font-family: 'Arial Narrow', Arial, sans-serif; margin: 0; padding: 0; background: #ccc; }
        
        .label-sheet {
            width: 100mm;
            height: 149mm; /* Sedikit kurang dari 150 biar ga page break kosong */
            background: white;
            padding: 5mm;
            box-sizing: border-box;
            border: 1px solid #999;
            margin: 20px auto;
            position: relative;
            page-break-after: always; /* 🔥 FIX: Otomatis ganti kertas tiap 1 label */
        }
        .label-sheet:last-child { page-break-after: auto; }

        h1 { margin: 0; font-size: 24pt; font-weight: 800; text-transform: uppercase; line-height: 1; }
        h2 { margin: 5px 0; font-size: 14pt; font-weight: normal; color: #333; }
        
        .zone-info { border-top: 3px solid #000; border-bottom: 3px solid #000; padding: 10px 0; margin: 15px 0; display: flex; justify-content: space-between; }
        .zone-box { width: 48%; }
        .label-title { font-size: 10pt; text-transform: uppercase; color: #666; font-weight: bold; }
        .label-val { font-size: 16pt; font-weight: bold; font-family: 'Courier New', monospace; }
        
        .big-qty { font-size: 40pt; font-weight: 900; text-align: right; line-height: 1; }
        .uom { font-size: 12pt; font-weight: bold; vertical-align: top; margin-left: 5px; }

        .barcode-area { text-align: center; margin-top: 20px; }
        .barcode-text { font-family: 'Courier New', monospace; font-size: 12pt; font-weight: bold; letter-spacing: 2px; }

        .footer { position: absolute; bottom: 5mm; left: 5mm; right: 5mm; text-align: center; font-size: 8pt; color: #666; border-top: 1px dashed #ccc; padding-top: 5px; }

        @media print {
            body { background: none; }
            .label-sheet { margin: 0; border: none; }
            .no-print { display: none !important; }
        }

        .btn-float { position: fixed; bottom: 20px; right: 20px; background: #333; color: white; padding: 15px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 1000;}
        .btn-float:hover { background: #000; }
    </style>
</head>
<body onload="window.print()">

    <?php if(!empty($labels)): ?>
        <?php foreach($labels as $d): ?>
        <div class="label-sheet">
            <div style="min-height: 80px;">
                <div class="label-title">Material ID</div>
                <h1><?= htmlspecialchars($d['product_code']) ?></h1>
                <h2><?= htmlspecialchars(substr($d['description'], 0, 40)) ?></h2>
            </div>

            <div class="zone-info">
                <div class="zone-box">
                    <div class="label-title">Batch / Lot</div>
                    <div class="label-val"><?= htmlspecialchars($d['batch']) ?></div>
                    <br>
                    <div class="label-title">Bin Location</div>
                    <div class="label-val" style="background:#000; color:#fff; padding:2px 5px; display: inline-block;"><?= htmlspecialchars($d['bin']) ?></div>
                </div>
                <div class="zone-box" style="text-align: right;">
                    <div class="label-title">Quantity</div>
                    <div class="big-qty">
                        <?= (float)$d['qty'] ?><span class="uom"><?= htmlspecialchars($d['base_uom']) ?></span>
                    </div>
                </div>
            </div>

            <div class="barcode-area">
                <div class="label-title" style="margin-bottom: 5px;">Handling Unit ID (SSCC)</div>
                <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= htmlspecialchars($d['hu_id']) ?>&code=Code128&dpi=150&imagetype=Png&rect=true" alt="Barcode" style="width: 90%; height: 60px;">
                <div class="barcode-text"><?= htmlspecialchars($d['hu_id']) ?></div>
            </div>

            <div class="footer">
                WMS GENERATED LABEL &bull; PRINTED: <?= date('Y-m-d H:i') ?> &bull; USER: <?= htmlspecialchars($user) ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding:50px;">
            <h1>DATA NOT FOUND</h1>
            <p>Invalid Parameters or Stock already moved/voided.</p>
        </div>
    <?php endif; ?>

    <a href="javascript:history.back()" class="btn-float no-print">&laquo; BACK</a>

</body>
</html>