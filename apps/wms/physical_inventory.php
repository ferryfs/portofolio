<?php
// apps/wms/physical_inventory.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) exit("Akses Ditolak.");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

if(isset($_POST['post_count'])) {
    if (!verifyCSRFToken()) die("Invalid Token");

    $qid = sanitizeInt($_POST['quant_id']);
    $qty_phys = (float)$_POST['qty_physical'];
    $qty_sys  = (float)$_POST['qty_system'];
    $prod_uuid= $_POST['product_uuid'];
    $bin      = $_POST['bin'];
    $user     = $_SESSION['wms_fullname'];

    $diff = $qty_phys - $qty_sys;

    if($diff != 0) {
        try {
            $pdo->beginTransaction();

            // 1. Update Stok Real
            if($qty_phys == 0) {
                safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$qid]);
            } else {
                safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$qty_phys, $qid]);
            }

            // 2. Catat Task 'PI_ADJ' untuk report selisih
            // 'DIFFERENCE' adalah bin virtual untuk menampung selisih
            safeQuery($pdo, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, source_bin, dest_bin, qty, status, created_at) VALUES ('PI_ADJ', ?, ?, 'DIFFERENCE', ?, 'CONFIRMED', NOW())", 
            [$prod_uuid, $bin, $diff]);
            
            // 3. Log Audit
            catat_log($pdo, $user, 'ADJUST', 'INV', "PI Bin $bin: $qty_sys -> $qty_phys (Diff: $diff)");
            
            $pdo->commit();
            $msg = "Adjustment Posted. Selisih: $diff";
            $alert = "warning";
        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "Error: " . $e->getMessage();
            $alert = "danger";
        }
    } else {
        $msg = "Count Match (Sesuai).";
        $alert = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Physical Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="bi bi-clipboard-check"></i> Stock Opname</h4>
        <a href="stock_master.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-$alert'>$msg</div>"; ?>

    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr><th>Bin</th><th>HU ID</th><th>Product</th><th>System Qty</th><th>Physical Qty</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php
                    // Ambil semua stok di rak '0010' (Rak Penyimpanan)
                    $sql = "SELECT q.*, p.product_code, p.base_uom FROM wms_quants q 
                            JOIN wms_products p ON q.product_uuid = p.product_uuid 
                            JOIN wms_storage_bins s ON q.lgpla = s.lgpla 
                            WHERE s.lgtyp = '0010' ORDER BY q.lgpla";
                    $stmt = $pdo->query($sql);
                    
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <form method="POST">
                            <?php echo csrfTokenField(); ?>
                            <input type="hidden" name="quant_id" value="<?= $row['quant_id'] ?>">
                            <input type="hidden" name="qty_system" value="<?= $row['qty'] ?>">
                            <input type="hidden" name="product_uuid" value="<?= $row['product_uuid'] ?>">
                            <input type="hidden" name="bin" value="<?= $row['lgpla'] ?>">

                            <td class="fw-bold"><?= $row['lgpla'] ?></td>
                            <td><span class="badge bg-secondary"><?= $row['hu_id'] ?></span></td>
                            <td><?= $row['product_code'] ?></td>
                            <td class="fw-bold text-primary"><?= (float)$row['qty'] ?></td>
                            <td>
                                <div class="input-group input-group-sm" style="width:140px">
                                    <input type="number" name="qty_physical" class="form-control fw-bold" value="<?= (float)$row['qty'] ?>" step="any">
                                    <span class="input-group-text"><?= $row['base_uom'] ?></span>
                                </div>
                            </td>
                            <td><button type="submit" name="post_count" class="btn btn-sm btn-dark">Post</button></td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>