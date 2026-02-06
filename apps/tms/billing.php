<?php
// apps/tms/billing.php (FINAL FIXED)

session_name("TMS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

// ============================================================
// 1. UPDATE BIAYA (PDO)
// ============================================================
if(isset($_POST['update_cost'])) {
    if (!verifyCSRFToken()) { die("Invalid Token"); }
    
    $ship_id = sanitizeInt($_POST['shipment_id']);
    $cost    = sanitizeInt($_POST['actual_cost']);
    
    // Update Cost dan Status jadi 'completed' kalau sudah dibayar
    safeQuery($pdo, "UPDATE tms_shipments SET total_cost = ?, status = 'completed' WHERE id = ?", [$cost, $ship_id]);
    echo "<script>alert('Biaya berhasil diupdate!'); window.location='billing.php';</script>";
}

// ============================================================
// 2. AMBIL DATA SHIPMENT (QUERY OPTIMIZED)
// ============================================================
// Kita pakai LEFT JOIN semua biar data tetap muncul walau ada yang kosong
$sql = "SELECT 
            shp.id, shp.shipment_no, shp.total_cost, shp.status,
            COALESCE(v.plate_number, 'Unknown') as plate_number, 
            COALESCE(ven.name, 'Internal') as vendor_name, 
            COALESCE(ven.type, 'internal') as vendor_type,
            COALESCE(o.order_no, '-') as order_ref,
            COALESCE(o.total_weight, 0) as total_weight
        FROM tms_shipments shp
        LEFT JOIN tms_vehicles v ON shp.vehicle_id = v.id
        LEFT JOIN tms_vendors ven ON v.vendor_id = ven.id
        LEFT JOIN tms_shipment_stops ss ON ss.shipment_id = shp.id AND ss.stop_type = 'delivery'
        LEFT JOIN tms_orders o ON ss.order_id = o.id
        ORDER BY shp.id DESC";

try {
    $data = safeGetAll($pdo, $sql);
} catch (Exception $e) {
    $data = []; // Kalau error (tabel kosong), return array kosong biar gak crash
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Billing & Costing | LogiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0f172a; --accent: #f59e0b; --bg-body: #f1f5f9; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); overflow-x: hidden; }
        .sidebar { width: 250px; height: 100vh; position: fixed; top: 0; left: 0; background: var(--sidebar-bg); color: #94a3b8; z-index: 1000; transition: 0.3s; }
        .sidebar-brand { padding: 20px; font-size: 1.5rem; font-weight: 800; color: white; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: #94a3b8; padding: 12px 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-right: 3px solid var(--accent); }
        .main-content { margin-left: 250px; padding: 20px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"><i class="fa fa-map-location-dot text-warning me-2"></i>LogiTrack</div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link"><i class="fa fa-gauge-high"></i> Dashboard</a>
            <a href="orders.php" class="nav-link"><i class="fa fa-truck-ramp-box"></i> Orders (SO/DO)</a>
            <a href="outbound.php" class="nav-link"><i class="fa fa-boxes-packing"></i> Outbound (POD)</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Data Master</div>
            <a href="fleet.php" class="nav-link"><i class="fa fa-truck"></i> Fleet Management</a>
            <a href="drivers.php" class="nav-link"><i class="fa fa-users-gear"></i> Drivers</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Settings</div>
            <a href="billing.php" class="nav-link active"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="auth.php?logout=true" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0"><i class="fa fa-file-invoice-dollar text-primary"></i> Freight Billing</h3>
                <p class="text-muted small mb-0">Cost Allocation & Vendor Invoice Reconciliation</p>
            </div>
            <button class="btn btn-success shadow-sm"><i class="fa fa-file-excel me-2"></i> Export Report</button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table class="table table-hover align-middle" id="tableBill">
                    <thead class="table-light">
                        <tr><th>Shipment No</th><th>Vendor / Armada</th><th>Order Ref</th><th>Weight</th><th>Cost Calculation</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($data)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data shipment. Lakukan Dispatch di menu Orders dulu.</td></tr>
                        <?php else: ?>
                            <?php foreach($data as $row): 
                                $est_cost = 0;
                                // Hitung Estimasi (Rate Card: 2000/kg utk Internal)
                                if($row['vendor_type'] == 'internal') {
                                    $est_cost = $row['total_weight'] * 2000; 
                                }
                                $final_cost = ($row['total_cost'] > 0) ? $row['total_cost'] : $est_cost;
                            ?>
                            <tr>
                                <td class="fw-bold"><?=htmlspecialchars($row['shipment_no'])?></td>
                                <td>
                                    <div class="fw-bold"><?=htmlspecialchars($row['vendor_name'])?></div>
                                    <small class="text-muted"><?=htmlspecialchars($row['plate_number'])?></small>
                                    <?php if($row['vendor_type']=='internal'): ?>
                                        <span class="badge bg-light text-dark border ms-1">Internal</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark ms-1">3PL</span>
                                    <?php endif; ?>
                                </td>
                                <td><?=htmlspecialchars($row['order_ref'])?></td>
                                <td><?=$row['total_weight']?> Kg</td>
                                <td>
                                    <div class="fw-bold text-end">Rp <?=number_format($final_cost,0,',','.')?></div>
                                    <small class="text-muted d-block text-end fst-italic" style="font-size: 0.7rem;">
                                        <?= ($row['vendor_type']=='internal') ? '(Rate: 2.000/kg)' : '(Vendor Invoice)' ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if($row['total_cost'] > 0): ?>
                                        <span class="badge bg-success"><i class="fa fa-check"></i> BILLED</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ESTIMATED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCost<?=$row['id']?>">
                                        <i class="fa fa-edit"></i> Edit Cost
                                    </button>

                                    <div class="modal fade" id="modalCost<?=$row['id']?>">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header"><h5 class="modal-title">Update Actual Cost</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <form method="POST">
                                                    <?php echo csrfTokenField(); ?>
                                                    <input type="hidden" name="shipment_id" value="<?=$row['id']?>">
                                                    
                                                    <div class="modal-body">
                                                        <div class="mb-3"><label>Shipment No</label><input type="text" class="form-control" value="<?=$row['shipment_no']?>" readonly></div>
                                                        <div class="mb-3">
                                                            <label>Actual Cost (Rp)</label>
                                                            <input type="number" name="actual_cost" class="form-control" value="<?=$final_cost?>" required>
                                                            <div class="form-text">Masukkan biaya real (Tol/Bensin atau Tagihan Vendor).</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer"><button type="submit" name="update_cost" class="btn btn-primary">Save & Finalize</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>$(document).ready(function() { $('#tableBill').DataTable(); });</script>
</body>
</html>