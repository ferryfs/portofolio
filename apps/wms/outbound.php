<?php
include '../../koneksi.php';

if(isset($_POST['post_gi'])) {
    $prod_uuid = $_POST['product_uuid'];
    $req_qty   = $_POST['qty']; // Qty yang diminta

    // 1. STRATEGI PICKING (FIFO - First In First Out)
    // Cari stok barang ini, urutkan dari GR Date terlama (ASC)
    $cari_stok = mysqli_query($conn, "
        SELECT * FROM wms_quants 
        WHERE product_uuid = '$prod_uuid' 
        ORDER BY gr_date ASC
    ");

    $sisa_butuh = $req_qty;
    $log_pesan = [];
    $sukses = true;

    // Loop stok yang ada
    while($row = mysqli_fetch_assoc($cari_stok)) {
        if($sisa_butuh <= 0) break; // Kalau kebutuhan udah terpenuhi, stop.

        $qty_di_bin = $row['qty'];
        $bin_lokasi = $row['lgpla'];
        $quant_id   = $row['quant_id'];

        if($qty_di_bin >= $sisa_butuh) {
            // Skenario A: Stok di bin ini CUKUP buat menuhin sisa kebutuhan
            $qty_ambil = $sisa_butuh;
            
            // Kurangi stok
            $qty_sisa_db = $qty_di_bin - $qty_ambil;
            if($qty_sisa_db == 0) {
                // Kalau habis, hapus record quant
                mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='$quant_id'");
            } else {
                // Update sisa stok
                mysqli_query($conn, "UPDATE wms_quants SET qty='$qty_sisa_db' WHERE quant_id='$quant_id'");
            }

            // 👉 UPDATE: CATAT KE WAREHOUSE TASK (LOG HISTORY)
            mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
            (process_type, product_uuid, source_bin, dest_bin, qty, status)
            VALUES ('PICKING', '$prod_uuid', '$bin_lokasi', 'GI-ZONE', '$qty_ambil', 'CONFIRMED')");

            $log_pesan[] = "✅ Diambil $qty_ambil Pcs dari Bin <b>$bin_lokasi</b> (Batch: {$row['batch']})";
            $sisa_butuh = 0; // Selesai

        } else {
            // Skenario B: Stok di bin ini KURANG (Ambil semua yang ada, lalu cari di bin lain)
            $qty_ambil = $qty_di_bin;
            
            // Hapus record karena diambil semua
            mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='$quant_id'");
            
            // 👉 UPDATE: CATAT KE WAREHOUSE TASK (LOG HISTORY)
            mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
            (process_type, product_uuid, source_bin, dest_bin, qty, status)
            VALUES ('PICKING', '$prod_uuid', '$bin_lokasi', 'GI-ZONE', '$qty_ambil', 'CONFIRMED')");
            
            $log_pesan[] = "⚠️ Diambil $qty_ambil Pcs dari Bin <b>$bin_lokasi</b> (Stok Habis, lanjut cari...)";
            $sisa_butuh -= $qty_ambil; // Masih butuh sisa
        }
    }

    if($sisa_butuh > 0) {
        $msg_type = "warning";
        $msg = "⚠️ Stok tidak cukup! Hanya berhasil mengambil sebagian. Kurang: $sisa_butuh Pcs.";
    } else {
        $msg_type = "success";
        $msg = "<b>Picking Selesai (FIFO Strategy):</b><br>" . implode("<br>", $log_pesan);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Outbound Picking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Outbound Delivery (Picking)</h5>
                </div>
                <div class="card-body">
                    
                    <?php if(isset($msg)) echo "<div class='alert alert-$msg_type'>$msg</div>"; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Product to Pick</label>
                            <select name="product_uuid" class="form-select" required>
                                <?php 
                                // Hanya tampilkan produk yang ADA stoknya
                                $p = mysqli_query($conn, "
                                    SELECT DISTINCT p.product_uuid, p.product_code, p.description 
                                    FROM wms_products p
                                    JOIN wms_quants q ON p.product_uuid = q.product_uuid
                                ");
                                while($row = mysqli_fetch_assoc($p)) {
                                    echo "<option value='".$row['product_uuid']."'>".$row['product_code']." - ".$row['description']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Quantity Needed</label>
                            <input type="number" name="qty" class="form-control" required>
                        </div>
                        
                        <div class="alert alert-light border small text-muted">
                            <strong>Strategi FIFO:</strong> Sistem akan otomatis mengambil barang dari Batch terlama (GR Date paling tua).
                        </div>

                        <button type="submit" name="post_gi" class="btn btn-success w-100">Create Warehouse Task (Picking)</button>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Back to Monitor</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>