<?php 
// apps/wms/logs.php
// V12: AUDIT COMMAND CENTER (Dual Logs: System & Stock)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'system';
$search = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$limit = 50;

// QUERY BUILDER
$filter_sys = ""; 
$filter_stk = "";
$params_sys = [];
$params_stk = [];

if($search) {
    $filter_sys = "WHERE user_id LIKE ? OR description LIKE ? OR module LIKE ?";
    $params_sys = ["%$search%", "%$search%", "%$search%"];

    // Untuk Stock Log, kita perlu join ke product biar bisa cari nama barang
    $filter_stk = "WHERE (p.product_code LIKE ? OR m.batch LIKE ? OR m.trx_ref LIKE ? OR m.user LIKE ?)";
    $params_stk = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail & Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; padding-bottom: 50px; }
        .card-log { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        
        .nav-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; }
        .nav-tabs .nav-link:hover { color: #1e293b; background: transparent; }
        .nav-tabs .nav-link.active { color: #2563eb; border-bottom-color: #2563eb; background: transparent; }
        
        .log-badge { font-size: 0.75rem; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; }
        .bg-CREATE { background: #dcfce7; color: #166534; }
        .bg-UPDATE { background: #fef9c3; color: #854d0e; }
        .bg-DELETE { background: #fee2e2; color: #991b1b; }
        .bg-LOGIN { background: #e0e7ff; color: #3730a3; }
        
        .move-in { color: #166534; font-weight: bold; } /* Putaway */
        .move-out { color: #991b1b; font-weight: bold; } /* Picking */
        .move-internal { color: #3b82f6; font-weight: bold; } /* Transfer */
    </style>
</head>
<body>

<div class="container-fluid py-4" style="max-width: 1400px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Audit Command Center</h3>
            <p class="text-muted m-0">Track User Activities & Stock Movements</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary fw-bold">Dashboard</a>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center border-bottom mb-4">
        <ul class="nav nav-tabs border-bottom-0">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == 'system' ? 'active' : '' ?>" href="?tab=system">
                    <i class="bi bi-person-lines-fill me-2"></i> User Activity Log
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == 'stock' ? 'active' : '' ?>" href="?tab=stock">
                    <i class="bi bi-box-seam-fill me-2"></i> Stock Movement Log
                </a>
            </li>
        </ul>
        
        <form class="mb-2" method="GET" style="width: 300px;">
            <input type="hidden" name="tab" value="<?= $active_tab ?>">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </form>
    </div>

    <div class="tab-content">
        
        <div class="tab-pane fade <?= $active_tab == 'system' ? 'show active' : '' ?>">
            <div class="card card-log">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Timestamp</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th class="text-end pe-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * FROM wms_system_logs $filter_sys ORDER BY id DESC LIMIT $limit";
                            $stmt = safeGetAll($pdo, $sql, $params_sys);
                            
                            if(empty($stmt)): echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No logs found.</td></tr>"; else:
                            foreach($stmt as $row):
                            ?>
                            <tr>
                                <td class="ps-4 text-secondary" style="font-family: monospace; font-size: 0.9rem;">
                                    <?= date('d M H:i:s', strtotime($row['log_date'])) ?>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['user_id']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['module']) ?></span></td>
                                <td><span class="log-badge bg-<?= $row['action_type'] ?>"><?= $row['action_type'] ?></span></td>
                                <td class="text-secondary"><?= htmlspecialchars($row['description']) ?></td>
                                <td class="text-end pe-4 font-monospace text-muted small"><?= $row['ip_address'] ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $active_tab == 'stock' ? 'show active' : '' ?>">
            <div class="card card-log">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Timestamp</th>
                                <th>Type / Ref</th>
                                <th>Item</th>
                                <th>Batch / HU</th>
                                <th class="text-center">Movement</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end pe-4">User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Join dengan Products agar bisa search nama barang
                            $sql = "SELECT m.*, p.product_code, p.description 
                                    FROM wms_stock_movements m 
                                    JOIN wms_products p ON m.product_uuid = p.product_uuid 
                                    $filter_stk 
                                    ORDER BY m.move_id DESC LIMIT $limit";
                            $stmt = safeGetAll($pdo, $sql, $params_stk);
                            
                            if(empty($stmt)): echo "<tr><td colspan='7' class='text-center py-5 text-muted'>No movements found.</td></tr>"; else:
                            foreach($stmt as $row):
                                // Tentukan Warna & Arah
                                $qty_style = "text-dark";
                                if($row['qty_change'] > 0) $qty_style = "move-in";
                                elseif($row['qty_change'] < 0) $qty_style = "move-out";
                                
                                $icon_dir = '<i class="bi bi-arrow-right text-muted mx-2"></i>';
                                if($row['move_type'] == 'RF_PICKING') $icon_dir = '<i class="bi bi-arrow-right text-danger mx-2"></i>';
                                if($row['move_type'] == 'RF_PUTAWAY') $icon_dir = '<i class="bi bi-arrow-right text-success mx-2"></i>';
                            ?>
                            <tr>
                                <td class="ps-4 text-secondary small" style="font-family: monospace;">
                                    <?= date('d M H:i:s', strtotime($row['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small"><?= $row['move_type'] ?></div>
                                    <div class="text-muted small" style="font-size:0.75rem"><?= $row['trx_ref'] ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary"><?= $row['product_code'] ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 200px;"><?= $row['description'] ?></div>
                                </td>
                                <td class="small">
                                    <div>Batch: <?= $row['batch'] ?></div>
                                    <div class="text-muted">HU: <?= $row['hu_id'] ?></div>
                                </td>
                                <td class="text-center small fw-bold">
                                    <?= $row['from_bin'] ?: '-' ?> 
                                    <?= $icon_dir ?> 
                                    <?= $row['to_bin'] ?: '-' ?>
                                </td>
                                <td class="text-end fw-bold fs-6 <?= $qty_style ?>">
                                    <?= ($row['qty_change'] > 0 ? '+' : '') . (float)$row['qty_change'] ?>
                                </td>
                                <td class="text-end pe-4 text-secondary small"><?= $row['user'] ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>