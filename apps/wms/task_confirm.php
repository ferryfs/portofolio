<?php
// apps/wms/task_confirm.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}

require_once __DIR__ . '/../../config/database.php';
require_once 'koneksi.php'; 

// 1. CEK ID
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: task.php?err=noid"); 
    exit();
}

$id = sanitizeInt($_GET['id']); // Tanum (Task Number)

// 2. AMBIL DATA TASK (PDO)
$stmt = $pdo->prepare("
    SELECT t.*, p.product_code, p.product_name 
    FROM wms_warehouse_tasks t 
    JOIN wms_products p ON t.product_uuid = p.product_uuid 
    WHERE t.tanum = ?
");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$d) die("Task ID tidak ditemukan.");

// 3. PROSES KONFIRMASI (POST)
if(isset($_POST['confirm_task'])) {
    $actual_bin = $_POST['actual_bin']; 
    $remarks    = $_POST['remarks'];
    
    $type = $d['process_type'];
    $prod = $d['product_uuid'];
    $qty  = $d['qty'];
    $batch = $d['batch'] ?? '-';
    $hu   = $d['hu_id'] ?? '';

    try {
        $pdo->beginTransaction();

        if($type == 'PUTAWAY') {
            // INSERT STOK BARU
            $sql = "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date, hu_id) 
                    VALUES (?, ?, ?, ?, NOW(), ?)";
            safeQuery($pdo, $sql, [$prod, $actual_bin, $batch, $qty, $hu]);
        
        } else if($type == 'PICKING') {
            // KURANGI STOK SOURCE
            $src_bin = $d['source_bin'];
            $stmt_cek = $pdo->prepare("SELECT quant_id, qty FROM wms_quants WHERE product_uuid=? AND lgpla=? LIMIT 1");
            $stmt_cek->execute([$prod, $src_bin]);
            $d_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
            
            if($d_cek) {
                $new_qty = $d_cek['qty'] - $qty;
                $qid = $d_cek['quant_id'];
                
                if($new_qty <= 0) safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$qid]);
                else safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$new_qty, $qid]);
                
                // Tambah stok ke GI-ZONE (Virtual Bin buat Shipping)
                safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date, hu_id) VALUES (?, 'GI-ZONE', ?, ?, NOW(), ?)", 
                [$prod, $batch, $qty, $hu]);

            } else {
                throw new Exception("Stok fisik di bin $src_bin tidak ditemukan!");
            }
        }

        // UPDATE STATUS TASK
        safeQuery($pdo, "UPDATE wms_warehouse_tasks SET status='CONFIRMED', dest_bin=?, updated_at=NOW() WHERE tanum=?", [$actual_bin, $id]);

        $pdo->commit();
        header("Location: task.php?msg=success");
        exit();

    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head> 
    <title>Confirm Task</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body { background-color: #e9ecef; }</style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-dark text-white py-3 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-check2-square me-2"></i> Confirm Task #<?= $id ?></h5>
                </div>
                <div class="card-body p-4">
                    
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <div class="bg-light p-3 rounded-3 mb-4 border">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Product Details</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <h4 class="mb-0 fw-bold"><?= $d['product_code'] ?></h4>
                                <small class="text-muted"><?= $d['product_name'] ?? 'Product Item' ?></small>
                            </div>
                            <div class="ms-auto text-end">
                                <span class="badge bg-primary fs-5 px-3 py-2"><?= (float)$d['qty'] ?></span>
                                <div class="small text-muted mt-1">Quantity</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row small">
                            <div class="col-6">
                                <span class="text-muted">Batch:</span> <b><?= $d['batch'] ?? '-' ?></b>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted">Type:</span> 
                                <b class="<?= $d['process_type']=='PUTAWAY'?'text-success':'text-danger' ?>">
                                    <?= $d['process_type'] ?>
                                </b>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Actual Bin Location</label>
                            
                            <?php if($d['process_type'] == 'PUTAWAY'): ?>
                                <select name="actual_bin" class="form-select form-select-lg fw-bold border-success">
                                    <option value="<?= $d['dest_bin'] ?>" selected><?= $d['dest_bin'] ?> (Suggested)</option>
                                    <?php 
                                    $b = $pdo->query("SELECT lgpla FROM wms_storage_bins WHERE lgtyp='0010' LIMIT 10");
                                    while($bin = $b->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='{$bin['lgpla']}'>{$bin['lgpla']}</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text text-success"><i class="bi bi-info-circle"></i> You can change destination bin if needed.</div>
                            
                            <?php else: ?>
                                <input type="text" name="actual_bin" class="form-control form-control-lg fw-bold bg-light" value="<?= $d['source_bin'] ?>" readonly>
                                <div class="form-text text-danger"><i class="bi bi-lock-fill"></i> Source bin cannot be changed (Stock allocated).</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="confirm_task" class="btn btn-success py-3 fw-bold shadow-sm">
                                <i class="bi bi-check-lg me-2"></i> CONFIRM EXECUTION
                            </button>
                            <a href="task.php" class="btn btn-light py-2 text-muted border">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>