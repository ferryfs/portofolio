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

// A. LOGIC MOVE HU (PINDAH RAK)
if(isset($_POST['post_transfer'])) {
    $quant_id = $_POST['quant_id'];
    $dest_bin = $_POST['dest_bin'];

    $q_src = mysqli_query($conn, "SELECT * FROM wms_quants WHERE quant_id='$quant_id'");
    $d_src = mysqli_fetch_assoc($q_src);

    if($d_src) {
        // Update Bin Lokasi
        mysqli_query($conn, "UPDATE wms_quants SET lgpla='$dest_bin' WHERE quant_id='$quant_id'");
        
        // Log Task (Internal Movement)
        mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
        (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status)
        VALUES ('INTERNAL', '{$d_src['product_uuid']}', '{$d_src['batch']}', '{$d_src['hu_id']}', '{$d_src['lgpla']}', '$dest_bin', '{$d_src['qty']}', 'CONFIRMED')");

        $msg = "✅ Sukses! HU <b>{$d_src['hu_id']}</b> dipindahkan ke $dest_bin.";
        $msg_type = "success";
    }
}

// B. LOGIC POSTING CHANGE (UBAH STATUS F1 <-> B6)
if(isset($_POST['post_status_change'])) {
    $quant_id_target = $_POST['quant_id_change'];
    $new_status      = $_POST['new_status'];

    $q_old = mysqli_query($conn, "SELECT * FROM wms_quants WHERE quant_id='$quant_id_target'");
    $d_old = mysqli_fetch_assoc($q_old);

    // Update Status
    $update = mysqli_query($conn, "UPDATE wms_quants SET stock_type='$new_status' WHERE quant_id='$quant_id_target'");
    
    if($update) {
        // Log Task (Status Change / QC)
        mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
        (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status)
        VALUES ('INTERNAL', '{$d_old['product_uuid']}', '{$d_old['batch']}', '{$d_old['hu_id']}', 'STATUS: {$d_old['stock_type']}', 'STATUS: $new_status', '{$d_old['qty']}', 'CONFIRMED')");

        $msg = "✅ Status Stok Berhasil Diubah jadi: <b>$new_status</b>";
        $msg_type = "warning";
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
<body class="bg-light">
<div class="container mt-4">
    
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="bi bi-arrows-move"></i> Internal Warehouse Process</h4>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Monitor</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-$msg_type'>$msg</div>"; ?>

    <div class="row">
        
        <div class="col-md-6 mb-4">
            <div class="card shadow border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">1. Ad-Hoc Movement (Pindah Rak)</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small text-muted p-2 mb-3">
                        <i class="bi bi-info-circle"></i> Memindahkan satu Pallet/HU utuh ke Rak lain.
                    </div>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold small">Pilih Handling Unit (HU)</label>
                            <select name="quant_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih HU di Rak --</option>
                                <?php 
                                $q = mysqli_query($conn, "SELECT q.*, p.product_code, p.base_uom FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid ORDER BY q.lgpla ASC");
                                while($row = mysqli_fetch_assoc($q)) {
                                    // FIX: QTY PAKE (float) BIAR GAK JADI FORMAT IDR (50.000)
                                    echo "<option value='".$row['quant_id']."'>[Bin: ".$row['lgpla']."] HU: ".$row['hu_id']." | ".$row['product_code']." | Qty: ". (float)$row['qty'] ." ".$row['base_uom']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Bin Tujuan</label>
                            <select name="dest_bin" class="form-select form-select-sm" required>
                                <?php 
                                $b = mysqli_query($conn, "SELECT lgpla FROM wms_storage_bins ORDER BY lgpla ASC");
                                while($row = mysqli_fetch_assoc($b)) echo "<option value='".$row['lgpla']."'>".$row['lgpla']."</option>";
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="post_transfer" class="btn btn-primary btn-sm w-100">Execute Transfer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">2. Posting Change (Quality/Block)</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small text-muted p-2 mb-3">
                        <i class="bi bi-info-circle"></i> Ubah status stok (Simulasi QC: Good to Damaged/Blocked).
                    </div>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold small">Pilih Stok</label>
                            <select name="quant_id_change" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Stok --</option>
                                <?php 
                                // Reset pointer query agar bisa diloop lagi
                                mysqli_data_seek($q, 0);
                                while($row = mysqli_fetch_assoc($q)) {
                                    echo "<option value='".$row['quant_id']."'>HU: ".$row['hu_id']." (Current: ".$row['stock_type'].")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Ubah Status Menjadi:</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="new_status" id="s1" value="F1" checked>
                                <label class="btn btn-outline-success btn-sm" for="s1">F1 (Avail)</label>

                                <input type="radio" class="btn-check" name="new_status" id="s2" value="Q4">
                                <label class="btn btn-outline-warning btn-sm" for="s2">Q4 (Quality)</label>

                                <input type="radio" class="btn-check" name="new_status" id="s3" value="B6">
                                <label class="btn btn-outline-danger btn-sm" for="s3">B6 (Block)</label>
                            </div>
                        </div>
                        <button type="submit" name="post_status_change" class="btn btn-warning btn-sm w-100">Change Status</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>