<?php
// apps/wms/stock_master.php
// V10.2: INVENTORY DASHBOARD (AJAX FIXED + GOOD VS BAD BREAKDOWN)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// --- 🔥 AJAX HANDLER ---
if(isset($_POST['action']) && $_POST['action'] == 'get_stock_detail') {
    ob_clean(); 
    
    $uuid = sanitizeInput($_POST['product_uuid']);
    
    $details = safeGetAll($pdo, "
        SELECT lgpla, batch, hu_id, qty, stock_type, gr_date 
        FROM wms_quants 
        WHERE product_uuid = ? AND qty > 0
        ORDER BY lgpla ASC, gr_date ASC
    ", [$uuid]);
    
    if(empty($details)) {
        echo '<div class="alert alert-light text-center mb-0 py-3 text-muted"><i class="bi bi-inbox me-2"></i>No active stock found in bins.</div>';
        exit;
    }
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">';
    echo '<thead class="table-light text-muted uppercase"><tr>
            <th>Bin Location</th><th>Batch / Lot</th><th>Pallet (HU ID)</th><th>Category</th><th class="text-end">Actual Qty</th><th>Age</th>
          </tr></thead><tbody>';
          
    foreach($details as $d) {
        $age = floor((time() - strtotime($d['gr_date'])) / (60 * 60 * 24));
        
        $typeLabel = 'Unrestricted'; $color = 'text-success'; $bg = 'bg-success-subtle';
        if($d['stock_type'] == 'Q4') { $typeLabel = 'In Quality'; $color = 'text-warning'; $bg = 'bg-warning-subtle'; }
        elseif($d['stock_type'] == 'B6') { $typeLabel = 'Blocked/Dmg'; $color = 'text-danger'; $bg = 'bg-danger-subtle'; }
        
        echo "<tr>
                <td class='fw-bold text-dark font-monospace'>{$d['lgpla']}</td>
                <td>{$d['batch']}</td>
                <td class='font-monospace text-muted'>{$d['hu_id']}</td>
                <td><span class='badge {$bg} {$color} border border-opacity-25'>{$typeLabel} ({$d['stock_type']})</span></td>
                <td class='text-end fw-bold fs-6 text-dark'>".number_format((float)$d['qty'])."</td>
                <td class='text-muted'>{$age} d</td>
              </tr>";
    }
    
    echo '</tbody></table></div>';
    exit; 
}


// 1. STATISTIK GLOBAL
$stats = safeGetOne($pdo, "
    SELECT 
        COUNT(DISTINCT product_uuid) as total_sku,
        COALESCE(SUM(qty), 0) as total_items,
        COALESCE(SUM(CASE WHEN stock_type='B6' THEN qty ELSE 0 END), 0) as total_blocked
    FROM wms_quants
");

// 2. STOCK SUMMARY (Group by Product with Good/Bad Split)
$stocks = safeGetAll($pdo, "
    SELECT 
        p.product_uuid, p.product_code, p.description, p.base_uom,
        COALESCE(SUM(CASE WHEN q.stock_type = 'F1' THEN q.qty ELSE 0 END), 0) as stock_f1,
        COALESCE(SUM(CASE WHEN q.stock_type = 'Q4' THEN q.qty ELSE 0 END), 0) as stock_q4,
        COALESCE(SUM(CASE WHEN q.stock_type IN ('F1', 'Q4') THEN q.qty ELSE 0 END), 0) as total_good,
        COALESCE(SUM(CASE WHEN q.stock_type = 'B6' THEN q.qty ELSE 0 END), 0) as total_bad,
        COALESCE(SUM(q.qty), 0) as grand_total
    FROM wms_products p
    LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid
    GROUP BY p.product_uuid, p.product_code, p.description, p.base_uom
    ORDER BY p.product_code ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Master | WMS Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --primary: #4f46e5; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); padding-bottom: 3rem; }
        
        .navbar-custom { background: #0f172a; padding: 15px 0; border-bottom: 3px solid var(--primary); }
        .navbar-brand { font-weight: 800; letter-spacing: 0.5px; }

        .stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.5rem; position: relative; overflow: hidden; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .stat-icon { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1; transform: rotate(-15deg); }
        .stat-value { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; line-height: 1; letter-spacing: -1px; }
        .stat-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        
        .border-l-primary { border-left: 5px solid var(--primary); }
        .border-l-success { border-left: 5px solid #10b981; }
        .border-l-danger  { border-left: 5px solid #ef4444; }

        .card-table { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
        .table thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; }
        .table tbody td { padding: 1.25rem 1.5rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .table tbody tr.main-row { transition: 0.2s; }
        .table tbody tr.main-row:hover { background-color: #f8fafc; cursor: pointer; }
        
        .detail-row { display: none; }
        .detail-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.5rem; margin: 0.5rem 0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
        
        .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
        .fw-800 { font-weight: 800; }
        .divider-col { border-right: 2px dashed #e2e8f0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-5">
    <div class="container-fluid px-5" style="max-width: 1600px;">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <i class="bi bi-boxes text-primary fs-4"></i> 
            <span>Inventory <span style="font-weight: 300;">Master</span></span>
        </a>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold"><i class="bi bi-house-door me-2"></i>Dashboard</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-5" style="max-width: 1600px;">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-800 m-0 text-dark">Live Stock Monitor</h3>
            <p class="text-muted m-0">Real-time capacity & location tracking</p>
        </div>
        <div class="d-flex gap-3">
            <a href="internal.php" class="btn btn-white border border-2 rounded-pill px-4 fw-bold shadow-sm text-dark hover-primary">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Internal Transfer
            </a>
            <a href="physical_inventory.php" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-clipboard-check text-warning me-2"></i>Stock Opname
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card border-l-primary">
                <div class="stat-value" style="color: var(--primary);"><?= number_format($stats['total_sku']) ?></div>
                <div class="stat-label">Active Products (SKU)</div>
                <i class="bi bi-tags-fill stat-icon" style="color: var(--primary);"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card border-l-success">
                <div class="stat-value text-success"><?= number_format($stats['total_items'] - $stats['total_blocked']) ?></div>
                <div class="stat-label">Total Good Inventory</div>
                <i class="bi bi-box-seam-fill stat-icon text-success"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card border-l-danger">
                <div class="stat-value text-danger"><?= number_format($stats['total_blocked']) ?></div>
                <div class="stat-label">Blocked / Damaged</div>
                <i class="bi bi-slash-circle-fill stat-icon text-danger"></i>
            </div>
        </div>
    </div>

    <div class="card-table mb-5">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="15%">Material Code</th>
                        <th>Product Description</th>
                        <th class="text-center">UoM</th>
                        <th class="text-end text-success"><i class="bi bi-check-circle me-1"></i> Avail (F1)</th>
                        <th class="text-end text-warning divider-col"><i class="bi bi-search me-1"></i> Qual (Q4)</th>
                        <th class="text-end text-primary" style="background:#e0e7ff;"><i class="bi bi-shield-check me-1"></i> Total Good</th>
                        <th class="text-end text-danger" style="background:#fee2e2;"><i class="bi bi-x-octagon me-1"></i> Total Bad</th>
                        <th class="text-end text-dark fs-6 bg-light"><i class="bi bi-layers me-1"></i> Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($stocks)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted fw-bold">No inventory records found.</td></tr>
                    <?php endif; ?>

                    <?php foreach($stocks as $idx => $row): 
                        $collapseId = "detail_" . $idx;
                        $hasStock = ($row['grand_total'] > 0);
                    ?>
                    <tr class="main-row" <?= $hasStock ? "onclick=\"toggleDetail('$collapseId', '{$row['product_uuid']}')\"" : "" ?> style="<?= !$hasStock ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                        <td class="text-center">
                            <?php if($hasStock): ?>
                                <i class="bi bi-chevron-right text-primary fs-5" id="icon_<?= $collapseId ?>" style="transition: 0.3s;"></i>
                            <?php else: ?>
                                <i class="bi bi-dash text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td><div class="fw-bold text-dark"><?= $row['product_code'] ?></div></td>
                        <td><div class="text-dark fw-medium"><?= $row['description'] ?></div></td>
                        <td class="text-center text-muted small fw-bold uppercase"><?= $row['base_uom'] ?></td>
                        
                        <td class="text-end fw-bold text-success"><?= number_format($row['stock_f1']) ?></td>
                        <td class="text-end fw-bold text-warning divider-col"><?= number_format($row['stock_q4']) ?></td>
                        
                        <td class="text-end fw-800 text-primary" style="background:#eef2ff; font-size: 1.1rem;"><?= number_format($row['total_good']) ?></td>
                        <td class="text-end fw-800 text-danger" style="background:#fef2f2; font-size: 1.1rem;"><?= number_format($row['total_bad']) ?></td>
                        <td class="text-end fw-800 text-dark bg-light" style="font-size: 1.1rem; border-left: 2px solid #e2e8f0;"><?= number_format($row['grand_total']) ?></td>
                    </tr>
                    
                    <?php if($hasStock): ?>
                    <tr class="detail-row" id="<?= $collapseId ?>">
                        <td colspan="9" class="p-0 border-0 bg-light">
                            <div class="px-5 py-3 border-bottom border-top border-primary border-opacity-10">
                                <div class="d-flex align-items-center mb-2 gap-2">
                                    <i class="bi bi-geo-alt-fill text-primary"></i>
                                    <h6 class="fw-bold text-dark m-0 uppercase" style="font-size: 0.8rem;">Storage Bin Breakdown</h6>
                                </div>
                                <div id="content_<?= $collapseId ?>" class="detail-card">
                                    <div class="text-center py-3 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2"></div> Scanning locations...</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function toggleDetail(rowId, prodUuid) {
    const row = document.getElementById(rowId);
    const icon = document.getElementById('icon_' + rowId);
    
    if (row.style.display === 'table-row') {
        row.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    } else {
        row.style.display = 'table-row';
        icon.style.transform = 'rotate(90deg)';
        
        const contentDiv = document.getElementById('content_' + rowId);
        if (contentDiv.innerHTML.includes('Scanning')) {
            const fd = new FormData();
            fd.append('action', 'get_stock_detail');
            fd.append('product_uuid', prodUuid);
            
            fetch('stock_master.php', { method: 'POST', body: fd })
                .then(r => r.text())
                .then(html => { contentDiv.innerHTML = html; })
                .catch(e => { contentDiv.innerHTML = '<span class="text-danger">Failed to load bin details.</span>'; });
        }
    }
}
</script>

</body>
</html>