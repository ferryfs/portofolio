<?php 
// apps/wms/index.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

// 1. CEK LOGIN
if(!isset($_SESSION['wms_login'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once 'koneksi.php'; // Helper Log

// --- FUNGSI SAFETY CHECK (Cek tabel ada/nggak biar gak error fatal) ---
function isTableExists($pdo, $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// --- LOGIKA DATA DASHBOARD ---
$total_open = 0; 
$total_recv = 0; 
$usage_percent = 0;

if (isTableExists($pdo, 'wms_warehouse_tasks')) {
    // Open Tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wms_warehouse_tasks WHERE status = ?");
    $stmt->execute(['OPEN']);
    $total_open = $stmt->fetchColumn();

    // Today's Receiving
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wms_warehouse_tasks WHERE process_type = ? AND DATE(created_at) = ?");
    $stmt->execute(['PUTAWAY', $today]);
    $total_recv = $stmt->fetchColumn();
}

if (isTableExists($pdo, 'wms_storage_bins') && isTableExists($pdo, 'wms_quants')) {
    // Bin Usage
    $used = $pdo->query("SELECT COUNT(DISTINCT lgpla) FROM wms_quants")->fetchColumn();
    $total = $pdo->query("SELECT COUNT(*) FROM wms_storage_bins")->fetchColumn();

    if ($total > 0) {
        $usage_percent = round(($used / $total) * 100);
    }
}

$chart_color = '#0d6efd'; 
if($usage_percent > 80) $chart_color = '#ffc107'; 
if($usage_percent > 90) $chart_color = '#dc3545'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMS Enterprise Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .module-card { transition: 0.3s ease; border: none; border-radius: 16px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .module-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); cursor: pointer; }
        .icon-box { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 20px; font-size: 26px; }
        
        .bg-blue-light { background-color: #ecf4ff; color: #0d6efd; }
        .bg-green-light { background-color: #e6f8f0; color: #0ca678; }
        .bg-purple-light { background-color: #f3f0ff; color: #7950f2; }
        .bg-orange-light { background-color: #fff9db; color: #f59f00; }
        .bg-dark-light { background-color: #e9ecef; color: #212529; }
        .bg-red-light { background-color: #f8d7da; color: #dc3545; }

        .donut-chart { width: 180px; height: 180px; border-radius: 50%; background: conic-gradient(<?= $chart_color ?> 0% <?= $usage_percent ?>%, #e9ecef <?= $usage_percent ?>% 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative; }
        .donut-inner { width: 140px; height: 140px; background: white; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 0 20px rgba(0,0,0,0.05); }
        .monitor-container { border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: white; height: 100%; }
        
        .guide-step { border-left: 3px solid #dee2e6; padding-left: 20px; margin-bottom: 20px; position: relative; }
        .guide-step::before { content: ''; width: 12px; height: 12px; background: #dee2e6; border-radius: 50%; position: absolute; left: -7.5px; top: 5px; }
        .guide-step.active { border-left-color: #0d6efd; }
        .guide-step.active::before { background: #0d6efd; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
            <i class="bi bi-box-seam-fill text-primary"></i> WMS Enterprise
        </a>
        
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-info btn-sm rounded-pill px-3" onclick="showGuide(true)">
                <i class="bi bi-question-circle"></i> Help
            </button>

            <div class="text-white text-end lh-1 d-none d-md-block">
                <div class="small fw-bold">
                    Hi, <?= htmlspecialchars($_SESSION['wms_fullname']) ?>
                    <?php if(isset($_SESSION['wms_count'])): ?>
                        <span class="badge bg-warning text-dark ms-1">#<?= $_SESSION['wms_count'] ?></span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.75rem; opacity: 0.7;">
                    <?= htmlspecialchars($_SESSION['wms_role']) ?>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-power"></i></a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card module-card p-4 h-100" onclick="window.location='inbound.php'">
                <div class="d-flex justify-content-between">
                    <div class="icon-box bg-blue-light"><i class="bi bi-box-arrow-in-down"></i></div>
                    <span class="badge bg-primary rounded-pill align-self-start bg-opacity-10 text-primary">IN</span>
                </div>
                <h5 class="fw-bold mb-1">Inbound</h5>
                <p class="text-muted small mb-0">Receiving, QC & Putaway Strategy.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card module-card p-4 h-100">
                <div class="icon-box bg-green-light"><i class="bi bi-box-arrow-up"></i></div>
                <h5 class="fw-bold mb-1">Outbound</h5>
                <p class="text-muted small mb-3">Picking & Goods Issue.</p>
                <div class="mt-auto d-flex gap-2">
                    <a href="outbound.php" class="btn btn-sm btn-light text-success fw-bold w-100 border-0 bg-green-light">Pick</a>
                    <a href="shipping.php" class="btn btn-sm btn-outline-success w-100">Ship</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card module-card p-4 h-100" onclick="window.location='stock_master.php'">
                <div class="icon-box bg-purple-light"><i class="bi bi-boxes"></i></div>
                <h5 class="fw-bold mb-1">Inventory</h5>
                <p class="text-muted small mb-0">Stock Master & Opname.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card module-card p-4 h-100" onclick="window.location='master_data.php'">
                <div class="icon-box bg-orange-light"><i class="bi bi-database"></i></div>
                <h5 class="fw-bold mb-1">Master Data</h5>
                <p class="text-muted small mb-0">Products, Bins & Configurations.</p>
            </div>
        </div>
    </div>

    <h6 class="text-uppercase fw-bold text-muted mb-3 small tracking-wide"><i class="bi bi-tools me-1"></i> Tools</h6>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.open('rf_scanner.php', '_blank')">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-dark text-white mb-0" style="width:50px; height:50px; font-size:1.2rem;"><i class="bi bi-qr-code-scan"></i></div>
                    <div><h6 class="fw-bold mb-0">RF Scanner</h6><small class="text-muted">Handheld View</small></div>
                    <div class="ms-auto"><i class="bi bi-box-arrow-up-right text-muted"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.open('print_label.php', '_blank')">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-dark-light mb-0" style="width:50px; height:50px; font-size:1.2rem;"><i class="bi bi-printer-fill"></i></div>
                    <div><h6 class="fw-bold mb-0">Print Label</h6><small class="text-muted">Barcode Generator</small></div>
                    <div class="ms-auto"><i class="bi bi-box-arrow-up-right text-muted"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.location='logs.php'">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-red-light mb-0" style="width:50px; height:50px; font-size:1.2rem;"><i class="bi bi-shield-lock-fill"></i></div>
                    <div><h6 class="fw-bold mb-0 text-danger">Audit Logs</h6><small class="text-muted">Activity</small></div>
                    <div class="ms-auto"><i class="bi bi-chevron-right text-muted"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="monitor-container h-100">
                <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <span class="fw-bold text-dark"><i class="bi bi-activity me-2 text-danger"></i> Live Monitor</span>
                    <a href="task.php" class="text-decoration-none small text-muted"><i class="bi bi-arrows-fullscreen"></i> Full Screen</a>
                </div>
                <iframe src="task.php" style="width:100%; height:420px; border:none; display:block;"></iframe>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center">
                    <h6 class="text-uppercase text-muted fw-bold mb-4 small">Capacity</h6>
                    <div class="donut-chart mb-4">
                        <div class="donut-inner">
                            <h1 class="display-5 fw-bold mb-0 text-dark"><?= $usage_percent ?>%</h1>
                            <small class="text-muted" style="font-size: 12px;">Used</small>
                        </div>
                    </div>
                    <div class="row w-100 g-3">
                        <div class="col-6"><div class="p-3 rounded-3 bg-light border-0"><div class="text-muted small mb-1">Tasks</div><div class="fw-bold text-dark fs-5"><?= $total_open ?></div></div></div>
                        <div class="col-6"><div class="p-3 rounded-3 bg-light border-0"><div class="text-muted small mb-1">Today's In</div><div class="fw-bold text-dark fs-5"><?= $total_recv ?></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="guideModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold">Panduan Pengguna</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="alert alert-info">Selamat Datang di WMS Enterprise! Sistem ini menggunakan alur <b>Task-Directed</b>.</div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="guide-step active"><strong>1. Inbound:</strong> Buka menu Inbound > Pilih PO > Terima Barang. Sistem otomatis membuat Task Putaway.</div>
                        <div class="guide-step active"><strong>2. Putaway:</strong> Gunakan RF Scanner atau Task Monitor untuk memindahkan barang dari GR-ZONE ke Rak Tujuan.</div>
                    </div>
                    <div class="col-md-6">
                        <div class="guide-step active"><strong>3. Outbound:</strong> Buka menu Outbound > Pilih SO > Generate Picking Task.</div>
                        <div class="guide-step active"><strong>4. Shipping:</strong> Setelah picking selesai, lakukan Post Goods Issue (PGI) untuk memotong stok resmi.</div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light"><button type="button" class="btn btn-primary px-4" onclick="finishGuide()">Saya Paham</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('guideModal'));
        if (!sessionStorage.getItem('wms_guide_seen')) { myModal.show(); }
    });
    function finishGuide() {
        sessionStorage.setItem('wms_guide_seen', 'true');
        var myModalEl = document.getElementById('guideModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();
    }
    function showGuide(force = false) {
        var myModal = new bootstrap.Modal(document.getElementById('guideModal'));
        myModal.show();
    }
</script>
</body>
</html>