<?php
session_name("HRIS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$nama_admin = $_SESSION['hris_name'];

// --- HITUNG STATISTIK (PDO) ---
// 1. Total Karyawan
$res_emp = safeGetOne($pdo, "SELECT COUNT(*) as total FROM ess_users");
$total_emp = $res_emp['total'] ?? 0;

// 2. Cuti Pending
$res_cuti = safeGetOne($pdo, "SELECT COUNT(*) as total FROM ess_leaves WHERE status='Pending'");
$total_pending = $res_cuti['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard HRIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; background: #1e293b; color: #adb5bd; padding-top: 20px; transition: all 0.3s; }
        .sidebar-brand { color: white; font-size: 1.5rem; font-weight: 800; text-align: center; margin-bottom: 30px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .menu-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin: 20px 25px 10px; font-weight: 700; color: #64748b; }
        .nav-link { color: #adb5bd; padding: 12px 25px; text-decoration: none; display: flex; align-items: center; transition: 0.2s; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { background: #0d6efd; color: white; }
        .nav-link i { width: 25px; text-align: center; margin-right: 10px; }
        .main-content { margin-left: 260px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"><i class="fa fa-cube"></i> HRIS PRO</div>
        <a href="index.php" class="nav-link active"><i class="fa fa-gauge-high"></i> Dashboard</a>
        <div class="menu-title">EMPLOYEE MANAGEMENT</div>
        <a href="menu_employee.php" class="nav-link"><i class="fa fa-users"></i> Data Karyawan</a>
        <a href="#" class="nav-link"><i class="fa fa-id-card"></i> Kontrak & Dokumen</a>
        <a href="menu_attendance.php" class="nav-link"><i class="fa fa-clock me-3"></i> Absensi & Cuti</a>
        <div class="menu-title">FINANCE</div>
        <a href="#" class="nav-link"><i class="fa fa-money-bill-wave"></i> Payroll (Gaji)</a>
        <a href="#" class="nav-link"><i class="fa fa-receipt"></i> Reimbursement</a>
        <div class="menu-title">RECRUITMENT</div>
        <a href="#" class="nav-link"><i class="fa fa-user-plus"></i> Job Posting (ATS)</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h4 class="fw-bold text-dark">Dashboard Overview</h4>
                <p class="text-muted mb-0">Selamat Datang, <?php echo htmlspecialchars($nama_admin); ?></p>
            </div>
            <a href="auth.php?logout=true" class="btn btn-outline-danger"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted mb-1">Total Karyawan</h6><h3 class="fw-bold mb-0"><?php echo $total_emp; ?></h3></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa fa-users"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted mb-1">Kehadiran Hari Ini</h6><h3 class="fw-bold mb-0 text-success">98%</h3></div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted mb-1">Cuti Pending</h6><h3 class="fw-bold mb-0 text-warning"><?php echo $total_pending; ?></h3></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa fa-envelope-open-text"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted mb-1">Payroll Bulan Ini</h6><h3 class="fw-bold mb-0 text-info">Rp 850M</h3></div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fa fa-wallet"></i></div>
                </div>
            </div>
        </div>

        <div class="alert alert-primary mt-4 border-0 shadow-sm d-flex align-items-center" role="alert">
            <i class="fa fa-info-circle fa-2x me-3"></i>
            <div>
                <h6 class="fw-bold mb-1">System Update</h6>
                <small>Modul <strong>Payroll</strong> akan maintenance pada tanggal 25. Mohon selesaikan rekap sebelum tanggal tersebut.</small>
            </div>
        </div>
    </div>

</body>
</html>