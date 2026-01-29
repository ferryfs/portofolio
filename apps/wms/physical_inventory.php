<?php
// 🔥 1. PASANG SESSION DI PALING ATAS
session_name("WMS_APP_SESSION");
session_start();

// 🔥 2. CEK KEAMANAN (Opsional tapi PENTING)
// Biar orang gak bisa buka file ini langsung lewat URL tanpa login
if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}
include '../../koneksi.php';

if(isset($_POST['post_count'])) {
    $quant_id   = $_POST['quant_id'];
    $qty_phys   = $_POST['qty_physical']; 
    $qty_system = $_POST['qty_system'];
    $product_uuid = $_POST['product_uuid'];
    $bin        = $_POST['bin'];

    $diff = $qty_phys - $qty_system;

    if($diff != 0) {
        if($qty_phys == 0) mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='$quant_id'");
        else mysqli_query($conn, "UPDATE wms_quants SET qty='$qty_phys' WHERE quant_id='$quant_id'");

        // Log WT (PI_ADJ)
        mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
        (process_type, product_uuid, source_bin, dest_bin, qty, status) 
        VALUES ('PI_ADJ', '$product_uuid', '$bin', 'DIFFERENCE', '$diff', 'CONFIRMED')");
        
        $msg = "Adjustment Posted: Selisih $diff Pcs.";
        $msg_type = "warning";
    } else {
        $msg = "Count Perfect (Sesuai).";
        $msg_type = "success";
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
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="bi bi-clipboard-check"></i> Physical Inventory</h4>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Monitor</a>
    </div>

    <div class="alert alert-info small">
        <strong><i class="bi bi-info-circle"></i> Info:</strong> Masukkan jumlah fisik yang ditemukan. Angka yang tampil adalah <strong>Base Unit</strong> (Pcs/Unit).
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-$msg_type'>$msg</div>"; ?>

    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Bin</th>
                        <th>HU ID</th>
                        <th>Product</th>
                        <th>System Qty</th>
                        <th>Counted Qty</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $q = mysqli_query($conn, "SELECT q.*, p.product_code, p.base_uom FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid JOIN wms_storage_bins s ON q.lgpla = s.lgpla WHERE s.lgtyp = '0010' ORDER BY q.lgpla ASC");
                    while($row = mysqli_fetch_assoc($q)):
                    ?>
                    <form method="POST">
                        <tr>
                            <td class="fw-bold"><?= $row['lgpla'] ?></td>
                            <td><span class="badge bg-secondary"><?= $row['hu_id'] ?></span></td>
                            <td><?= $row['product_code'] ?></td>
                            
                            <td class="fw-bold text-primary">
                                <?= (float)$row['qty'] ?> <span class="small text-muted"><?= $row['base_uom'] ?></span>
                            </td>
                            
                            <td>
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="number" name="qty_physical" class="form-control fw-bold" value="<?= (float)$row['qty'] ?>" step="any" required>
                                    <span class="input-group-text"><?= $row['base_uom'] ?></span>
                                </div>
                                <input type="hidden" name="quant_id" value="<?= $row['quant_id'] ?>">
                                <input type="hidden" name="qty_system" value="<?= $row['qty'] ?>">
                                <input type="hidden" name="product_uuid" value="<?= $row['product_uuid'] ?>">
                                <input type="hidden" name="bin" value="<?= $row['lgpla'] ?>">
                            </td>
                            <td><button type="submit" name="post_count" class="btn btn-sm btn-dark">Post</button></td>
                        </tr>
                    </form>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>