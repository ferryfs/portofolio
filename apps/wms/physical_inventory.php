<?php
// apps/wms/physical_inventory.php
// V13: FULL ENTERPRISE SUITE (Smart UI, Filters, Pagination, History Parsing, CSV Export)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$msg = ""; $alert = "";
$user = $_SESSION['wms_fullname'];

// =========================================================================
// 📥 EXPORT TO CSV LOGIC (Dengan Kolom Bin Terpisah)
// =========================================================================
if(isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Opname_Report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['Log Date', 'User Executed', 'Action', 'Bin Location', 'Pallet (HU)', 'Details & Reason']);
    
    $logs = safeGetAll($pdo, "SELECT log_date, user_id, action_type, description FROM wms_system_logs WHERE module = 'OPNAME' ORDER BY log_date DESC");
    
    foreach($logs as $l) {
        // Ekstrak HU dan Bin dari teks description pakai Regex (Standard IT Audit)
        preg_match('/HU ([A-Za-z0-9\-]+)/', $l['description'], $matches_hu);
        $hu_id = $matches_hu[1] ?? '-';
        
        preg_match('/in Bin ([A-Za-z0-9\-]+)/', $l['description'], $matches_bin);
        $bin_id = $matches_bin[1] ?? '-';

        fputcsv($output, [$l['log_date'], $l['user_id'], $l['action_type'], $bin_id, $hu_id, $l['description']]);
    }
    exit; 
}

// =========================================================================
// 🧠 PROSES POSTING (ADJUSTMENT LOGIC)
// =========================================================================
if(isset($_POST['post_count'])) {
    $qid = sanitizeInt($_POST['quant_id']);
    $adj_type = $_POST['adj_type']; 
    $qty_sys  = (float)$_POST['qty_system'];
    $prod_uuid= $_POST['product_uuid'];
    $bin      = $_POST['bin'];
    $hu       = $_POST['hu_id'];
    $sku      = $_POST['sku_code'];

    try {
        $pdo->beginTransaction();

        $stok = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=? FOR UPDATE", [$qid]);
        if(!$stok) throw new Exception("Stock record not found or already moved.");

        if($adj_type == 'MATCH') {
            $sys_desc = "PI VERIFIED: HU $hu ($sku) in Bin $bin is Match. Qty: $qty_sys.";
            safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) VALUES (?, 'OPNAME', 'VERIFY_MATCH', ?, ?, NOW())", [$user, $sys_desc, $_SERVER['REMOTE_ADDR']]);
            $msg = "✅ Stock verified! <b>$hu</b> in <b>$bin</b> is mathematically correct."; $alert = "success";
        } 
        else {
            $qty_phys = (float)$_POST['qty_physical'];
            $reason   = sanitizeInput($_POST['reason']);
            if(empty($reason)) throw new Exception("Enterprise Policy: Reason code is MANDATORY!");

            $diff = $qty_phys - $qty_sys;
            
            if($adj_type == 'LOSS' && $diff >= 0) throw new Exception("For LOSS adjustment, physical qty must be LESS than system qty.");
            if($adj_type == 'GAIN' && $diff <= 0) throw new Exception("For GAIN adjustment, physical qty must be MORE than system qty.");
            if($qty_phys < 0) throw new Exception("Physical quantity cannot be negative!");

            if($qty_phys == 0) {
                safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$qid]);
                $cek_sisa = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_quants WHERE lgpla=?", [$bin]);
                if($cek_sisa['c'] == 0) safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='EMPTY' WHERE lgpla=?", [$bin]);
            } else {
                safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$qty_phys, $qid]);
                safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='OCCUPIED' WHERE lgpla=?", [$bin]);
            }

            $move_type = ($diff > 0) ? 'PI_GAIN' : 'PI_LOSS';
            $trx_ref = "PI-" . date('ymdHis');
            safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, reason_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [$trx_ref, $prod_uuid, $hu, $diff, $move_type, $user, $bin, $reason]);

            $action_label = ($diff > 0) ? 'ADJUST_GAIN' : 'ADJUST_LOSS';
            $sys_desc = "PI ADJUSTMENT: HU $hu ($sku) in Bin $bin. System: $qty_sys | Actual Input: $qty_phys | Diff: $diff | Reason: $reason";
            safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) VALUES (?, 'OPNAME', ?, ?, ?, NOW())", [$user, $action_label, $sys_desc, $_SERVER['REMOTE_ADDR']]);

            $msg = "✅ Adjusment Saved! <b>$hu</b> in <b>$bin</b> updated to <b>$qty_phys</b>. (Variance: $diff)"; $alert = "warning";
        }

        $pdo->commit();
    } catch(Exception $e) {
        $pdo->rollBack();
        $msg = "⛔ Error: " . $e->getMessage(); $alert = "danger";
    }
}

