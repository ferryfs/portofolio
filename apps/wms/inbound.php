<?php
// apps/wms/inbound.php
// V13.4: SMART STATUS FIX + ZERO QTY AUTO-SWEEPER
// Features: Tracks Physical Work, Not Just Document Status. Automatically rejects/cleans qty=0 from external systems.

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['wms_fullname'] ?? 'User';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

// --- 🛡️ 0. THE ZERO-QTY SWEEPER (NEW) ---
// Ini akan otomatis menghapus item PO yang nyasar dari sistem luar dengan Qty <= 0
// dan kalau satu PO isinya kosong semua, PO-nya bakal otomatis di-CANCEL
try {
    // 1. Hapus item sampah
    safeQuery($pdo, "DELETE FROM wms_po_items WHERE qty_ordered <= 0");
    
    // 2. Tutup/Cancel PO yang jadi kosong melompong gara-gara itemnya kehapus
    safeQuery($pdo, "UPDATE wms_po_header h 
                     SET status = 'CANCELLED' 
                     WHERE status = 'OPEN' 
                     AND NOT EXISTS (SELECT 1 FROM wms_po_items i WHERE i.po_number = h.po_number)");
} catch (Exception $e) {
    // Silently ignore if table is locked, let it run next time
}

// --- 1. FILTER LOGIC ---
$view_mode = $_GET['view'] ?? 'active'; // active | history
$search = sanitizeInput($_GET['q'] ?? '');
$vendor_filter = sanitizeInput($_GET['vendor'] ?? '');

// 🧠 LOGIC BARU: "Active" berarti Dokumen Open ATAU Fisik Masih Jalan ATAU Ada Masalah
if ($view_mode == 'active') {
    // Tampilkan jika: Header OPEN -ATAU- Ada Task Pending -ATAU- Ada Mismatch
    $status_condition = "
        (
            h.status = 'OPEN' 
            OR EXISTS (
                SELECT 1 FROM wms_gr_header gh 
                JOIN wms_quants q ON gh.gr_number = q.gr_ref
                JOIN wms_warehouse_tasks t ON q.hu_id = t.hu_id
                WHERE gh.po_number = h.po_number AND t.status = 'OPEN'
            )
            OR EXISTS (
                SELECT 1 FROM wms_gr_header gh2
                JOIN wms_gr_items gi ON gh2.gr_number = gi.gr_number
                WHERE gh2.po_number = h.po_number AND gi.discrepancy_status = 'MISMATCH'
            )
        )
    ";
} else {
    // History cuma buat yang bener-bener bersih (Closed & No Task & No Mismatch)
    $status_condition = "
        h.status = 'CLOSED' 
        AND NOT EXISTS (
            SELECT 1 FROM wms_gr_header gh 
            JOIN wms_quants q ON gh.gr_number = q.gr_ref
            JOIN wms_warehouse_tasks t ON q.hu_id = t.hu_id
            WHERE gh.po_number = h.po_number AND t.status = 'OPEN'
        )
        AND NOT EXISTS (
            SELECT 1 FROM wms_gr_header gh2
            JOIN wms_gr_items gi ON gh2.gr_number = gi.gr_number
            WHERE gh2.po_number = h.po_number AND gi.discrepancy_status = 'MISMATCH'
        )
    ";
}

// --- 2. DATA FETCHING ---
$params = [];
// Kita tambah subquery 'pending_tasks' biar admin tau kenapa PO Closed masih nongol
$sql = "SELECT h.*, 
        COUNT(DISTINCT i.po_item_id) as total_sku,
        COALESCE(SUM(i.qty_ordered), 0) as total_qty,
        COALESCE(SUM(i.received_qty), 0) as total_recv,
        (SELECT COUNT(*) FROM wms_gr_items gi 
         JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number 
         WHERE gh.po_number = h.po_number AND gi.discrepancy_status = 'MISMATCH') as discrepancy_count,
        (SELECT COUNT(*) FROM wms_gr_header gh 
         JOIN wms_quants q ON gh.gr_number = q.gr_ref
         JOIN wms_warehouse_tasks t ON q.hu_id = t.hu_id
         WHERE gh.po_number = h.po_number AND t.status = 'OPEN') as open_tasks
        FROM wms_po_header h
        LEFT JOIN wms_po_items i ON h.po_number = i.po_number
        WHERE $status_condition";

if($search) {
    $sql .= " AND (h.po_number LIKE ? OR h.vendor_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($vendor_filter) {
    $sql .= " AND h.vendor_name = ?";
    $params[] = $vendor_filter;
}

$sql .= " GROUP BY h.po_number ORDER BY h.expected_date ASC";
$list = safeGetAll($pdo, $sql, $params);

// Stats KPI (Update Logic juga)
$kpi_open = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_po_header WHERE status='OPEN'")['c'];
$kpi_pending_work = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_warehouse_tasks WHERE status='OPEN' AND process_type='PUTAWAY'")['c'];
$kpi_mismatch = safeGetOne($pdo, "SELECT COUNT(DISTINCT h.po_number) as c 
                                  FROM wms_gr_items gi 
                                  JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number 
                                  JOIN wms_po_header h ON gh.po_number = h.po_number
                                  WHERE gi.discrepancy_status = 'MISMATCH'")['c'];

// Vendors Dropdown
$vendors = safeGetAll($pdo, "SELECT DISTINCT vendor_name FROM wms_po_header ORDER BY vendor_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbound Dashboard | WMS V13.4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }
        body.dark-mode {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
        }
        body { background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; transition: 0.3s; }
        
        .navbar-glass { background: var(--card-bg); border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .kpi-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; transition: 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .glass-table { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table { margin: 0; color: var(--text); }
        .table thead th { background: var(--bg); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--border); }
        .table tbody td { padding: 15px 20px; vertical-align: middle; border-bottom: 1px solid var(--border); }
        .table tbody tr:hover { background-color: var(--bg); }
        .pulse-red { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); } }
        .theme-toggle { cursor: pointer; padding: 8px; border-radius: 50%; border: 1px solid var(--border); }
    </style>
</head>
<body>

    <div class="navbar-glass">
        <div class="d-flex align-items-center gap-3">
            <h4 class="fw-bold m-0 text-primary"><i class="bi bi-box-seam-fill me-2"></i>Inbound Dashboard</h4>
            <span class="badge bg-light text-muted border">V13.4</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-stars-fill text-warning"></i></div>
            <div class="vr text-muted"></div>
            <div class="d-flex align-items-center gap-2">
                <div class="text-end lh-1">
                    <div class="fw-bold small"><?= htmlspecialchars($user_id) ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Warehouse Admin</div>
                </div>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;">
                    <?= strtoupper(substr($user_id, 0, 1)) ?>
                </div>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm fw-bold">Exit</a>
        </div>
    </div>

    <div class="container-fluid px-4 py-4">
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text"></i></div>
                    <div><div class="small text-muted fw-bold">OPEN PO DOCS</div><h3 class="fw-bold m-0"><?= $kpi_open ?></h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-person-walking"></i></div>
                    <div><div class="small text-muted fw-bold">PENDING PUTAWAY</div><h3 class="fw-bold m-0"><?= number_format($kpi_pending_work) ?> Tasks</h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card <?= $kpi_mismatch > 0 ? 'border-danger' : '' ?>">
                    <div class="kpi-icon bg-danger bg-opacity-10 text-danger <?= $kpi_mismatch > 0 ? 'pulse-red' : '' ?>">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div><div class="small text-muted fw-bold text-danger">DISCREPANCIES</div><h3 class="fw-bold m-0 text-danger"><?= $kpi_mismatch ?></h3></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div class="btn-group rounded-pill bg-white border p-1 shadow-sm">
                <a href="?view=active" class="btn btn-sm rounded-pill px-4 <?= $view_mode=='active'?'btn-dark fw-bold':'text-muted' ?>">Active Flow</a>
                <a href="?view=history" class="btn btn-sm rounded-pill px-4 <?= $view_mode=='history'?'btn-dark fw-bold':'text-muted' ?>">History</a>
            </div>

            <form class="d-flex align-items-center gap-2 bg-white border rounded-pill px-3 py-1 shadow-sm">
                <i class="bi bi-search text-muted"></i>
                <input type="text" name="q" class="form-control border-0 bg-transparent shadow-none" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                <div class="vr text-muted h-50"></div>
                <select name="vendor" class="form-select border-0 bg-transparent shadow-none" onchange="this.form.submit()" style="width: 150px;">
                    <option value="">All Vendors</option>
                    <?php foreach($vendors as $v): ?>
                        <option value="<?= $v['vendor_name'] ?>" <?= $vendor_filter==$v['vendor_name']?'selected':'' ?>><?= $v['vendor_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="view" value="<?= $view_mode ?>">
            </form>
        </div>

        <div class="glass-table">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Purchase Order</th>
                            <th>Vendor Details</th>
                            <th>Arrival Plan</th>
                            <th>Reception Progress</th>
                            <th>Operational Status</th>
                            <th class="text-end pe-4">Command</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($list)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No active operations found.</td></tr>
                        <?php endif; ?>

                        <?php foreach($list as $row): 
                            $pct = ($row['total_qty'] > 0) ? ($row['total_recv'] / $row['total_qty']) * 100 : 0;
                            $has_mismatch = $row['discrepancy_count'] > 0;
                            $has_tasks = $row['open_tasks'] > 0;
                            
                            // 🧠 SMART STATUS LOGIC (UI)
                            if($has_mismatch) {
                                $status_badge = '<span class="badge bg-danger text-white pulse-red"><i class="bi bi-exclamation-triangle me-1"></i> MISMATCH</span>';
                                $row_class = 'bg-danger bg-opacity-10';
                            } elseif($has_tasks) {
                                $status_badge = '<span class="badge bg-warning text-dark border border-warning"><i class="bi bi-gear-wide-connected me-1"></i> PUTAWAY PENDING</span>';
                                $row_class = '';
                            } elseif($row['status'] == 'OPEN') {
                                $status_badge = '<span class="badge bg-primary-subtle text-primary border border-primary">RECEIVING OPEN</span>';
                                $row_class = '';
                            } else {
                                $status_badge = '<span class="badge bg-success-subtle text-success border border-success">COMPLETED</span>';
                                $row_class = 'opacity-75';
                            }
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-light border rounded p-2 text-center" style="width: 50px;">
                                        <i class="bi bi-file-text fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6"><?= $row['po_number'] ?></div>
                                        <div class="small text-muted"><?= $row['total_sku'] ?> SKU Items</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $row['vendor_name'] ?></div>
                                <div class="small text-muted"><?= $row['po_type'] ?> &bull; <?= $row['currency'] ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['expected_date'])) ?></div>
                                <div class="small text-muted">Expected</div>
                            </td>
                            <td style="width: 250px;">
                                <div class="d-flex justify-content-between small fw-bold mb-1">
                                    <span><?= number_format($row['total_recv']) ?> / <?= number_format($row['total_qty']) ?></span>
                                    <span class="text-primary"><?= round($pct) ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <?= $status_badge ?>
                                    <?php if($row['status'] == 'CLOSED' && ($has_tasks || $has_mismatch)): ?>
                                        <small class="text-muted fst-italic" style="font-size: 0.65rem;">Doc Closed, Ops Active</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <a href="receiving.php?po=<?= $row['po_number'] ?>" class="btn btn-primary rounded-pill btn-sm fw-bold px-4 shadow-sm">
                                    Process <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if(localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
    </script>
</body>
</html>