<?php
// apps/wms/stock_master.php
// V10: INVENTORY DASHBOARD (With Drill-Down Details)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// 1. STATISTIK GLOBAL (Real-time)
$stats = safeGetOne($pdo, "
    SELECT 
        COUNT(DISTINCT product_uuid) as total_sku,
        COALESCE(SUM(qty), 0) as total_items,
        COALESCE(SUM(CASE WHEN stock_type='B6' THEN qty ELSE 0 END), 0) as total_blocked
    FROM wms_quants
");

// 2. STOCK SUMMARY (Group by Product)
$stocks = safeGetAll($pdo, "
    SELECT 
        p.product_uuid, p.product_code, p.description, p.base_uom,
        COALESCE(SUM(CASE WHEN q.stock_type = 'F1' THEN q.qty ELSE 0 END), 0) as stock_f1,
        COALESCE(SUM(CASE WHEN q.stock_type = 'Q4' THEN q.qty ELSE 0 END), 0) as stock_q4,
        COALESCE(SUM(CASE WHEN q.stock_type = 'B6' THEN q.qty ELSE 0 END), 0) as stock_b6,
        COALESCE(SUM(q.qty), 0) as total_stock
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --text: #0f172a; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text); padding-bottom: 3rem; }
        
        /* Stats Card Modern */
        .stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.5rem; position: relative; overflow: hidden; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .stat-icon { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1; transform: rotate(-15deg); }
        .stat-value { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; line-height: 1; }
        .stat-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        
        .border-l-primary { border-left: 5px solid #3b82f6; }
        .border-l-success { border-left: 5px solid #10b981; }
        .border-l-danger  { border-left: 5px solid #ef4444; }

        /* Modern Table */
        .card-table { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .table thead th { background: #f1f5f9; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; }
        .table tbody td { padding: 1.25rem 1.5rem; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
        .table tbody tr.main-row:hover { background-color: #f8fafc; cursor: pointer; }
        
        /* Detail Row Animation */
        .detail-row { background-color: #f8fafc; display: none; }
        .detail-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin: 0.5rem 0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

<div class="container-fluid px-5 py-4" style="max-width: 1600px;">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold m-0 text-dark">Inventory Master</h2>
            <p class="text-muted m-0">Real-time stock levels & location tracking</p>
        </div>
        <div class="d-flex gap-2">
            <a href="internal.php" class="btn btn-white border fw-bold shadow-sm"><i class="bi bi-arrow-left-right me-2"></i>Internal Transfer</a>
            <a href="physical_inventory.php" class="btn btn-dark fw-bold shadow-sm"><i class="bi bi-clipboard-check me-2"></i>Stock Opname</a>
            <a href="index.php" class="btn btn-outline-secondary fw-bold ms-2">Exit</a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card border-l-primary">
                <div class="stat-value text-primary"><?= number_format($stats['total_sku']) ?></div>
                <div class="stat-label">Active Products (SKU)</div>
                <i class="bi bi-tags-fill stat-icon text-primary"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card border-l-success">
                <div class="stat-value text-success"><?= number_format($stats['total_items']) ?></div>
                <div class="stat-label">Total Inventory (Qty)</div>
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

    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="15%">Product Code</th>
                        <th>Description</th>
                        <th class="text-center">UoM</th>
                        <th class="text-end text-success">Unrestricted (F1)</th>
                        <th class="text-end text-warning">Quality (Q4)</th>
                        <th class="text-end text-danger">Blocked (B6)</th>
                        <th class="text-end fw-bold">Total Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($stocks)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted fw-bold">No inventory records found.</td></tr>
                    <?php endif; ?>

                    <?php foreach($stocks as $idx => $row): 
                        $collapseId = "detail_" . $idx;
                    ?>
                    <tr class="main-row" onclick="toggleDetail('<?= $collapseId ?>', '<?= $row['product_uuid'] ?>')">
                        <td class="text-center"><i class="bi bi-chevron-right text-muted" id="icon_<?= $collapseId ?>"></i></td>
                        <td class="fw-bold text-dark"><?= $row['product_code'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td class="text-center text-muted small fw-bold"><?= $row['base_uom'] ?></td>
                        <td class="text-end fw-bold text-success"><?= number_format($row['stock_f1']) ?></td>
                        <td class="text-end fw-bold text-warning"><?= number_format($row['stock_q4']) ?></td>
                        <td class="text-end fw-bold text-danger"><?= number_format($row['stock_b6']) ?></td>
                        <td class="text-end fw-bold fs-6"><?= number_format($row['total_stock']) ?></td>
                    </tr>
                    
                    <tr class="detail-row" id="<?= $collapseId ?>">
                        <td colspan="8" class="p-0">
                            <div class="p-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-geo-alt-fill me-2"></i> Bin Location Breakdown</h6>
                                <div id="content_<?= $collapseId ?>" class="detail-card">
                                    <div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div> Loading details...</div>
                                </div>
                            </div>
                        </td>
                    </tr>
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
    
    // Toggle Show/Hide
    if (row.style.display === 'table-row') {
        row.style.display = 'none';
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-right');
    } else {
        row.style.display = 'table-row';
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-down');
        
        // Fetch Data if empty
        const contentDiv = document.getElementById('content_' + rowId);
        if (contentDiv.innerHTML.includes('Loading')) {
            fetchDetail(prodUuid, contentDiv);
        }
    }
}

function fetchDetail(uuid, targetElement) {
    const fd = new FormData();
    fd.append('action', 'get_stock_detail');
    fd.append('product_uuid', uuid);
    
    // Kita buat endpoint kecil di file ini juga buat handle AJAX
    fetch('stock_master.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(html => { targetElement.innerHTML = html; })
        .catch(e => { targetElement.innerHTML = '<span class="text-danger">Failed to load details</span>'; });
}
</script>

</body>
</html>

<?php
// --- AJAX HANDLER (Taruh paling bawah atau di file terpisah) ---
if(isset($_POST['action']) && $_POST['action'] == 'get_stock_detail') {
    // Bersihkan buffer biar cuma output HTML tabel
    ob_clean(); 
    
    $uuid = sanitizeInput($_POST['product_uuid']);
    
    // Query Detail per Bin & Batch
    $details = safeGetAll($pdo, "
        SELECT lgpla, batch, hu_id, qty, stock_type, gr_date 
        FROM wms_quants 
        WHERE product_uuid = ? 
        ORDER BY lgpla ASC, gr_date ASC
    ", [$uuid]);
    
    if(empty($details)) {
        echo '<div class="text-muted text-center py-2">No active stock in bins.</div>';
        exit;
    }
    
    echo '<table class="table table-sm table-bordered mb-0">';
    echo '<thead class="table-light"><tr>
            <th>Bin Location</th><th>Batch</th><th>HU ID</th><th>Type</th><th class="text-end">Qty</th><th>Age (Days)</th>
          </tr></thead><tbody>';
          
    foreach($details as $d) {
        // Hitung umur stok (Aging)
        $age = floor((time() - strtotime($d['gr_date'])) / (60 * 60 * 24));
        $color = $d['stock_type']=='F1' ? 'text-success' : ($d['stock_type']=='B6'?'text-danger':'text-warning');
        
        echo "<tr>
                <td class='fw-bold font-monospace'>{$d['lgpla']}</td>
                <td>{$d['batch']}</td>
                <td class='font-monospace small'>{$d['hu_id']}</td>
                <td class='fw-bold $color'>{$d['stock_type']}</td>
                <td class='text-end fw-bold'>".number_format((float)$d['qty'])."</td>
                <td class='text-muted small'>{$age} days</td>
              </tr>";
    }
    
    echo '</tbody></table>';
    exit; // Stop execution after ajax response
}
?>