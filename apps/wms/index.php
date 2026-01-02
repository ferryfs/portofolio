<?php 
session_start();

// 1. CEK KEAMANAN
if(!isset($_SESSION['wms_login'])) {
    header("Location: ../../index.php?err=no_access");
    exit();
}

include '../../koneksi.php'; 

// --- LOGIKA DATA DASHBOARD ---
$q_open = mysqli_query($conn, "SELECT COUNT(*) as total FROM wms_warehouse_tasks WHERE status = 'OPEN'");
$d_open = mysqli_fetch_assoc($q_open);
$total_open = $d_open['total'];

$today = date('Y-m-d');
$q_recv = mysqli_query($conn, "SELECT COUNT(*) as total FROM wms_warehouse_tasks WHERE process_type = 'PUTAWAY' AND DATE(created_at) = '$today'");
$d_recv = mysqli_fetch_assoc($q_recv);
$total_recv = $d_recv['total'];

$q_bin_used = mysqli_query($conn, "SELECT COUNT(DISTINCT lgpla) as used FROM wms_quants");
$d_bin_used = mysqli_fetch_assoc($q_bin_used);
$q_bin_all  = mysqli_query($conn, "SELECT COUNT(*) as total FROM wms_storage_bins");
$d_bin_all  = mysqli_fetch_assoc($q_bin_all);

