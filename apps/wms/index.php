<?php 
// 🔥 SESSION ISOLATION
session_name("WMS_APP_SESSION");
session_start();

// 1. CEK KEAMANAN
if(!isset($_SESSION['wms_login'])) {
    header("Location: login.php"); // Redirect ke login lokal
    exit();
}

include 'koneksi.php'; 

// --- LOGIKA DATA DASHBOARD (DEFENSIVE CODING) ---
$total_open = 0; $total_recv = 0; $usage_percent = 0;

// Cek tabel sebelum query biar gak error
// Check table existence (prepared)
$stmt = $conn->prepare("SHOW TABLES LIKE 'wms_warehouse_tasks'");
$stmt->execute();
$check_table = $stmt->get_result();
if(mysqli_num_rows($check_table) > 0) {
    // Open Tasks
    $status_open = 'OPEN';
    $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM wms_warehouse_tasks WHERE status = ?");
    $stmt2->bind_param("s", $status_open);
    $stmt2->execute();
    $q_open = $stmt2->get_result();
    if($q_open) { $d = mysqli_fetch_assoc($q_open); $total_open = $d['total']; }
    $stmt2->close();

    // Today's Receiving
    $today = date('Y-m-d');
    $proc = 'PUTAWAY';
    $stmt3 = $conn->prepare("SELECT COUNT(*) as total FROM wms_warehouse_tasks WHERE process_type = ? AND DATE(created_at) = ?");
    $stmt3->bind_param("ss", $proc, $today);
    $stmt3->execute();
    $q_recv = $stmt3->get_result();
    if($q_recv) { $d = mysqli_fetch_assoc($q_recv); $total_recv = $d['total']; }
    $stmt3->close();
}

