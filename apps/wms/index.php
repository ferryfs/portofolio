<?php 
// apps/wms/index.php (V15: ULTIMATE CONTROL TOWER)
// Features: Real-time Chart.js, Live Metrics, Proper Layout, Iframe Fix

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once 'koneksi.php';

// --- FUNGSI SAFETY CHECK ---
function isTableExists($pdo, $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// --- LOGIKA DATA DASHBOARD REAL-TIME ---
$total_open = 0; 
$total_recv = 0; 
$used_bins = 0;
$total_bins = 0;
$free_bins = 0;
$usage_percent = 0;
$vol_in = 0;
$vol_out = 0;

if (isTableExists($pdo, 'wms_warehouse_tasks')) {
    $total_open = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_warehouse_tasks WHERE status = 'OPEN'")['c'] ?? 0;
    $total_recv = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_warehouse_tasks WHERE process_type = 'PUTAWAY' AND DATE(created_at) = CURDATE()")['c'] ?? 0;
}

if (isTableExists($pdo, 'wms_storage_bins') && isTableExists($pdo, 'wms_quants')) {
    $used_bins = safeGetOne($pdo, "SELECT COUNT(DISTINCT lgpla) as c FROM wms_quants WHERE lgpla NOT IN ('GR-ZONE', 'BLOCK-ZONE', 'SYSTEM')")['c'] ?? 0;
    $total_bins = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_storage_bins WHERE lgpla NOT IN ('GR-ZONE', 'BLOCK-ZONE', 'SYSTEM')")['c'] ?? 0;
    
    $free_bins = max(0, $total_bins - $used_bins);
    if ($total_bins > 0) {
        $usage_percent = round(($used_bins / $total_bins) * 100);
    }
}

if (isTableExists($pdo, 'wms_stock_movements')) {
    // Volume Inbound hari ini (Barang masuk dari Vendor)
    $vol_in = safeGetOne($pdo, "SELECT COALESCE(SUM(qty_change),0) as c FROM wms_stock_movements WHERE move_type = 'GR_IN' AND DATE(created_at) = CURDATE()")['c'] ?? 0;
    // Volume Outbound hari ini (Barang keluar / Picking)
    $vol_out = safeGetOne($pdo, "SELECT COALESCE(ABS(SUM(qty_change)),0) as c FROM wms_stock_movements WHERE qty_change < 0 AND DATE(created_at) = CURDATE()")['c'] ?? 0;
}

$chart_color = '#4f46e5'; 
if($usage_percent > 80) $chart_color = '#f59e0b'; 
if($usage_percent > 90) $chart_color = '#ef4444'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMS Enterprise | Control Tower</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5; --primary-light: #e0e7ff;
            --success: #10b981; --success-light: #d1fae5;
            --warning: #f59e0b; --warning-light: #fef3c7;
            --danger: #ef4444; --danger-light: #fee2e2;
            --dark: #0f172a; --gray: #64748b; --bg: #f8fafc; --card: #ffffff;
        }

        body { background-color: var(--bg); font-family: 'Inter', sans-serif; color: var(--dark); padding-bottom: 50px; }
        
        .navbar-custom { background: var(--dark); padding: 15px 0; border-bottom: 3px solid var(--primary); }
        .navbar-brand { font-weight: 800; letter-spacing: 0.5px; }
        
        .action-bar { background: white; padding: 20px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; }

        .module-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid #e2e8f0; border-radius: 20px; background: var(--card); height: 100%; position: relative; overflow: hidden; cursor: pointer; }
        .module-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border-color: var(--primary); }
        .module-card::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary); transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease; }
        .module-card:hover::after { transform: scaleX(1); }
        .icon-box-lg { width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; font-size: 32px; margin-bottom: 20px; transition: 0.3s; }
        .module-card:hover .icon-box-lg { transform: scale(1.1) rotate(5deg); }

        .c-inbound { background: var(--primary-light); color: var(--primary); }
        .c-outbound { background: var(--success-light); color: var(--success); }
        .c-inventory { background: var(--warning-light); color: var(--warning); }
        .c-master { background: #e2e8f0; color: #475569; }

        /* Health Panel & Iframe */
        .glass-panel { background: var(--card); border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); overflow: hidden; display: flex; flex-direction: column; height: 100%; }
        .panel-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        
        /* Iframe fix */
        .iframe-wrapper { flex-grow: 1; display: flex; min-height: 550px; background: var(--bg); }
        .iframe-wrapper iframe { flex-grow: 1; width: 100%; border: none; }

        .metric-box { padding: 15px; border-radius: 16px; background: var(--bg); border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        
        .chart-container { position: relative; height: 220px; width: 100%; display: flex; justify-content: center; align-items: center; margin: 20px 0; }
        .chart-center-text { position: absolute; text-align: center; pointer-events: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <i class="bi bi-box-seam-fill text-primary" style="font-size: 1.5rem;"></i> 
            <span>WMS <span style="font-weight: 300;">Enterprise</span></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-dark border border-secondary btn-sm rounded-pill px-3" onclick="showGuide()"><i class="bi bi-question-circle me-1"></i> Help</button>
            <div class="text-white text-end lh-1 d-none d-md-block ms-2 pe-3 border-end border-secondary">
                <div class="small fw-bold"><?= htmlspecialchars($_SESSION['wms_fullname']) ?></div>
                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px;"><?= htmlspecialchars($_SESSION['wms_role']) ?></div>
            </div>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-circle" style="width: 35px; height: 35px; display: flex; align-items:center; justify-content:center;" title="Logout"><i class="bi bi-power"></i></a>
        </div>
    </div>
</nav>

<div class="action-bar">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Control Dashboard</h4>
            <div class="text-muted small"><?= date('l, d F Y') ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="rf_scanner.php" target="_blank" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-qr-code-scan me-2"></i> RF Emulator</a>
            <a href="logs.php" class="btn btn-danger text-white rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-shield-check me-2"></i> Audit Center</a>
        </div>
    </div>
</div>

<div class="container">
    
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="module-card p-4" onclick="window.location='inbound.php'">
                <div class="d-flex justify-content-between">
                    <div class="icon-box-lg c-inbound"><i class="bi bi-box-arrow-in-down"></i></div>
                    <span class="badge bg-primary rounded-pill align-self-start bg-opacity-10 text-primary px-3 py-2">RCV</span>
                </div>
                <h5 class="fw-bold mb-2">Inbound</h5>
                <p class="text-muted small mb-0 lh-sm">Goods Receiving, Quality Check & Putaway.</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="module-card p-4">
                <div class="d-flex justify-content-between">
                    <div class="icon-box-lg c-outbound"><i class="bi bi-box-arrow-up"></i></div>
                    <span class="badge bg-success rounded-pill align-self-start bg-opacity-10 text-success px-3 py-2">SHP</span>
                </div>
                <h5 class="fw-bold mb-2">Outbound</h5>
                <p class="text-muted small mb-3 lh-sm">Order Picking & Goods Issue.</p>
                <div class="mt-auto d-flex gap-2 position-relative" style="z-index: 2;">
                    <a href="outbound.php" class="btn btn-sm btn-success fw-bold w-100 rounded-pill">Pick</a>
                    <a href="shipping.php" class="btn btn-sm btn-outline-success w-100 rounded-pill border-2">Ship</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="module-card p-4" onclick="window.location='stock_master.php'">
                <div class="icon-box-lg c-inventory"><i class="bi bi-boxes"></i></div>
                <h5 class="fw-bold mb-2">Inventory</h5>
                <p class="text-muted small mb-0 lh-sm">Live Stock Master & Adjustments.</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="module-card p-4" onclick="window.location='master_data.php'">
                <div class="icon-box-lg c-master"><i class="bi bi-database"></i></div>
                <h5 class="fw-bold mb-2">Master Data</h5>
                <p class="text-muted small mb-0 lh-sm">Product DB, Storage Bins & Configs.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="glass-panel">
                <div class="panel-header">
                    <h6 class="fw-bold m-0 text-dark"><span class="spinner-grow spinner-grow-sm text-danger me-2" role="status"></span> Live Task Monitor</h6>
                    <a href="task.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold text-muted"><i class="bi bi-arrows-fullscreen me-1"></i> Expand</a>
                </div>
                <div class="iframe-wrapper">
                    <iframe src="task.php"></iframe>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-panel p-4">
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-heart-pulse text-danger me-2"></i> Warehouse Health</h6>
                <p class="small text-muted mb-4">Real-time capacity & flow metrics</p>
                
                <div class="chart-container">
                    <canvas id="capacityChart"></canvas>
                    <div class="chart-center-text">
                        <h2 class="fw-bold m-0 text-dark" style="letter-spacing: -1px;"><?= $usage_percent ?>%</h2>
                        <span class="small text-muted">Used</span>
                    </div>
                </div>

                <div class="row g-2 mb-4 text-center">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border"><div class="small text-muted">Total Bins</div><div class="fw-bold fs-5"><?= number_format($total_bins) ?></div></div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border"><div class="small text-muted">Empty Bins</div><div class="fw-bold fs-5 text-success"><?= number_format($free_bins) ?></div></div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark small text-uppercase mt-2 mb-3">Today's Flow Volume</h6>
                
                <div class="metric-box">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded"><i class="bi bi-box-arrow-in-down fs-5"></i></div>
                        <div><div class="fw-bold text-dark">Inbound</div><div class="small text-muted">Received Qty</div></div>
                    </div>
                    <h4 class="fw-bold text-primary m-0">+<?= number_format((float)$vol_in) ?></h4>
                </div>

                <div class="metric-box">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded"><i class="bi bi-box-arrow-up fs-5"></i></div>
                        <div><div class="fw-bold text-dark">Outbound</div><div class="small text-muted">Issued Qty</div></div>
                    </div>
                    <h4 class="fw-bold text-warning m-0">-<?= number_format((float)$vol_out) ?></h4>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="guideModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow:hidden;">
            <div class="modal-header bg-dark text-white border-0 padding-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-rocket-takeoff me-2 text-warning"></i> Operational Guide</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <p class="text-muted mb-4">Sistem WMS Enterprise menggunakan alur <strong>Task-Directed</strong>. Semua pergerakan barang harus melalui pembuatan Task untuk menjamin akurasi Audit.</p>
                <div style="border-left: 2px dashed #cbd5e1; padding-left: 20px; margin-bottom: 25px; position: relative;">
                    <div style="width: 14px; height: 14px; background: var(--primary); border: 3px solid white; border-radius: 50%; position: absolute; left: -8px; top: 0; box-shadow: 0 0 0 1px #cbd5e1;"></div>
                    <h6 class="fw-bold text-primary mb-1">1. Inbound & Receiving</h6>
                    <p class="small text-muted mb-0">Buka Inbound > Pilih PO > Input Qty. Cetak Label HU.</p>
                </div>
                <div style="border-left: 2px dashed #cbd5e1; padding-left: 20px; margin-bottom: 25px; position: relative;">
                    <div style="width: 14px; height: 14px; background: var(--primary); border: 3px solid white; border-radius: 50%; position: absolute; left: -8px; top: 0; box-shadow: 0 0 0 1px #cbd5e1;"></div>
                    <h6 class="fw-bold text-primary mb-1">2. Putaway (Penyimpanan)</h6>
                    <p class="small text-muted mb-0">Gunakan RF Scanner/Desktop. Pindah dari GR-ZONE ke Rak Tujuan.</p>
                </div>
                <div style="border-left: 2px dashed transparent; padding-left: 20px; position: relative;">
                    <div style="width: 14px; height: 14px; background: var(--success); border: 3px solid white; border-radius: 50%; position: absolute; left: -8px; top: 0; box-shadow: 0 0 0 1px #cbd5e1;"></div>
                    <h6 class="fw-bold text-success mb-1">3. Outbound & Picking</h6>
                    <p class="small text-muted mb-0">Pilih SO > Generate Task > Pick Barang > PGI.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 🔥 INISIALISASI CHART.JS (Interaktif & Fungsional)
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('capacityChart').getContext('2d');
        const usedBins = <?= $used_bins ?>;
        const freeBins = <?= $free_bins ?>;
        const chartColor = '<?= $chart_color ?>';

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Occupied Bins', 'Empty Bins'],
                datasets: [{
                    data: [usedBins, freeBins],
                    backgroundColor: [chartColor, '#f1f5f9'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });

        // Tampilkan guide awal
        if (!sessionStorage.getItem('wms_guide_seen')) { 
            new bootstrap.Modal(document.getElementById('guideModal')).show(); 
            sessionStorage.setItem('wms_guide_seen', 'true');
        }
    });

    function showGuide() {
        new bootstrap.Modal(document.getElementById('guideModal')).show();
    }
</script>
</body>
</html>