// =========================================================================
// 🔍 PENGATURAN VIEW, FILTER & PAGINATION
// =========================================================================
$view = $_GET['view'] ?? 'count'; 

// Variabel Filter
$f_bin = sanitizeInput($_GET['f_bin'] ?? '');
$f_sku = sanitizeInput($_GET['f_sku'] ?? '');
$f_hu  = sanitizeInput($_GET['f_hu'] ?? '');

if($view == 'count') {
    $where_clause = "1=1";
    $params = [];
    if($f_bin) { $where_clause .= " AND q.lgpla LIKE ?"; $params[] = "%$f_bin%"; }
    if($f_sku) { $where_clause .= " AND p.product_code LIKE ?"; $params[] = "%$f_sku%"; }
    if($f_hu)  { $where_clause .= " AND q.hu_id LIKE ?"; $params[] = "%$f_hu%"; }

    // Pagination
    $limit = 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $total_rows = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE $where_clause", $params)['c'];
    $total_pages = ceil($total_rows / $limit);

    $sql = "SELECT q.*, p.product_code, p.description, p.base_uom 
            FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid 
            WHERE $where_clause ORDER BY q.lgpla ASC, p.product_code ASC LIMIT $limit OFFSET $offset";
    $stocks = safeGetAll($pdo, $sql, $params);

} else {
    // Tarik list history
    $history = safeGetAll($pdo, "SELECT log_date, user_id, action_type, description FROM wms_system_logs WHERE module = 'OPNAME' ORDER BY log_date DESC LIMIT 100");
}