// Bin Usage
// Bin Usage checks
$stmt = $conn->prepare("SHOW TABLES LIKE 'wms_storage_bins'");
$stmt->execute();
$check_bins = $stmt->get_result();
if(mysqli_num_rows($check_bins) > 0) {
    $stmt_b1 = $conn->prepare("SELECT COUNT(DISTINCT lgpla) as used FROM wms_quants");
    $stmt_b1->execute();
    $q_bin_used = $stmt_b1->get_result();
    $d_bin_used = mysqli_fetch_assoc($q_bin_used);
    $stmt_b1->close();
    
    $stmt_b2 = $conn->prepare("SELECT COUNT(*) as total FROM wms_storage_bins");
    $stmt_b2->execute();
    $q_bin_all = $stmt_b2->get_result();
    $d_bin_all  = mysqli_fetch_assoc($q_bin_all);
    $stmt_b2->close();

    if($d_bin_all['total'] > 0) {
        $usage_percent = round(($d_bin_used['used'] / $d_bin_all['total']) * 100);
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
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .module-card { transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); border: none; border-radius: 16px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .module-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); cursor: pointer; }
        .icon-box { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 20px; font-size: 26px; }
        
        /* Colors */
        .bg-blue-light { background-color: #ecf4ff; color: #0d6efd; }
        .bg-green-light { background-color: #e6f8f0; color: #0ca678; }
        .bg-purple-light { background-color: #f3f0ff; color: #7950f2; }
        .bg-orange-light { background-color: #fff9db; color: #f59f00; }
        .bg-dark-light { background-color: #e9ecef; color: #212529; }
        .bg-red-light { background-color: #f8d7da; color: #dc3545; }

        .donut-chart { width: 180px; height: 180px; border-radius: 50%; background: conic-gradient(<?= $chart_color ?> 0% <?= $usage_percent ?>%, #e9ecef <?= $usage_percent ?>% 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative; }
        .donut-inner { width: 140px; height: 140px; background: white; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 0 20px rgba(0,0,0,0.05); }
        .monitor-container { border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: white; height: 100%; }

        /* Guide Modal Styles */
        .nav-pills .nav-link { color: #495057; font-weight: 500; }
        .nav-pills .nav-link.active { background-color: #0d6efd; color: white; }
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
                <i class="bi bi-question-circle"></i> Help & Legend
            </button>

            <div class="text-white text-end lh-1 d-none d-md-block">
                <div class="small fw-bold">
                    Hi, <?= isset($_SESSION['wms_fullname']) ? $_SESSION['wms_fullname'] : 'Guest' ?>
                    <?php if(isset($_SESSION['wms_count'])): ?>
                        <span class="badge bg-warning text-dark ms-1">#<?= $_SESSION['wms_count'] ?></span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.75rem; opacity: 0.7;">
                    <?= isset($_SESSION['wms_role']) ? $_SESSION['wms_role'] : 'User' ?>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-power"></i>
            </a>
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

    <h6 class="text-uppercase fw-bold text-muted mb-3 small tracking-wide"><i class="bi bi-tools me-1"></i> Execution & Tools</h6>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card module-card p-3 h-100" onclick="window.open('rf_scanner.php', '_blank')">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-dark text-white mb-0" style="width:50px; height:50px; font-size:1.2rem;">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">RF Scanner</h6>
                        <small class="text-muted">Mobile / Handheld View</small>
                    </div>
                    <div class="ms-auto"><i class="bi bi-box-arrow-up-right text-muted"></i></div>
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
                        <h6 class="fw-bold mb-0">Print Label</h6>
                        <small class="text-muted">Barcode Generator</small>
                    </div>
                    <div class="ms-auto"><i class="bi bi-box-arrow-up-right text-muted"></i></div>
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
                        <small class="text-muted">System Activity</small>
                    </div>
                    <div class="ms-auto"><i class="bi bi-chevron-right text-muted"></i></div>
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
                    <h6 class="text-uppercase text-muted fw-bold mb-4 small tracking-wide">Warehouse Capacity</h6>
                    <div class="donut-chart mb-4">
                        <div class="donut-inner">
                            <h1 class="display-5 fw-bold mb-0 text-dark"><?= $usage_percent ?>%</h1>
                            <small class="text-muted" style="font-size: 12px;">Used</small>
                        </div>
                    </div>
                    <div class="row w-100 g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border-0">
                                <div class="text-muted small mb-1">Open Tasks</div>
                                <div class="fw-bold text-dark fs-5"><?= $total_open ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border-0">
                                <div class="text-muted small mb-1">Today's In</div>
                                <div class="fw-bold text-dark fs-5"><?= $total_recv ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="guideModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-book-half me-2"></i> Panduan Pengguna WMS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex h-100">
                    <div class="nav flex-column nav-pills p-3 bg-light border-end" style="width: 250px;" role="tablist">
                        <button class="nav-link active text-start mb-2" data-bs-toggle="pill" data-bs-target="#guide-intro">
                            <i class="bi bi-diagram-3 me-2"></i> Alur Sistem
                        </button>
                        <button class="nav-link text-start mb-2" data-bs-toggle="pill" data-bs-target="#guide-colors">
                            <i class="bi bi-palette me-2"></i> Arti Warna & Kode
                        </button>
                        <button class="nav-link text-start mb-2" data-bs-toggle="pill" data-bs-target="#guide-in">
                            <i class="bi bi-box-arrow-in-down me-2"></i> Cara Inbound
                        </button>
                        <button class="nav-link text-start mb-2" data-bs-toggle="pill" data-bs-target="#guide-out">
                            <i class="bi bi-box-arrow-up me-2"></i> Cara Outbound
                        </button>
                    </div>
                    
                    <div class="tab-content p-4 flex-fill" style="overflow-y: auto;">
                        <div class="tab-pane fade show active" id="guide-intro">
                            <h4 class="fw-bold text-primary mb-3">Selamat Datang di Ekosistem WMS!</h4>
                            <p>Sistem ini dirancang dengan logika <strong>"Task-Directed"</strong>.</p>
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold">Flowchart:</h6>
                                    <div class="d-flex align-items-center justify-content-around text-center mt-3 text-muted small">
                                        <div><div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-pc-display fs-3 text-primary"></i></div><strong>1. ADMIN</strong><br>Buat Tiket</div>
                                        <i class="bi bi-arrow-right fs-4"></i>
                                        <div><div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-list-task fs-3 text-warning"></i></div><strong>2. SYSTEM</strong><br>Status: OPEN</div>
                                        <i class="bi bi-arrow-right fs-4"></i>
                                        <div><div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-qr-code fs-3 text-dark"></i></div><strong>3. OPERATOR</strong><br>Scan Rak</div>
                                        <i class="bi bi-arrow-right fs-4"></i>
                                        <div><div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-database-check fs-3 text-success"></i></div><strong>4. SELESAI</strong><br>Stok Update</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="guide-colors">
                            <h4 class="fw-bold text-primary mb-3">Kamus Warna</h4>
                            <table class="table table-bordered align-middle">
                                <tbody>
                                    <tr><td><span class="badge bg-warning text-dark w-100 py-2">OPEN</span></td><td>Tugas Baru. Stok fisik belum bergerak.</td></tr>
                                    <tr><td><span class="badge bg-success w-100 py-2">CONFIRMED</span></td><td>Tugas Selesai. Stok database sudah update.</td></tr>
                                    <tr><td><span class="badge bg-danger w-100 py-2">BLOCKED</span></td><td>Barang Rusak/Ditahan.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="guide-in">
                            <h4 class="fw-bold text-primary mb-3">Inbound (Masuk)</h4>
                            <div class="guide-step active"><strong>1. Terima Dokumen:</strong> Klik Inbound > Pilih PO.</div>
                            <div class="guide-step active"><strong>2. Cek Fisik:</strong> Input jumlah Received Qty.</div>
                            <div class="guide-step active"><strong>3. Generate Task:</strong> Klik Save. Tugas akan dikirim ke Scanner.</div>
                        </div>

                        <div class="tab-pane fade" id="guide-out">
                            <h4 class="fw-bold text-primary mb-3">Outbound (Keluar)</h4>
                            <div class="guide-step active"><strong>1. Order Masuk:</strong> Klik Outbound > Pilih SO.</div>
                            <div class="guide-step active"><strong>2. Cek FIFO:</strong> Sistem mencari stok terlama otomatis.</div>
                            <div class="guide-step active"><strong>3. Release Task:</strong> Klik Generate Picking.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary px-4" onclick="finishGuide()">Saya Paham & Mulai Bekerja</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // LOGIC POPUP OTOMATIS
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('guideModal'));
        
        // Cek apakah user sudah melihat guide di sesi ini?
        if (!sessionStorage.getItem('wms_guide_seen')) {
            myModal.show();
        }
    });

    // Fungsi tombol "Saya Paham"
    function finishGuide() {
        sessionStorage.setItem('wms_guide_seen', 'true');
        var myModalEl = document.getElementById('guideModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();
    }

    // Fungsi tombol Help manual (selalu muncul)
    function showGuide(force = false) {
        var myModal = new bootstrap.Modal(document.getElementById('guideModal'));
        myModal.show();
    }
</script>
<script>
    // Cari semua elemen <a> di halaman ini
    document.querySelectorAll('a').forEach(function(link) {
        // Cek dulu, jangan ubah link logout atau yang punya target="_blank"
        if(link.getAttribute('href') && !link.getAttribute('href').includes('logout') && link.getAttribute('target') !== '_blank') {
            
            let urlTujuan = link.getAttribute('href');
            
            // Hapus href asli biar gak muncul di pojok
            link.setAttribute('href', 'javascript:void(0);');
            
            // Tambahin fungsi klik manual
            link.addEventListener('click', function() {
                window.location.href = urlTujuan;
            });
        }
    });
</script>
</body>
</html>