<?php
include '../../koneksi.php';

// LOGIC SIMPAN INBOUND (GR)
if(isset($_POST['post_gr'])) {
    $prod_uuid = $_POST['product_uuid'];
    $qty       = $_POST['qty'];
    $batch     = "BATCH-" . date('ymd'); // Auto Batch
    $gr_date   = date('Y-m-d');

    // 1. STRATEGI PUTAWAY (Cari Bin Kosong di Storage Type 0010)
    // Kita cari bin di 0010 yang GAK ADA di tabel quants
    $cari_bin = mysqli_query($conn, "
        SELECT b.lgpla FROM wms_storage_bins b 
        LEFT JOIN wms_quants q ON b.lgpla = q.lgpla 
        WHERE b.lgtyp = '0010' AND q.quant_id IS NULL 
        LIMIT 1
    ");
    
    $bin_data = mysqli_fetch_assoc($cari_bin);

    if($bin_data) {
        $target_bin = $bin_data['lgpla'];
        
        // 2. INSERT KE QUANTS (STOCK)
        $sql = "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date) 
                VALUES ('$prod_uuid', '$target_bin', '$batch', '$qty', '$gr_date')";
        
        if(mysqli_query($conn, $sql)) { // INSERT WAREHOUSE TASK (LOG)
        mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
        (process_type, product_uuid, source_bin, dest_bin, qty, status)
        VALUES ('PUTAWAY', '$prod_uuid', 'GR-ZONE', '$target_bin', '$qty', 'CONFIRMED')");
            $msg = "✅ Goods Receipt Sukses! Barang otomatis ditaruh di Bin: <b>$target_bin</b>";
        } else {
            $msg = "❌ Gagal Insert Database.";
        }
    } else {
        $msg = "⚠️ Gagal Putaway: Gudang Penuh (Tidak ada Bin kosong di tipe 0010).";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Inbound Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Inbound Delivery (GR)</h5>
                </div>
                <div class="card-body">
                    
                    <?php if(isset($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Product</label>
                            <select name="product_uuid" class="form-select" required>
                                <?php 
                                $p = mysqli_query($conn, "SELECT * FROM wms_products");
                                while($row = mysqli_fetch_assoc($p)) {
                                    echo "<option value='".$row['product_uuid']."'>".$row['product_code']." - ".$row['description']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Quantity</label>
                            <input type="number" name="qty" class="form-control" required>
                        </div>
                        
                        <div class="alert alert-warning small">
                            <i class="bi bi-info-circle"></i> Sistem akan otomatis mencari Bin Kosong (Empty Bin Strategy).
                        </div>

                        <button type="submit" name="post_gr" class="btn btn-primary w-100">Post Goods Receipt</button>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Back to Monitor</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>