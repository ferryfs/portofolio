<?php
include '../../koneksi.php';

// LOGIC SAVE GR & CREATE PUTAWAY TASK
if(isset($_POST['save_gr'])) {
    $po_num  = $_POST['po_number'];
    $sj_num  = $_POST['sj_number'];
    $gr_num  = "GR-" . date('YmdHis');
    
    // 1. Simpan Header Receiving
    mysqli_query($conn, "INSERT INTO wms_inbound_receiving (gr_number, po_number, sj_number, received_date, status) VALUES ('$gr_num', '$po_num', '$sj_num', NOW(), 'POSTED')");

    // 2. Loop Item Inputan User
    $products = $_POST['product_uuid']; // Array
    $qty_ords = $_POST['qty_ordered'];  // Array
    $qty_recs = $_POST['qty_received']; // Array

    $task_count = 0;

    foreach($products as $i => $prod_uuid) {
        $qty_ord = $qty_ords[$i];
        $qty_rec = $qty_recs[$i];
        
        // Skip jika qty terima 0
        if($qty_rec > 0) {
            
            // ANALISA SELISIH (Short/Over/Match)
            $diff = $qty_rec - $qty_ord;
            $remark = "MATCH";
            if($diff < 0) $remark = "SHORT_SHIPMENT (Partial)";
            if($diff > 0) $remark = "OVER_DELIVERY (Subject to Return)";

            // AUTO CREATE PUTAWAY TASK (Status: OPEN)
            // Strategi: Cari Bin Kosong (Empty Bin)
            $cari_bin = mysqli_query($conn, "SELECT b.lgpla FROM wms_storage_bins b LEFT JOIN wms_quants q ON b.lgpla = q.lgpla WHERE b.lgtyp = '0010' AND q.quant_id IS NULL LIMIT 1");
            $bin_data = mysqli_fetch_assoc($cari_bin);
            
            // Kalau gudang penuh, taruh di 'GR-ZONE' (Temporary)
            $target_bin = ($bin_data) ? $bin_data['lgpla'] : 'GR-ZONE'; 
            
            $batch = "BATCH-" . date('ymd');
            $hu_id = "HU" . rand(100000,999999);

            // Insert Task (Status OPEN -> Operator harus confirm nanti)
            $sql_task = "INSERT INTO wms_warehouse_tasks 
            (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, status_task)
            VALUES ('PUTAWAY', '$prod_uuid', '$batch', '$hu_id', 'DOOR-IN', '$target_bin', '$qty_rec', 'OPEN', 'OPEN')";
            
            if(mysqli_query($conn, $sql_task)) {
                $task_count++;
                
                // Note: Kita BELUM tambah stok ke wms_quants. 
                // Stok baru bertambah nanti saat Task di-confirm oleh operator di menu 'Task Monitor'.
                // Ini best practice SAP: Stok di "Bin Tujuan" belum ada sebelum fisik ditaruh.
            }
        }
    }

    $msg = "✅ <b>Goods Receipt Posted!</b><br>GR No: $gr_num.<br>System created <b>$task_count Putaway Tasks</b> (Status: OPEN).<br>Silakan arahkan operator untuk execute task.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Inbound Receiving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> </head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="bi bi-truck"></i> Gate In & Receiving</h4>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

    <div class="card shadow border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">1. Reference Document (PO)</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="fw-bold">Pilih Purchase Order (PO)</label>
                        <select name="po_number" id="poSelect" class="form-select" required>
                            <option value="">-- Select PO --</option>
                            <?php 
                            $q_po = mysqli_query($conn, "SELECT * FROM wms_po_header WHERE status='OPEN'");
                            while($row = mysqli_fetch_assoc($q_po)) {
                                echo "<option value='".$row['po_number']."'>".$row['po_number']." - ".$row['vendor_name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Nomor Surat Jalan (SJ)</label>
                        <input type="text" name="sj_number" class="form-control" placeholder="Input DO/SJ Number" required>
                    </div>
                </div>

                <h6 class="border-bottom pb-2 fw-bold text-secondary">Items to Receive (Unloading Check)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product Code</th>
                                <th>Description</th>
                                <th class="text-center" width="120">Qty Order (PO)</th>
                                <th class="text-center" width="150">Qty Received (Fisik)</th>
                                <th>Variance Check</th>
                            </tr>
                        </thead>
                        <tbody id="poItemsBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Please select PO first...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning small">
                    <i class="bi bi-info-circle"></i> <strong>Sistem Logic:</strong><br>
                    1. Input Qty Fisik yang diterima.<br>
                    2. Jika Qty > Order, sistem akan menandai sebagai <strong>Over Delivery</strong>.<br>
                    3. Klik Save untuk membuat <strong>Warehouse Task (Putaway)</strong> dengan status <strong>OPEN</strong>.
                </div>

                <div class="d-grid">
                    <button type="submit" name="save_gr" class="btn btn-primary fw-bold p-2">
                        <i class="bi bi-save"></i> POST GR & CREATE TASKS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Saat pilih PO, panggil API
    $('#poSelect').change(function() {
        var poNum = $(this).val();
        if(poNum) {
            $.ajax({
                url: 'get_po_items.php',
                type: 'GET',
                data: { po: poNum },
                dataType: 'json',
                success: function(data) {
                    var rows = '';
                    $.each(data, function(key, val) {
                        rows += `
                        <tr>
                            <td>
                                <strong>${val.product_code}</strong>
                                <input type="hidden" name="product_uuid[]" value="${val.product_uuid}">
                            </td>
                            <td>${val.description}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary" style="font-size:1em;">${val.qty_ordered} ${val.base_uom}</span>
                                <input type="hidden" name="qty_ordered[]" id="ord_${key}" value="${val.qty_ordered}">
                            </td>
                            <td>
                                <input type="number" name="qty_received[]" id="rec_${key}" class="form-control fw-bold text-center qty-input" 
                                       data-id="${key}" placeholder="0" required>
                            </td>
                            <td id="status_${key}" class="fw-bold small text-muted">-</td>
                        </tr>
                        `;
                    });
                    $('#poItemsBody').html(rows);
                }
            });
        } else {
            $('#poItemsBody').html('<tr><td colspan="5" class="text-center text-muted">Please select PO first...</td></tr>');
        }
    });

    // Validasi Real-time Qty
    $(document).on('keyup change', '.qty-input', function() {
        var id = $(this).data('id');
        var ordered = parseFloat($('#ord_' + id).val());
        var received = parseFloat($(this).val());
        var statusCell = $('#status_' + id);

        if(isNaN(received)) received = 0;

        if(received == ordered) {
            statusCell.html('<span class="text-success"><i class="bi bi-check-circle"></i> Match</span>');
        } else if(received < ordered) {
            var diff = ordered - received;
            statusCell.html('<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Short (' + diff + ') - Partial</span>');
        } else {
            var diff = received - ordered;
            statusCell.html('<span class="text-danger"><i class="bi bi-x-circle"></i> Over (' + diff + ') - Subject Return</span>');
        }
    });
});
</script>

</body>
</html>