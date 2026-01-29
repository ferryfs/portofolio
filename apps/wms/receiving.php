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

// --- LOGIC SAVE GR & CREATE PUTAWAY TASK ---
if(isset($_POST['save_gr'])) {
    $po_num  = $_POST['po_number'];
    $sj_num  = $_POST['sj_number'];
    
    // Generate GR Number Unik (Misal: GR20231025-001)
    $gr_num  = "GR" . date('Ymd') . "-" . rand(100,999);
    
    // 1. Simpan Header Receiving (Pastikan tabel wms_inbound_receiving ada, kalau blm ada skip/buat dulu)
    // mysqli_query($conn, "INSERT INTO wms_inbound_receiving (...) VALUES (...)"); 

    // 2. Loop Item Inputan User
    $products = $_POST['product_uuid']; // Array UUID
    $qty_recs = $_POST['qty_received']; // Array Qty Fisik

    $task_count = 0;

    foreach($products as $i => $prod_uuid) {
        $qty_rec = (float)$qty_recs[$i];
        
        // Skip jika qty terima 0 atau kosong
        if($qty_rec > 0) {
            
            // Strategi Bin: Cari Bin Kosong (Mockup Logic)
            // Di real case, query ke tabel wms_storage_bins
            $target_bin = "A-01-" . rand(10, 99); 
            
            $batch = "BATCH-" . date('ymd');
            $hu_id = "HU" . rand(100000,999999); // Generate Pallet ID

            // Insert Task (Status OPEN -> Operator harus confirm nanti)
            // Kolom 'status_task' DIHAPUS karena di DB kamu cuma ada 'status'
            $sql_task = "INSERT INTO wms_warehouse_tasks 
            (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at)
            VALUES ('PUTAWAY', '$prod_uuid', '$batch', '$hu_id', 'GR-ZONE', '$target_bin', '$qty_rec', 'OPEN', NOW())";
            
            if(mysqli_query($conn, $sql_task)) {
                $task_count++;
            } else {
                $err = mysqli_error($conn);
            }
        }
    }

    if($task_count > 0) {
        $success = "GR Posted! <b>$task_count Tasks</b> created. Check Task Monitor.";
    } else {
        $error = "Gagal membuat task. Pastikan Qty diisi. " . ($err ?? '');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Inbound Receiving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card-form { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
        .table-custom th { background-color: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-truck text-primary me-2"></i>Inbound Gate</h4>
            <small class="text-muted">Receiving PO & Generate Putaway Tasks</small>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>

    <?php if(isset($success)) echo "<div class='alert alert-success border-0 shadow-sm'><i class='bi bi-check-circle-fill me-2'></i> $success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger border-0 shadow-sm'><i class='bi bi-exclamation-triangle-fill me-2'></i> $error</div>"; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-form h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-text me-2"></i> 1. Document Reference</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Purchase Order (PO)</label>
                                <select name="po_number" id="poSelect" class="form-select" required>
                                    <option value="">-- Select PO --</option>
                                    <option value="PO-2023-001">PO-2023-001 - PT. SUPPLIER JAYA</option>
                                    <option value="PO-2023-002">PO-2023-002 - CV. SUMBER MAKMUR</option>
                                    <?php 
                                    // Uncomment kalau tabel PO udah ada
                                    // $q_po = mysqli_query($conn, "SELECT * FROM wms_po_header WHERE status='OPEN'");
                                    // while($row = mysqli_fetch_assoc($q_po)) {
                                    //     echo "<option value='".$row['po_number']."'>".$row['po_number']." - ".$row['vendor_name']."</option>";
                                    // }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Surat Jalan (SJ / DO)</label>
                                <input type="text" name="sj_number" class="form-control" placeholder="No. SJ dari Supplier" required>
                            </div>
                        </div>

                        <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">2. Unloading Check (Fisik)</h6>
                        
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle table-custom">
                                <thead>
                                    <tr>
                                        <th width="30%">Item / Product</th>
                                        <th width="15%" class="text-center">Ord (PO)</th>
                                        <th width="20%">Rcv (Fisik)</th>
                                        <th width="35%">Verification</th>
                                    </tr>
                                </thead>
                                <tbody id="poItemsBody">
                                    <tr><td colspan="4" class="text-center text-muted py-4">Please select PO Number first...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="save_gr" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i> POST GR & CREATE TASKS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-form h-100">
                <div class="card-header bg-warning bg-opacity-10 py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2"></i> Putaway Queue</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle small">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">WT No</th>
                                    <th>Product</th>
                                    <th class="text-end pe-3">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Ambil 7 Task Putaway Terakhir
                                $q_recent = mysqli_query($conn, "SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.process_type='PUTAWAY' ORDER BY t.tanum DESC LIMIT 7");
                                while($row = mysqli_fetch_assoc($q_recent)):
                                ?>
                                <tr>
                                    <td class="ps-3 text-primary fw-bold">#<?= $row['tanum'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= $row['product_code'] ?></div>
                                        <div class="text-muted" style="font-size:0.75em"><?= $row['dest_bin'] ?></div>
                                    </td>
                                    <td class="text-end pe-3 fw-bold"><?= (float)$row['qty'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-center border-0 py-3">
                    <a href="task.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Monitor</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Saat Pilih PO
    $('#poSelect').change(function() {
        var poNum = $(this).val();
        
        // --- SIMULASI DATA DUMMY (KARENA DB PO BELUM TENTU ADA) ---
        // Nanti ganti bagian ini dengan $.ajax ke 'get_po_items.php'
        if(poNum) {
            // Simulasi response JSON dari server
            var mockData = [
                { uuid: 'e577239e-2cc2-11ef-9ec2-d8bbc1215b6d', code: 'MAT-A-01', name: 'Raw Material A', qty_ord: 100, uom: 'KG' },
                { uuid: 'e577239e-2cc2-11ef-9ec2-d8bbc1215b6d', code: 'MAT-B-02', name: 'Packaging Box', qty_ord: 500, uom: 'PCS' }
            ];
            
            var rows = '';
            $.each(mockData, function(i, item) {
                rows += `
                <tr>
                    <td>
                        <div class="fw-bold text-dark">${item.code}</div>
                        <small class="text-muted">${item.name}</small>
                        <input type="hidden" name="product_uuid[]" value="${item.uuid}">
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">${item.qty_ord} ${item.uom}</span>
                        <input type="hidden" id="ord_${i}" value="${item.qty_ord}">
                    </td>
                    <td>
                        <input type="number" name="qty_received[]" data-id="${i}" class="form-control text-center fw-bold qty-input" placeholder="0">
                    </td>
                    <td id="status_${i}" class="small text-muted fst-italic align-middle">Waiting input...</td>
                </tr>
                `;
            });
            $('#poItemsBody').html(rows);
            
        } else {
            $('#poItemsBody').html('<tr><td colspan="4" class="text-center text-muted py-4">Please select PO Number first...</td></tr>');
        }
    });

    // Validasi Qty Real-time
    $(document).on('keyup change', '.qty-input', function() {
        var id = $(this).data('id');
        var ord = parseFloat($('#ord_'+id).val());
        var rcv = parseFloat($(this).val());
        var stat = $('#status_'+id);

        if(isNaN(rcv) || rcv === 0) {
            stat.html('Waiting input...'); return;
        }

        if(rcv === ord) {
            stat.html('<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> MATCH</span>');
        } else if(rcv < ord) {
            stat.html('<span class="text-warning fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> SHORT (' + (ord-rcv) + ')</span>');
        } else {
            stat.html('<span class="text-danger fw-bold"><i class="bi bi-x-circle-fill"></i> OVER (' + (rcv-ord) + ')</span>');
        }
    });
});
</script>

</body>
</html>