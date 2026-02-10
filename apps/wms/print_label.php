<?php
// apps/wms/print_label.php
// V8: FLEXIBLE LABEL PRINTING (HU / TASK / BATCH)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { exit("Access Denied."); }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$d = null; // Data container

// 1. LOGIC PENGAMBILAN DATA (Flexible)
// Bisa dari HU_ID (Stok Fisik) atau TASK_ID (Tugas Gudang)

if(isset($_GET['hu'])) {
    // Ambil dari Stok Fisik (wms_quants)
    $hu = sanitizeInput($_GET['hu']);
    $sql = "SELECT q.hu_id, q.batch, q.qty, q.lgpla as bin, 
                   p.product_code, p.description, p.base_uom 
            FROM wms_quants q 
            JOIN wms_products p ON q.product_uuid = p.product_uuid 
            WHERE q.hu_id = ?";
    $d = safeGetOne($pdo, $sql, [$hu]);

} elseif(isset($_GET['task'])) {
    // Ambil dari Tugas Gudang (wms_warehouse_tasks)
    $task_id = sanitizeInput($_GET['task']);
    $sql = "SELECT t.hu_id, t.batch, t.qty, t.dest_bin as bin, 
                   p.product_code, p.description, p.base_uom 
            FROM wms_warehouse_tasks t 
            JOIN wms_products p ON t.product_uuid = p.product_uuid 
            WHERE t.tanum = ?";
    $d = safeGetOne($pdo, $sql, [$task_id]);

} else {
    // Fallback: Ambil Task Terakhir user ini (Biar ga blank)
    // Berguna kalau habis confirm task terus lupa ID-nya
    $user = $_SESSION['wms_fullname'];
    // Cari di audit log (V8 Feature) biar akurat siapa yg kerja
    $log = safeGetOne($pdo, "SELECT hu_id FROM wms_stock_movements WHERE user=? ORDER BY move_id DESC LIMIT 1", [$user]);
    
    if($log) {
        $hu = $log['hu_id'];
        // Re-query stock data
        $d = safeGetOne($pdo, "SELECT q.hu_id, q.batch, q.qty, q.lgpla as bin, p.product_code, p.description, p.base_uom 
                               FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid 
                               WHERE q.hu_id = ?", [$hu]);
    }
}

// 2. LOG AUDIT PRINTING (Optional: Uncomment kalau butuh log kertas)
// safeQuery($pdo, "INSERT INTO wms_system_logs ..."); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Label <?= $d['hu_id'] ?? 'ERROR' ?></title>
    <style>
        /* CSS KHUSUS PRINTER LABEL (4x6 Inch / 10x15 cm) */
        @page { size: 100mm 150mm; margin: 0; }
        
        body { font-family: 'Arial Narrow', Arial, sans-serif; margin: 0; padding: 0; background: #ccc; }
        
        .label-sheet {
            width: 100mm;
            height: 149mm; /* Sedikit kurang dari 150 biar ga page break */
            background: white;
            padding: 5mm;
            box-sizing: border-box;
            border: 1px solid #999;
            margin: 20px auto;
            position: relative;
        }

        h1 { margin: 0; font-size: 24pt; font-weight: 800; text-transform: uppercase; line-height: 1; }
        h2 { margin: 5px 0; font-size: 14pt; font-weight: normal; color: #333; }
        
        .zone-info { border-top: 3px solid #000; border-bottom: 3px solid #000; padding: 10px 0; margin: 15px 0; display: flex; justify-content: space-between; }
        .zone-box { width: 48%; }
        .label-title { font-size: 10pt; text-transform: uppercase; color: #666; font-weight: bold; }
        .label-val { font-size: 16pt; font-weight: bold; font-family: 'Courier New', monospace; }
        
        .big-qty { font-size: 40pt; font-weight: 900; text-align: right; }
        .uom { font-size: 12pt; font-weight: bold; vertical-align: top; }

        .barcode-area { text-align: center; margin-top: 20px; }
        .barcode-text { font-family: 'Courier New', monospace; font-size: 12pt; font-weight: bold; letter-spacing: 2px; }

        .footer { position: absolute; bottom: 5mm; left: 5mm; right: 5mm; text-align: center; font-size: 8pt; color: #666; border-top: 1px dashed #ccc; padding-top: 5px; }

        @media print {
            body { background: none; }
            .label-sheet { margin: 0; border: none; }
            .no-print { display: none !important; }
        }

        /* Tombol Balik */
        .btn-float { position: fixed; bottom: 20px; right: 20px; background: #333; color: white; padding: 15px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn-float:hover { background: #000; }
    </style>
</head>
<body onload="window.print()">

    <?php if($d): ?>
    <div class="label-sheet">
        <div style="min-height: 80px;">
            <div class="label-title">Material ID</div>
            <h1><?= $d['product_code'] ?></h1>
            <h2><?= substr($d['description'], 0, 40) ?></h2>
        </div>

        <div class="zone-info">
            <div class="zone-box">
                <div class="label-title">Batch / Lot</div>
                <div class="label-val"><?= $d['batch'] ?></div>
                <br>
                <div class="label-title">Bin Location</div>
                <div class="label-val" style="background:#000; color:#fff; padding:2px 5px;"><?= $d['bin'] ?></div>
            </div>
            <div class="zone-box" style="text-align: right;">
                <div class="label-title">Quantity</div>
                <div class="big-qty">
                    <?= (float)$d['qty'] ?><span class="uom"><?= $d['base_uom'] ?></span>
                </div>
            </div>
        </div>

        <div class="barcode-area">
            <div class="label-title" style="margin-bottom: 5px;">Handling Unit ID (SSCC)</div>
            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= $d['hu_id'] ?>&code=Code128&dpi=150&imagetype=Png&rect=true" alt="Barcode" style="width: 90%; height: 60px;">
            <div class="barcode-text"><?= $d['hu_id'] ?></div>
        </div>

        <div class="footer">
            WMS GENERATED LABEL &bull; PRINTED: <?= date('Y-m-d H:i') ?> &bull; USER: <?= $_SESSION['wms_fullname'] ?>
        </div>
    </div>
    <?php else: ?>
        <div style="text-align:center; padding:50px;">
            <h1>DATA NOT FOUND</h1>
            <p>Invalid HU ID or Task ID.</p>
        </div>
    <?php endif; ?>

    <a href="javascript:history.back()" class="btn-float no-print">&laquo; BACK</a>

</body>
</html>