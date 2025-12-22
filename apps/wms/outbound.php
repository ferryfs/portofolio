<?php
include '../../koneksi.php';

// LOGIC GENERATE PICKING TASK
if(isset($_POST['create_picking'])) {
    $so_num = $_POST['so_number'];
    
    // Loop Item yang dikirim dari form
    $products = $_POST['product_uuid']; // Array
    $qtys     = $_POST['qty_to_pick'];  // Array

    $task_count = 0;
    $error_count = 0;

    foreach($products as $i => $prod_uuid) {
        $qty_need = $qtys[$i];
        
        if($qty_need > 0) {
            // STRATEGI FIFO: Cari stok F1, urutkan GR Date terlama
            $cari_stok = mysqli_query($conn, "SELECT * FROM wms_quants WHERE product_uuid='$prod_uuid' AND stock_type='F1' ORDER BY gr_date ASC");
            
            $sisa_butuh = $qty_need;

            while($row = mysqli_fetch_assoc($cari_stok)) {
                if($sisa_butuh <= 0) break;

                $qty_bin = $row['qty'];
                $bin_loc = $row['lgpla'];
                $hu_id   = $row['hu_id'];
                $batch   = $row['batch'];

                // Tentukan qty yang diambil dari bin ini
                $qty_ambil = ($qty_bin >= $sisa_butuh) ? $sisa_butuh : $qty_bin;

                // CREATE TASK (Picking)
                // Source: Bin Asal, Dest: GI-ZONE (Zona Pengiriman)
                $sql_task = "INSERT INTO wms_warehouse_tasks 
                (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, status_task)
                VALUES ('PICKING', '$prod_uuid', '$batch', '$hu_id', '$bin_loc', 'GI-ZONE', '$qty_ambil', 'CONFIRMED', 'OPEN')";
                
                // Note: Status 'CONFIRMED' di database lama lo artinya 'LOG'. 
                // Tapi Status Task 'OPEN' artinya belum dikerjakan operator. 
                // Nanti kita update logic confirm-nya.
                
                if(mysqli_query($conn, $sql_task)) {
                    $task_count++;
                    $sisa_butuh -= $qty_ambil;
                }
            }

            if($sisa_butuh > 0) $error_count++; // Ada yang ga cukup stoknya
        }
    }

    if($task_count > 0) {
        $msg = "✅ <b>Success!</b> $task_count Picking Tasks Created. Silakan cek Monitor.";
        if($error_count > 0) $msg .= "<br>⚠️ Beberapa item stoknya kurang (Partial Picking).";
        $msg_type = "success";
    } else {
        $msg = "❌ Gagal membuat task. Cek stok availability.";
        $msg_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Outbound Process</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="bi bi-box-arrow-up"></i> Outbound Delivery Order</h4>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-$msg_type'>$msg</div>"; ?>

    <div class="card shadow border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">1. Sales Order Reference</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                
                <div class="mb-4">
                    <label class="fw-bold">Select Sales Order (SO)</label>
                    <select name="so_number" id="soSelect" class="form-select form-select-lg" required>
                        <option value="">-- Choose Order to Pick --</option>
                        <?php 
                        $q_so = mysqli_query($conn, "SELECT * FROM wms_so_header WHERE status='OPEN'");
                        while($row = mysqli_fetch_assoc($q_so)) {
                            echo "<option value='".$row['so_number']."'>".$row['so_number']." - ".$row['customer_name']." (Due: ".$row['delivery_date'].")</option>";
                        }
                        ?>
                    </select>
                </div>

                <h6 class="border-bottom pb-2 fw-bold text-secondary">Picking Strategy (FIFO Allocation)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Ordered Qty</th>
                                <th class="text-center">Avail. Stock (F1)</th>
                                <th class="text-center" width="150">Qty to Pick</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="soItemsBody">
                            <tr><td colspan="5" class="text-center text-muted py-3">Please select Sales Order...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" name="create_picking" class="btn btn-success fw-bold p-2">
                        <i class="bi bi-list-check"></i> GENERATE PICKING TASKS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#soSelect').change(function() {
        var soNum = $(this).val();
        if(soNum) {
            $.ajax({
                url: 'get_so_items.php',
                type: 'GET',
                data: { so: soNum },
                dataType: 'json',
                success: function(data) {
                    var rows = '';
                    $.each(data, function(key, val) {
                        // Logic Status Stok
                        var needed = parseFloat(val.qty_ordered);
                        var avail  = parseFloat(val.stock_available);
                        var status = '';
                        var inputClass = '';

                        if(avail >= needed) {
                            status = '<span class="badge bg-success">Full Stock</span>';
                            inputClass = 'border-success';
                        } else if (avail > 0) {
                            status = '<span class="badge bg-warning text-dark">Partial ('+avail+')</span>';
                            inputClass = 'border-warning';
                        } else {
                            status = '<span class="badge bg-danger">Out of Stock</span>';
                            inputClass = 'border-danger';
                        }

                        // Auto-fill picking qty max sesuai avail
                        var pickQty = (avail >= needed) ? needed : avail;

                        rows += `
                        <tr>
                            <td>
                                <strong>${val.product_code}</strong><br><small>${val.description}</small>
                                <input type="hidden" name="product_uuid[]" value="${val.product_uuid}">
                            </td>
                            <td class="text-center fw-bold">${needed} ${val.base_uom}</td>
                            <td class="text-center">${avail} ${val.base_uom}</td>
                            <td>
                                <input type="number" name="qty_to_pick[]" class="form-control text-center fw-bold ${inputClass}" 
                                       value="${pickQty}" max="${avail}" min="0">
                            </td>
                            <td class="text-center">${status}</td>
                        </tr>
                        `;
                    });
                    $('#soItemsBody').html(rows);
                }
            });
        } else {
            $('#soItemsBody').html('<tr><td colspan="5" class="text-center text-muted">Select SO...</td></tr>');
        }
    });
});
</script>

</body>
</html>