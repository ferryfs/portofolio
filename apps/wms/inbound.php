<?php
// apps/wms/inbound.php
// PREMIUM DASHBOARD UI (FIXED INCLUDES)

// Error Reporting (Biar gak blank putih kalau ada salah)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit(); }

// INCLUDE WAJIB (Sesuaikan path)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; // WAJIB: Karena safeGetOne ada disini

// Logic Tab Filter
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$status_filter = ($tab == 'history') ? 'CLOSED' : 'OPEN';

// Query Data PO + Progress Calculation
// Menggunakan LEFT JOIN agar PO yang belum ada item tetap muncul
$sql = "SELECT h.*, 
        COUNT(i.po_item_id) as total_sku,
        SUM(i.qty_ordered) as total_qty,
        SUM(i.received_qty) as total_recv
        FROM wms_po_header h
        LEFT JOIN wms_po_items i ON h.po_number = i.po_number
        WHERE h.status = ?
        GROUP BY h.po_number
        ORDER BY h.expected_date ASC"; // Urutkan dari yang terlama (Urgent)

$list = safeGetAll($pdo, $sql, [$status_filter]);

// Stats KPI (Dashboard Atas)
$kpi_open = safeGetOne($pdo, "SELECT count(*) as c FROM wms_po_header WHERE status='OPEN'")['c'];
// Handle jika null
$kpi_item_row = safeGetOne($pdo, "SELECT sum(qty_ordered - received_qty) as c FROM wms_po_items WHERE status='OPEN'");
$kpi_item = $kpi_item_row['c'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbound Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #4f46e5; --bg: #f8fafc; }
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; padding-bottom: 50px; }
        
        .container-fluid { max-width: 1400px; padding: 40px; }
        
        /* KPI Cards */
        .kpi-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; display: flex; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .kpi-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 20px; }
        .kpi-content h3 { font-size: 1.75rem; font-weight: 800; margin: 0; color: #0f172a; }
        .kpi-content p { margin: 0; color: #64748b; font-weight: 500; font-size: 0.9rem; }

        /* Navigation Tabs */
        .nav-tabs-custom { border-bottom: 2px solid #e2e8f0; margin-bottom: 30px; }
        .nav-tabs-custom .nav-link { border: none; background: transparent; color: #64748b; font-weight: 600; padding: 12px 20px; font-size: 0.95rem; margin-bottom: -2px; }
        .nav-tabs-custom .nav-link.active { color: var(--primary); border-bottom: 2px solid var(--primary); }
        .nav-tabs-custom .nav-link:hover { color: #334155; }

        /* Table */
        .card-table { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
        .table thead th { background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
        .table tbody td { padding: 20px 24px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .table tbody tr:last-child td { border-bottom: none; }
        
        /* Elements */
        .badge-status { padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-open { background: #dcfce7; color: #166534; }
        .badge-closed { background: #f1f5f9; color: #475569; }
        
        .progress-custom { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; width: 140px; margin-top: 6px; }
        
        .btn-action { background: var(--primary); color: white; padding: 8px 24px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s; display: inline-block; }
        .btn-action:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(67, 56, 202, 0.3); color: white; }
    </style>
</head>
<body>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark m-0">Inbound Dashboard</h2>
            <p class="text-muted m-0 mt-1">Manage incoming purchase orders & receipts</p>
        </div>
        <a href="index.php" class="btn btn-white border fw-bold text-muted"><i class="bi bi-arrow-left me-2"></i>Back</a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#e0e7ff; color:#4338ca"><i class="bi bi-truck"></i></div>
                <div class="kpi-content">
                    <h3><?= $kpi_open ?></h3>
                    <p>Open Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#dcfce7; color:#15803d"><i class="bi bi-box-seam"></i></div>
                <div class="kpi-content">
                    <h3><?= number_format($kpi_item) ?></h3>
                    <p>Units to Receive</p>
                </div>
            </div>
        </div>
    </div>

    <div class="nav nav-tabs-custom">
        <a href="?tab=active" class="nav-link <?= $tab == 'active' ? 'active' : '' ?>">Active Shipments</a>
        <a href="?tab=history" class="nav-link <?= $tab == 'history' ? 'active' : '' ?>">Received History</a>
    </div>

    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Vendor Information</th>
                        <th>Expected Date</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No <?= $tab ?> purchase orders found.</td></tr>
                    <?php endif; ?>

                    <?php foreach($list as $row): 
                        $qty_ord = $row['total_qty'] > 0 ? $row['total_qty'] : 1;
                        $pct = ($row['total_recv'] / $qty_ord) * 100;
                        $color = $pct >= 100 ? 'bg-success' : 'bg-primary';
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold fs-6 text-dark"><?= $row['po_number'] ?></div>
                            <div class="small text-muted"><?= $row['total_sku'] ?> Items</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= $row['vendor_name'] ?></div>
                            <div class="small text-muted text-uppercase"><?= $row['po_type'] ?> &bull; <?= $row['currency'] ?></div>
                        </td>
                        <td>
                            <span class="text-dark fw-medium">
                                <i class="bi bi-calendar4 me-2 text-muted"></i>
                                <?= date('d M Y', strtotime($row['expected_date'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                                <span><?= number_format($row['total_recv']) ?> / <?= number_format($row['total_qty']) ?></span>
                                <span><?= round($pct) ?>%</span>
                            </div>
                            <div class="progress-custom">
                                <div class="<?= $color ?>" style="width: <?= $pct ?>%; height:100%"></div>
                            </div>
                        </td>
                        <td>
                            <?php if($row['status']=='OPEN'): ?>
                                <span class="badge-status badge-open">Open</span>
                                <?php if($row['is_locked']): ?>
                                    <span class="badge bg-warning text-dark ms-1"><i class="bi bi-lock-fill"></i></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge-status badge-closed">Closed</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($row['status'] == 'OPEN'): ?>
                                <a href="receiving.php?po=<?= $row['po_number'] ?>" class="btn-action">
                                    Process <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            <?php else: ?>
                                <a href="receiving.php?po=<?= $row['po_number'] ?>&view_only=1" class="btn btn-light border fw-bold text-muted px-4 rounded-3">
                                    View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>