$usage_percent = 0;
if($d_bin_all['total'] > 0) {
    $usage_percent = round(($d_bin_used['used'] / $d_bin_all['total']) * 100);
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
            <button class="btn btn-outline-info btn-sm rounded-pill px-3" onclick="showGuide()">
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
            <a href="../../logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
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
                <h5 class="modal-title fw-bold"><i class="bi bi-book-half me-2"></i> Panduan Pengguna WMS & Legenda</h5>
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
                        <button class="nav-link text-start mb-2" data-bs-toggle="pill" data-bs-target="#guide-rf">
                            <i class="bi bi-phone me-2"></i> Cara Pakai Scanner
                        </button>
                    </div>
                    
                    <div class="tab-content p-4 flex-fill" style="overflow-y: auto;">
                        
                        <div class="tab-pane fade show active" id="guide-intro">
                            <h4 class="fw-bold text-primary mb-3">Selamat Datang di Ekosistem WMS!</h4>
                            <p>Sistem ini dirancang dengan logika <strong>"Task-Directed"</strong> (Berbasis Tugas). Tidak ada stok yang berubah tanpa konfirmasi dari lapangan.</p>

                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold">Flowchart Sederhana:</h6>
                                    <div class="d-flex align-items-center justify-content-around text-center mt-3 text-muted small">
                                        <div>
                                            <div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-pc-display fs-3 text-primary"></i></div>
                                            <strong>1. ADMIN</strong><br>Buat Tiket
                                        </div>
                                        <i class="bi bi-arrow-right fs-4"></i>
                                        <div>
                                            <div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-list-task fs-3 text-warning"></i></div>
                                            <strong>2. SYSTEM</strong><br>Status: OPEN
                                        </div>
                                        <i class="bi bi-arrow-right fs-4"></i>
                                        <div>
                                            <div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-qr-code fs-3 text-dark"></i></div>
                                            <strong>3. OPERATOR</strong><br>Scan Rak
                                        </div>
                                        <i class="bi bi-arrow-right fs-4"></i>
                                        <div>
                                            <div class="bg-white p-2 rounded shadow-sm border mb-2"><i class="bi bi-database-check fs-3 text-success"></i></div>
                                            <strong>4. SELESAI</strong><br>Stok Update
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="guide-colors">
                            <h4 class="fw-bold text-primary mb-3">Kamus Warna & Status</h4>
                            <p>Pahami indikator warna di Monitor dan Dashboard agar tidak salah langkah.</p>
                            
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="150">Indikator</th>
                                        <th>Arti & Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark w-100 py-2">OPEN</span></td>
                                        <td>
                                            <strong>Tugas Baru Dibuat.</strong><br>
                                            Stok fisik belum bergerak. Menunggu operator Scanner untuk mengerjakan.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success w-100 py-2">CONFIRMED</span></td>
                                        <td>
                                            <strong>Tugas Selesai.</strong><br>
                                            Operator sudah scan rak yang benar. Stok di database sudah resmi bertambah/berkurang.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-danger w-100 py-2">BLOCKED (B6)</span></td>
                                        <td>
                                            <strong>Barang Rusak/Ditahan.</strong><br>
                                            Stok ini ada di gudang tapi tidak bisa dijual (Outbound). Harus dipindah ke area Reject.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-info text-dark w-100 py-2">QI (Q4)</span></td>
                                        <td>
                                            <strong>Quality Inspection.</strong><br>
                                            Barang baru datang, harus dicek QC dulu sebelum statusnya jadi Available (F1).
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="guide-in">
                            <h4 class="fw-bold text-primary mb-3">Panduan Inbound (Barang Masuk)</h4>
                            
                            <div class="guide-step active">
                                <strong>Langkah 1: Terima Dokumen</strong><br>
                                Klik modul <strong>Inbound</strong>. Pilih Nomor PO (Purchase Order) dari Supplier.
                            </div>
                            <div class="guide-step active">
                                <strong>Langkah 2: Cek Fisik & Input</strong><br>
                                Hitung barang yang datang. Masukkan jumlahnya di kolom "Received Qty".<br>
                                <em>Tips: Jangan input melebihi jumlah pesanan.</em>
                            </div>
                            <div class="guide-step active">
                                <strong>Langkah 3: Generate Task</strong><br>
                                Klik tombol <strong>Save & Generate Task</strong>.<br>
                                <span class="text-danger small"><i class="bi bi-exclamation-circle"></i> Perhatian: Di tahap ini, stok di menu "Inventory" BELUM bertambah. Ini wajar.</span>
                            </div>
                            <div class="guide-step">
                                <strong>Langkah 4: Serahkan ke Operator</strong><br>
                                Tugas akan muncul otomatis di layar <strong>RF Scanner</strong> operator untuk ditaruh ke rak (Putaway).
                            </div>
                        </div>

                        <div class="tab-pane fade" id="guide-out">
                            <h4 class="fw-bold text-primary mb-3">Panduan Outbound (Barang Keluar)</h4>
                            
                            <div class="guide-step active">
                                <strong>Langkah 1: Order Masuk</strong><br>
                                Klik modul <strong>Outbound</strong>. Pilih Nomor SO (Sales Order).
                            </div>
                            <div class="guide-step active">
                                <strong>Langkah 2: Cek Ketersediaan (FIFO)</strong><br>
                                Sistem akan otomatis mencari stok dengan prinsip <strong>First In First Out</strong>.
                            </div>
                            <div class="guide-step active">
                                <strong>Langkah 3: Release Task</strong><br>
                                Jika stok aman, klik <strong>Generate Picking Task</strong>. Tugas akan dikirim ke Scanner Operator.
                            </div>
                        </div>

                        <div class="tab-pane fade" id="guide-rf">
                            <h4 class="fw-bold text-primary mb-3">Cara Menggunakan RF Scanner Simulator</h4>
                            <p>Gunakan ini di HP atau Tab baru untuk simulasi Operator.</p>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-dark text-white fw-bold">Tampilan Layar</div>
                                        <div class="card-body bg-secondary text-white">
                                            <small class="d-block mb-2 text-warning">TASK #1002 - PICKING</small>
                                            <h5 class="fw-bold">PROD-001 (Indomie)</h5>
                                            <p>Lokasi: <span class="badge bg-danger fs-6">A-01-01</span></p>
                                            <input type="text" class="form-control form-control-sm mb-2" placeholder="SCAN BIN...">
                                            <button class="btn btn-success btn-sm w-100">CONFIRM</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Fitur Penting:</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> <strong>Auto-Task:</strong> Tidak perlu milih menu. Tugas OPEN langsung muncul.</li>
                                        <li class="mb-2"><i class="bi bi-shield-lock text-danger me-2"></i> <strong>Safety Lock:</strong> Jika disuruh ke Rak A, tapi operator scan Rak B, sistem akan ERROR (Menolak).</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Saya Paham, Tutup Panduan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // SCRIPT POPUP OTOMATIS (TANPA SYARAT)
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('guideModal'));
        
        // Hapus semua ingatan browser biar fresh
        localStorage.removeItem('wms_guide_hidden');
        sessionStorage.removeItem('wms_guide_session_shown');

        // LANGSUNG MUNCUL
        myModal.show();
    });

    // Fungsi tombol Help manual
    function showGuide() {
        var myModal = new bootstrap.Modal(document.getElementById('guideModal'));
        myModal.show();
    }
</script>

</body>
</html>