<?php
// apps/wms/internal.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) exit("Akses Ditolak.");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// A. MOVE HU (Pindah Rak)
if(isset($_POST['post_transfer'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    
    $qid = sanitizeInt($_POST['quant_id']);
    $bin = sanitizeInput($_POST['dest_bin']);
    $user = $_SESSION['wms_fullname'];

    $src = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=?", [$qid]);
    if($src) {
        try {
            $pdo->beginTransaction();
            // Update Bin di Stok
            safeQuery($pdo, "UPDATE wms_quants SET lgpla=? WHERE quant_id=?", [$bin, $qid]);
            
            // Catat Task
            $sql = "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at) 
                    VALUES ('INTERNAL', ?, ?, ?, ?, ?, ?, 'CONFIRMED', NOW())";
            safeQuery($pdo, $sql, [$src['product_uuid'], $src['batch'], $src['hu_id'], $src['lgpla'], $bin, $src['qty']]);
            
            $pdo->commit();
            catat_log($pdo, $user, 'MOVE', 'INT', "Move HU {$src['hu_id']} to $bin");
            $msg = "✅ Moved Successfully."; $alert = "success";
        } catch(Exception $e) { $pdo->rollBack(); $msg = "Error"; $alert="danger"; }
    }
}

// B. CHANGE STATUS (F1 -> B6 dll)
if(isset($_POST['post_status_change'])) {
    if (!verifyCSRFToken()) die("Invalid Token");

    $qid = sanitizeInt($_POST['quant_id_change']);
    $stat = sanitizeInput($_POST['new_status']);
    $user = $_SESSION['wms_fullname'];
    
    $old = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=?", [$qid]);
    if($old) {
        try {
            $pdo->beginTransaction();
            safeQuery($pdo, "UPDATE wms_quants SET stock_type=? WHERE quant_id=?", [$stat, $qid]);
            
            // Catat Task (Bin Source & Dest diisi Status lama & baru)
            $src_info = "STAT:" . $old['stock_type'];
            $dest_info = "STAT:" . $stat;
            
            $sql = "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at) 
                    VALUES ('INTERNAL', ?, ?, ?, ?, ?, ?, 'CONFIRMED', NOW())";
            safeQuery($pdo, $sql, [$old['product_uuid'], $old['batch'], $old['hu_id'], $src_info, $dest_info, $old['qty']]);
            
            $pdo->commit();
            catat_log($pdo, $user, 'STATUS', 'INT', "Change Status HU {$old['hu_id']}: {$old['stock_type']} -> $stat");
            $msg = "✅ Status Changed to $stat."; $alert = "warning";
        } catch(Exception $e) { $pdo->rollBack(); $msg = "Error"; $alert="danger"; }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Internal Process</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4>Internal Process</h4>
        <a href="stock_master.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-$alert'>$msg</div>"; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card p-3 h-100 border-primary shadow-sm">
                <h5 class="text-primary mb-3">1. Move HU (Pindah Rak)</h5>
                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    <label class="fw-bold small">Pilih HU</label>
                    <select name="quant_id" class="form-select mb-3">
                        <?php 
                        $stmt = $pdo->query("SELECT q.*, p.product_code FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid ORDER BY q.lgpla");
                        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$r['quant_id']}'>[{$r['lgpla']}] HU:{$r['hu_id']} ({$r['product_code']})</option>";
                        }
                        ?>
                    </select>
                    <label class="fw-bold small">Bin Tujuan</label>
                    <select name="dest_bin" class="form-select mb-3">
                        <?php 
                        $bins = $pdo->query("SELECT lgpla FROM wms_storage_bins ORDER BY lgpla");
                        while($b = $bins->fetch(PDO::FETCH_ASSOC)) echo "<option value='{$b['lgpla']}'>{$b['lgpla']}</option>";
                        ?>
                    </select>
                    <button type="submit" name="post_transfer" class="btn btn-primary w-100">Execute Move</button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3 h-100 border-warning shadow-sm">
                <h5 class="text-warning text-dark mb-3">2. Change Status (QC)</h5>
                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    <label class="fw-bold small">Pilih Stok</label>
                    <select name="quant_id_change" class="form-select mb-3">
                        <?php 
                        // Reuse query pointer (execute ulang)
                        $stmt->execute();
                        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$r['quant_id']}'>HU:{$r['hu_id']} (Current: {$r['stock_type']})</option>";
                        }
                        ?>
                    </select>
                    <label class="fw-bold small">Status Baru</label>
                    <div class="btn-group w-100 mb-3">
                        <input type="radio" class="btn-check" name="new_status" id="f1" value="F1" checked>
                        <label class="btn btn-outline-success" for="f1">F1 (Ok)</label>

                        <input type="radio" class="btn-check" name="new_status" id="q4" value="Q4">
                        <label class="btn btn-outline-warning" for="q4">Q4 (QC)</label>

                        <input type="radio" class="btn-check" name="new_status" id="b6" value="B6">
                        <label class="btn btn-outline-danger" for="b6">B6 (Block)</label>
                    </div>
                    <button type="submit" name="post_status_change" class="btn btn-warning w-100">Change Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>