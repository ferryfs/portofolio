<?php
// apps/wms/outbound.php
// ENTERPRISE OUTBOUND - Picking Release Console

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user = $_SESSION['wms_fullname'];
$msg = ""; $msg_type = "";

// ---------------------------------------------------------
// LOGIC: RELEASE PICKING (FROM RESERVATION)
// ---------------------------------------------------------
if(isset($_POST['release_picking'])) {
    if (!verifyCSRFToken()) die("Security Alert: Invalid Token");

    $so_num = sanitizeInput($_POST['so_number']);
    
    try {
        $pdo->beginTransaction();

        $so = safeGetOne($pdo, "SELECT status FROM wms_so_header WHERE so_number=? FOR UPDATE", [$so_num]);
        if($so['status'] != 'RESERVED') throw new Exception("SO must be RESERVED first. Current status: {$so['status']}");

        $sql_res = "SELECT r.*, q.lgpla, q.batch, q.hu_id 
                    FROM wms_stock_reservations r 
                    JOIN wms_quants q ON r.quant_id = q.quant_id
                    WHERE r.so_number = ?";
        $reservations = safeGetAll($pdo, $sql_res, [$so_num]);

        if(empty($reservations)) throw new Exception("No stock reservation found. Please re-run reservation engine.");

        $count = 0;
        foreach($reservations as $res) {
            $sql_task = "INSERT INTO wms_warehouse_tasks 
                         (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at)
                         VALUES ('PICKING', (SELECT product_uuid FROM wms_quants WHERE quant_id=?), ?, ?, ?, 'GI-ZONE', ?, 'OPEN', NOW())";
            safeQuery($pdo, $sql_task, [$res['quant_id'], $res['batch'], $res['hu_id'], $res['lgpla'], $res['qty_reserved']]);
            $count++;
        }

        safeQuery($pdo, "UPDATE wms_so_header SET status='PICKING' WHERE so_number=?", [$so_num]);
        $pdo->commit();

        $msg = "$count Picking Tasks Released — Sent to RF Scanner.";
        $msg_type = "success";
        catat_log($pdo, $user, 'RELEASE', 'OUTBOUND', "Released picking task for SO: $so_num");

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $msg = $e->getMessage();
        $msg_type = "danger";
    }
}

// KPI Stats
$kpi_reserved = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_so_header WHERE status='RESERVED'")['c'] ?? 0;
$kpi_picking  = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_so_header WHERE status='PICKING'")['c'] ?? 0;
$kpi_tasks    = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_warehouse_tasks WHERE status='OPEN' AND process_type='PICKING'")['c'] ?? 0;

// Main query
$sql = "SELECT h.*, 
        COUNT(i.so_item_id) as total_sku,
        COALESCE(SUM(i.qty_ordered), 0) as total_qty
        FROM wms_so_header h
        LEFT JOIN wms_so_items i ON h.so_number = i.so_number
        WHERE h.status IN ('RESERVED', 'PICKING')
        GROUP BY h.so_number
        ORDER BY FIELD(h.status, 'RESERVED', 'PICKING'), h.expected_date ASC";
