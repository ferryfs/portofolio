<?php 
// Panggil koneksi dari folder root (mundur 2 langkah)
include '../../koneksi.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAP EWM Monitor (/SCWM/MON)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sap-header { background-color: #0a2e52; color: white; padding: 15px 20px; border-bottom: 4px solid #f0ab00; }
        .tcode { font-family: monospace; background: #333; color: #0f0; padding: 2px 6px; border-radius: 4px; font-size: 0.9rem; }
        .card-menu { transition: 0.3s; cursor: pointer; border: none; border-left: 5px solid transparent; height: 100%; }
        .card-menu:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .border-inbound { border-left-color: #0d6efd; }
        .border-outbound { border-left-color: #198754; }
        .border-master { border-left-color: #ffc107; }
        .border-tools { border-left-color: #6610f2; }
    </style>
</head>
<body>

<div class="sap-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <h4 class="mb-0 fw-bold"><i class="bi bi-box-seam-fill"></i> SAP S/4HANA EWM</h4>
        <span class="badge bg-light text-dark">Simulasi Lite</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="tcode me-2">/SCWM/MON</span>
        <a href="tasks.php" class="btn btn-sm btn-outline-info text-white border-light"><i class="bi bi-clock-history"></i> WT History</a>
        <a href="../../index.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-left"></i> Exit</a>
    </div>
</div>

<div class="container mt-4">
    
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm card-menu border-inbound">
                <h6 class="fw-bold text-primary"><i class="bi bi-box-arrow-in-down"></i> Inbound Process</h6>
                <p class="small text-muted mb-3">Penerimaan barang & Putaway otomatis (Empty Bin Strategy).</p>
                <a href="inbound.php" class="btn btn-sm btn-primary w-100 mt-auto">Create Inbound Delivery</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm card-menu border-outbound">
                <h6 class="fw-bold text-success"><i class="bi bi-box-arrow-up"></i> Outbound Process</h6>
                <p class="small text-muted mb-3">Pengiriman & Picking Barang (FIFO Strategy).</p>
                <a href="outbound.php" class="btn btn-sm btn-success w-100 mt-auto">Create Outbound Task</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm card-menu border-master">
                <h6 class="fw-bold text-warning"><i class="bi bi-database"></i> Master Data</h6>
                <p class="small text-muted mb-3">Product Master, Storage Bin, & HU Management.</p>
                <a href="master_data.php" class="btn btn-sm btn-warning w-100 mt-auto">Manage Master Data</a>
            </div>
        </div>
    </div>

    <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="bi bi-tools"></i> Execution & Tools</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card p-3 shadow-sm card-menu border-tools bg-dark text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold"><i class="bi bi-phone"></i> RF Framework</h6>
                        <p class="small mb-0 text-white-50">Handheld Scanner Simulation</p>
                    </div>
                    <a href="rf_scanner.php" target="_blank" class="btn btn-light btn-sm fw-bold">OPEN SCANNER</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 shadow-sm card-menu border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold"><i class="bi bi-printer"></i> HU Label Printing</h6>
                        <p class="small text-muted mb-0">Cetak Label Pallet / Handling Unit</p>
                    </div>
                    <a href="print_label.php" target="_blank" class="btn btn-outline-dark btn-sm">PRINT LAST HU</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-task"></i> Physical Stock (Quants)</h6>
            <div>
                <span class="badge bg-secondary">Live Data</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark small">
                        <tr>
                            <th>Storage Type</th>
                            <th>Storage Bin</th>
                            <th>Product Code</th>
                            <th>Description</th>
                            <th>HU ID</th>
                            <th>Batch</th>
                            <th class="text-end">Qty</th>
                            <th class="text-center">UoM</th>
                            <th>GR Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // JOIN TABEL PAKE PREFIX wms_
                        $q = mysqli_query($conn, "
                            SELECT q.*, p.product_code, p.description, p.base_uom, s.lgtyp 
                            FROM wms_quants q
                            JOIN wms_products p ON q.product_uuid = p.product_uuid
                            JOIN wms_storage_bins s ON q.lgpla = s.lgpla
                            ORDER BY s.lgtyp, s.lgpla ASC
                        ");
                        
                        if(mysqli_num_rows($q) > 0) {
                            while($row = mysqli_fetch_assoc($q)):
                        ?>
                        <tr class="small">
                            <td><span class="badge bg-secondary"><?= $row['lgtyp'] ?></span></td>
                            <td class="fw-bold text-primary"><?= $row['lgpla'] ?></td>
                            <td class="fw-bold"><?= $row['product_code'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td><?= $row['hu_id'] ?? '-' ?></td>
                            <td><?= $row['batch'] ?></td>
                            <td class="text-end fw-bold"><?= number_format($row['qty'], 0) ?></td>
                            <td class="text-center"><?= $row['base_uom'] ?></td>
                            <td><?= $row['gr_date'] ?></td>
                        </tr>
                        <?php endwhile; 
                        } else {
                            echo '<tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-box-seam display-4 d-block mb-2"></i>Gudang Kosong (No Stock Found)<br><small>Silakan lakukan Inbound Process.</small></td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>