<?php
// apps/wms/task.php
// V9: PREMIUM TASK MONITOR (Complete Columns + V8 Logic)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// Logic Filter
$view = isset($_GET['view']) ? $_GET['view'] : 'open';
$filter_status = ($view == 'history') ? "t.status = 'CONFIRMED'" : "t.status = 'OPEN'";

// Query Data (LENGKAP: Join Product, User, dll)
$sql = "SELECT t.*, p.product_code, p.description, p.base_uom 
        FROM wms_warehouse_tasks t 
        JOIN wms_products p ON t.product_uuid = p.product_uuid 
        WHERE $filter_status 
        ORDER BY t.created_at DESC";
$tasks = safeGetAll($pdo, $sql);

// KPI Stats
$kpi_open = safeGetOne($pdo, "SELECT count(*) as c FROM wms_warehouse_tasks WHERE status='OPEN'")['c'];
$kpi_putaway = safeGetOne($pdo, "SELECT count(*) as c FROM wms_warehouse_tasks WHERE status='OPEN' AND process_type='PUTAWAY'")['c'];
$kpi_picking = safeGetOne($pdo, "SELECT count(*) as c FROM wms_warehouse_tasks WHERE status='OPEN' AND process_type='PICKING'")['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Control Tower</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --text: #1e293b; --primary: #2563eb; }
        body { background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, sans-serif; padding-bottom: 50px; }
        
        .kpi-card { background: var(--card); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; }
        .kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 15px; }
        
        .main-card { background: var(--card); border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
        
        .table thead th { background: #f1f5f9; color: #64748b; text-transform: uppercase; font-size: 0.75rem; font-weight: 700; padding: 15px; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .badge-task { padding: 5px 10px; border-radius: 6px; font-weight: 600; font-size: 0.75rem; }
        .type-putaway { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .type-picking { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        
        .font-mono { font-family: 'Consolas', monospace; color: #475569; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark">Task Monitor</h3>
            <p class="text-muted m-0">Live tracking of warehouse movements</p>
        </div>
        <div>
            <a href="rf_scanner.php" target="_blank" class="btn btn-dark"><i class="bi bi-qr-code-scan me-2"></i>RF Emulator</a>
            <a href="index.php" class="btn btn-outline-secondary ms-2">Back</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-list-task"></i></div>
                <div><h3 class="fw-bold m-0"><?= $kpi_open ?></h3><small class="text-muted">Total Open Tasks</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-box-arrow-in-down"></i></div>
                <div><h3 class="fw-bold m-0"><?= $kpi_putaway ?></h3><small class="text-muted">Pending Putaway</small></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-box-arrow-up"></i></div>
                <div><h3 class="fw-bold m-0"><?= $kpi_picking ?></h3><small class="text-muted">Pending Picking</small></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3 border-bottom-0">
        <li class="nav-item">
            <a class="nav-link <?= $view=='open'?'active fw-bold':'' ?>" href="?view=open"><i class="bi bi-hourglass-split me-2"></i>Open Tasks</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view=='history'?'active fw-bold':'' ?>" href="?view=history"><i class="bi bi-clock-history me-2"></i>History / Logs</a>
        </li>
    </ul>

    <div class="main-card">
        <div class="table-responsive">
            <table class="table mb-0 table-hover">
                <thead>
                    <tr>
                        <th>Task ID</th>
                        <th>Type</th>
                        <th>Product Details</th>
                        <th>Qty / UoM</th>
                        <th>Route (From &rarr; To)</th>
                        <th>Ref / Batch / HU</th>
                        <th>Created At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($tasks)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted fw-bold">No tasks found in this view.</td></tr>
                    <?php endif; ?>

                    <?php foreach($tasks as $row): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">WT-<?= $row['tanum'] ?></div>
                        </td>
                        <td>
                            <?php if($row['process_type'] == 'PUTAWAY'): ?>
                                <span class="badge-task type-putaway"><i class="bi bi-arrow-down-short"></i> PUTAWAY</span>
                            <?php else: ?>
                                <span class="badge-task type-picking"><i class="bi bi-arrow-up-short"></i> PICKING</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= $row['product_code'] ?></div>
                            <div class="small text-muted text-truncate" style="max-width: 200px;"><?= $row['description'] ?></div>
                        </td>
                        <td>
                            <span class="fs-6 fw-bold text-dark"><?= (float)$row['qty'] ?></span> 
                            <span class="small text-muted"><?= $row['base_uom'] ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center font-mono small">
                                <span class="text-muted"><?= $row['source_bin'] ?></span>
                                <i class="bi bi-arrow-right mx-2 text-primary"></i>
                                <span class="fw-bold text-dark"><?= $row['dest_bin'] ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="small text-muted">HU: <span class="font-mono text-dark"><?= $row['hu_id'] ?? '-' ?></span></div>
                            <div class="small text-muted">Batch: <span class="font-mono text-dark"><?= $row['batch'] ?? '-' ?></span></div>
                        </td>
                        <td>
                            <div class="small text-dark"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                            <div class="small text-muted" style="font-size:0.75rem"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                        </td>
                        <td class="text-end">
                            <?php if($row['status'] == 'OPEN'): ?>
                                <a href="task_confirm.php?id=<?= $row['tanum'] ?>" class="btn btn-sm btn-outline-primary fw-bold">
                                    Process
                                </a>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-check-all"></i> Done</span>
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