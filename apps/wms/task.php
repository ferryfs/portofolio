<?php
// apps/wms/task.php
// V13: WAREHOUSE CONTROL TOWER (Enterprise Unified UI + Responsive Logic)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$user_id = $_SESSION['wms_fullname'] ?? 'System';

// --- LOGIC FILTER ---
$tab = $_GET['tab'] ?? 'all'; 
$search = sanitizeInput($_GET['q'] ?? '');

$where = "WHERE 1=1";
$params = [];

if($tab == 'history') {
    $where .= " AND t.status = 'CONFIRMED'";
} else {
    $where .= " AND t.status = 'OPEN'";
    if($tab == 'putaway') $where .= " AND t.process_type = 'PUTAWAY'";
    if($tab == 'picking') $where .= " AND t.process_type = 'PICKING'";
}

if($search) {
    // Search lebih luas: ID, Product, HU, Batch
    $where .= " AND (t.tanum LIKE ? OR p.product_code LIKE ? OR t.hu_id LIKE ? OR t.batch LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

// Query Utama
$sql = "SELECT t.*, p.product_code, p.description, p.base_uom 
        FROM wms_warehouse_tasks t 
        JOIN wms_products p ON t.product_uuid = p.product_uuid 
        $where 
        ORDER BY t.priority DESC, t.created_at ASC";

$tasks = safeGetAll($pdo, $sql, $params);

// KPI Live Stats
$kpi_putaway = safeGetOne($pdo, "SELECT count(*) as c FROM wms_warehouse_tasks WHERE status='OPEN' AND process_type='PUTAWAY'")['c'];
$kpi_picking = safeGetOne($pdo, "SELECT count(*) as c FROM wms_warehouse_tasks WHERE status='OPEN' AND process_type='PICKING'")['c'];
$kpi_today   = safeGetOne($pdo, "SELECT count(*) as c FROM wms_warehouse_tasks WHERE status='CONFIRMED' AND DATE(updated_at) = CURDATE()")['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Monitor | V13 Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #111827;
            --border: #e5e7eb;
        }
        body.dark-mode {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --border: #334155;
        }
        body { background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; transition: 0.3s; }
        
        /* Navbar */
        .navbar-glass {
            background: var(--card-bg); border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
        }

        /* KPI Cards */
        .kpi-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: 0.2s;
        }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        /* Table */
        .glass-table {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .table { margin: 0; color: var(--text); }
        .table thead th { background: var(--bg); color: #6b7280; font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--border); }
        .table tbody td { padding: 15px 20px; vertical-align: middle; border-bottom: 1px solid var(--border); }
        .table tbody tr:hover { background-color: var(--bg); }

        .badge-soft { padding: 6px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .bin-route { font-family: 'Consolas', monospace; background: var(--bg); padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: 1px solid var(--border); }
        
        .theme-toggle { cursor: pointer; padding: 8px; border-radius: 50%; border: 1px solid var(--border); background: var(--card-bg); }

        /* 🔥 MOBILE RESPONSIVE LOGIC */
        @media (max-width: 768px) {
            .desktop-only { display: none !important; }
            .kpi-row { grid-template-columns: 1fr; }
            .navbar-glass { padding: 1rem; }
            .table-responsive { border: 0; }
        }
    </style>
</head>
<body>

    <div class="navbar-glass">
        <div class="d-flex align-items-center gap-3">
            <h4 class="fw-bold m-0 text-primary"><i class="bi bi-list-check me-2"></i>Task Control</h4>
            <span class="badge bg-light text-muted border desktop-only">V13 Unified</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-stars-fill text-warning"></i></div>
            
            <div class="desktop-only d-flex gap-2">
                <a href="rf_scanner.php" target="_blank" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-qr-code me-2"></i>RF Emulator</a>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">Exit</a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4 py-4">
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-arrow-down-square"></i></div>
                    <div><div class="small text-muted fw-bold">INBOUND PUTAWAY</div><h3 class="fw-bold m-0"><?= $kpi_putaway ?></h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-arrow-up-square"></i></div>
                    <div><div class="small text-muted fw-bold">OUTBOUND PICKING</div><h3 class="fw-bold m-0"><?= $kpi_picking ?></h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-check2-all"></i></div>
                    <div><div class="small text-muted fw-bold">COMPLETED TODAY</div><h3 class="fw-bold m-0"><?= $kpi_today ?></h3></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div class="btn-group rounded-pill bg-white border p-1 shadow-sm overflow-auto" style="max-width: 100%;">
                <a href="?tab=all" class="btn btn-sm rounded-pill px-4 <?= $tab=='all'?'btn-dark fw-bold':'text-muted' ?>">All Tasks</a>
                <a href="?tab=putaway" class="btn btn-sm rounded-pill px-4 <?= $tab=='putaway'?'btn-success fw-bold':'text-muted' ?>">Putaway</a>
                <a href="?tab=picking" class="btn btn-sm rounded-pill px-4 <?= $tab=='picking'?'btn-primary fw-bold':'text-muted' ?>">Picking</a>
                <a href="?tab=history" class="btn btn-sm rounded-pill px-4 <?= $tab=='history'?'btn-secondary fw-bold':'text-muted' ?>">History</a>
            </div>
            
            <form class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
                <input type="text" name="q" class="form-control rounded-pill border-0 shadow-sm ps-4" placeholder="Search Task / Batch / HU..." value="<?= htmlspecialchars($search) ?>" style="min-width: 250px;">
                <button type="submit" class="btn btn-white border rounded-circle shadow-sm"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="glass-table">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Task ID</th>
                            <th>Operation</th>
                            <th>Product Info</th>
                            <th>Qty</th>
                            <th class="desktop-only">Route</th>
                            <th class="desktop-only">Ref (HU/Batch)</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tasks)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted fw-bold">No active tasks found.</td></tr>
                        <?php endif; ?>

                        <?php foreach($tasks as $r): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary font-monospace">TASK-<?= str_pad($r['tanum'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <?php if($r['process_type'] == 'PUTAWAY'): ?>
                                    <span class="badge-soft bg-success-subtle text-success border border-success">PUTAWAY</span>
                                <?php else: ?>
                                    <span class="badge-soft bg-primary-subtle text-primary border border-primary">PICKING</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold"><?= $r['product_code'] ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?= $r['description'] ?></div>
                                <div class="d-md-none small text-muted mt-1">
                                    Ref: <?= $r['hu_id'] ?>
                                </div>
                            </td>
                            <td><span class="fw-bold fs-6"><?= (float)$r['qty'] ?></span> <small class="text-muted"><?= $r['base_uom'] ?></small></td>
                            <td class="desktop-only">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="bin-route"><?= $r['source_bin'] ?></span>
                                    <i class="bi bi-arrow-right text-muted small"></i>
                                    <span class="bin-route text-primary"><?= $r['dest_bin'] == 'SYSTEM' ? 'ANY' : $r['dest_bin'] ?></span>
                                </div>
                            </td>
                            <td class="desktop-only">
                                <div class="small font-monospace text-dark"><?= $r['hu_id'] ?></div>
                                <div class="small text-muted font-monospace"><?= $r['batch'] ?></div>
                            </td>
                            <td class="text-end pe-4">
                                <?php if($r['status'] == 'OPEN'): ?>
                                    <a href="task_confirm.php?id=<?= $r['tanum'] ?>" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold shadow-sm">
                                        Exec
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-check2-all text-success"></i> Done</span>
                                <?php endif; ?>
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