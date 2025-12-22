<?php
include '../../koneksi.php';

$id = $_GET['id']; // ID Warehouse Task

// AMBIL DATA TASK
$q = mysqli_query($conn, "
    SELECT t.*, p.product_code, p.description 
    FROM wms_warehouse_tasks t 
    JOIN wms_products p ON t.product_uuid = p.product_uuid 
    WHERE t.tanum = '$id'
");
$d = mysqli_fetch_assoc($q);

// PROSES KONFIRMASI
if(isset($_POST['confirm_task'])) {
    $actual_bin = $_POST['actual_bin']; // Bin Pilihan Operator
    $remarks    = $_POST['remarks'];
    
    // 1. MASUKKAN STOK KE QUANTS (Fisik Barang masuk Rak)
    // Karena ini Putaway, berarti nambah stok baru
    $sql_stock = "INSERT INTO wms_quants 
        (product_uuid, lgpla, batch, qty, gr_date, stock_type, hu_id) 
        VALUES 
        ('{$d['product_uuid']}', '$actual_bin', '{$d['batch']}', '{$d['qty']}', NOW(), 'F1', '{$d['hu_id']}')";
    
    if(mysqli_query($conn, $sql_stock)) {
        // 2. UPDATE STATUS TASK JADI CONFIRMED
        // Update juga dest_bin kalau operator ganti bin
        $sql_update = "UPDATE wms_warehouse_tasks SET 
            status = 'CONFIRMED', 
            status_task = 'CLOSED',
            dest_bin = '$actual_bin', 
            confirmed_at = NOW(),
            operator_id = 'USER-01' -- Nanti diganti session user
            WHERE tanum = '$id'";
            
        mysqli_query($conn, $sql_update);
        
        // Redirect balik ke Task List
        header("Location: tasks.php?msg=success");
        exit();
    } else {
        $error = "Gagal update stok: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head> <title>Confirm Task</title> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> </head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Execute Warehouse Task #<?= str_pad($id, 10, "0", STR_PAD_LEFT) ?></h5>
                </div>
                <div class="card-body">
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Product</small><br>
                            <strong><?= $d['product_code'] ?></strong>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">Quantity</small><br>
                            <span class="fs-4 fw-bold"><?= (float)$d['qty'] ?></span>
                        </div>
                    </div>
                    <div class="row mb-3 border-bottom pb-3">
                        <div class="col-6">
                            <small class="text-muted">Handling Unit</small><br>
                            <strong><?= $d['hu_id'] ?></strong>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">Batch</small><br>
                            <strong><?= $d['batch'] ?></strong>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold text-primary">Destination Bin</label>
                            <div class="input-group">
                                <select name="actual_bin" class="form-select fw-bold" required>
                                    <option value="<?= $d['dest_bin'] ?>" selected>Suggested: <?= $d['dest_bin'] ?></option>
                                    <option disabled>--- Override Bin ---</option>
                                    <?php 
                                    // Tampilkan Bin Kosong Lainnya buat opsi pindah
                                    $b = mysqli_query($conn, "SELECT lgpla FROM wms_storage_bins WHERE lgtyp='0010'");
                                    while($bin = mysqli_fetch_assoc($b)) {
                                        echo "<option value='{$bin['lgpla']}'>{$bin['lgpla']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-text text-muted">
                                *Sistem menyarankan <b><?= $d['dest_bin'] ?></b>. Ubah jika Bin penuh/rusak.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Contoh: Bin A penuh, pindah ke Bin B..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="confirm_task" class="btn btn-success p-2 fw-bold">
                                ✅ CONFIRM PUTAWAY
                            </button>
                            <a href="tasks.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>