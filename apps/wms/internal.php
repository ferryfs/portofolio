<?php
// apps/wms/internal.php
// V9: INTERNAL MOVEMENTS & QC STATUS CHANGE

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$msg = ""; $alert = ""; $user = $_SESSION['wms_fullname'];

// A. MOVE HU (Pindah Rak)
if(isset($_POST['post_transfer'])) {
    $qid = sanitizeInt($_POST['quant_id']);
    $bin = strtoupper(sanitizeInput($_POST['dest_bin']));

    $src = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=?", [$qid]);
    if($src) {
        try {
            $pdo->beginTransaction();
            // Update Bin
            safeQuery($pdo, "UPDATE wms_quants SET lgpla=? WHERE quant_id=?", [$bin, $qid]);
            
            // Audit Trail
            safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, 0, 'BIN_MOVE', ?)", 
                      ["MOV-{$src['lgpla']}-TO-$bin", $src['product_uuid'], $src['hu_id'], $user]);

            $pdo->commit();
            $msg = "✅ Moved HU <b>{$src['hu_id']}</b> to <b>$bin</b>."; $alert = "success";
        } catch(Exception $e) { $pdo->rollBack(); $msg = "Error"; $alert="danger"; }
    }
}

// B. CHANGE STATUS (QC)
if(isset($_POST['post_status'])) {
    $qid = sanitizeInt($_POST['quant_id_stat']);
    $stat = sanitizeInput($_POST['new_status']);
    
    $old = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=?", [$qid]);
    if($old) {
        try {
            $pdo->beginTransaction();
            // Update Status
            safeQuery($pdo, "UPDATE wms_quants SET stock_type=? WHERE quant_id=?", [$stat, $qid]);
            
            // Audit Trail
            safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, 0, 'STAT_CHG', ?)", 
                      ["STAT-{$old['stock_type']}-TO-$stat", $old['product_uuid'], $old['hu_id'], $user]);

            $pdo->commit();
            $msg = "✅ Status Changed to <b>$stat</b>."; $alert = "warning";
        } catch(Exception $e) { $pdo->rollBack(); $msg = "Error"; $alert="danger"; }
    }
}

// LOAD LIST STOK (Untuk Dropdown)
$stok_list = safeGetAll($pdo, "SELECT q.quant_id, q.hu_id, q.lgpla, q.stock_type, p.product_code 
                               FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid 
                               ORDER BY q.lgpla ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internal Process</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style> body { background: #f8fafc; } .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); } </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-arrows-move me-2"></i>Internal Operations</h4>
        <a href="stock_master.php" class="btn btn-outline-secondary">Back</a>
    </div>

    <?php if($msg): ?><div class="alert alert-<?= $alert ?>"><?= $msg ?></div><?php endif; ?>

    <div class="row g-4">
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-box-seam me-2"></i> Move HU (Bin Transfer)
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Handling Unit</label>
                            <select name="quant_id" class="form-select" required>
                                <option value="">-- Select HU --</option>
                                <?php foreach($stok_list as $r): ?>
                                    <option value="<?= $r['quant_id'] ?>">
                                        [<?= $r['lgpla'] ?>] HU: <?= $r['hu_id'] ?> (<?= $r['product_code'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Destination Bin</label>
                            <input type="text" name="dest_bin" class="form-control text-uppercase" placeholder="e.g. A-01-02" required>
                        </div>
                        <button type="submit" name="post_transfer" class="btn btn-primary w-100 fw-bold">Execute Move</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="bi bi-shield-check me-2"></i> Change Stock Status (QC)
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Stock</label>
                            <select name="quant_id_stat" class="form-select" required>
                                <option value="">-- Select Stock --</option>
                                <?php foreach($stok_list as $r): ?>
                                    <option value="<?= $r['quant_id'] ?>">
                                        HU: <?= $r['hu_id'] ?> (Current: <?= $r['stock_type'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">New Status</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="new_status" id="s1" value="F1" checked>
                                <label class="btn btn-outline-success" for="s1">F1 (Good)</label>

                                <input type="radio" class="btn-check" name="new_status" id="s2" value="Q4">
                                <label class="btn btn-outline-warning text-dark" for="s2">Q4 (QC)</label>

                                <input type="radio" class="btn-check" name="new_status" id="s3" value="B6">
                                <label class="btn btn-outline-danger" for="s3">B6 (Block)</label>
                            </div>
                        </div>
                        <button type="submit" name="post_status" class="btn btn-warning w-100 fw-bold">Update Status</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>