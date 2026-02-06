<?php
// apps/tms/fleet.php (FIXED PDO & CSRF)

session_name("TMS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

// ============================================================
// 1. LOGIC TAMBAH ARMADA
// ============================================================
if (isset($_POST['add_fleet'])) {
    
    // 🔥 FIX: Tambahkan Validasi CSRF
    if (!verifyCSRFToken()) {
        die("Security Alert: Invalid Token. Silakan refresh halaman.");
    }
    
    $plate  = sanitizeInput($_POST['plate_number']);
    $type   = sanitizeInput($_POST['vehicle_type']);
    $vendor = sanitizeInt($_POST['vendor_id']); 
    
    // Cek Duplikat Plat (Gunakan 'plate_number' sesuai JSON)
    $cek = safeGetOne($pdo, "SELECT id FROM tms_vehicles WHERE plate_number = ?", [$plate]);
    
    if ($cek) {
        echo "<script>alert('Plat nomor sudah terdaftar!'); window.location='fleet.php';</script>";
    } else {
        // Insert Data (Sesuaikan kolom dengan JSON)
        $sql = "INSERT INTO tms_vehicles (vendor_id, plate_number, vehicle_type, status) VALUES (?, ?, ?, 'available')";
        
        if(safeQuery($pdo, $sql, [$vendor, $plate, $type])) {
            echo "<script>alert('Armada $plate Berhasil Ditambah!'); window.location='fleet.php';</script>";
        } else {
            echo "<script>alert('Gagal menambah armada.'); window.location='fleet.php';</script>";
        }
    }
}

// ============================================================
// 2. AMBIL DATA (VIEW)
// ============================================================

// Ambil Fleet + Vendor Name
// Gunakan LEFT JOIN agar jika vendor terhapus, mobil tetap muncul
$fleets = [];
try {
    $fleets = safeGetAll($pdo, "SELECT v.*, ven.name as vendor_name, ven.type as v_type 
                                FROM tms_vehicles v 
                                LEFT JOIN tms_vendors ven ON v.vendor_id = ven.id 
                                ORDER BY v.id DESC");
} catch (Exception $e) { /* Silent Error if table not exists */ }

// Ambil List Vendor untuk Dropdown
$vendors = [];
try {
    $vendors = safeGetAll($pdo, "SELECT * FROM tms_vendors");
} catch (Exception $e) { /* Silent */ }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fleet Management | LogiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
            <a href="fleet.php" class="nav-link active"><i class="fa fa-truck"></i> Fleet Management</a>
            <a href="drivers.php" class="nav-link"><i class="fa fa-users-gear"></i> Drivers</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Settings</div>
            <a href="billing.php" class="nav-link"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="help.php" class="nav-link"><i class="fa fa-circle-question"></i> User Guide</a>
            <a href="auth.php?logout=true" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0"><i class="fa fa-truck text-primary"></i> Fleet Management</h3>
                <p class="text-muted small mb-0">Manage Vehicles & Vendor Assets</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFleet">
                <i class="fa fa-plus me-2"></i> Add Vehicle
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table class="table table-hover align-middle" id="tableFleet">
                    <thead class="table-light">
                        <tr><th>Plate Number</th><th>Type</th><th>Vendor / Owner</th><th>Capacity</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($fleets as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['plate_number']) ?></td>
                            <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['vendor_name'] ?? 'Unknown Vendor') ?> 
                                <?php if(($row['v_type'] ?? '') =='internal'): ?>
                                    <span class="badge bg-light text-dark border ms-1">Internal</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark ms-1">3PL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= number_format($row['capacity_weight'] ?? 0, 0) ?> Kg 
                                <?php if($row['capacity_volume']): ?> / <?= $row['capacity_volume'] ?> CBM <?php endif; ?>
                            </td> 
                            <td><span class="badge bg-success text-uppercase"><?= $row['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFleet">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Armada Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    
                    <div class="modal-body">
                        <div class="mb-3"><label>Nomor Polisi (Plat)</label><input type="text" name="plate_number" class="form-control" placeholder="B 1234 XYZ" required></div>
                        <div class="mb-3"><label>Tipe Kendaraan</label>
                            <select name="vehicle_type" class="form-select">
                                <option value="Blindvan">Blindvan</option>
                                <option value="CDE Box">CDE (Engkel) Box</option>
                                <option value="CDD Box">CDD (Double) Box</option>
                                <option value="Wingbox">Wingbox</option>
                            </select>
                        </div>
                        <div class="mb-3"><label>Pemilik (Vendor)</label>
                            <select name="vendor_id" class="form-select">
                                <?php foreach($vendors as $v): ?>
                                <option value="<?=$v['id']?>"><?=$v['name']?> (<?=$v['type']?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="add_fleet" class="btn btn-primary">Simpan Armada</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>$(document).ready(function() { $('#tableFleet').DataTable(); });</script>
</body>
</html>