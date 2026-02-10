<?php
// apps/wms/master_data.php
// V9: MASTER DATA MANAGEMENT (Premium UI + Search + Audit)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user = $_SESSION['wms_fullname'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
$search = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

// ---------------------------------------------------------
// 🧠 LOGIC: CRUD PRODUCT
// ---------------------------------------------------------
if(isset($_POST['save_product'])) {
    if (!verifyCSRFToken()) die('Security Error: Invalid Token');

    $uuid = $_POST['product_uuid'] ?: "PROD-" . strtoupper(bin2hex(random_bytes(4)));
    $code = sanitizeInput($_POST['product_code']);
    $desc = sanitizeInput($_POST['description']);
    $uom  = sanitizeInput($_POST['base_uom']);
    
    try {
        $pdo->beginTransaction();
        
        if($_POST['is_edit'] == '1') {
            // UPDATE
            $sql = "UPDATE wms_products SET product_code=?, description=?, base_uom=? WHERE product_uuid=?";
            safeQuery($pdo, $sql, [$code, $desc, $uom, $uuid]);
            catat_log($pdo, $user, 'UPDATE', 'PRODUCT', "Updated Product: $code");
            $msg = "✅ Product <b>$code</b> updated successfully.";
        } else {
            // CREATE
            // Cek duplikat kode dulu
            $cek = safeGetOne($pdo, "SELECT 1 FROM wms_products WHERE product_code=?", [$code]);
            if($cek) throw new Exception("Product Code $code already exists!");

            $sql = "INSERT INTO wms_products (product_uuid, product_code, description, base_uom, capacity_uom, conversion_qty) VALUES (?, ?, ?, ?, 'PAL', 50)";
            safeQuery($pdo, $sql, [$uuid, $code, $desc, $uom]);
            catat_log($pdo, $user, 'CREATE', 'PRODUCT', "Created Product: $code");
            $msg = "✅ Product <b>$code</b> created successfully.";
        }
        $pdo->commit();
        $msg_type = "success";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

if(isset($_GET['del_prod'])) {
    $id = sanitizeInput($_GET['del_prod']);
    try {
        $old = safeGetOne($pdo, "SELECT product_code FROM wms_products WHERE product_uuid=?", [$id]);
        if($old) {
            safeQuery($pdo, "DELETE FROM wms_products WHERE product_uuid=?", [$id]);
            catat_log($pdo, $user, 'DELETE', 'PRODUCT', "Deleted Product: {$old['product_code']}");
            header("Location: master_data.php?tab=products&msg=deleted"); exit;
        }
    } catch(Exception $e) {
        // Biasanya error foreign key constraint (produk udah dipake transaksi)
        header("Location: master_data.php?tab=products&err=used"); exit;
    }
}

// ---------------------------------------------------------
// 🧠 LOGIC: CRUD BIN
// ---------------------------------------------------------
if(isset($_POST['save_bin'])) {
    if (!verifyCSRFToken()) die('Security Error: Invalid Token');

    $bin  = strtoupper(sanitizeInput($_POST['lgpla']));
    $type = sanitizeInput($_POST['lgtyp']);
    $max  = sanitizeInt($_POST['max_weight']);
    
    try {
        $sql = "INSERT INTO wms_storage_bins (lgpla, lgtyp, max_weight) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE max_weight=?, lgtyp=?";
        safeQuery($pdo, $sql, [$bin, $type, $max, $max, $type]);
        
        catat_log($pdo, $user, 'UPSERT', 'BIN', "Saved Bin: $bin");
        $msg = "✅ Storage Bin <b>$bin</b> saved.";
        $msg_type = "success";
        $active_tab = 'bins';
    } catch(Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

if(isset($_GET['del_bin'])) {
    $id = sanitizeInput($_GET['del_bin']);
    try {
        safeQuery($pdo, "DELETE FROM wms_storage_bins WHERE lgpla=?", [$id]);
        catat_log($pdo, $user, 'DELETE', 'BIN', "Deleted Bin: $id");
        header("Location: master_data.php?tab=bins&msg=bin_deleted"); exit;
    } catch(Exception $e) {
        header("Location: master_data.php?tab=bins&err=bin_used"); exit;
    }
}

// ---------------------------------------------------------
// 🔍 DATA FETCHING (WITH SEARCH)
// ---------------------------------------------------------
$limit = 50; // Pagination limit (Simple limit for now)

// Products
$sql_prod = "SELECT * FROM wms_products WHERE product_code LIKE ? OR description LIKE ? ORDER BY product_code ASC LIMIT $limit";
$products = safeGetAll($pdo, $sql_prod, ["%$search%", "%$search%"]);

// Bins
$sql_bin = "SELECT * FROM wms_storage_bins WHERE lgpla LIKE ? ORDER BY lgpla ASC LIMIT $limit";
$bins = safeGetAll($pdo, $sql_bin, ["%$search%"]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Data Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, sans-serif; padding-bottom: 50px; }
        
        /* Header & Nav */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .nav-tabs .nav-link { color: #64748b; font-weight: 600; padding: 12px 20px; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link:hover { color: #1e293b; background: transparent; }
        .nav-tabs .nav-link.active { color: #2563eb; border-bottom-color: #2563eb; background: transparent; }
        
        /* Card & Table */
        .card-table { background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
        .table thead th { background: #f1f5f9; color: #475569; font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; }
        .table tbody td { padding: 15px 20px; vertical-align: middle; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .table-hover tbody tr:hover { background-color: #f8fafc; }
        
        /* Action Buttons */
        .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid transparent; transition: 0.2s; }
        .btn-icon:hover { background: #e2e8f0; }
        .text-danger:hover { background: #fef2f2; border-color: #fecaca; }
        .text-warning:hover { background: #fffbeb; border-color: #fde68a; }
        
        .search-box { position: relative; max-width: 300px; }
        .search-box input { padding-left: 40px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 1400px;">
    
    <div class="page-header">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-database-gear text-primary me-2"></i>Master Data</h3>
            <p class="text-muted m-0 mt-1">Manage Products & Storage Locations</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary fw-bold px-4">Back to Dashboard</a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['err']) && $_GET['err'] == 'used'): ?>
        <div class="alert alert-danger shadow-sm border-0 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Cannot delete this item! It is currently used in transactions or stock.
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center border-bottom mb-4">
        <ul class="nav nav-tabs border-bottom-0">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == 'products' ? 'active' : '' ?>" href="?tab=products">
                    <i class="bi bi-box-seam me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == 'bins' ? 'active' : '' ?>" href="?tab=bins">
                    <i class="bi bi-grid-3x3 me-2"></i>Storage Bins
                </a>
            </li>
        </ul>
        
        <form class="search-box mb-2" method="GET">
            <input type="hidden" name="tab" value="<?= $active_tab ?>">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" onblur="this.form.submit()">
        </form>
    </div>

    <div class="tab-content">
        
        <div class="tab-pane fade <?= $active_tab == 'products' ? 'show active' : '' ?>">
            <div class="card-table">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h6 class="fw-bold m-0 text-secondary">Product List (<?= count($products) ?>)</h6>
                    <button class="btn btn-primary btn-sm fw-bold px-3" onclick="openModalProd()">
                        <i class="bi bi-plus-lg me-1"></i> Add Product
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Code</th><th>Description</th><th>UoM</th><th>Packing</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($products)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No products found matching "<?= $search ?>"</td></tr>
                            <?php endif; ?>

                            <?php foreach($products as $r): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['product_code']) ?></td>
                                <td><?= htmlspecialchars($r['description']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $r['base_uom'] ?></span></td>
                                <td class="small text-muted">1 <?= $r['capacity_uom'] ?> = <?= $r['conversion_qty'] ?> <?= $r['base_uom'] ?></td>
                                <td class="text-end">
                                    <button class="btn-icon text-warning me-1" onclick='editProd(<?= json_encode($r) ?>)' title="Edit"><i class="bi bi-pencil-square"></i></button>
                                    <a href="?del_prod=<?= $r['product_uuid'] ?>" class="btn-icon text-danger btn-del" title="Delete"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $active_tab == 'bins' ? 'show active' : '' ?>">
            <div class="card-table">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h6 class="fw-bold m-0 text-secondary">Storage Bin List (<?= count($bins) ?>)</h6>
                    <button class="btn btn-primary btn-sm fw-bold px-3" onclick="openModalBin()">
                        <i class="bi bi-plus-lg me-1"></i> Add Bin
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Storage Bin</th><th>Storage Type</th><th>Max Capacity</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($bins)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No bins found matching "<?= $search ?>"</td></tr>
                            <?php endif; ?>

                            <?php foreach($bins as $r): ?>
                            <tr>
                                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($r['lgpla']) ?></td>
                                <td>
                                    <?php if($r['lgtyp']=='0010'): ?><span class="badge bg-secondary">High Rack (0010)</span>
                                    <?php elseif($r['lgtyp']=='9010'): ?><span class="badge bg-info text-dark">GR Zone (9010)</span>
                                    <?php else: ?><span class="badge bg-light text-dark border"><?= $r['lgtyp'] ?></span><?php endif; ?>
                                </td>
                                <td><?= number_format($r['max_weight']) ?> KG</td>
                                <td class="text-end">
                                    <button class="btn-icon text-warning me-1" onclick='editBin(<?= json_encode($r) ?>)'><i class="bi bi-pencil-square"></i></button>
                                    <a href="?del_bin=<?= $r['lgpla'] ?>" class="btn-icon text-danger btn-del"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalProd" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="prodTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="is_edit" id="is_edit_prod" value="0">
                    <input type="hidden" name="product_uuid" id="prod_uuid">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Product Code</label>
                        <input type="text" name="product_code" id="prod_code" class="form-control" required placeholder="e.g. ITEM-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <input type="text" name="description" id="prod_desc" class="form-control" required placeholder="Product Name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Base UoM</label>
                        <select name="base_uom" id="prod_uom" class="form-select">
                            <option value="PCS">PCS - Pieces</option>
                            <option value="BOX">BOX - Boxes</option>
                            <option value="KG">KG - Kilogram</option>
                            <option value="L">L - Liter</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_product" class="btn btn-primary fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Manage Bin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Bin ID (Location)</label>
                        <input type="text" name="lgpla" id="bin_code" class="form-control text-uppercase" required placeholder="e.g. A-01-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Storage Type</label>
                        <select name="lgtyp" id="bin_type" class="form-select">
                            <option value="0010">0010 - High Rack Storage</option>
                            <option value="9010">9010 - GR Zone (Inbound)</option>
                            <option value="9020">9020 - GI Zone (Outbound)</option>
                            <option value="9999">9999 - Lost & Found</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Max Capacity (KG)</label>
                        <input type="number" name="max_weight" id="bin_weight" class="form-control" value="1000">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_bin" class="btn btn-primary fw-bold">Save Bin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const modalProd = new bootstrap.Modal(document.getElementById('modalProd'));
    const modalBin = new bootstrap.Modal(document.getElementById('modalBin'));

    function openModalProd() {
        document.getElementById('prodTitle').innerText = 'Add New Product';
        document.getElementById('is_edit_prod').value = '0';
        document.getElementById('prod_uuid').value = '';
        document.getElementById('prod_code').value = '';
        document.getElementById('prod_desc').value = '';
        modalProd.show();
    }

    function editProd(data) {
        document.getElementById('prodTitle').innerText = 'Edit Product';
        document.getElementById('is_edit_prod').value = '1';
        document.getElementById('prod_uuid').value = data.product_uuid;
        document.getElementById('prod_code').value = data.product_code;
        document.getElementById('prod_desc').value = data.description;
        document.getElementById('prod_uom').value = data.base_uom;
        modalProd.show();
    }

    function openModalBin() {
        document.getElementById('bin_code').value = '';
        document.getElementById('bin_code').readOnly = false; 
        modalBin.show();
    }

    function editBin(data) {
        document.getElementById('bin_code').value = data.lgpla;
        document.getElementById('bin_code').readOnly = true; 
        document.getElementById('bin_type').value = data.lgtyp;
        document.getElementById('bin_weight').value = data.max_weight;
        modalBin.show();
    }

    // SweetAlert Delete Confirmation
    document.querySelectorAll('.btn-del').forEach(item => {
        item.addEventListener('click', event => {
            event.preventDefault();
            const url = item.getAttribute('href');
            Swal.fire({
                title: 'Delete this item?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
        })
    });
</script>
</body>
</html>