<?php
// apps/wms/task_confirm.php
// V9: PREMIUM UI + V8 LOGIC (FORTRESS)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$id = sanitizeInt($_GET['id'] ?? 0);
$user_id = $_SESSION['wms_fullname'];

// Ambil Data Task + Product Info
$task = safeGetOne($pdo, "SELECT t.*, p.product_code, p.description, p.base_uom 
                          FROM wms_warehouse_tasks t 
                          JOIN wms_products p ON t.product_uuid = p.product_uuid 
                          WHERE t.tanum = ?", [$id]);

if(!$task) die("Task Not Found or Invalid ID.");

$msg = ""; $msg_type = "";

// --- LOGIC EKSEKUSI (SAMA DENGAN RF SCANNER) ---
if(isset($_POST['confirm'])) {
    try {
        $pdo->beginTransaction();
        
        // Re-check status (Anti Race Condition)
        $t = safeGetOne($pdo, "SELECT * FROM wms_warehouse_tasks WHERE tanum=? AND status='OPEN' FOR UPDATE", [$id]);
        if(!$t) throw new Exception("Task already processed by someone else!");

        $type = $t['process_type'];
        $prod = $t['product_uuid'];
        $qty  = $t['qty'];
        $batch = $t['batch'];
        $hu   = $t['hu_id'];
        $final_bin = strtoupper(sanitizeInput($_POST['actual_bin'])); // Input Admin

        // Validasi Target Bin (Biar gak salah taruh sembarangan)
        // Di mode desktop admin boleh override, tapi kita kasih warning logic kalau kosong
        if(empty($final_bin)) throw new Exception("Bin Location cannot be empty!");

        // 1. UPDATE STOCK LOGIC
        if($type == 'PUTAWAY') {
            // Masuk ke Bin Tujuan
            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) VALUES (?, ?, ?, ?, ?, NOW())", 
                      [$prod, $final_bin, $batch, $hu, $qty]);
        } 
        elseif($type == 'PICKING') {
            // Picking Logic: Kurangi Source, Pindah ke GI
            $stok = safeGetOne($pdo, "SELECT quant_id, qty FROM wms_quants WHERE product_uuid=? AND lgpla=? AND batch=? LIMIT 1 FOR UPDATE", 
                               [$prod, $t['source_bin'], $batch]);
            
            if(!$stok || $stok['qty'] < $qty) throw new Exception("Stock in source bin insufficient!");
            
            $sisa = $stok['qty'] - $qty;
            if($sisa <= 0) safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$stok['quant_id']]);
            else safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$sisa, $stok['quant_id']]);

            // Pindah ke GI-ZONE (Virtual Outbound)
            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) VALUES (?, 'GI-ZONE', ?, ?, ?, NOW())", 
                      [$prod, $batch, $hu, $qty]);
        }

        // 2. CLOSE TASK
        safeQuery($pdo, "UPDATE wms_warehouse_tasks SET status='CONFIRMED', updated_at=NOW(), dest_bin=? WHERE tanum=?", [$final_bin, $id]);

        // 3. AUDIT TRAIL
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                  ["WT-$id", $prod, $batch, $hu, $qty, "DESK_$type", $user_id]);

        $pdo->commit();
        
        // Redirect balik ke dashboard task
        header("Location: task.php?msg=success"); exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = $e->getMessage();
        $msg_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Task #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 20px; border-radius: 12px 12px 0 0 !important; }
        
        .route-box { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 10px; padding: 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .bin-badge { background: #fff; padding: 8px 16px; border-radius: 6px; border: 1px solid #e2e8f0; font-family: monospace; font-weight: bold; font-size: 1.1rem; color: #334155; }
        .arrow-icon { font-size: 1.5rem; color: #94a3b8; }
        
        .product-highlight { background: #eff6ff; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6; }
        
        .btn-confirm { padding: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body class="py-5">

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Task Execution</h4>
            <p class="text-muted m-0">Manual confirmation via Desktop</p>
        </div>
        <a href="task.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back to Monitor</a>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0"><i class="bi bi-ticket-detailed me-2 text-primary"></i> Task Details #<?= $task['tanum'] ?></h6>
                    <span class="badge bg-primary"><?= $task['process_type'] ?></span>
                </div>
                <div class="card-body">
                    
                    <div class="route-box">
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">SOURCE BIN</small>
                            <div class="bin-badge"><?= $task['source_bin'] ?></div>
                        </div>
                        <div class="arrow-icon"><i class="bi bi-arrow-right-circle-fill text-primary"></i></div>
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">TARGET BIN</small>
                            <div class="bin-badge text-primary border-primary"><?= $task['dest_bin'] ?></div>
                        </div>
                    </div>

                    <div class="product-highlight mb-4">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Product Item</div>
                        <h4 class="fw-bold text-dark m-0"><?= $task['product_code'] ?></h4>
                        <div class="text-secondary"><?= $task['description'] ?></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block">Quantity</small>
                                <span class="fs-5 fw-bold text-dark"><?= (float)$task['qty'] ?> <span class="fs-6 text-muted"><?= $task['base_uom'] ?></span></span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block">Batch No</small>
                                <span class="fs-6 fw-bold font-monospace text-dark"><?= $task['batch'] ?></span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block">HU ID</small>
                                <span class="fs-6 fw-bold font-monospace text-dark text-truncate d-block"><?= $task['hu_id'] ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="fw-bold m-0"><i class="bi bi-check-circle-fill me-2"></i> Confirm Execution</h6>
                </div>
                <div class="card-body d-flex flex-column">
                    
                    <?php if($msg): ?>
                        <div class="alert alert-<?= $msg_type ?> mb-3"><?= $msg ?></div>
                    <?php endif; ?>

                    <form method="POST" class="flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Confirm Destination Bin</label>
                                <input type="text" name="actual_bin" 
                                       class="form-control form-control-lg fw-bold border-primary text-uppercase" 
                                       value="<?= $task['dest_bin'] ?>" 
                                       placeholder="SCAN OR TYPE BIN" required>
                                <div class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> Verify physical location matches.
                                </div>
                            </div>

                            <div class="alert alert-light border small text-muted">
                                <i class="bi bi-exclamation-triangle me-1"></i> Warning: This action will perform manual stock movement and update inventory levels immediately.
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="confirm" class="btn btn-primary btn-lg btn-confirm shadow-sm">
                                <i class="bi bi-save2 me-2"></i> CONFIRM TASK
                            </button>
                            <a href="task.php" class="btn btn-light text-muted">Cancel Operation</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>