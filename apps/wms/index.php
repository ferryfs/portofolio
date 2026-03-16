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

<!-- ============================================================ -->
<!-- USER GUIDE MODAL — FULL VERSION                             -->
<!-- ============================================================ -->
<style>
    /* Guide Modal Styles */
    #guideModal .modal-dialog { max-width: 860px; }
    .guide-nav { display: flex; gap: 6px; flex-wrap: wrap; padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .guide-nav .nav-btn { font-size: 0.75rem; font-weight: 600; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; transition: 0.2s; }
    .guide-nav .nav-btn:hover, .guide-nav .nav-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
    .guide-section { display: none; padding: 24px 28px 28px; }
    .guide-section.active { display: block; }
    .guide-section h5 { font-weight: 800; color: #0f172a; margin-bottom: 4px; }
    .guide-section h6 { font-weight: 700; color: var(--primary); margin: 20px 0 8px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .guide-section p, .guide-section li { font-size: 0.875rem; color: #475569; line-height: 1.7; }
    .guide-section ul { padding-left: 20px; margin-bottom: 12px; }
    .guide-section ul li { margin-bottom: 4px; }
    .flow-steps { counter-reset: step-counter; list-style: none; padding: 0; }
    .flow-steps li { counter-increment: step-counter; display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; padding: 12px 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
    .flow-steps li::before { content: counter(step-counter); min-width: 28px; height: 28px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0; }
    .flow-steps li .step-content strong { font-size: 0.875rem; color: #0f172a; display: block; margin-bottom: 2px; }
    .flow-steps li .step-content span { font-size: 0.8rem; color: #64748b; }
    .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0; }
    .status-badge { padding: 10px 14px; border-radius: 10px; font-size: 0.8rem; }
    .status-badge strong { display: block; font-size: 0.85rem; margin-bottom: 2px; }
    .zone-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 12px 0; }
    .zone-card { padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; font-size: 0.78rem; }
    .zone-card strong { display: block; font-size: 0.85rem; margin-bottom: 4px; }
    .note-box { padding: 12px 16px; border-radius: 10px; font-size: 0.82rem; margin: 12px 0; border-left: 4px solid; }
    .note-box.info { background: #eff6ff; border-color: #3b82f6; color: #1e40af; }
    .note-box.warning { background: #fffbeb; border-color: #f59e0b; color: #78350f; }
    .note-box.danger { background: #fef2f2; border-color: #ef4444; color: #7f1d1d; }
    .note-box.success { background: #f0fdf4; border-color: #10b981; color: #065f46; }
    .guide-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; margin: 12px 0; }
    .guide-table th { background: #0f172a; color: white; padding: 8px 12px; text-align: left; font-weight: 600; }
    .guide-table td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; color: #475569; vertical-align: top; }
    .guide-table tr:nth-child(even) td { background: #f8fafc; }
    .scenario-row td:first-child { font-weight: 700; color: #0f172a; }
    #guideModal .modal-body { padding: 0 !important; max-height: 72vh; overflow-y: auto; }
</style>

<div class="modal fade" id="guideModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow:hidden;">
            <!-- Header -->
            <div class="modal-header bg-dark text-white border-0" style="padding: 18px 24px;">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-book-half me-2 text-warning"></i> Smart WMS — User Guide Lengkap</h5>
                    <div style="font-size:0.75rem; color:#94a3b8;">Panduan operasional untuk semua pengguna sistem</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <!-- Tab Navigation -->
            <div class="guide-nav">
                <button class="nav-btn active" onclick="showGuideTab('overview', this)">📘 Overview</button>
                <button class="nav-btn" onclick="showGuideTab('inbound', this)">📥 Inbound</button>
                <button class="nav-btn" onclick="showGuideTab('putaway', this)">📦 Putaway</button>
                <button class="nav-btn" onclick="showGuideTab('outbound', this)">📤 Outbound</button>
                <button class="nav-btn" onclick="showGuideTab('picking', this)">🔍 Picking & PGI</button>
                <button class="nav-btn" onclick="showGuideTab('inventory', this)">📊 Inventory</button>
                <button class="nav-btn" onclick="showGuideTab('masterdata', this)">🗄️ Master Data</button>
                <button class="nav-btn" onclick="showGuideTab('rf', this)">📱 RF Scanner</button>
                <button class="nav-btn" onclick="showGuideTab('troubleshoot', this)">🔧 Troubleshooting</button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">

                <!-- ═══ TAB: OVERVIEW ═══ -->
                <div class="guide-section active" id="tab-overview">
                    <h5>📘 Konsep & Alur Sistem</h5>
                    <p class="text-muted">Smart WMS menggunakan prinsip <strong>Task-Directed</strong> — setiap pergerakan barang fisik harus memiliki Task di sistem terlebih dahulu.</p>

                    <h6>Zona Gudang</h6>
                    <div class="zone-grid">
                        <div class="zone-card" style="background:#eff6ff;"><strong>GR-ZONE</strong>Staging barang masuk dari vendor (status Q4)</div>
                        <div class="zone-card" style="background:#f0fdf4;"><strong>GI-ZONE</strong>Loading area barang siap kirim ke customer</div>
                        <div class="zone-card" style="background:#fef2f2;"><strong>BLOCK-ZONE</strong>Isolasi barang rusak/damaged (status B6)</div>
                        <div class="zone-card" style="background:#fafafa;"><strong>A-01-01 dst</strong>Rak reguler — stok normal (status F1)</div>
                        <div class="zone-card" style="background:#fffbeb;"><strong>OVERFLOW-ZONE</strong>Luapan jika semua bin penuh</div>
                        <div class="zone-card" style="background:#f8fafc;"><strong>SYSTEM</strong>Placeholder task sebelum bin ditentukan</div>
                    </div>

                    <h6>Tipe Stok</h6>
                    <div class="status-grid">
                        <div class="status-badge" style="background:#fffbeb;"><strong>Q4 — Quarantine</strong>Baru diterima, belum putaway, belum bisa dijual</div>
                        <div class="status-badge" style="background:#f0fdf4;"><strong>F1 — Unrestricted</strong>Stok bersih, bebas dijual & di-reserve SO</div>
                        <div class="status-badge" style="background:#fef2f2;"><strong>B6 — Blocked/Damaged</strong>Barang rusak, tidak bisa dijual</div>
                        <div class="status-badge" style="background:#eff6ff;"><strong>Reserved</strong>F1 yang sudah dikunci untuk SO aktif</div>
                    </div>

                    <h6>Alur Besar Sistem (End-to-End)</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Receiving (Admin)</strong><span>Input GR dari PO → Sistem buat HU per pallet → Barang masuk GR-ZONE (Q4)</span></div></li>
                        <li><div class="step-content"><strong>Putaway (Operator RF / Desktop)</strong><span>Konfirmasi task PUTAWAY → Pindah HU dari GR-ZONE ke rak → Stok jadi F1, siap jual</span></div></li>
                        <li><div class="step-content"><strong>Sales Order (Admin)</strong><span>Buat SO, tambah item, jalankan Reservation Engine → Stok F1 dikunci untuk SO</span></div></li>
                        <li><div class="step-content"><strong>Release Picking (Admin)</strong><span>Outbound → Release Picking → Sistem buat task PICKING untuk operator RF</span></div></li>
                        <li><div class="step-content"><strong>Picking (Operator RF)</strong><span>Ambil barang dari rak → Pindah ke GI-ZONE (loading area)</span></div></li>
                        <li><div class="step-content"><strong>PGI — Post Goods Issue (Admin)</strong><span>Shipping → PGI → Stok keluar dari GI-ZONE → SO COMPLETED</span></div></li>
                    </ol>
                    <div class="note-box info"><strong>ℹ️ Info:</strong> Setiap pergerakan barang dicatat otomatis di <code>wms_stock_movements</code> dan <code>wms_system_logs</code> sebagai audit trail yang tidak bisa dihapus.</div>
                </div>

                <!-- ═══ TAB: INBOUND ═══ -->
                <div class="guide-section" id="tab-inbound">
                    <h5>📥 Inbound — Penerimaan Barang</h5>
                    <p class="text-muted">Masuk melalui menu <strong>Inbound → Receiving</strong>. Semua penerimaan barang dari vendor harus terikat ke Purchase Order (PO) yang sudah ada.</p>

                    <h6>Langkah Input GR (Goods Receipt)</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Pilih PO</strong><span>Klik PO dari panel kiri (hanya PO berstatus OPEN yang tampil).</span></div></li>
                        <li><div class="step-content"><strong>Klik Post GR pada item</strong><span>Pilih item yang ingin diterima, klik tombol GR.</span></div></li>
                        <li><div class="step-content"><strong>Isi Form GR</strong><span>No. SJ Vendor, Qty Good, Qty Damaged, Expiry Date (opsional), Batch ID (auto jika kosong), UoM mode (PCS/PACK), Catatan.</span></div></li>
                        <li><div class="step-content"><strong>Preview</strong><span>Klik Preview untuk lihat kalkulasi HU, rekomendasi bin, dan total qty sebelum submit.</span></div></li>
                        <li><div class="step-content"><strong>Submit</strong><span>Klik Post GR → GR Number dibuat → HU dibuat di GR-ZONE → Task PUTAWAY otomatis terbuat.</span></div></li>
                    </ol>

                    <h6>Aturan Validasi</h6>
                    <ul>
                        <li>PO harus berstatus <strong>OPEN</strong> — PO CLOSED tidak bisa diterima.</li>
                        <li>Nomor SJ vendor <strong>tidak boleh duplikat</strong> untuk PO yang sama.</li>
                        <li>Qty yang diterima <strong>tidak boleh melebihi sisa PO</strong> (Over Receiving = ditolak).</li>
                        <li>Expiry date tidak boleh di masa lalu.</li>
                        <li>Qty good + qty bad tidak boleh nol.</li>
                    </ul>

                    <h6>Setelah GR Berhasil</h6>
                    <ul>
                        <li>Stok masuk ke <strong>GR-ZONE</strong> dengan status <strong>Q4</strong>.</li>
                        <li>Task PUTAWAY otomatis dibuat per HU.</li>
                        <li>Sistem rekomendasikan bin tujuan (konsolidasi atau bin kosong terdekat).</li>
                        <li>Bisa Print Label HU dari halaman print_label.php.</li>
                    </ul>

                    <h6>Reverse GR</h6>
                    <ul>
                        <li>Hanya bisa jika operator <strong>belum mulai putaway</strong> (qty_actual_good = 0).</li>
                        <li>Efek: stok GR-ZONE dihapus, task putaway dihapus, received_qty PO dikurangi, PO kembali OPEN.</li>
                    </ul>
                    <div class="note-box warning"><strong>⚠️ Perhatian:</strong> Reverse GR TIDAK menghapus stok yang sudah di-putaway ke rak. Hanya staging (GR-ZONE dan BLOCK-ZONE) yang di-rollback.</div>
                </div>

                <!-- ═══ TAB: PUTAWAY ═══ -->
                <div class="guide-section" id="tab-putaway">
                    <h5>📦 Putaway — Penyimpanan ke Rak</h5>
                    <p class="text-muted">Putaway bisa dilakukan via <strong>Desktop (task_confirm.php)</strong> atau <strong>RF Scanner</strong>. Tujuan: pindah HU dari GR-ZONE ke rak reguler.</p>

                    <h6>Cara Putaway via Desktop</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Buka Task Monitor</strong><span>Dari Dashboard klik "Expand" pada Live Task Monitor, atau langsung buka task.php.</span></div></li>
                        <li><div class="step-content"><strong>Pilih Task PUTAWAY</strong><span>Klik task yang ingin dikonfirmasi → masuk ke task_confirm.php.</span></div></li>
                        <li><div class="step-content"><strong>Isi Form</strong><span>Target Bin (auto-fill rekomendasi sistem, bisa di-override), Qty Good, Qty Damaged, Remarks.</span></div></li>
                        <li><div class="step-content"><strong>Confirm</strong><span>Klik Confirm Task → stok pindah ke rak, status jadi F1.</span></div></li>
                    </ol>

                    <h6>Cara Putaway via RF Scanner</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Buka rf_scanner.php</strong><span>Di device mobile/tablet atau RF Emulator dari dashboard.</span></div></li>
                        <li><div class="step-content"><strong>Pilih PUTAWAY</strong><span>Dari menu utama RF → pilih tanggal → pilih task.</span></div></li>
                        <li><div class="step-content"><strong>Input qty & scan bin</strong><span>Isi Qty Good, Qty Bad. Scan/ketik Target Bin (format: A-01-01).</span></div></li>
                        <li><div class="step-content"><strong>CONFIRM TASK</strong><span>Klik konfirmasi → sistem pindah stok.</span></div></li>
                    </ol>

                    <h6>Skenario Putaway</h6>
                    <table class="guide-table">
                        <thead><tr><th>Skenario</th><th>Yang Terjadi</th></tr></thead>
                        <tbody class="scenario-row">
                            <tr><td>Full (no damage)</td><td>Seluruh HU pindah ke target bin. Status F1. HU ID tetap sama.</td></tr>
                            <tr><td>Partial</td><td>Sebagian pindah, sisa di GR-ZONE. HU baru dibuat prefix <code>SPL-</code>.</td></tr>
                            <tr><td>Ada barang rusak</td><td>Qty good → target bin (F1). Qty damaged → BLOCK-ZONE (B6). Split HU otomatis.</td></tr>
                            <tr><td>Override bin rekomendasi</td><td>Diizinkan, dicatat sebagai OVERRIDE di movement log.</td></tr>
                        </tbody>
                    </table>

                    <h6>Discrepancy</h6>
                    <p>Jika qty actual (good+damaged) berbeda dari qty reported admin → status berubah <strong>MISMATCH</strong> → notifikasi merah di Inbound Dashboard → harus diselesaikan di <strong>discrepancy.php</strong>.</p>
                    <div class="note-box warning"><strong>⚠️ Pilihan Resolve:</strong> <strong>WRITE_OFF_LOSS</strong> jika actual &lt; reported (kurang). <strong>APPROVE_SURPLUS</strong> jika actual &gt; reported (lebih). Perubahan ini mengubah dokumen GR secara permanen.</div>
                </div>

                <!-- ═══ TAB: OUTBOUND ═══ -->
                <div class="guide-section" id="tab-outbound">
                    <h5>📤 Outbound — Sales Order & Reserve</h5>
                    <p class="text-muted">Alur outbound dimulai dari pembuatan SO di <strong>sales_order.php</strong>, kemudian release picking di <strong>outbound.php</strong>.</p>

                    <h6>Membuat Sales Order</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Buka sales_order.php</strong><span>Klik "+ New SO" → Isi header: Customer, Ship To, Expected Date, Priority (NORMAL/HIGH/URGENT), Remarks.</span></div></li>
                        <li><div class="step-content"><strong>Tambah Item</strong><span>Pilih produk dari dropdown (menampilkan available stock F1) → input qty → klik Add.</span></div></li>
                        <li><div class="step-content"><strong>Reserve Stock</strong><span>Klik "Reserve Stock" → Sistem jalankan FEFO (First Expired First Out) untuk kunci stok F1.</span></div></li>
                        <li><div class="step-content"><strong>Status jadi RESERVED</strong><span>Stok dikunci, SO siap untuk release picking.</span></div></li>
                    </ol>

                    <h6>Aturan Reservation Engine</h6>
                    <ul>
                        <li>Hanya stok di <strong>rak reguler (F1)</strong> yang bisa di-reserve — GR-ZONE dan GI-ZONE dikecualikan.</li>
                        <li>Urutan prioritas: expiry date terpendek → gr_date terlama.</li>
                        <li>Jika stok tidak cukup → muncul error "Stock Shortage" → reservasi dibatalkan.</li>
                    </ul>

                    <h6>Release Picking (outbound.php)</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Pilih SO berstatus RESERVED</strong><span>Hanya SO dengan status RESERVED yang tampil di halaman ini.</span></div></li>
                        <li><div class="step-content"><strong>Klik Release Picking</strong><span>Sistem buat task PICKING untuk setiap HU yang di-reserve → Status SO jadi PICKING.</span></div></li>
                        <li><div class="step-content"><strong>Operator RF mengerjakan task</strong><span>Task PICKING muncul di rf_scanner.php untuk dikerjakan operator.</span></div></li>
                    </ol>

                    <h6>Status SO</h6>
                    <div class="status-grid">
                        <div class="status-badge" style="background:#f8fafc;"><strong>CREATED</strong>SO baru, belum di-reserve</div>
                        <div class="status-badge" style="background:#fffbeb;"><strong>RESERVED</strong>Stok sudah dikunci, menunggu release</div>
                        <div class="status-badge" style="background:#eff6ff;"><strong>PICKING</strong>Task picking sudah di-release ke RF</div>
                        <div class="status-badge" style="background:#f0fdf4;"><strong>COMPLETED</strong>PGI selesai, barang sudah keluar</div>
                    </div>
                    <div class="note-box info"><strong>ℹ️ Info:</strong> Item SO hanya bisa diedit/dihapus selama status masih <strong>CREATED</strong>. Setelah RESERVED, SO terkunci.</div>
                </div>

                <!-- ═══ TAB: PICKING & PGI ═══ -->
                <div class="guide-section" id="tab-picking">
                    <h5>🔍 Picking & Post Goods Issue (PGI)</h5>
                    <p class="text-muted">Picking dilakukan operator RF. PGI dilakukan admin di <strong>shipping.php</strong> setelah barang ada di GI-ZONE.</p>

                    <h6>Picking via RF Scanner</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Buka rf_scanner.php → Pilih PICKING</strong><span>Pilih tanggal → daftar task PICKING muncul.</span></div></li>
                        <li><div class="step-content"><strong>Pilih task & pergi ke rak sumber</strong><span>Sistem tampilkan produk, qty, dan source bin (lokasi rak). Pergi ke lokasi tersebut.</span></div></li>
                        <li><div class="step-content"><strong>Ambil barang & input qty</strong><span>Masukkan Qty Good yang berhasil diambil. Bin tujuan otomatis GI-ZONE (tidak perlu scan).</span></div></li>
                        <li><div class="step-content"><strong>CONFIRM TASK</strong><span>Stok dipindah dari rak ke GI-ZONE. Task jadi CONFIRMED.</span></div></li>
                    </ol>
                    <div class="note-box success"><strong>✅ Beda PUTAWAY vs PICKING:</strong> PUTAWAY butuh scan bin tujuan (rak reguler). PICKING TIDAK butuh scan — tujuan selalu GI-ZONE secara otomatis.</div>

                    <h6>Post Goods Issue (PGI) — shipping.php</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Pilih SO dari panel kiri</strong><span>Hanya SO berstatus RESERVED/PICKING yang tampil.</span></div></li>
                        <li><div class="step-content"><strong>Cek progress</strong><span>Panel kanan menampilkan qty ready di GI-ZONE vs qty order per item.</span></div></li>
                        <li><div class="step-content"><strong>Klik POST GOODS ISSUE</strong><span>Sistem validasi GI-ZONE → potong stok → catat movement GI_OUT → SO jadi COMPLETED.</span></div></li>
                    </ol>

                    <h6>Skenario PGI</h6>
                    <table class="guide-table">
                        <thead><tr><th>Skenario</th><th>Hasil</th></tr></thead>
                        <tbody class="scenario-row">
                            <tr><td>Full PGI (semua ready)</td><td>Sukses. Semua stok GI-ZONE terpotong. SO → COMPLETED.</td></tr>
                            <tr><td>Partial PGI (sebagian ready)</td><td>Diizinkan. Sistem ship qty yang ada. SO → COMPLETED.</td></tr>
                            <tr><td>GI-ZONE kosong</td><td>DITOLAK. Error: "Stock not found in GI-ZONE". Selesaikan picking dulu.</td></tr>
                        </tbody>
                    </table>
                    <div class="note-box danger"><strong>🔴 Penting:</strong> Setelah PGI, stok tidak bisa di-rollback otomatis. Koreksi harus via Physical Inventory adjustment.</div>
                </div>

                <!-- ═══ TAB: INVENTORY ═══ -->
                <div class="guide-section" id="tab-inventory">
                    <h5>📊 Inventory — Stok, Transfer & Opname</h5>

                    <h6>Stock Master (stock_master.php)</h6>
                    <p>Tampilan stok real-time seluruh gudang. Filter by: Produk, Bin, Batch, atau Stock Type (F1/Q4/B6).</p>
                    <table class="guide-table">
                        <thead><tr><th>Kolom</th><th>Arti</th></tr></thead>
                        <tbody>
                            <tr><td><strong>LGPLA</strong></td><td>Lokasi bin stok berada</td></tr>
                            <tr><td><strong>HU ID</strong></td><td>ID unik per pallet/handling unit</td></tr>
                            <tr><td><strong>Qty</strong></td><td>Total qty di HU</td></tr>
                            <tr><td><strong>Reserved</strong></td><td>Qty yang dikunci untuk SO aktif</td></tr>
                            <tr><td><strong>Available</strong></td><td>Qty − Reserved − Picked = stok bebas</td></tr>
                        </tbody>
                    </table>

                    <h6>Internal Transfer (internal.php)</h6>
                    <p>Pindah stok antar bin tanpa transaksi jual-beli. Cocok untuk reorganisasi gudang atau konsolidasi stok.</p>
                    <ul>
                        <li><strong>Full move:</strong> HU langsung pindah ke bin tujuan.</li>
                        <li><strong>Partial move:</strong> HU di-split, sisa di bin lama, qty pindah ke bin baru (prefix <code>SPL-</code>).</li>
                        <li><strong>QC Hold/Release:</strong> Ubah status F1 ↔ B6 ↔ Q4 dengan alasan wajib.</li>
                    </ul>
                    <div class="note-box info"><strong>ℹ️ Audit:</strong> Transfer dicatat sebagai double-entry: BIN_OUT (dari sumber) dan BIN_IN (ke tujuan).</div>

                    <h6>Physical Inventory / Opname (physical_inventory.php)</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Pilih Bin</strong><span>Semua HU yang tercatat di bin tersebut ditampilkan.</span></div></li>
                        <li><div class="step-content"><strong>Hitung fisik</strong><span>Hitung barang di rak secara fisik.</span></div></li>
                        <li><div class="step-content"><strong>Input hasil</strong><span>MATCH (cocok), LOSS (kurang), atau GAIN (lebih). Reason code wajib untuk LOSS/GAIN.</span></div></li>
                        <li><div class="step-content"><strong>Post</strong><span>Sistem update qty di wms_quants. Log PI_LOSS/PI_GAIN dicatat.</span></div></li>
                    </ol>
                    <div class="note-box warning"><strong>⚠️ Perhatian:</strong> Stok yang sedang di-reserve untuk SO aktif TIDAK bisa di-adjust. Cancel reservasi SO terlebih dahulu.</div>
                </div>

                <!-- ═══ TAB: MASTER DATA ═══ -->
                <div class="guide-section" id="tab-masterdata">
                    <h5>🗄️ Master Data</h5>
                    <p class="text-muted">Kelola data referensi sistem: produk, rak, dan tipe zona. Buka melalui menu <strong>Master Data</strong> di dashboard.</p>

                    <h6>Tab Products — Manajemen Produk</h6>
                    <table class="guide-table">
                        <thead><tr><th>Field</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Product Code</strong></td><td>SKU unik produk, digunakan di label HU dan semua transaksi</td></tr>
                            <tr><td><strong>Base UoM</strong></td><td>Satuan dasar (PCS, BOX, KG, dll)</td></tr>
                            <tr><td><strong>Capacity UoM</strong></td><td>Satuan kapasitas pallet (PALLET, BOX)</td></tr>
                            <tr><td><strong>Conversion Qty</strong></td><td>Jumlah base per 1 kapasitas. Contoh: 20 PCS/BOX → saat terima 100 PCS, dibuat 5 HU @20</td></tr>
                            <tr><td><strong>QC Required</strong></td><td>Jika 1, item perlu quality check sebelum putaway</td></tr>
                        </tbody>
                    </table>

                    <h6>Tab Storage Bins — Manajemen Rak</h6>
                    <ul>
                        <li><strong>Format bin standar:</strong> huruf besar + angka, mis <code>A-01-01</code> atau <code>AA-02-03</code>.</li>
                        <li>Bin hanya bisa <strong>dihapus jika EMPTY</strong> (tidak ada stok). Bin OCCUPIED dilindungi.</li>
                        <li>Bin zona khusus (GR-ZONE, GI-ZONE, BLOCK-ZONE) sudah ada secara default.</li>
                    </ul>

                    <h6>Tab Storage Types</h6>
                    <table class="guide-table">
                        <thead><tr><th>Kode</th><th>Nama</th><th>Putaway Rule</th></tr></thead>
                        <tbody>
                            <tr><td><code>0010</code></td><td>High Rack Storage</td><td>EMPTY_BIN — masuk ke bin kosong</td></tr>
                            <tr><td><code>9010</code></td><td>GR Zone / Block Zone</td><td>ADD_STOCK — ditumpuk, tidak perlu bin kosong</td></tr>
                            <tr><td><code>9020</code></td><td>GI Zone (Outbound)</td><td>ADD_STOCK — ditumpuk di loading area</td></tr>
                        </tbody>
                    </table>
                    <div class="note-box info"><strong>ℹ️ Info:</strong> Sistem Directed Putaway akan secara otomatis merekomendasikan bin berdasarkan aturan ini saat GR di-post.</div>
                </div>

                <!-- ═══ TAB: RF SCANNER ═══ -->
                <div class="guide-section" id="tab-rf">
                    <h5>📱 RF Scanner — Panduan Operator Lapangan</h5>
                    <p class="text-muted">Buka <strong>rf_scanner.php</strong> di device mobile/tablet. Atau klik "RF Emulator" di dashboard untuk simulasi dari komputer.</p>

                    <h6>Menu Utama RF</h6>
                    <table class="guide-table">
                        <thead><tr><th>Menu</th><th>Fungsi</th></tr></thead>
                        <tbody>
                            <tr><td><strong>PUTAWAY</strong></td><td>Kerjakan task penyimpanan barang dari GR-ZONE ke rak</td></tr>
                            <tr><td><strong>PICKING</strong></td><td>Kerjakan task pengambilan barang dari rak ke GI-ZONE</td></tr>
                            <tr><td><strong>CHECK STOCK</strong></td><td>Cek stok di bin tertentu atau cari produk berdasarkan kode</td></tr>
                            <tr><td><strong>BACK TO ADMIN</strong></td><td>Kembali ke halaman dashboard admin</td></tr>
                        </tbody>
                    </table>

                    <h6>Tips Penting RF Scanner</h6>
                    <ul>
                        <li>Task PUTAWAY: butuh <strong>scan bin tujuan</strong> (format A-01-01). Sistem tampilkan rekomendasi bin.</li>
                        <li>Task PICKING: <strong>TIDAK perlu scan bin</strong> — tujuan otomatis GI-ZONE.</li>
                        <li>Filter tanggal tersedia untuk melihat task dari hari sebelumnya.</li>
                        <li>Check Stock bisa digunakan kapan saja tanpa task aktif — cocok untuk spot check cepat.</li>
                    </ul>

                    <h6>Check Stock</h6>
                    <ol class="flow-steps">
                        <li><div class="step-content"><strong>Pilih CHECK STOCK</strong><span>Dari menu utama RF.</span></div></li>
                        <li><div class="step-content"><strong>Scan atau ketik</strong><span>Nama bin (mis: A-01-01) atau kode produk (mis: MAT-IPH-15).</span></div></li>
                        <li><div class="step-content"><strong>Hasil tampil</strong><span>Bin, HU ID, Qty, Product Code, Description, Batch — semua ditampilkan.</span></div></li>
                    </ol>
                    <div class="note-box success"><strong>✅ Untuk Admin:</strong> Klik "RF Emulator" di Action Bar dashboard untuk membuka RF Scanner di tab baru — bisa digunakan di komputer untuk konfirmasi task tanpa device mobile.</div>
                </div>

                <!-- ═══ TAB: TROUBLESHOOTING ═══ -->
                <div class="guide-section" id="tab-troubleshoot">
                    <h5>🔧 Troubleshooting & Skenario Khusus</h5>

                    <table class="guide-table">
                        <thead><tr><th style="width:35%">Error / Situasi</th><th>Penyebab & Solusi</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>Duplicate SJ Number</strong></td>
                                <td>Nomor SJ vendor sudah digunakan untuk PO yang sama. Cek di halaman Inbound apakah GR sudah ada. Jika salah, lakukan Reverse GR lalu input ulang.</td>
                            </tr>
                            <tr>
                                <td><strong>Over Receiving Detected</strong></td>
                                <td>Qty melebihi sisa PO. Cek kolom "Received / Ordered" di panel kanan Receiving. Input hanya sisa yang belum diterima.</td>
                            </tr>
                            <tr>
                                <td><strong>PHANTOM STOCK ALERT</strong></td>
                                <td>HU tidak ditemukan di source bin. Kemungkinan sudah dipindah. Cek stock_master.php untuk cari HU ID tersebut.</td>
                            </tr>
                            <tr>
                                <td><strong>INVALID BIN FORMAT</strong></td>
                                <td>Format bin tidak valid. Format yang diterima: A-01-01, AA-99-99, BLOCK-ZONE, GI-ZONE, OVERFLOW-ZONE. Tidak boleh ada spasi atau huruf kecil.</td>
                            </tr>
                            <tr>
                                <td><strong>Stock Shortage (Reserve)</strong></td>
                                <td>Stok F1 tidak cukup. Kemungkinan stok masih Q4 di GR-ZONE (putaway dulu), atau sudah di-reserve SO lain. Cek stock_master.php.</td>
                            </tr>
                            <tr>
                                <td><strong>SO tidak muncul di Outbound</strong></td>
                                <td>SO belum di-reserve. Outbound.php hanya tampilkan SO status RESERVED/PICKING. Kembali ke sales_order.php dan jalankan Reserve Stock.</td>
                            </tr>
                            <tr>
                                <td><strong>Stock not found in GI-ZONE</strong></td>
                                <td>Picking belum selesai. Cek task.php — apakah masih ada task PICKING OPEN? Minta operator RF selesaikan dulu.</td>
                            </tr>
                            <tr>
                                <td><strong>ADJUSTMENT BLOCKED</strong></td>
                                <td>Stok sedang di-reserve SO aktif. Cancel reservasi SO tersebut dulu di sales_order.php, baru lakukan adjustment PI.</td>
                            </tr>
                            <tr>
                                <td><strong>Discrepancy tidak muncul</strong></td>
                                <td>Semua GR items sudah BALANCED. MISMATCH hanya terjadi jika qty actual ≠ qty reported saat putaway dikonfirmasi.</td>
                            </tr>
                        </tbody>
                    </table>

                    <h6>Tips Operasional Harian</h6>
                    <ul>
                        <li>Mulai hari dengan cek Dashboard — pastikan tidak ada task PUTAWAY menumpuk di GR-ZONE.</li>
                        <li>Selesaikan semua putaway sebelum Receiving baru — hindari GR-ZONE penuh.</li>
                        <li>Notifikasi merah di Inbound? Selesaikan Discrepancy sebelum end of day.</li>
                        <li>Sebelum PGI, selalu cek progress picking di Shipping — pastikan GI-ZONE sudah ready.</li>
                        <li>Lakukan spot check Physical Inventory secara berkala — minimal 1 bin per hari.</li>
                        <li>Jangan pernah pindah barang fisik tanpa mencatat di sistem (Internal Transfer atau task).</li>
                        <li>Gunakan Audit Center (logs.php) untuk tracing jika ada selisih stok yang tidak bisa dijelaskan.</li>
                    </ul>
                </div>

            </div><!-- end modal-body -->
        </div>
    </div>
</div>

<script>
function showGuideTab(tab, btn) {
    document.querySelectorAll('.guide-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    if(btn) btn.classList.add('active');
}
</script>

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