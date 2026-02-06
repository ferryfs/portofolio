<?php
// apps/wms/rf_scanner.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { exit("Akses Ditolak."); }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$msg = ""; $msg_type = "";
$current_page = isset($_GET['page']) ? $_GET['page'] : 'task'; 

// --- LOGIC CONFIRM TASK ---
if(isset($_POST['btn_exec'])) {
    // Validasi input sederhana (RF Scanner biasanya ga support CSRF ribet, tapi kita pakai sanitasi)
    $task_id = sanitizeInput($_POST['task_id']);
    $scanned_bin = strtoupper(trim($_POST['scan_check']));

    // Ambil Data Task
    $task = safeGetOne($pdo, "SELECT * FROM wms_warehouse_tasks WHERE tanum = ? AND status='OPEN'", [$task_id]);

    if($task) {
        $prod_uuid = $task['product_uuid'];
        $qty       = (float)$task['qty'];
        $batch     = $task['batch'] ?? '-';
        $hu_id     = $task['hu_id'] ?? '';
        $type      = $task['process_type']; 
        
        $success = false;

        try {
            $pdo->beginTransaction();

            if($type == 'PUTAWAY') {
                // Barang masuk ke Rak Scan
                $actual_dest = $scanned_bin; 
                safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) VALUES (?, ?, ?, ?, ?, NOW())", [$prod_uuid, $actual_dest, $batch, $hu_id, $qty]);
                safeQuery($pdo, "UPDATE wms_warehouse_tasks SET dest_bin=? WHERE tanum=?", [$actual_dest, $task_id]);
                $success = true;
            
            } else if($type == 'PICKING') {
                $target_source = strtoupper($task['source_bin']);
                
                if($scanned_bin !== $target_source) {
                    $msg = "WRONG BIN! SCAN: $target_source"; 
                    $msg_type = "red";
                } else {
                    // Cek Stok di Source
                    $stok = safeGetOne($pdo, "SELECT quant_id, qty FROM wms_quants WHERE product_uuid=? AND lgpla=? LIMIT 1", [$prod_uuid, $target_source]);
                    
                    if($stok) {
                        $new_qty = $stok['qty'] - $qty;
                        $qid = $stok['quant_id'];
                        
                        // Potong Stok Source
                        if($new_qty <= 0) safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$qid]);
                        else safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$new_qty, $qid]);
                        
                        // PINDAHKAN KE GI-ZONE (Staging Area)
                        // Agar Shipping bisa mendeteksi stok ini siap dikirim
                        safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) VALUES (?, 'GI-ZONE', ?, ?, ?, NOW())", [$prod_uuid, $batch, $hu_id, $qty]);

                        $success = true;
                    } else {
                        $msg = "STOCK NOT FOUND!"; $msg_type = "red";
                    }
                }
            }

            if($success) {
                safeQuery($pdo, "UPDATE wms_warehouse_tasks SET status='CONFIRMED', updated_at=NOW() WHERE tanum=?", [$task_id]);
                $pdo->commit();
                $msg = "TASK #$task_id DONE!"; $msg_type = "green";
                header("Refresh:1"); 
            } else {
                $pdo->rollBack();
            }

        } catch (Exception $e) { $pdo->rollBack(); $msg = "ERR: ".$e->getMessage(); $msg_type = "red"; }
    } else { $msg = "TASK INVALID"; $msg_type = "red"; }
}

// --- QUERY GET TASK ---
$current_task = null;
if($current_page == 'task') {
    $current_task = $pdo->query("SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.status = 'OPEN' ORDER BY t.tanum ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RF Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background:#222; color:#0f0; font-family:monospace; height:100vh; display:flex; align-items:center; justify-content:center; } .rf-screen { background:#000; border:10px solid #333; width:350px; height:600px; padding:15px; display:flex; flex-direction:column; } .task-card { border:2px dashed #0f0; padding:10px; margin:10px 0; } .input-dark { background:#000; border:1px solid #0f0; color:#fff; width:100%; text-transform:uppercase; }</style>
</head>
<body>
<div class="rf-screen">
    <div style="border-bottom:1px solid #0f0; margin-bottom:10px;">RF-01 | <a href="?page=menu" style="color:#0f0">MENU</a></div>
    
    <?php if($msg): ?><div style="color:<?= $msg_type=='red'?'#f00':'#0f0' ?>; font-weight:bold; text-align:center;"><?= $msg ?></div><?php endif; ?>

    <?php if($current_page == 'task'): ?>
        <?php if($current_task): ?>
            <div class="task-card">
                <div>TASK: #<?= $current_task['tanum'] ?></div>
                <div>TYPE: <span style="color:#0ff"><?= $current_task['process_type'] ?></span></div>
                <div>PROD: <?= $current_task['product_code'] ?></div>
                <hr style="border-color:#0f0">
                <div style="font-size:1.5rem">QTY: <?= (float)$current_task['qty'] ?></div>
                <div>BIN: <?= $current_task['process_type'] == 'PUTAWAY' ? $current_task['dest_bin'] : $current_task['source_bin'] ?></div>
            </div>
            <form method="POST" style="margin-top:auto">
                <input type="hidden" name="task_id" value="<?= $current_task['tanum'] ?>">
                <div>SCAN BIN:</div>
                <input type="text" name="scan_check" class="input-dark" autofocus autocomplete="off">
                <button name="btn_exec" style="width:100%; background:#0f0; border:none; padding:10px; font-weight:bold; margin-top:10px; cursor:pointer;">CONFIRM</button>
            </form>
        <?php else: ?>
            <div style="text-align:center; margin-top:50px;">NO TASKS.<br><a href="?page=task" style="color:#0f0">REFRESH</a></div>
        <?php endif; ?>
    <?php elseif($current_page == 'menu'): ?>
        <a href="?page=task" style="color:#0f0; display:block; margin:10px;">1. TASK MODE</a>
        <a href="index.php" style="color:#888; display:block; margin:10px;">0. EXIT</a>
    <?php endif; ?>
</div>
</body>
</html>