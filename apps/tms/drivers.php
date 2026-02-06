<?php
// apps/tms/drivers.php (PDO FULL)

session_name("TMS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

// TAMBAH DRIVER BARU (PDO)
if (isset($_POST['add_driver'])) {
    
    // Validasi CSRF
    if (!verifyCSRFToken()) { die("Invalid Token"); }

    $name  = sanitizeInput($_POST['fullname']);
    $phone = sanitizeInput($_POST['phone']);
    $sim   = sanitizeInput($_POST['license_type']);
    
    // 1. Create User Login Otomatis
    $username = strtolower(str_replace(' ', '', $name)) . rand(10,99);
    $password = 'driver123';
    $hash     = hashPassword($password); // Pakai helper security
    
    try {
        $pdo->beginTransaction();
        
        // Insert User
        $sql_user = "INSERT INTO tms_users (tenant_id, fullname, username, password, role, status) VALUES (1, ?, ?, ?, 'driver', 'active')";
        safeQuery($pdo, $sql_user, [$name, $username, $hash]);
        $new_user_id = $pdo->lastInsertId();

        // Insert Driver Profile
        $sql_driver = "INSERT INTO tms_drivers (user_id, vendor_id, phone, license_type) VALUES (?, 1, ?, ?)";
        safeQuery($pdo, $sql_driver, [$new_user_id, $phone, $sim]);
        
        $pdo->commit();
        echo "<script>alert('Driver $name Berhasil Ditambah! Login: $username'); window.location='drivers.php';</script>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Gagal menambah driver.');</script>";
    }
}

// AMBIL DATA DRIVER (PDO)
$drivers = $pdo->query("SELECT d.*, u.fullname, u.status as user_status FROM tms_drivers d JOIN tms_users u ON d.user_id = u.id ORDER BY d.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Driver Management | LogiTrack</title>
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
            <a href="fleet.php" class="nav-link"><i class="fa fa-truck"></i> Fleet Management</a>
            <a href="drivers.php" class="nav-link active"><i class="fa fa-users-gear"></i> Drivers</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Settings</div>
            <a href="billing.php" class="nav-link"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="help.php" class="nav-link"><i class="fa fa-circle-question"></i> User Guide</a>
            <a href="auth.php?logout=true" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0"><i class="fa fa-users-gear text-primary"></i> Driver Management</h3>
                <p class="text-muted small mb-0">Manage Personnel & Licenses</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDriver">
                <i class="fa fa-plus me-2"></i> Add Driver
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table class="table table-hover align-middle" id="tableDriver">
                    <thead class="table-light">
                        <tr><th>Nama Lengkap</th><th>No. HP / WA</th><th>Jenis SIM</th><th>Status Akun</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($drivers as $row): ?>
                        <tr>
                            <td class="fw-bold">
                                <img src="https://ui-avatars.com/api/?name=<?=urlencode($row['fullname'])?>&background=random" class="rounded-circle me-2" width="30">
                                <?=htmlspecialchars($row['fullname'])?>
                            </td>
                            <td><?=htmlspecialchars($row['phone'])?></td>
                            <td><span class="badge bg-secondary"><?=htmlspecialchars($row['license_type'])?></span></td>
                            <td><span class="badge bg-success text-uppercase"><?=htmlspecialchars($row['user_status'])?></span></td>
                            <td><button class="btn btn-sm btn-outline-dark"><i class="fa fa-pen"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDriver">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Registrasi Supir Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    
                    <div class="modal-body">
                        <div class="mb-3"><label>Nama Lengkap</label><input type="text" name="fullname" class="form-control" required></div>
                        <div class="mb-3"><label>No. HP (WhatsApp)</label><input type="text" name="phone" class="form-control" placeholder="0812..." required></div>
                        <div class="mb-3"><label>Jenis SIM</label>
                            <select name="license_type" class="form-select">
                                <option value="B1 Polos">B1 Polos</option>
                                <option value="B1 Umum">B1 Umum</option>
                                <option value="B2 Umum">B2 Umum</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="add_driver" class="btn btn-primary">Simpan Data</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>$(document).ready(function() { $('#tableDriver').DataTable(); });</script>
</body>
</html>