<?php 
include '../../koneksi.php'; 

// --- LOGIKA PHP TETAP SAMA SEPERTI SEBELUMNYA ---
// 1. HITUNG OPEN TASKS
$q_open = mysqli_query($conn, "SELECT COUNT(*) as total FROM wms_warehouse_tasks WHERE status = 'OPEN'");
$d_open = mysqli_fetch_assoc($q_open);
$total_open = $d_open['total'];

// 2. HITUNG RECEIVED TODAY
$today = date('Y-m-d');
$q_recv = mysqli_query($conn, "SELECT COUNT(*) as total FROM wms_warehouse_tasks WHERE process_type = 'PUTAWAY' AND DATE(created_at) = '$today'");
$d_recv = mysqli_fetch_assoc($q_recv);
$total_recv = $d_recv['total'];

// 3. HITUNG KAPASITAS BIN
$q_bin_used = mysqli_query($conn, "SELECT COUNT(DISTINCT lgpla) as used FROM wms_quants");
$d_bin_used = mysqli_fetch_assoc($q_bin_used);
$q_bin_all  = mysqli_query($conn, "SELECT COUNT(*) as total FROM wms_storage_bins");
$d_bin_all  = mysqli_fetch_assoc($q_bin_all);

$usage_percent = 0;
if($d_bin_all['total'] > 0) {
    $usage_percent = round(($d_bin_used['used'] / $d_bin_all['total']) * 100);
}

// Tentukan warna chart
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
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Module Cards */
        .module-card { transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); border: none; border-radius: 16px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .module-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); cursor: pointer; }
        .icon-box { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 20px; font-size: 26px; }
        
        /* Colors */
        .bg-blue-light { background-color: #ecf4ff; color: #0d6efd; }
        .bg-green-light { background-color: #e6f8f0; color: #0ca678; }
        .bg-purple-light { background-color: #f3f0ff; color: #7950f2; }
        .bg-orange-light { background-color: #fff9db; color: #f59f00; }
        
        /* Tool Colors (New) */
        .bg-dark-light { background-color: #e9ecef; color: #212529; }
        .bg-red-light { background-color: #f8d7da; color: #dc3545; }

        /* Donut Chart CSS */
        .donut-chart {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: conic-gradient(
                <?= $chart_color ?> 0% <?= $usage_percent ?>%, 
                #e9ecef <?= $usage_percent ?>% 100%
            );
            display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative;
        }
        .donut-inner {
            width: 140px; height: 140px; background: white; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
        }

        /* Iframe Container */
        .monitor-container {
            border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: white; height: 100%;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
            <i class="bi bi-box-seam-fill text-primary"></i> WMS Enterprise
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="text-white text-end lh-1 d-none d-md-block">
                <div class="small fw-bold">PT. Maju Mundur</div>
                <div style="font-size: 0.75rem; opacity: 0.7;">Warehouse Operator</div>
            </div>
            <a href="../../index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card module-card p-4 h-100" onclick="window.location='receiving.php'">
                <div class="d-flex justify-content-between">
                    <div class="icon-box bg-blue-light"><i class="bi bi-box-arrow-in-down"></i></div>
                    <span class="badge bg-primary rounded-pill align-self-start" style="opacity:0.1; color: #0d6efd !important;">IN</span>
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
                <p class="text-muted small mb-0">Stock Master, Transfer & Opname.</p>
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

    <h6 class="text-uppercase fw-bold text-muted mb-3 small tracking-wide"><i class="bi bi-tools me-1"></i> Execution & Tools</h6>
    <div class="row g-4 mb-5">
        
        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.open('rf_scanner.php', '_blank')">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-dark text-white mb-0" style="width:50px; height:50px; font-size:1.2rem;">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">RF Framework</h6>
                        <small class="text-muted">Handheld Scanner Simulator</small>
                    </div>
                    <div class="ms-auto">
                        <i class="bi bi-box-arrow-up-right text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.open('print_label.php', '_blank')">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-dark-light mb-0" style="width:50px; height:50px; font-size:1.2rem;">
                        <i class="bi bi-printer-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">HU Label</h6>
                        <small class="text-muted">Print Pallet / Carton ID</small>
                    </div>
                    <div class="ms-auto">
                        <i class="bi bi-box-arrow-up-right text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.location='logs.php'">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-red-light mb-0" style="width:50px; height:50px; font-size:1.2rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-danger">Audit Logs</h6>
                        <small class="text-muted">System Security Activity</small>
                    </div>
                    <div class="ms-auto">
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="monitor-container h-100">
                <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <span class="fw-bold text-dark"><i class="bi bi-activity me-2 text-danger"></i> Live Operations Monitor</span>
                    <a href="task.php" class="text-decoration-none small text-muted hover-link"><i class="bi bi-arrows-fullscreen"></i> Full Screen</a>
                </div>
                <iframe src="task.php" style="width:100%; height:420px; border:none; display:block;"></iframe>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center">
                    
                    <h6 class="text-uppercase text-muted fw-bold mb-4 small tracking-wide">Warehouse Occupancy</h6>
                    
                    <div class="donut-chart mb-4">
                        <div class="donut-inner">
                            <h1 class="display-5 fw-bold mb-0 text-dark"><?= $usage_percent ?>%</h1>
                            <small class="text-muted" style="font-size: 12px;">Used</small>
                        </div>
                    </div>

                    <div class="row w-100 g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border-0">
                                <i class="bi bi-list-task text-danger mb-2 d-block fs-5"></i>
                                <div class="text-muted small mb-1">Open Tasks</div>
                                <div class="fw-bold text-dark fs-5"><?= $total_open ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border-0">
                                <i class="bi bi-box-seam text-success mb-2 d-block fs-5"></i>
                                <div class="text-muted small mb-1">Putaway Today</div>
                                <div class="fw-bold text-dark fs-5"><?= $total_recv ?></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>