<?php 
// apps/wms/logs.php
// V14: ENTERPRISE AUDIT CENTER (Pagination, Universal Filter, Smart Print)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'stock'; 
$search = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';

// 🔥 PAGINATION LOGIC
$limit = 20; 
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if($page_num < 1) $page_num = 1;
$offset = ($page_num - 1) * $limit;

// ---------------------------------------------------------
// 🔍 KPI STATS (Live Dashboard)
// ---------------------------------------------------------
$today_moves = safeGetOne($pdo, "SELECT count(*) as c FROM wms_stock_movements WHERE DATE(created_at) = CURDATE()")['c'];
$inbound_vol = safeGetOne($pdo, "SELECT COALESCE(SUM(qty_change),0) as c FROM wms_stock_movements WHERE move_type IN ('GR_IN', 'PI_GAIN') AND DATE(created_at) = CURDATE()")['c'];
$outbound_vol = safeGetOne($pdo, "SELECT COALESCE(ABS(SUM(qty_change)),0) as c FROM wms_stock_movements WHERE qty_change < 0 AND DATE(created_at) = CURDATE()")['c'];

// ---------------------------------------------------------
// 🔍 UNIVERSAL QUERY BUILDER
// ---------------------------------------------------------
$filter_sys = "WHERE 1=1"; 
$filter_stk = "WHERE 1=1";
$params_sys = [];
$params_stk = [];

if($search) {
    $filter_sys .= " AND (user_id LIKE ? OR description LIKE ? OR module LIKE ? OR action_type LIKE ?)";
    array_push($params_sys, "%$search%", "%$search%", "%$search%", "%$search%");

    $filter_stk .= " AND (p.product_code LIKE ? OR m.batch LIKE ? OR m.trx_ref LIKE ? OR m.user LIKE ? OR m.hu_id LIKE ?)";
    array_push($params_stk, "%$search%", "%$search%", "%$search%", "%$search%", "%$search%");
}

if($type_filter) {
    if($active_tab == 'stock') {
        $filter_stk .= " AND m.move_type = ?";
        $params_stk[] = $type_filter;
    } else {
        $filter_sys .= " AND action_type = ?";
        $params_sys[] = $type_filter;
    }
}

// Get Data for Dropdown dynamically (supports future OUTBOUND types automatically)
if ($active_tab == 'stock') {
    $move_types = safeGetAll($pdo, "SELECT DISTINCT move_type as type_name FROM wms_stock_movements ORDER BY move_type");
    // Count total for pagination
    $total_rows = safeGetOne($pdo, "SELECT count(*) as c FROM wms_stock_movements m JOIN wms_products p ON m.product_uuid = p.product_uuid $filter_stk", $params_stk)['c'];
} else {
    $move_types = safeGetAll($pdo, "SELECT DISTINCT action_type as type_name FROM wms_system_logs ORDER BY action_type");
    // Count total for pagination
    $total_rows = safeGetOne($pdo, "SELECT count(*) as c FROM wms_system_logs $filter_sys", $params_sys)['c'];
}

$total_pages = ceil($total_rows / $limit);

