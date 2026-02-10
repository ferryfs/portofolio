<?php
// apps/wms/physical_inventory.php
// V9: SECURE STOCK OPNAME

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$msg = ""; $alert = "";

// PROSES POSTING (ADJUSTMENT)
if(isset($_POST['post_count'])) {
    $qid = sanitizeInt($_POST['quant_id']);
    $qty_phys = (float)$_POST['qty_physical']; // Input User
    $qty_sys  = (float)$_POST['qty_system'];   // Stok Lama
    $prod_uuid= $_POST['product_uuid'];
    $bin      = $_POST['bin'];
    $user     = $_SESSION['wms_fullname'];

    $diff = $qty_phys - $qty_sys;

    if($diff != 0) {
        try {
            $pdo->beginTransaction();

            // 1. Update Real Stock (Quant)
            if($qty_phys <= 0) {
                safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$qid]);
            } else {
                safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$qty_phys, $qid]);
            }

            // 2. Audit Trail (Movement)
            safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, ?, 'PI_ADJ', ?)", 
                      ["PI-$bin", $prod_uuid, "BIN-$bin", $diff, $user]);

            // 3. Log Task (For Reporting)
            safeQuery($pdo, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, source_bin, dest_bin, qty, status, created_at) VALUES ('PI_ADJ', ?, ?, 'DIFFERENCE', ?, 'CONFIRMED', NOW())", 
                      [$prod_uuid, $bin, $diff]);

            $pdo->commit();
            $msg = "✅ Adjustment Posted. Difference: $diff";
            $alert = "warning";

        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "Error: " . $e->getMessage();
            $alert = "danger";
        }
    } else {
        $msg = "✅ Count Match. No adjustment needed.";
        $alert = "success";
    }
}

// QUERY STOK PER BIN (Untuk Opname)
// Fokus pada rak penyimpanan utama (misal '0010' atau 'GR-ZONE')
$stocks = safeGetAll($pdo, "
    SELECT q.*, p.product_code, p.description, p.base_uom 
    FROM wms_quants q 
    JOIN wms_products p ON q.product_uuid = p.product_uuid 
    ORDER BY q.lgpla ASC, p.product_code ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Physical Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; padding-bottom: 50px; }
        .card-pi { border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table thead th { background: #1e293b; color: white; font-weight: 500; text-transform: uppercase; font-size: 0.8rem; padding: 15px; }
        .table tbody td { padding: 15px; vertical-align: middle; }
    </style>
</head>
<body>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="bi bi-clipboard-check text-primary me-2"></i>Physical Inventory</h4>
            <p class="text-muted m-0">Stock Opname & Adjustment</p>
        </div>
        <a href="stock_master.php" class="btn btn-outline-secondary">Back to Master</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $alert ?> shadow-sm border-0 d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-2 fs-4"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="card-pi bg-white">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Bin Location</th>
                        <th>Product / Desc</th>
                        <th>HU / Batch</th>
                        <th class="text-end">System Qty</th>
                        <th style="width: 200px;">Physical Count</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($stocks as $row): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="quant_id" value="<?= $row['quant_id'] ?>">
                            <input type="hidden" name="qty_system" value="<?= $row['qty'] ?>">
                            <input type="hidden" name="product_uuid" value="<?= $row['product_uuid'] ?>">
                            <input type="hidden" name="bin" value="<?= $row['lgpla'] ?>">

                            <td class="fw-bold text-primary font-monospace"><?= $row['lgpla'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= $row['product_code'] ?></div>
                                <div class="small text-muted"><?= substr($row['description'],0,30) ?>...</div>
                            </td>
                            <td>
                                <div class="badge bg-light text-dark border mb-1">HU: <?= $row['hu_id'] ?></div><br>
                                <small class="text-muted">Batch: <?= $row['batch'] ?></small>
                            </td>
                            <td class="text-end fw-bold fs-5"><?= (float)$row['qty'] ?></td>
                            <td>
                                <div class="input-group">
                                    <input type="number" name="qty_physical" class="form-control fw-bold border-primary" 
                                           value="<?= (float)$row['qty'] ?>" step="0.01" required>
                                    <span class="input-group-text small"><?= $row['base_uom'] ?></span>
                                </div>
                            </td>
                            <td>
                                <button type="submit" name="post_count" class="btn btn-dark btn-sm fw-bold px-3">
                                    Post
                                </button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>