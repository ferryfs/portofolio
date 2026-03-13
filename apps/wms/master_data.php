<?php
// apps/wms/master_data.php — ENTERPRISE MASTER DATA

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user = $_SESSION['wms_fullname'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
$search = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$msg = ""; $msg_type = "";

// CRUD PRODUCT
if(isset($_POST['save_product'])) {
    if (!verifyCSRFToken()) die('Security Error: Invalid Token');
    $uuid = $_POST['product_uuid'] ?: "PROD-" . strtoupper(bin2hex(random_bytes(4)));
    $code = sanitizeInput($_POST['product_code']);
    $desc = sanitizeInput($_POST['description']);
    $uom  = sanitizeInput($_POST['base_uom']);
    $cap_uom  = sanitizeInput($_POST['capacity_uom']);
    $conv_qty = sanitizeInt($_POST['conversion_qty']);
    if($conv_qty <= 0) $conv_qty = 1;
    try {
        $pdo->beginTransaction();
        if($_POST['is_edit'] == '1') {
            safeQuery($pdo, "UPDATE wms_products SET product_code=?, description=?, base_uom=?, capacity_uom=?, conversion_qty=? WHERE product_uuid=?", [$code, $desc, $uom, $cap_uom, $conv_qty, $uuid]);
            catat_log($pdo, $user, 'UPDATE', 'PRODUCT', "Updated Product: $code");
            $msg = "Product <b>$code</b> updated successfully."; $msg_type = "success";
        } else {
            $cek = safeGetOne($pdo, "SELECT 1 FROM wms_products WHERE product_code=?", [$code]);
            if($cek) throw new Exception("Product Code $code already exists!");
            safeQuery($pdo, "INSERT INTO wms_products (product_uuid, product_code, description, base_uom, capacity_uom, conversion_qty) VALUES (?, ?, ?, ?, ?, ?)", [$uuid, $code, $desc, $uom, $cap_uom, $conv_qty]);
            catat_log($pdo, $user, 'CREATE', 'PRODUCT', "Created Product: $code");
            $msg = "Product <b>$code</b> created successfully."; $msg_type = "success";
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack(); $msg = $e->getMessage(); $msg_type = "danger";
    }
}

if(isset($_GET['del_prod'])) {
    $id = sanitizeInput($_GET['del_prod']);
    try {
        $old = safeGetOne($pdo, "SELECT product_code FROM wms_products WHERE product_uuid=?", [$id]);
        if($old) { safeQuery($pdo, "DELETE FROM wms_products WHERE product_uuid=?", [$id]); catat_log($pdo, $user, 'DELETE', 'PRODUCT', "Deleted: {$old['product_code']}"); }
        header("Location: master_data.php?tab=products&msg=deleted"); exit;
    } catch(Exception $e) { header("Location: master_data.php?tab=products&err=used"); exit; }
}

// CRUD BIN
if(isset($_POST['save_bin'])) {
    if (!verifyCSRFToken()) die('Security Error: Invalid Token');
    $bin  = strtoupper(sanitizeInput($_POST['lgpla']));
    $type = sanitizeInput($_POST['lgtyp']);
    $max  = sanitizeInt($_POST['max_weight']);
    try {
        safeQuery($pdo, "INSERT INTO wms_storage_bins (lgpla, lgtyp, max_weight) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE max_weight=?, lgtyp=?", [$bin, $type, $max, $max, $type]);
        catat_log($pdo, $user, 'UPSERT', 'BIN', "Saved Bin: $bin");
        $msg = "Storage Bin <b>$bin</b> saved."; $msg_type = "success"; $active_tab = 'bins';
    } catch(Exception $e) { $msg = $e->getMessage(); $msg_type = "danger"; }
}

if(isset($_GET['del_bin'])) {
    $id = sanitizeInput($_GET['del_bin']);
    try {
        safeQuery($pdo, "DELETE FROM wms_storage_bins WHERE lgpla=?", [$id]);
        catat_log($pdo, $user, 'DELETE', 'BIN', "Deleted Bin: $id");
        header("Location: master_data.php?tab=bins&msg=bin_deleted"); exit;
    } catch(Exception $e) { header("Location: master_data.php?tab=bins&err=bin_used"); exit; }
}

// DATA
$limit = 50;
$products = safeGetAll($pdo, "SELECT * FROM wms_products WHERE product_code LIKE ? OR description LIKE ? ORDER BY product_code ASC LIMIT $limit", ["%$search%", "%$search%"]);
$bins = safeGetAll($pdo, "SELECT b.*, (SELECT COUNT(*) FROM wms_quants WHERE lgpla = b.lgpla) as stock_count FROM wms_storage_bins b WHERE b.lgpla LIKE ? ORDER BY b.lgpla ASC LIMIT $limit", ["%$search%"]);

// Stats
$total_products = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_products")['c'] ?? 0;
$total_bins     = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_storage_bins")['c'] ?? 0;
$occupied_bins  = safeGetOne($pdo, "SELECT COUNT(DISTINCT lgpla) as c FROM wms_quants")['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data | Smart WMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4f46e5; --primary-light: #e0e7ff;
            --dark: #0f172a; --bg: #f8fafc; --card: #ffffff;
            --border: #e2e8f0; --text: #1e293b; --muted: #64748b;
        }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); padding-bottom: 60px; }
        
        .navbar-wms { background: var(--dark); padding: 14px 0; border-bottom: 3px solid var(--primary); position: sticky; top: 0; z-index: 100; }

        .kpi-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 1.4rem 1.6rem; display: flex; align-items: center; gap: 1.2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); transition: 0.25s; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.08); }
        .kpi-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .kpi-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); }

        /* Tabs */
        .tab-nav { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 6px; display: inline-flex; gap: 4px; }
        .tab-btn { padding: 9px 22px; border-radius: 12px; font-weight: 600; font-size: 0.875rem; border: none; background: transparent; color: var(--muted); cursor: pointer; transition: 0.2s; }
        .tab-btn.active { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
        .tab-btn:hover:not(.active) { background: var(--bg); color: var(--text); }

        /* Search box */
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); }
        .search-wrap input { padding-left: 42px; border-radius: 12px; border: 1px solid var(--border); background: var(--card); height: 42px; transition: 0.2s; }
        .search-wrap input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }

        /* Table */
        .glass-table { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); }
        .table { margin: 0; color: var(--text); }
        .table thead th { background: #f8fafc; color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 20px; border-bottom: 1px solid var(--border); font-weight: 700; }
        .table tbody td { padding: 14px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .table tbody tr:hover { background: #f8fafc; }
        .table tbody tr:last-child td { border-bottom: none; }

        /* Packing badge */
        .pack-rule { background: #f1f5f9; border: 1px solid var(--border); border-radius: 8px; padding: 4px 10px; font-size: 0.8rem; font-family: monospace; color: var(--text); }
        
        /* Bin type badge */
        .bin-type { padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; }
        .bin-type-0010 { background: #e0e7ff; color: #4338ca; }
        .bin-type-9010 { background: #dcfce7; color: #16a34a; }
        .bin-type-9020 { background: #fef9c3; color: #a16207; }

        /* Action buttons */
        .btn-action { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: transparent; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; }
        .btn-action:hover.edit { background: #fef3c7; border-color: #fbbf24; color: #d97706; }
        .btn-action:hover.del { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
        .btn-action.edit { color: #f59e0b; }
        .btn-action.del { color: #ef4444; }

        /* Modal */
        .modal-content { border: none; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { background: var(--dark); color: #fff; border-radius: 24px 24px 0 0; padding: 24px 28px; border: none; }
        .modal-body { padding: 28px; }
        .modal-footer { padding: 20px 28px; background: #f8fafc; border-radius: 0 0 24px 24px; border-top: 1px solid var(--border); }
        .form-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 10px; border: 2px solid #f1f5f9; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: none; }

        /* Table header toolbar */
        .table-toolbar { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<nav class="navbar-wms shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="d-flex align-items-center gap-2 text-white text-decoration-none" href="index.php">
            <i class="bi bi-box-seam-fill text-primary fs-5"></i>
            <span class="fw-bold">WMS <span style="font-weight:300;">Enterprise</span></span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-house me-1"></i>Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-0">Master Data</h4>
            <p class="text-muted small mb-0">Manage Products, Packing Rules & Storage Locations</p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
            <i class="bi <?= $msg_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
            <div><?= $msg ?></div>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-tags-fill"></i></div>
                <div>
                    <div class="kpi-label">Total Products</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= number_format($total_products) ?></h2>
                    <div class="small text-muted">Registered SKUs</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div>
                    <div class="kpi-label">Total Bins</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= number_format($total_bins) ?></h2>
                    <div class="small text-muted">Storage Locations</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-box-fill"></i></div>
                <div>
                    <div class="kpi-label">Occupied Bins</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= number_format($occupied_bins) ?></h2>
                    <div class="small text-muted"><?= $total_bins > 0 ? round(($occupied_bins/$total_bins)*100) : 0 ?>% Utilization</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Nav + Search -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="tab-nav">
            <button class="tab-btn <?= $active_tab=='products'?'active':'' ?>" onclick="switchTab('products')">
                <i class="bi bi-tags me-2"></i>Products (<?= count($products) ?>)
            </button>
            <button class="tab-btn <?= $active_tab=='bins'?'active':'' ?>" onclick="switchTab('bins')">
                <i class="bi bi-grid-3x3-gap me-2"></i>Storage Bins (<?= count($bins) ?>)
            </button>
        </div>

        <form method="GET" id="searchForm" class="d-flex align-items-center gap-2">
            <input type="hidden" name="tab" id="searchTab" value="<?= $active_tab ?>">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="width:220px;">
            </div>
        </form>
    </div>

    <!-- Products Tab -->
    <div id="tab-products" class="tab-content-pane <?= $active_tab!='products'?'d-none':'' ?>">
        <div class="glass-table">
            <div class="table-toolbar">
                <h6 class="fw-bold m-0"><i class="bi bi-tags me-2 text-primary"></i>Product Registry</h6>
                <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="openModalProd()">
                    <i class="bi bi-plus-lg me-1"></i>Add Product
                </button>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Description</th>
                            <th class="text-center">Base UoM</th>
                            <th>Packing Rule</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No products found.</td></tr>
                        <?php endif; ?>
                        <?php foreach($products as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary font-monospace"><?= htmlspecialchars($r['product_code']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($r['description']) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light border text-dark fw-bold"><?= htmlspecialchars($r['base_uom']) ?></span>
                            </td>
                            <td>
                                <span class="pack-rule">1 <?= htmlspecialchars($r['capacity_uom']) ?> = <?= $r['conversion_qty'] ?> <?= htmlspecialchars($r['base_uom']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn-action edit me-1" onclick='editProd(<?= json_encode($r) ?>)' title="Edit"><i class="bi bi-pencil-square fs-6"></i></button>
                                <button class="btn-action del btn-del" data-href="?del_prod=<?= urlencode($r['product_uuid']) ?>&tab=products" title="Delete"><i class="bi bi-trash fs-6"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bins Tab -->
    <div id="tab-bins" class="tab-content-pane <?= $active_tab!='bins'?'d-none':'' ?>">
        <div class="glass-table">
            <div class="table-toolbar">
                <h6 class="fw-bold m-0"><i class="bi bi-grid-3x3-gap me-2 text-success"></i>Storage Bin Registry</h6>
                <button class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="openModalBin()">
                    <i class="bi bi-plus-lg me-1"></i>Add Bin
                </button>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Bin Location</th>
                            <th>Type</th>
                            <th class="text-center">Max Capacity</th>
                            <th class="text-center">Stock HUs</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($bins)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No bins found.</td></tr>
                        <?php endif; ?>
                        <?php foreach($bins as $r): 
                            $typeClass = 'bin-type-' . $r['lgtyp'];
                            $typeLabels = ['0010' => 'High Rack', '9010' => 'Inbound GR', '9020' => 'Outbound GI'];
                            $typeLabel = $typeLabels[$r['lgtyp']] ?? $r['lgtyp'];
                            $hasStock = ($r['stock_count'] > 0);
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold font-monospace text-dark"><?= htmlspecialchars($r['lgpla']) ?></div>
                            </td>
                            <td>
                                <span class="bin-type <?= $typeClass ?>"><?= $r['lgtyp'] ?> — <?= $typeLabel ?></span>
                            </td>
                            <td class="text-center fw-bold"><?= number_format($r['max_weight']) ?> <span class="text-muted small fw-normal">KG</span></td>
                            <td class="text-center">
                                <?php if($hasStock): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success"><?= $r['stock_count'] ?> HUs</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Empty</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn-action edit me-1" onclick='editBin(<?= json_encode($r) ?>)' title="Edit"><i class="bi bi-pencil-square fs-6"></i></button>
                                <?php if(!$hasStock): ?>
                                <button class="btn-action del btn-del" data-href="?del_bin=<?= urlencode($r['lgpla']) ?>&tab=bins" title="Delete"><i class="bi bi-trash fs-6"></i></button>
                                <?php else: ?>
                                <button class="btn-action del" title="Cannot delete — has stock" style="opacity:0.3;cursor:not-allowed;"><i class="bi bi-trash fs-6"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Product Modal -->
<div class="modal fade" id="modalProd" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="modal-header">
                    <div>
                        <h5 class="fw-bold m-0" id="prodTitle">Product Details</h5>
                        <div class="small opacity-60 mt-1">Fill in product master data & packing configuration</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="is_edit" id="is_edit_prod" value="0">
                    <input type="hidden" name="product_uuid" id="prod_uuid">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Product Code *</label>
                            <input type="text" name="product_code" id="prod_code" class="form-control" required placeholder="e.g. ITEM-001">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Description *</label>
                            <input type="text" name="description" id="prod_desc" class="form-control" required placeholder="Full product description">
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="small fw-bold text-muted mb-3 text-uppercase" style="letter-spacing:0.5px;">Packing Configuration</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Base Unit of Measure</label>
                                <select name="base_uom" id="prod_uom" class="form-select">
                                    <option value="PCS">PCS — Pieces</option>
                                    <option value="KG">KG — Kilogram</option>
                                    <option value="L">L — Liter</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pack/Pallet Type</label>
                                <select name="capacity_uom" id="prod_cap" class="form-select">
                                    <option value="PAL">PAL — Pallet</option>
                                    <option value="CTN">CTN — Carton</option>
                                    <option value="BOX">BOX — Box</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Qty per Pack</label>
                                <input type="number" name="conversion_qty" id="prod_conv" class="form-control" value="1" min="1" required>
                            </div>
                        </div>
                        <div class="mt-3 small text-muted bg-white p-2 rounded-2 border">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            Example: If <strong>1 Pallet</strong> holds <strong>100 PCS</strong>, set Pack Type = <strong>PAL</strong>, Qty = <strong>100</strong>.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_product" class="btn btn-primary rounded-pill px-5 fw-bold">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bin Modal -->
<div class="modal fade" id="modalBin" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="modal-header">
                    <div>
                        <h5 class="fw-bold m-0">Storage Bin Details</h5>
                        <div class="small opacity-60 mt-1">Configure a physical storage location</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bin Location Code *</label>
                        <input type="text" name="lgpla" id="bin_code" class="form-control text-uppercase fw-bold font-monospace" required placeholder="e.g. A-01-01">
                        <div class="form-text">Use consistent format like Zone-Row-Column (A-01-01)</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Bin Type</label>
                            <select name="lgtyp" id="bin_type" class="form-select">
                                <option value="0010">0010 — High Rack Storage</option>
                                <option value="9010">9010 — Inbound GR Zone</option>
                                <option value="9020">9020 — Outbound GI Zone</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Weight (KG)</label>
                            <input type="number" name="max_weight" id="bin_weight" class="form-control" value="1000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_bin" class="btn btn-success rounded-pill px-5 fw-bold">Save Bin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalProd = new bootstrap.Modal(document.getElementById('modalProd'));
    const modalBin  = new bootstrap.Modal(document.getElementById('modalBin'));

    function switchTab(tab) {
        document.querySelectorAll('.tab-content-pane').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.remove('d-none');
        event.currentTarget.classList.add('active');
        document.getElementById('searchTab').value = tab;
    }

    function openModalProd() {
        document.getElementById('prodTitle').innerText = 'Add New Product';
        document.getElementById('is_edit_prod').value = '0';
        document.getElementById('prod_uuid').value = '';
        document.getElementById('prod_code').value = '';
        document.getElementById('prod_code').readOnly = false;
        document.getElementById('prod_desc').value = '';
        document.getElementById('prod_uom').value = 'PCS';
        document.getElementById('prod_cap').value = 'PAL';
        document.getElementById('prod_conv').value = '1';
        modalProd.show();
    }

    function editProd(data) {
        document.getElementById('prodTitle').innerText = 'Edit Product';
        document.getElementById('is_edit_prod').value = '1';
        document.getElementById('prod_uuid').value = data.product_uuid;
        document.getElementById('prod_code').value = data.product_code;
        document.getElementById('prod_code').readOnly = true;
        document.getElementById('prod_desc').value = data.description;
        document.getElementById('prod_uom').value = data.base_uom;
        document.getElementById('prod_cap').value = data.capacity_uom || 'PAL';
        document.getElementById('prod_conv').value = data.conversion_qty || 1;
        modalProd.show();
    }

    function openModalBin() {
        document.getElementById('bin_code').value = '';
        document.getElementById('bin_code').readOnly = false;
        document.getElementById('bin_weight').value = '1000';
        modalBin.show();
    }

    function editBin(data) {
        document.getElementById('bin_code').value = data.lgpla;
        document.getElementById('bin_code').readOnly = true;
        document.getElementById('bin_type').value = data.lgtyp;
        document.getElementById('bin_weight').value = data.max_weight;
        modalBin.show();
    }

    // Delete confirmation
    document.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', e => {
            const url = btn.dataset.href;
            Swal.fire({
                title: 'Confirm Delete',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(result => { if(result.isConfirmed) window.location.href = url; });
        });
    });

    // Auto-submit search on enter
    document.querySelector('input[name="q"]').addEventListener('keydown', function(e) {
        if(e.key === 'Enter') { e.preventDefault(); document.getElementById('searchForm').submit(); }
    });
</script>
</body>
</html>
