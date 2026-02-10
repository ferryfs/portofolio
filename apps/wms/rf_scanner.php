<?php
// apps/wms/rf_scanner.php
// V3: MOBILE UI FIXED + V8 LOGIC

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: ../../login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user_id = $_SESSION['wms_fullname'];
$page = isset($_GET['page']) ? $_GET['page'] : 'menu';
$msg = ""; $msg_type = "";

// --- LOGIC EKSEKUSI (SAMA DENGAN V8) ---
if(isset($_POST['btn_exec'])) {
    $task_id = sanitizeInput($_POST['task_id']);
    $scan_val = strtoupper(trim($_POST['scan_check']));

    try {
        $pdo->beginTransaction();

        $task = safeGetOne($pdo, "SELECT * FROM wms_warehouse_tasks WHERE tanum = ? AND status='OPEN' FOR UPDATE", [$task_id]);
        if(!$task) throw new Exception("Task unavailable.");

        $type = $task['process_type'];
        $prod = $task['product_uuid'];
        $qty  = (float)$task['qty'];
        $batch = $task['batch'];
        $hu   = $task['hu_id'];

        // Validasi Scan
        $target = ($type == 'PICKING') ? $task['source_bin'] : $task['dest_bin'];
        // Bypass sementara jika kosong (Hapus di production)
        if($scan_val == "") $scan_val = $target; 

        if($scan_val !== strtoupper($target)) throw new Exception("WRONG BIN! Expected: $target");

        // Stock Movement Logic
        if($type == 'PUTAWAY') {
            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) VALUES (?, ?, ?, ?, ?, NOW())", [$prod, $target, $batch, $hu, $qty]);
        } elseif($type == 'PICKING') {
            $stok = safeGetOne($pdo, "SELECT quant_id, qty FROM wms_quants WHERE product_uuid=? AND lgpla=? AND batch=? LIMIT 1 FOR UPDATE", [$prod, $task['source_bin'], $batch]);
            if(!$stok || $stok['qty'] < $qty) throw new Exception("Stock Not Found!");
            
            $sisa = $stok['qty'] - $qty;
            if($sisa <= 0) safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$stok['quant_id']]);
            else safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$sisa, $stok['quant_id']]);

            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) VALUES (?, 'GI-ZONE', ?, ?, ?, NOW())", [$prod, $batch, $hu, $qty]);
        }

        safeQuery($pdo, "UPDATE wms_warehouse_tasks SET status='CONFIRMED', updated_at=NOW(), dest_bin=? WHERE tanum=?", [$scan_val, $task_id]);
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, ?, ?, ?, ?)", ["WT-$task_id", $prod, $batch, $hu, $qty, "RF_$type", $user_id]);

        $pdo->commit();
        $msg = "TASK DONE!"; $msg_type = "success";
        $page = 'list';

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = $e->getMessage(); $msg_type = "error";
        $page = 'exec';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>RF Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* CSS SIMULASI HP */
        body { background-color: #121212; display: flex; justify-content: center; min-height: 100vh; margin: 0; font-family: monospace; }
        
        .mobile-wrapper {
            width: 100%;
            max-width: 420px; /* Ukuran HP Standard */
            background-color: #000;
            min-height: 100vh;
            border-right: 1px solid #333;
            border-left: 1px solid #333;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 50px rgba(0,0,0,0.5);
        }

        .rf-header { background: #222; padding: 15px; border-bottom: 2px solid #444; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .rf-content { flex: 1; padding: 15px; overflow-y: auto; }
        
        /* UI Elements */
        .btn-menu { display: block; width: 100%; background: #333; border: 1px solid #555; color: #0f0; padding: 15px; text-align: left; font-weight: bold; margin-bottom: 10px; text-decoration: none; transition: 0.2s; }
        .btn-menu:active, .btn-menu:hover { background: #0f0; color: #000; }
        
        .task-card { border: 1px dashed #444; padding: 12px; margin-bottom: 12px; background: #0a0a0a; color: #ddd; }
        .task-head { display: flex; justify-content: space-between; margin-bottom: 5px; color: #0f0; font-weight: bold; }
        
        .input-scan { width: 100%; background: #000; border: 2px solid #0f0; color: #fff; padding: 12px; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 15px; outline: none; }
        
        .alert-rf { padding: 10px; text-align: center; font-weight: bold; margin-bottom: 15px; border: 1px solid; }
        .s-ok { border-color: #0f0; color: #0f0; background: #002200; }
        .s-err { border-color: #f00; color: #f00; background: #220000; }
    </style>
</head>
<body>

<div class="mobile-wrapper">
    <div class="rf-header">
        <span class="fw-bold"><i class="bi bi-upc-scan"></i> RF-01</span>
        <a href="?page=menu" class="text-secondary text-decoration-none"><i class="bi bi-grid-fill"></i> MENU</a>
    </div>

    <div class="rf-content">
        <?php if($msg): ?>
            <div class="alert-rf <?= $msg_type=='success'?'s-ok':'s-err' ?>"><?= $msg ?></div>
        <?php endif; ?>

        <?php if($page == 'menu'): ?>
            <div class="text-center text-muted mb-4 small">-- MAIN MENU --</div>
            <a href="?page=list&type=PUTAWAY" class="btn-menu">1. INBOUND (PUTAWAY)</a>
            <a href="?page=list&type=PICKING" class="btn-menu">2. OUTBOUND (PICKING)</a>
            <a href="?page=list" class="btn-menu">3. ALL TASKS</a>
            <a href="index.php" class="btn-menu" style="color:#888; border-color:#444;">0. EXIT</a>

        <?php elseif($page == 'list'): ?>
            <?php 
            $fil = isset($_GET['type']) ? "AND t.process_type='{$_GET['type']}'" : "";
            $l = safeGetAll($pdo, "SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid=p.product_uuid WHERE t.status='OPEN' $fil LIMIT 10");
            ?>
            <div class="d-flex justify-content-between mb-3 text-secondary fw-bold">
                <span>TASKS (<?= count($l) ?>)</span>
                <a href="?page=menu" class="text-decoration-none text-secondary">BACK</a>
            </div>
            
            <?php if(empty($l)): echo "<div class='text-center text-muted py-5'>NO DATA</div>"; endif; ?>
            
            <?php foreach($l as $r): ?>
            <a href="?page=exec&id=<?= $r['tanum'] ?>" class="text-decoration-none">
                <div class="task-card">
                    <div class="task-head">
                        <span>#<?= $r['tanum'] ?></span>
                        <span><?= $r['process_type'] ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-white"><?= $r['product_code'] ?></div>
                    <div class="d-flex justify-content-between mt-2 small text-secondary">
                        <span>QTY: <b class="text-white"><?= (float)$r['qty'] ?></b></span>
                        <span>BIN: <b class="text-warning"><?= $r['process_type']=='PUTAWAY'?$r['dest_bin']:$r['source_bin'] ?></b></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>

        <?php elseif($page == 'exec'): ?>
            <?php 
            $id = $_GET['id'] ?? 0;
            $t = safeGetOne($pdo, "SELECT t.*, p.product_code, p.description FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid=p.product_uuid WHERE t.tanum=?", [$id]);
            $bin = $t['process_type']=='PUTAWAY' ? $t['dest_bin'] : $t['source_bin'];
            ?>
            
            <div class="mb-3 border-bottom border-secondary pb-3">
                <div class="text-secondary small">ITEM</div>
                <div class="fw-bold fs-4 text-white"><?= $t['product_code'] ?></div>
                <div class="text-muted small"><?= $t['description'] ?></div>
            </div>

            <div class="row mb-4">
                <div class="col-6">
                    <div class="text-secondary small">QTY</div>
                    <div class="fs-2 fw-bold text-success"><?= (float)$t['qty'] ?></div>
                </div>
                <div class="col-6 text-end">
                    <div class="text-secondary small">SCAN BIN</div>
                    <div class="fs-2 fw-bold text-warning"><?= $bin ?></div>
                </div>
            </div>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="task_id" value="<?= $id ?>">
                <input type="text" name="scan_check" class="input-scan" autofocus placeholder="SCAN HERE...">
                <button name="btn_exec" class="btn-menu text-center bg-success text-black border-0">CONFIRM</button>
            </form>
            <a href="?page=list" class="btn btn-sm btn-outline-secondary w-100">CANCEL</a>

        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const inp = document.querySelector(".input-scan");
    if(inp) inp.focus();
});
</script>
</body>
</html>