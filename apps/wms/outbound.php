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

// --- LOGIC GENERATE PICKING TASK ---
if(isset($_POST['create_picking'])) {
    $so_num = $_POST['so_number'];
    
    // Loop Item dari Form
    $products = $_POST['product_uuid']; 
    $qtys     = $_POST['qty_to_pick'];  

    $task_count = 0;
    $short_count = 0;

    foreach($products as $i => $prod_uuid) {
        $qty_need = (float)$qtys[$i];
        
        if($qty_need > 0) {
            // STRATEGI FIFO: Cari stok di wms_warehouse_tasks (yg status CONFIRMED & PUTAWAY)
            // Atau kalau tabel wms_quants lu udah jalan, pake query lu yg lama.
            // Disini gue pake simulasi Query Stok FIFO:
            
            // Misal kita ambil stok dari tabel Tasks (karena tabel quants mungkin blm sinkron)
            // Kita cari Task Putaway yg sisa stoknya masih ada (logika sederhana)
            // NAMUN, biar script lu jalan lancar, kita simplify:
            // "Picking langsung ambil dari Bin A-01 (General) kalau stok cukup"
            
            $batch = "BATCH-AUTO"; 
            $source_bin = "A-01-0" . rand(1,5); // Simulasi Bin Asal
            $hu_id = "HU-OUT-" . rand(100,999);

            // Create Task PICKING (Status OPEN) -> Picker harus ambil barang
            $sql_task = "INSERT INTO wms_warehouse_tasks 
            (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at)
            VALUES ('PICKING', '$prod_uuid', '$batch', '$hu_id', '$source_bin', 'GI-ZONE', '$qty_need', 'OPEN', NOW())";
            
            if(mysqli_query($conn, $sql_task)) {
                $task_count++;
            }
        }
    }

    if($task_count > 0) {
        $success = "Success! <b>$task_count Picking Tasks</b> created. Operator can start picking.";
    } else {
        $error = "Failed to create tasks. Check stock availability.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Outbound Picking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card-form { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .bg-gradient-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .table-custom th { background-color: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-box-arrow-up text-primary me-2"></i>Outbound Dock</h4>
            <small class="text-muted">Generate Picking Tasks from Sales Order</small>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>

    <?php if(isset($success)) echo "<div class='alert alert-success border-0 shadow-sm'><i class='bi bi-check-circle-fill me-2'></i> $success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger border-0 shadow-sm'><i class='bi bi-exclamation-triangle-fill me-2'></i> $error</div>"; ?>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card card-form h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-cart3 me-2"></i> 1. Sales Order Reference</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Select Sales Order (SO)</label>
                            <select name="so_number" id="soSelect" class="form-select form-select-lg" required>
                                <option value="">-- Choose Order --</option>
                                <option value="SO-2023-001">SO-2023-001 - TOKO MAJU JAYA</option>
                                <option value="SO-2023-002">SO-2023-002 - PT. SENTOSA ABADI</option>
                                <?php 
                                // $q_so = mysqli_query($conn, "SELECT * FROM wms_so_header WHERE status='OPEN'");
                                // while($row = mysqli_fetch_assoc($q_so)) {
                                //     echo "<option value='".$row['so_number']."'>".$row['so_number']." - ".$row['customer_name']."</option>";
                                // }
                                ?>
                            </select>
                        </div>

                        <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">2. Picking Strategy (Check Stock)</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle table-custom">
                                <thead>
                                    <tr>
                                        <th>Product Info</th>
                                        <th class="text-center">Ord Qty</th>
                                        <th class="text-center">Stock Avail</th>
                                        <th class="text-center" width="140">Qty Pick</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="soItemsBody">
                                    <tr><td colspan="5" class="text-center text-muted py-4">Select Sales Order to load items...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="create_picking" class="btn btn-success px-4 py-2 fw-bold shadow-sm" style="background: #764ba2; border:none;">
                                <i class="bi bi-box-seam me-2"></i> RELEASE PICKING TASKS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-form h-100">
                <div class="card-header bg-gradient-purple py-3 text-white">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i> Active Picking Tasks</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle small">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">WT No</th>
                                    <th>Item</th>
                                    <th class="text-end pe-3">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Ambil 7 Task Picking Terakhir
                                $q_pick = mysqli_query($conn, "SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.process_type='PICKING' ORDER BY t.tanum DESC LIMIT 7");
                                while($row = mysqli_fetch_assoc($q_pick)):
                                ?>
                                <tr>
                                    <td class="ps-3 text-primary fw-bold">#<?= $row['tanum'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= $row['product_code'] ?></div>
                                        <div class="text-muted" style="font-size:0.75em">
                                            <?= $row['source_bin'] ?> <i class="bi bi-arrow-right"></i> GI
                                        </div>
                                    </td>
                                    <td class="text-end pe-3 fw-bold text-danger"><?= (float)$row['qty'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-center border-0 py-3">
                    <a href="task.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Monitor All</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    $('#soSelect').change(function() {
        var soNum = $(this).val();
        
        // --- SIMULASI DATA DUMMY (Ganti dengan AJAX ke get_so_items.php) ---
        if(soNum) {
            var mockData = [
                { uuid: 'p1', code: 'MAT-A-01', desc: 'Raw Material A', qty_ord: 50, stock: 120, uom: 'KG' },
                { uuid: 'p2', code: 'PROD-X-99', desc: 'Finished Good X', qty_ord: 10, stock: 5, uom: 'BOX' } // Stok kurang
            ];

            var rows = '';
            $.each(mockData, function(i, item) {
                var pickQty = (item.stock >= item.qty_ord) ? item.qty_ord : item.stock;
                var statusBadge = (item.stock >= item.qty_ord) 
                    ? '<span class="badge bg-success">Ready</span>' 
                    : '<span class="badge bg-danger">Shortage</span>';

                rows += `
                <tr>
                    <td>
                        <div class="fw-bold">${item.code}</div>
                        <small class="text-muted">${item.desc}</small>
                        <input type="hidden" name="product_uuid[]" value="${item.uuid}">
                    </td>
                    <td class="text-center fw-bold">${item.qty_ord}</td>
                    <td class="text-center text-muted">${item.stock}</td>
                    <td class="text-center">
                        <input type="number" name="qty_to_pick[]" class="form-control text-center fw-bold" value="${pickQty}" readonly>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                </tr>
                `;
            });
            $('#soItemsBody').html(rows);
            
        } else {
            $('#soItemsBody').html('<tr><td colspan="5" class="text-center text-muted py-4">Select Sales Order...</td></tr>');
        }
    });
});
</script>

</body>
</html>