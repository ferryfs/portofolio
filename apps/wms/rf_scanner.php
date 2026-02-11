<?php
// apps/wms/rf_scanner.php
// V4: ENTERPRISE RF (Supports 3-Layer Stock Engine)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: ../../login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user_id = $_SESSION['wms_fullname'];
$page = isset($_GET['page']) ? $_GET['page'] : 'menu';
$msg = ""; $msg_type = "";

// --- LOGIC EKSEKUSI (ENTERPRISE V4) ---
if(isset($_POST['btn_exec'])) {
    $task_id = sanitizeInput($_POST['task_id']);
    $scan_val = strtoupper(trim($_POST['scan_check']));

    try {
        $pdo->beginTransaction();

        // 1. Lock Task
        $task = safeGetOne($pdo, "SELECT * FROM wms_warehouse_tasks WHERE tanum = ? AND status='OPEN' FOR UPDATE", [$task_id]);
        if(!$task) throw new Exception("Task unavailable (Locked or Closed).");

        $type  = $task['process_type'];
        $prod  = $task['product_uuid'];
        $qty   = (float)$task['qty'];
        $batch = $task['batch'];
        $hu    = $task['hu_id'];

        // 2. Validasi Scan (Picking=Source, Putaway=Dest)
        // --- KODINGAN RF_SCANNER.PHP (BAGIAN VALIDASI) ---

        // 2. Tentukan Target Bin
        // Kalau Putaway, yang dicek adalah dest_bin (Bin Tujuan)
        $target = ($type == 'PICKING') ? $task['source_bin'] : $task['dest_bin'];

        // Logic Khusus Putaway biar gak error "Expected: SYSTEM"
        if($type == 'PUTAWAY' && (strpos(strtoupper($target), 'SYSTEM') !== false)) {
            
            // 🧠 DISINI KUNCINYA:
            // Kalau targetnya ada kata "SYSTEM", jangan paksa picker scan tulisan "SYSTEM"
            // Tapi cek apakah bin yang di-scan picker (misal A-01-01) itu VALID ada di gudang?
            
            $bin_exists = safeGetOne($pdo, "SELECT 1 FROM wms_storage_bins WHERE lgpla = ?", [$scan_val]);
            
            if(!$bin_exists) {
                throw new Exception("INVALID BIN! Bin $scan_val tidak terdaftar di Master Data.");
            }
            
            // Kalau bin-nya ada di master data, kita ijinkan! 
            // Kita ganti target aslinya jadi bin yang di-scan picker tadi.
            $target = $scan_val; 
            
        } else {
            // Kalau bukan saran system (alias target bin-nya sudah spesifik), tetep harus sama.
            if($scan_val !== strtoupper($target)) {
                throw new Exception("WRONG BIN! Expected: $target");
            }
        }

        // 3. STOCK MOVEMENT ENGINE
        if($type == 'PUTAWAY') {
            // Inbound: Nambah Stok Baru (Qty nambah, Reserved 0)
            // Cek apakah batch/hu ini sudah ada di bin tujuan? (Merge)
            $cek = safeGetOne($pdo, "SELECT quant_id FROM wms_quants WHERE product_uuid=? AND lgpla=? AND batch=? AND hu_id=?", [$prod, $target, $batch, $hu]);
            
            if($cek) {
                safeQuery($pdo, "UPDATE wms_quants SET qty = qty + ? WHERE quant_id=?", [$qty, $cek['quant_id']]);
            } else {
                safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, reserved_qty, gr_date) VALUES (?, ?, ?, ?, ?, 0, NOW())", 
                          [$prod, $target, $batch, $hu, $qty]);
            }

        } elseif($type == 'PICKING') {
            // Outbound: Kurangi Stok & Hapus Reservasi
            $stok = safeGetOne($pdo, "SELECT quant_id, qty, reserved_qty FROM wms_quants WHERE product_uuid=? AND lgpla=? AND batch=? LIMIT 1 FOR UPDATE", 
                               [$prod, $task['source_bin'], $batch]);
            
            if(!$stok || $stok['qty'] < $qty) throw new Exception("Physical Stock Not Found/Insufficient!");
            
            // Logic 3 Layer: Kurangi Qty Fisik DAN Reserved Qty
            // Karena Task Picking dibuat dari Reservasi, maka saat dieksekusi, reservasinya dianggap "Terpakai" (Consumed)
            $new_qty = $stok['qty'] - $qty;
            $new_res = $stok['reserved_qty'] - $qty; 
            if($new_res < 0) $new_res = 0; // Safety net biar gak minus

            if($new_qty <= 0) {
                safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$stok['quant_id']]);
            } else {
                safeQuery($pdo, "UPDATE wms_quants SET qty=?, reserved_qty=? WHERE quant_id=?", [$new_qty, $new_res, $stok['quant_id']]);
            }

            // Tambahkan 'F1' agar stok di GI-ZONE terbaca sebagai stok siap kirim
            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, reserved_qty, gr_date, stock_type) 
                 VALUES (?, 'GI-ZONE', ?, ?, ?, 0, NOW(), 'F1')", 
                 [$prod, $batch, $hu, $qty]);
        }

        // Pakai confirmed_at sesuai JSON database lu tadi
        safeQuery($pdo, "UPDATE wms_warehouse_tasks SET status='CONFIRMED', confirmed_at=NOW(), dest_bin=? WHERE tanum=?", [$scan_val, $task_id]);
        
        // Audit Trail Lengkap
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, qty_change, move_type, user, from_bin, to_bin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                  ["WT-$task_id", $prod, $batch, $hu, $qty, "RF_$type", $user_id, $task['source_bin'], $scan_val]);

        $pdo->commit();
        $msg = "✅ TASK CONFIRMED!"; $msg_type = "success";
        $page = 'list';

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "⛔ ERROR: " . $e->getMessage(); $msg_type = "error";
        $page = 'exec';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>RF Scanner V4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* INDUSTRIAL DARK THEME (ZEBRA/HONEYWELL STYLE) */
        body { background-color: #000; color: #00ff00; font-family: 'Consolas', monospace; display: flex; justify-content: center; min-height: 100vh; margin: 0; }
        
        .mobile-wrapper { width: 100%; max-width: 480px; background-color: #111; min-height: 100vh; border-left: 1px solid #333; border-right: 1px solid #333; display: flex; flex-direction: column; }
        
        .rf-header { background: #222; padding: 12px 15px; border-bottom: 2px solid #00ff00; display: flex; justify-content: space-between; align-items: center; }
        .rf-content { flex: 1; padding: 15px; overflow-y: auto; }
        
        a { text-decoration: none; }
        
        /* MENU BUTTONS */
        .btn-menu { display: block; width: 100%; background: #000; border: 1px solid #333; color: #00ff00; padding: 15px; font-size: 1.1rem; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; transition: 0.1s; }
        .btn-menu:active, .btn-menu:hover { background: #00ff00; color: #000; }
        
        /* TASK CARD */
        .task-card { background: #000; border: 1px solid #444; padding: 12px; margin-bottom: 10px; border-left: 4px solid #444; }
        .task-card.PUTAWAY { border-left-color: #00ff00; }
        .task-card.PICKING { border-left-color: #00aaff; }
        
        .lbl { color: #888; font-size: 0.75rem; text-transform: uppercase; }
        .val { color: #fff; font-weight: bold; font-size: 1rem; }
        .val-hl { color: #ffff00; font-size: 1.2rem; }
        
        /* INPUT & ALERTS */
        .input-scan { width: 100%; background: #000; border: 2px solid #00ff00; color: #fff; padding: 15px; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 15px; outline: none; text-align: center; }
        .input-scan:focus { background: #112211; box-shadow: 0 0 15px rgba(0,255,0,0.3); }
        
        .alert-rf { padding: 15px; text-align: center; font-weight: bold; margin-bottom: 20px; border: 2px solid; text-transform: uppercase; }
        .s-ok { border-color: #00ff00; background: #002200; color: #00ff00; }
        .s-err { border-color: #ff0000; background: #220000; color: #ff0000; }
    </style>
</head>
<body>

<div class="mobile-wrapper">
    <div class="rf-header">
        <span style="font-weight:900; letter-spacing:1px;">RF-SCAN v4</span>
        <a href="?page=menu" style="color:#888;"><i class="bi bi-grid-3x3-gap-fill"></i> MENU</a>
    </div>

    <div class="rf-content">
        <?php if($msg): ?>
            <div class="alert-rf <?= $msg_type=='success'?'s-ok':'s-err' ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if($page == 'menu'): ?>
            <div style="text-align:center; color:#666; margin-bottom:20px; font-size:0.8rem;">SELECT OPERATION MODE</div>
            <a href="?page=list&type=PUTAWAY" class="btn-menu">1. INBOUND (PUTAWAY)</a>
            <a href="?page=list&type=PICKING" class="btn-menu">2. OUTBOUND (PICKING)</a>
            <a href="?page=list" class="btn-menu">3. ALL OPEN TASKS</a>
            <div style="margin-top:30px;"></div>
            <a href="index.php" class="btn-menu" style="border-color:#444; color:#888;">0. LOGOUT / EXIT</a>

        <?php elseif($page == 'list'): ?>
            <?php 
            $fil = isset($_GET['type']) ? "AND t.process_type='{$_GET['type']}'" : "";
            $sql = "SELECT t.*, p.product_code 
                    FROM wms_warehouse_tasks t 
                    JOIN wms_products p ON t.product_uuid=p.product_uuid 
                    WHERE t.status='OPEN' $fil 
                    ORDER BY t.priority DESC, t.created_at ASC LIMIT 10";
            $l = safeGetAll($pdo, $sql);
            ?>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:15px; color:#888;">
                <span>OPEN TASKS: <?= count($l) ?></span>
                <a href="?page=menu" style="color:#00ff00;">BACK</a>
            </div>

            <?php if(empty($l)): ?>
                <div style="text-align:center; padding:50px; color:#444;">
                    <i class="bi bi-check-circle" style="font-size:3rem;"></i><br>NO TASKS PENDING
                </div>
            <?php endif; ?>

            <?php foreach($l as $r): ?>
            <a href="?page=exec&id=<?= $r['tanum'] ?>">
                <div class="task-card <?= $r['process_type'] ?>">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span class="lbl">WT-<?= $r['tanum'] ?></span>
                        <span style="font-weight:bold; color:<?= $r['process_type']=='PUTAWAY'?'#00ff00':'#00aaff' ?>"><?= $r['process_type'] ?></span>
                    </div>
                    <div class="val" style="margin-bottom:8px;"><?= $r['product_code'] ?></div>
                    <div style="display:flex; justify-content:space-between;">
                        <div><span class="lbl">QTY</span> <span class="val"><?= (float)$r['qty'] ?></span></div>
                        <div style="text-align:right;">
                            <span class="lbl">TARGET BIN</span><br>
                            <span class="val-hl"><?= $r['process_type']=='PUTAWAY' ? $r['dest_bin'] : $r['source_bin'] ?></span>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>

        <?php elseif($page == 'exec'): ?>
            <?php 
            $id = sanitizeInput($_GET['id'] ?? 0);
            $t = safeGetOne($pdo, "SELECT t.*, p.product_code, p.description FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid=p.product_uuid WHERE t.tanum=?", [$id]);
            
            if(!$t): echo "<div class='alert-rf s-err'>TASK NOT FOUND</div><a href='?page=list' class='btn-menu'>BACK</a>"; else:
                $target_bin = $t['process_type']=='PUTAWAY' ? $t['dest_bin'] : $t['source_bin'];
                $prompt = $t['process_type']=='PUTAWAY' ? "SCAN DESTINATION BIN" : "SCAN SOURCE BIN";
            ?>
            
            <div style="background:#222; padding:15px; margin-bottom:20px; border:1px solid #444;">
                <div class="lbl">ITEM DETAILS</div>
                <div class="val" style="font-size:1.4rem; color:#fff;"><?= $t['product_code'] ?></div>
                <div style="color:#aaa; font-size:0.9rem; margin-bottom:15px;"><?= substr($t['description'],0,30) ?></div>
                
                <div style="display:flex; justify-content:space-between; border-top:1px solid #444; padding-top:10px;">
                    <div><span class="lbl">BATCH</span><br><span class="val"><?= $t['batch'] ?></span></div>
                    <div style="text-align:right;"><span class="lbl">QUANTITY</span><br><span class="val-hl"><?= (float)$t['qty'] ?></span></div>
                </div>
            </div>

            <div style="text-align:center; margin-bottom:10px;">
                <span class="lbl">TARGET LOCATION</span><br>
                <span class="val" style="font-size:2rem; color: #00ff00;"><?= $target_bin ?></span>
            </div>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="task_id" value="<?= $id ?>">
                <div style="margin-bottom:5px; font-weight:bold; color:#00aaff;"><?= $prompt ?></div>
                <input type="text" name="scan_check" class="input-scan" autofocus placeholder="[ SCAN BIN ]">
                
                <button name="btn_exec" class="btn-menu" style="background:#003300; border-color:#00ff00;">CONFIRM TASK</button>
            </form>
            
            <a href="?page=list" class="btn-menu" style="background:#330000; border-color:#ff0000; color:#ffaaaa; text-align:center;">CANCEL</a>

            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto focus biar scanner enak
document.addEventListener("DOMContentLoaded", function() {
    let inp = document.querySelector(".input-scan");
    if(inp) { 
        inp.focus(); 
        inp.click(); // Hack buat beberapa model scanner mobile
    }
});
</script>
</body>
</html>