// Helper: Badge Logic
function getMoveBadge($type) {
    $map = [
        'GR_IN'        => ['bg' => 'bg-success-subtle text-success border-success', 'icon' => 'bi-box-arrow-in-down'],
        'GR_BAD'       => ['bg' => 'bg-danger-subtle text-danger border-danger', 'icon' => 'bi-x-octagon'],
        'RF_PUTAWAY'   => ['bg' => 'bg-primary-subtle text-primary border-primary', 'icon' => 'bi-arrow-down-square-fill'],
        'DESK_CONFIRM' => ['bg' => 'bg-info-subtle text-info border-info', 'icon' => 'bi-pc-display'],
        'RF_PICKING'   => ['bg' => 'bg-warning-subtle text-warning border-warning', 'icon' => 'bi-arrow-up-square-fill'],
        'BIN_MOVE'     => ['bg' => 'bg-secondary-subtle text-secondary border-secondary', 'icon' => 'bi-arrows-move'],
        'PI_GAIN'      => ['bg' => 'bg-success text-white', 'icon' => 'bi-graph-up-arrow'],
        'PI_LOSS'      => ['bg' => 'bg-danger text-white', 'icon' => 'bi-graph-down-arrow'],
    ];
    return $map[$type] ?? ['bg' => 'bg-light text-dark border', 'icon' => 'bi-circle'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Center | V14 Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --bg: #f3f4f6; --card-bg: #ffffff; --text: #111827; --text-muted: #6b7280; --border: #e5e7eb; }
        body.dark-mode { --bg: #0f172a; --card-bg: #1e293b; --text: #f8fafc; --text-muted: #94a3b8; --border: #334155; }
        body { background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; transition: 0.3s; padding-bottom: 50px; }
        
        .kpi-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        .glass-table { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table { margin: 0; color: var(--text); }
        .table thead th { background: var(--bg); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--border); }
        .table tbody td { padding: 15px 20px; vertical-align: middle; border-bottom: 1px solid var(--border); }
        .table tbody tr:hover { background-color: var(--bg); }

        .badge-move { padding: 6px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border: 1px solid; display: inline-flex; align-items: center; gap: 6px; }
        .font-mono { font-family: 'Consolas', monospace; font-size: 0.85rem; }
        .theme-toggle { cursor: pointer; padding: 8px; border-radius: 50%; border: 1px solid var(--border); background: var(--card-bg); }
        .qty-plus { color: #16a34a; font-weight: 800; }
        .qty-min { color: #dc2626; font-weight: 800; }

        /* 🔥 PRINT CSS: CUMA NGE-PRINT TABEL */
        @media print {
            .hide-on-print { display: none !important; }
            body { background-color: #fff !important; padding: 0 !important; color: #000 !important; }
            .container-fluid { padding: 0 !important; }
            .glass-table { border: none !important; box-shadow: none !important; }
            .table thead th { background-color: #fff !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
            .table tbody td { border-bottom: 1px solid #ddd !important; }
            .badge-move { border: none !important; background: none !important; padding: 0 !important; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-card border-bottom hide-on-print" style="background: var(--card-bg); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100;">
        <div class="d-flex align-items-center gap-3">
            <h4 class="fw-bold m-0 text-primary"><i class="bi bi-shield-check me-2"></i>Audit Center</h4>
            <span class="badge bg-light text-muted border">V14 Enterprise</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-stars-fill text-warning"></i></div>
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 fw-bold btn-sm"><i class="bi bi-printer me-2"></i>Print</button>
            <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm btn-sm">Dashboard</a>
        </div>
    </div>

    <div class="container-fluid px-4 py-4">
        
        <div class="row g-4 mb-4 hide-on-print kpi-row">
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-activity"></i></div>
                    <div><div class="small text-muted fw-bold">TOTAL MOVES (TODAY)</div><h3 class="fw-bold m-0"><?= number_format($today_moves) ?></h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-arrow-down-right"></i></div>
                    <div><div class="small text-muted fw-bold">INBOUND VOL (QTY)</div><h3 class="fw-bold m-0 text-success">+<?= number_format($inbound_vol) ?></h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-up-right"></i></div>
                    <div><div class="small text-muted fw-bold">OUTBOUND VOL (QTY)</div><h3 class="fw-bold m-0 text-warning">-<?= number_format($outbound_vol) ?></h3></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 hide-on-print filter-row">
            <div class="btn-group rounded-pill bg-white border p-1 shadow-sm">
                <a href="?tab=stock" class="btn btn-sm rounded-pill px-4 <?= $active_tab=='stock'?'btn-primary fw-bold':'text-muted' ?>">Stock Movements</a>
                <a href="?tab=system" class="btn btn-sm rounded-pill px-4 <?= $active_tab=='system'?'btn-dark fw-bold':'text-muted' ?>">System Logs</a>
            </div>
            
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="tab" value="<?= $active_tab ?>">
                <select name="type" class="form-select rounded-pill border shadow-sm" style="width: 160px;" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <?php foreach($move_types as $mt): ?>
                        <option value="<?= htmlspecialchars($mt['type_name']) ?>" <?= $type_filter==$mt['type_name']?'selected':'' ?>><?= htmlspecialchars($mt['type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="input-group shadow-sm rounded-pill overflow-hidden">
                    <input type="text" name="q" class="form-control border-0 ps-4" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
                    <button type="submit" class="btn btn-white border-start"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="glass-table mb-4">
            <div class="table-responsive">
                
                <?php if($active_tab == 'stock'): ?>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Timestamp</th>
                            <th>Movement Type</th>
                            <th>Product Info</th>
                            <th>Reference</th>
                            <th class="text-center">Route</th>
                            <th class="text-end">Qty Change</th>
                            <th class="text-end pe-4">Actor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt_stk = safeGetAll($pdo, "SELECT m.*, p.product_code, p.description 
                                                      FROM wms_stock_movements m 
                                                      JOIN wms_products p ON m.product_uuid = p.product_uuid 
                                                      $filter_stk 
                                                      ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset", $params_stk);
                        if(empty($stmt_stk)): echo "<tr><td colspan='7' class='text-center py-5 text-muted'>No data found.</td></tr>"; else:
                        foreach($stmt_stk as $row):
                            $badge = getMoveBadge($row['move_type']);
                            $q = (float)$row['qty_change'];
                            $q_cls = $q > 0 ? 'qty-plus' : ($q < 0 ? 'qty-min' : 'text-muted');
                            $q_sgn = $q > 0 ? '+' : '';
                        ?>
                        <tr>
                            <td class="ps-4 font-mono text-muted"><?= date('d M Y H:i:s', strtotime($row['created_at'])) ?></td>
                            <td>
                                <span class="badge-move <?= $badge['bg'] ?>">
                                    <i class="bi <?= $badge['icon'] ?>"></i> <?= str_replace('_', ' ', $row['move_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold"><?= $row['product_code'] ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?= $row['description'] ?></div>
                            </td>
                            <td>
                                <div class="font-mono small fw-bold text-dark"><?= $row['hu_id'] ?: '-' ?></div>
                                <div class="font-mono small text-muted"><?= $row['trx_ref'] ?></div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2 font-mono small">
                                    <span class="bg-light px-2 rounded border"><?= $row['from_bin'] ?: 'OUT' ?></span>
                                    <i class="bi bi-arrow-right text-muted"></i>
                                    <span class="bg-light px-2 rounded border fw-bold text-primary"><?= $row['to_bin'] ?: 'OUT' ?></span>
                                </div>
                            </td>
                            <td class="text-end <?= $q_cls ?> fs-6"><?= $q_sgn . $q ?></td>
                            <td class="text-end pe-4 text-muted small"><i class="bi bi-person-fill me-1"></i><?= $row['user'] ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <?php else: ?>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th class="text-end pe-4">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt_sys = safeGetAll($pdo, "SELECT * FROM wms_system_logs $filter_sys ORDER BY log_date DESC LIMIT $limit OFFSET $offset", $params_sys);
                        if(empty($stmt_sys)): echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No system logs found.</td></tr>"; else:
                        foreach($stmt_sys as $row):
                        ?>
                        <tr>
                            <td class="ps-4 font-mono text-muted"><?= date('d M Y H:i:s', strtotime($row['log_date'])) ?></td>
                            <td><div class="fw-bold text-dark"><?= htmlspecialchars($row['user_id']) ?></div></td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2"><?= htmlspecialchars($row['module']) ?></span></td>
                            <td><span class="fw-bold text-dark small"><?= htmlspecialchars($row['action_type']) ?></span></td>
                            <td class="text-muted small"><?= htmlspecialchars($row['description']) ?></td>
                            <td class="text-end pe-4 font-mono text-muted small"><?= htmlspecialchars($row['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>

            </div>
        </div>

        <?php if($total_pages > 1): ?>
        <nav class="mb-5 hide-on-print pagination-row">
            <ul class="pagination justify-content-center align-items-center gap-2">
                <li class="page-item <?= ($page_num <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link rounded-pill px-4 border shadow-sm fw-bold <?= ($page_num <= 1) ? 'text-muted bg-light' : 'text-primary' ?>" 
                       href="?tab=<?= urlencode($active_tab) ?>&type=<?= urlencode($type_filter) ?>&q=<?= urlencode($search) ?>&p=<?= $page_num - 1 ?>">
                       <i class="bi bi-chevron-left me-1"></i> Prev
                    </a>
                </li>
                
                <li class="page-item disabled">
                    <span class="page-link border-0 bg-transparent text-muted fw-bold">Page <?= $page_num ?> of <?= $total_pages ?></span>
                </li>

                <li class="page-item <?= ($page_num >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link rounded-pill px-4 border shadow-sm fw-bold <?= ($page_num >= $total_pages) ? 'text-muted bg-light' : 'text-primary' ?>" 
                       href="?tab=<?= urlencode($active_tab) ?>&type=<?= urlencode($type_filter) ?>&q=<?= urlencode($search) ?>&p=<?= $page_num + 1 ?>">
                       Next <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

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