function buildUrl($newPage) {
    global $f_bin, $f_sku, $f_hu, $view;
    return "?view=$view&page=$newPage" . ($f_bin ? "&f_bin=$f_bin" : "") . ($f_sku ? "&f_sku=$f_sku" : "") . ($f_hu ? "&f_hu=$f_hu" : "");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Opname | WMS Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #f8fafc; --primary: #4f46e5; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; padding-bottom: 50px; }
        .navbar-custom { background: #0f172a; padding: 15px 0; border-bottom: 3px solid var(--primary); }
        .card-pi { border: none; border-radius: 0 0 16px 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); background: white; }
        .table thead th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 16px 20px; }
        .table tbody td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        .nav-tabs .nav-link { font-weight: bold; color: #64748b; border: none; padding: 15px 25px; transition: 0.2s;}
        .nav-tabs .nav-link:hover { color: var(--primary); }
        .nav-tabs .nav-link.active { color: var(--primary); border-bottom: 3px solid var(--primary); background: transparent; }
        
        /* Form Radio Pintar */
        .adj-radio:checked + label { background-color: #64748b; color: white; border-color: #64748b; }
        .adj-radio-loss:checked + label { background-color: #ef4444; color: white; border-color: #ef4444; }
        .adj-radio-gain:checked + label { background-color: #10b981; color: white; border-color: #10b981; }
        
        .smart-input-box { display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; }
        .qty-input { border: 2px solid #e2e8f0; border-radius: 10px; font-weight: bold; padding: 10px; font-size: 1.1rem; text-align: center; }
        .qty-input:focus { border-color: var(--primary); box-shadow: none; }
        .reason-select { border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px; font-weight: 500; }

        /* Filter Panel Dropdown */
        .filter-panel { background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: none; }
        .filter-panel.active { display: block; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-5">
    <div class="container-fluid px-5" style="max-width: 1500px;">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <i class="bi bi-clipboard2-check text-primary fs-4"></i> 
            <span>Stock <span style="font-weight: 300;">Opname</span></span>
        </a>
        <a href="stock_master.php" class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold"><i class="bi bi-arrow-left me-2"></i>Inventory Master</a>
    </div>
</nav>

<div class="container-fluid px-5" style="max-width: 1500px;">
    
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark">Enterprise Cycle Count</h3>
            <p class="text-muted m-0">Validate physical inventory & download audit reports.</p>
        </div>
        <div>
            <?php if($view == 'count'): ?>
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="document.getElementById('filterPanel').classList.toggle('active')">
                    <i class="bi bi-funnel-fill me-2"></i>Toggle Filters
                </button>
            <?php endif; ?>
            
            <?php if($view == 'history'): ?>
                <a href="?export=csv" class="btn btn-success rounded-pill fw-bold shadow-sm px-4">
                    <i class="bi bi-file-earmark-excel-fill me-2"></i>Export to CSV
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $alert ?> shadow-sm border-0 d-flex align-items-center rounded-4 fw-bold mb-4">
            <i class="bi bi-info-circle-fill me-3 fs-4"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs border-bottom-0">
        <li class="nav-item">
            <a class="nav-link <?= $view=='count'?'active':'' ?>" href="?view=count"><i class="bi bi-upc-scan me-2"></i>Active Counting</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view=='history'?'active':'' ?>" href="?view=history"><i class="bi bi-clock-history me-2"></i>Adjustment History</a>
        </li>
    </ul>

    <div class="bg-white border border-top-0 rounded-bottom-4 shadow-sm mb-5 p-1">
        
        <?php if($view == 'count'): ?>
        <div class="filter-panel m-3" id="filterPanel">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="count">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Bin Location</label>
                    <input type="text" name="f_bin" class="form-control form-control-sm border-2" value="<?= htmlspecialchars($f_bin) ?>" placeholder="e.g. A-01">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Material SKU</label>
                    <input type="text" name="f_sku" class="form-control form-control-sm border-2" value="<?= htmlspecialchars($f_sku) ?>" placeholder="e.g. PROD-">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Pallet (HU) ID</label>
                    <input type="text" name="f_hu" class="form-control form-control-sm border-2" value="<?= htmlspecialchars($f_hu) ?>" placeholder="Search Handling Unit...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold btn-sm py-2"><i class="bi bi-search me-1"></i> Apply Filter</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th width="15%">Storage Bin</th>
                        <th width="25%">Material Info</th>
                        <th class="text-center" width="10%">Sys Qty</th>
                        <th width="40%">Adjustment Decision</th>
                        <th width="10%" class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($stocks)): ?><tr><td colspan="5" class="text-center py-5"><i class="bi bi-inbox fs-1 text-muted"></i><br>No records found.</td></tr><?php endif; ?>

                    <?php foreach($stocks as $idx => $row): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="quant_id" value="<?= $row['quant_id'] ?>">
                            <input type="hidden" name="qty_system" id="sys_<?= $idx ?>" value="<?= (float)$row['qty'] ?>">
                            <input type="hidden" name="product_uuid" value="<?= $row['product_uuid'] ?>">
                            <input type="hidden" name="sku_code" value="<?= $row['product_code'] ?>">
                            <input type="hidden" name="bin" value="<?= $row['lgpla'] ?>">
                            <input type="hidden" name="hu_id" value="<?= $row['hu_id'] ?>">

                            <td class="ps-4"><span class="fw-bold font-monospace bg-light border p-2 rounded text-primary"><?= $row['lgpla'] ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= $row['product_code'] ?></div>
                                <div class="small text-muted font-monospace mb-1">HU: <?= $row['hu_id'] ?></div>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold fs-4 text-secondary"><?= (float)$row['qty'] ?></div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <input type="radio" name="adj_type" id="m_<?= $idx ?>" value="MATCH" class="btn-check adj-radio" onchange="toggleBox(<?= $idx ?>, 'MATCH')" required>
                                    <label class="btn btn-outline-secondary btn-sm fw-bold px-3" for="m_<?= $idx ?>"><i class="bi bi-check-circle"></i> Match</label>

                                    <input type="radio" name="adj_type" id="l_<?= $idx ?>" value="LOSS" class="btn-check adj-radio-loss" onchange="toggleBox(<?= $idx ?>, 'LOSS')">
                                    <label class="btn btn-outline-danger btn-sm fw-bold px-3" for="l_<?= $idx ?>"><i class="bi bi-arrow-down-right"></i> Loss (-)</label>

                                    <input type="radio" name="adj_type" id="g_<?= $idx ?>" value="GAIN" class="btn-check adj-radio-gain" onchange="toggleBox(<?= $idx ?>, 'GAIN')">
                                    <label class="btn btn-outline-success btn-sm fw-bold px-3" for="g_<?= $idx ?>"><i class="bi bi-arrow-up-right"></i> Gain (+)</label>
                                </div>

                                <div id="box_<?= $idx ?>" class="smart-input-box">
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <input type="number" name="qty_physical" id="phys_<?= $idx ?>" class="form-control qty-input" placeholder="Actual Qty" step="0.01" oninput="calcMath(<?= $idx ?>)">
                                            <div id="hint_<?= $idx ?>" class="small fw-bold text-center mt-1"></div>
                                        </div>
                                        <div class="col-8">
                                            <select name="reason" id="rsn_<?= $idx ?>" class="form-select reason-select">
                                                </select>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <button type="submit" name="post_count" class="btn btn-dark fw-bold shadow-sm px-4 py-2 rounded-pill">POST</button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if(isset($total_pages) && $total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center p-4 border-top bg-light">
            <div class="small text-muted fw-bold">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_rows) ?> of <?= $total_rows ?> records
            </div>
            <ul class="pagination pagination-sm m-0 shadow-sm">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link px-3 fw-bold text-dark" href="<?= buildUrl($page - 1) ?>">Previous</a>
                </li>
                <li class="page-item disabled"><span class="page-link px-3 bg-white text-muted">Page <?= $page ?> of <?= $total_pages ?></span></li>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link px-3 fw-bold text-dark" href="<?= buildUrl($page + 1) ?>">Next</a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="table-responsive p-3">
            <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th width="15%">Date & Time</th>
                        <th width="15%">Executed By</th>
                        <th width="12%">Bin Location</th>
                        <th width="15%">Pallet (HU)</th>
                        <th width="15%">Action</th>
                        <th>Details & Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($history)): ?><tr><td colspan="6" class="text-center py-5">No adjustment history found.</td></tr><?php endif; ?>
                    <?php foreach($history as $h): 
                        // Pecah teks deskripsi untuk dapet Bin dan HU biar rapi
                        preg_match('/HU ([A-Za-z0-9\-]+)/', $h['description'], $matches_hu);
                        $hu_id = $matches_hu[1] ?? '-';
                        
                        preg_match('/in Bin ([A-Za-z0-9\-]+)/', $h['description'], $matches_bin);
                        $bin_id = $matches_bin[1] ?? '-';
                    ?>
                    <tr>
                        <td class="text-muted"><i class="bi bi-clock me-1"></i> <?= date('d M Y H:i', strtotime($h['log_date'])) ?></td>
                        <td class="fw-bold text-dark"><?= $h['user_id'] ?></td>
                        <td><span class="font-monospace fw-bold text-primary"><?= $bin_id ?></span></td>
                        <td><span class="font-monospace text-muted"><?= $hu_id ?></span></td>
                        <td>
                            <?php 
                                $badge = 'bg-secondary';
                                if($h['action_type'] == 'ADJUST_LOSS') $badge = 'bg-danger';
                                elseif($h['action_type'] == 'ADJUST_GAIN') $badge = 'bg-success';
                                elseif($h['action_type'] == 'VERIFY_MATCH') $badge = 'bg-info text-dark';
                            ?>
                            <span class="badge <?= $badge ?> fw-bold"><?= $h['action_type'] ?></span>
                        </td>
                        <td class="text-muted"><?= $h['description'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
// Biarin Filter tetap kebuka kalau ada parameter pencarian
<?php if($f_bin || $f_sku || $f_hu): ?>
    document.getElementById('filterPanel').classList.add('active');
<?php endif; ?>

function toggleBox(idx, type) {
    let box = document.getElementById('box_' + idx);
    let physInput = document.getElementById('phys_' + idx);
    let rsnDrop = document.getElementById('rsn_' + idx);
    let hint = document.getElementById('hint_' + idx);
    
    if (type === 'MATCH') {
        box.style.display = 'none';
        physInput.required = false;
        rsnDrop.required = false;
        physInput.value = '';
    } else {
        box.style.display = 'block';
        physInput.required = true;
        rsnDrop.required = true;
        physInput.value = '';
        hint.innerHTML = '';
        
        rsnDrop.innerHTML = '<option value="">-- Choose Reason --</option>';
        if (type === 'LOSS') {
            rsnDrop.style.borderColor = '#fca5a5'; rsnDrop.style.backgroundColor = '#fef2f2';
            rsnDrop.innerHTML += '<option value="MISSING_LOST">Lost in Warehouse</option>';
            rsnDrop.innerHTML += '<option value="DAMAGED_SCRAP">Damaged / Scrapped</option>';
            rsnDrop.innerHTML += '<option value="SHRINKAGE">Theft / Shrinkage</option>';
        } else if (type === 'GAIN') {
            rsnDrop.style.borderColor = '#6ee7b7'; rsnDrop.style.backgroundColor = '#ecfdf5';
            rsnDrop.innerHTML += '<option value="FOUND_HIDDEN">Found Missing Item</option>';
            rsnDrop.innerHTML += '<option value="VENDOR_OVERAGE">Vendor Sent Extra</option>';
            rsnDrop.innerHTML += '<option value="ADMIN_TYPO">Correction: Prev Admin Typo</option>';
        }
    }
}

function calcMath(idx) {
    let sysQty = parseFloat(document.getElementById('sys_' + idx).value);
    let physQty = parseFloat(document.getElementById('phys_' + idx).value);
    let hint = document.getElementById('hint_' + idx);
    
    let isLoss = document.getElementById('l_' + idx).checked;
    let isGain = document.getElementById('g_' + idx).checked;

    if (isNaN(physQty)) { hint.innerHTML = ''; return; }

    let diff = physQty - sysQty;

    if (isLoss && diff >= 0) {
        hint.innerHTML = '<span class="text-danger">Must be LESS than ' + sysQty + '</span>';
    } else if (isGain && diff <= 0) {
        hint.innerHTML = '<span class="text-danger">Must be MORE than ' + sysQty + '</span>';
    } else {
        let sign = diff > 0 ? '+' : '';
        let color = isLoss ? 'text-danger' : 'text-success';
        hint.innerHTML = `<span class="${color}">Selisih: ${sign}${diff}</span>`;
    }
}
</script>

</body>
</html>