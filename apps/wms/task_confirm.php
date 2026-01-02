<?php
include '../../koneksi.php';

// 1. CEK ID
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: task.php?err=noid"); // Balik kalau ga ada ID
    exit();
}

$id = $_GET['id']; // ID Warehouse Task

// 2. AMBIL DATA TASK
$q = mysqli_query($conn, "
    SELECT t.*, p.product_code, p.product_name 
    FROM wms_warehouse_tasks t 
    JOIN wms_products p ON t.product_uuid = p.product_uuid 
    WHERE t.tanum = '$id'
");

// Cek apakah data ketemu
if(mysqli_num_rows($q) == 0) {
    die("Task ID tidak ditemukan.");
}

$d = mysqli_fetch_assoc($q);

// 3. PROSES KONFIRMASI (POST)
if(isset($_POST['confirm_task'])) {
    $actual_bin = $_POST['actual_bin']; 
    $remarks    = $_POST['remarks'];
    
    // --- LOGIC UPDATE STOK (QUANTS) ---
    // Cek tipe proses: PUTAWAY (Nambah) atau PICKING (Kurang)
    $type = $d['process_type'];
    $prod = $d['product_uuid'];
    $qty  = $d['qty'];
    $batch = isset($d['batch']) ? $d['batch'] : '-';
    $hu   = isset($d['hu_id']) ? $d['hu_id'] : '';

    $success_stock = false;

    if($type == 'PUTAWAY') {
        // Insert Stok Baru
        $sql_stock = "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date, hu_id) 
                      VALUES ('$prod', '$actual_bin', '$batch', '$qty', NOW(), '$hu')";
        if(mysqli_query($conn, $sql_stock)) $success_stock = true;
    
    } else if($type == 'PICKING') {
        // Kurangi Stok dari Source Bin
        $src_bin = $d['source_bin'];
        // Cari stok yg cocok
        $q_cek = mysqli_query($conn, "SELECT quant_id, qty FROM wms_quants WHERE product_uuid='$prod' AND lgpla='$src_bin' LIMIT 1");
        $d_cek = mysqli_fetch_assoc($q_cek);
        
        if($d_cek) {
            $new_qty = $d_cek['qty'] - $qty;
            $qid = $d_cek['quant_id'];
            if($new_qty <= 0) mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='$qid'");
            else mysqli_query($conn, "UPDATE wms_quants SET qty='$new_qty' WHERE quant_id='$qid'");
            $success_stock = true;
        } else {
            $error = "Stok fisik di bin $src_bin tidak ditemukan!";
        }
    }

    // --- UPDATE TASK STATUS ---
    if($success_stock) {
        $sql_update = "UPDATE wms_warehouse_tasks SET 
            status = 'CONFIRMED', 
            dest_bin = '$actual_bin', 
            updated_at = NOW() 
            WHERE tanum = '$id'";
            
        if(mysqli_query($conn, $sql_update)) {
            header("Location: task.php?msg=success"); // Redirect ke task.php
            exit();
        } else {
            $error = "Gagal update task: " . mysqli_error($conn);
        }
    } else {
        if(!isset($error)) $error = "Gagal update stok database: " . mysqli_error($conn);
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
                                    // Opsi pindah rak
                                    $b = mysqli_query($conn, "SELECT lgpla FROM wms_storage_bins WHERE lgtyp='0010' LIMIT 10");
                                    while($bin = mysqli_fetch_assoc($b)) {
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