$list = safeGetAll($pdo, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outbound | Smart WMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4f46e5; --primary-light: #e0e7ff;
            --success: #10b981; --success-light: #d1fae5;
            --warning: #f59e0b; --warning-light: #fef3c7;
            --danger: #ef4444; --danger-light: #fee2e2;
            --dark: #0f172a; --bg: #f8fafc; --card: #ffffff;
            --border: #e2e8f0; --text: #1e293b; --muted: #64748b;
        }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); padding-bottom: 60px; }

        /* Navbar */
        .navbar-wms { background: var(--dark); padding: 14px 0; border-bottom: 3px solid var(--primary); position: sticky; top: 0; z-index: 100; }

        /* KPI Cards */
        .kpi-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 1.5rem; display: flex; align-items: center; gap: 1.2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); transition: 0.25s; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08); }
        .kpi-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }

        /* SO Cards */
        .so-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; transition: 0.25s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); }
        .so-card:hover { transform: translateY(-3px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .so-card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .so-card-body { padding: 20px 24px; }
        .so-card-footer { padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }

        .border-accent-reserved { border-top: 4px solid var(--warning); }
        .border-accent-picking  { border-top: 4px solid var(--primary); }

        .meta-item { display: flex; align-items: center; gap: 8px; }
        .meta-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); }
        .meta-value { font-size: 0.95rem; font-weight: 700; color: var(--text); }

        .progress-thin { height: 5px; border-radius: 999px; background: #e2e8f0; }
        .progress-thin .progress-bar { border-radius: 999px; }

        .badge-status { padding: 6px 14px; border-radius: 50px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; }

        /* Alert */
        .alert-wms { border: none; border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; font-weight: 600; }

        /* Empty state */
        .empty-state { text-align: center; padding: 80px 20px; opacity: 0.5; }

        /* Action bar */
        .action-bar { background: var(--card); border-bottom: 1px solid var(--border); padding: 16px 0; }
    </style>
</head>
<body>

<nav class="navbar navbar-wms shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="index.php">
            <i class="bi bi-box-seam-fill text-primary fs-5"></i>
            <span class="fw-700">WMS <span class="fw-300">Enterprise</span></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="text-white text-end lh-1 d-none d-md-block pe-3 border-end border-secondary">
                <div class="small fw-bold"><?= htmlspecialchars($_SESSION['wms_fullname']) ?></div>
                <div style="font-size:0.72rem; color:#94a3b8;"><?= htmlspecialchars($_SESSION['wms_role'] ?? 'ADMIN') ?></div>
            </div>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-house me-1"></i>Dashboard</a>
        </div>
    </div>
</nav>

<div class="action-bar">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h4 class="fw-bold mb-0">Outbound Console</h4>
            <p class="text-muted small mb-0">Release Picking Tasks & Monitor Order Fulfillment</p>
        </div>
        <div class="d-flex gap-2">
            <a href="sales_order.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-plus-circle me-2"></i>New Sales Order
            </a>
            <a href="shipping.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="bi bi-truck me-2"></i>Shipping
            </a>
        </div>
    </div>
</div>

<div class="container mt-4">

    <?php if($msg): ?>
        <div class="alert-wms alert-<?= $msg_type == 'success' ? 'success bg-success bg-opacity-10 text-success' : 'danger bg-danger bg-opacity-10 text-danger' ?> mb-4">
            <i class="bi <?= $msg_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
            <span><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-lock-fill"></i></div>
                <div>
                    <div class="meta-label">Stock Reserved</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= $kpi_reserved ?></h2>
                    <div class="small text-muted">Awaiting Release</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-walking"></i></div>
                <div>
                    <div class="meta-label">Picking in Progress</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= $kpi_picking ?></h2>
                    <div class="small text-muted">Active Orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-list-task"></i></div>
                <div>
                    <div class="meta-label">Open Picking Tasks</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= $kpi_tasks ?></h2>
                    <div class="small text-muted">On Floor</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SO List -->
    <?php if(empty($list)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox display-1"></i>
            <h4 class="mt-4 fw-bold">No Orders Ready for Release</h4>
            <p class="text-muted">Create and reserve a Sales Order to begin outbound process.</p>
            <a href="sales_order.php" class="btn btn-primary rounded-pill px-5 mt-2 fw-bold">Create Sales Order</a>
        </div>
    <?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-muted text-uppercase small m-0">Active Orders — <?= count($list) ?> Document(s)</h6>
    </div>

    <div class="row g-4">
        <?php foreach($list as $row): 
            $isReserved = $row['status'] == 'RESERVED';
            $accentClass = $isReserved ? 'border-accent-reserved' : 'border-accent-picking';
        ?>
        <div class="col-12">
            <div class="so-card <?= $accentClass ?>">
                <div class="so-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon <?= $isReserved ? 'bg-warning bg-opacity-10 text-warning' : 'bg-primary bg-opacity-10 text-primary' ?>" style="width:44px;height:44px;font-size:1.2rem;border-radius:12px;">
                            <i class="bi <?= $isReserved ? 'bi-lock-fill' : 'bi-person-walking' ?>"></i>
                        </div>
                        <div>
                            <div class="fw-bold font-monospace fs-6"><?= htmlspecialchars($row['so_number']) ?></div>
                            <div class="small text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($row['customer_name']) ?></div>
                        </div>
                    </div>
                    <?php if($isReserved): ?>
                        <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning">
                            <i class="bi bi-lock-fill"></i> Stock Reserved
                        </span>
                    <?php else: ?>
                        <span class="badge-status bg-primary bg-opacity-10 text-primary border border-primary">
                            <i class="bi bi-activity"></i> Picking Active
                        </span>
                    <?php endif; ?>
                </div>

                <div class="so-card-body">
                    <div class="row g-4">
                        <div class="col-sm-3">
                            <div class="meta-label mb-1">SKU Count</div>
                            <div class="meta-value"><?= $row['total_sku'] ?> Items</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="meta-label mb-1">Total Quantity</div>
                            <div class="meta-value"><?= number_format($row['total_qty']) ?> Pcs</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="meta-label mb-1">Expected Date</div>
                            <div class="meta-value"><?= !empty($row['expected_date']) ? date('d M Y', strtotime($row['expected_date'])) : '—' ?></div>
                        </div>
                        <div class="col-sm-3">
                            <div class="meta-label mb-1">Priority</div>
                            <div class="meta-value">
                                <?php 
                                $prio = $row['priority'] ?? 'NORMAL';
                                $prioClass = $prio == 'HIGH' ? 'text-danger' : ($prio == 'MEDIUM' ? 'text-warning' : 'text-muted');
                                ?>
                                <span class="<?= $prioClass ?>"><?= htmlspecialchars($prio) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if(!empty($row['remarks'])): ?>
                    <div class="mt-3 p-3 bg-light rounded-3 border small text-muted">
                        <i class="bi bi-chat-quote me-2"></i><?= htmlspecialchars($row['remarks']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="so-card-footer">
                    <div class="small text-muted">
                        <?php if(!$isReserved): ?>
                            <i class="bi bi-clock me-1"></i>Picking tasks sent to warehouse floor
                        <?php else: ?>
                            <i class="bi bi-info-circle me-1"></i>Ready to release — stock locked in system
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if($isReserved): ?>
                            <form method="POST" class="d-inline">
                                <?php echo csrfTokenField(); ?>
                                <input type="hidden" name="so_number" value="<?= htmlspecialchars($row['so_number']) ?>">
                                <button type="submit" name="release_picking" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" 
                                        onclick="return confirm('Release picking tasks for <?= $row['so_number'] ?>?')">
                                    <i class="bi bi-send-fill me-2"></i>Release to Floor
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="task.php?tab=picking" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-eye me-2"></i>Monitor Tasks
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
