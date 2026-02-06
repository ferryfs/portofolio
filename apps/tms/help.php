<?php
session_name("TMS_APP_SESSION");
session_start();
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>User Guide | LogiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .accordion-button:not(.collapsed) { background-color: #fef3c7; color: #92400e; }
        .step-number { background: var(--accent); color: #000; width: 25px; height: 25px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; font-size: 0.8rem; }
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
            <a href="billing.php" class="nav-link"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="help.php" class="nav-link active"><i class="fa fa-circle-question"></i> User Guide</a>
            <a href="auth.php?logout=true" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div><h3 class="fw-bold mb-0"><i class="fa fa-book-open text-primary"></i> User Guide</h3><p class="text-muted small mb-0">Panduan penggunaan sistem LogiTrack TMS</p></div>
        </div>

        <div class="row">
            <div class="col-lg-9">
                <div class="accordion shadow-sm" id="accordionGuide">
                    <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                        <h2 class="accordion-header"><button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c1"><i class="fa fa-database me-2"></i> 1. Persiapan Data (Master Data)</button></h2>
                        <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#accordionGuide"><div class="accordion-body bg-white"><p>Sebelum memulai order, pastikan data Armada dan Supir sudah terdaftar.</p></div></div>
                    </div>
                    </div>
            </div>
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm p-3">
                    <h6 class="fw-bold mb-3">Butuh Bantuan?</h6>
                    <p class="small text-muted">Hubungi tim IT Support jika terjadi kendala sistem.</p>
                    <a href="#" class="btn btn-outline-success btn-sm w-100"><i class="fab fa-whatsapp me-1"></i> Chat IT Support</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>