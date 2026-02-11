<?php
// apps/wms/sales_order.php
// V12: ENTERPRISE SO (Rich Header + Smart Item Selector)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user = $_SESSION['wms_fullname'];
$msg = ""; $msg_type = "";

// ---------------------------------------------------------
// 🧠 1. CREATE HEADER SO (LENGKAP)
// ---------------------------------------------------------
if(isset($_POST['create_header'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    
    $cust = sanitizeInput($_POST['customer_name']);
    $date = sanitizeInput($_POST['expected_date']);
    $po_ref = sanitizeInput($_POST['po_reference']);
    $addr = sanitizeInput($_POST['ship_to']);
    $prio = sanitizeInput($_POST['priority']);
    $note = sanitizeInput($_POST['remarks']);
    
    $so_num = "SO-" . date('ymd') . "-" . rand(100,999);
    
    try {
        $sql = "INSERT INTO wms_so_header (so_number, customer_name, po_reference, expected_date, ship_to, priority, remarks, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'CREATED', NOW())";
        safeQuery($pdo, $sql, [$so_num, $cust, $po_ref, $date, $addr, $prio, $note]);
        
        header("Location: sales_order.php?so=$so_num"); exit;
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage(); $msg_type="danger";
    }
}

// ---------------------------------------------------------
// 🧠 2. ADD ITEM (WITH STOCK CHECK)
// ---------------------------------------------------------
if(isset($_POST['add_item'])) {
    $so_num = sanitizeInput($_POST['so_number']);
    $prod   = sanitizeInput($_POST['product_uuid']);
    $qty    = (float)$_POST['qty_ordered'];
    
    $head = safeGetOne($pdo, "SELECT status FROM wms_so_header WHERE so_number=?", [$so_num]);
    if($head['status'] != 'CREATED') die("Cannot edit SO. Status is {$head['status']}");

    // Cek Double Item (Optional, biar rapi)
    $exist = safeGetOne($pdo, "SELECT 1 FROM wms_so_items WHERE so_number=? AND product_uuid=?", [$so_num, $prod]);
    if($exist) {
        // Kalau udah ada, update qty aja
        safeQuery($pdo, "UPDATE wms_so_items SET qty_ordered = qty_ordered + ? WHERE so_number=? AND product_uuid=?", [$qty, $so_num, $prod]);
    } else {
        safeQuery($pdo, "INSERT INTO wms_so_items (so_number, product_uuid, qty_ordered) VALUES (?, ?, ?)", [$so_num, $prod, $qty]);
    }
    
    header("Location: sales_order.php?so=$so_num"); exit;
}

// ---------------------------------------------------------
// 🧠 3. DELETE ITEM
// ---------------------------------------------------------
if(isset($_GET['del_item']) && isset($_GET['so'])) {
    $item_id = sanitizeInput($_GET['del_item']);
    $so_num = sanitizeInput($_GET['so']);
    
    // Cek status dulu
    $head = safeGetOne($pdo, "SELECT status FROM wms_so_header WHERE so_number=?", [$so_num]);
    if($head['status'] == 'CREATED') {
        safeQuery($pdo, "DELETE FROM wms_so_items WHERE so_item_id=?", [$item_id]);
    }
    header("Location: sales_order.php?so=$so_num"); exit;
}

// ---------------------------------------------------------
// 🧠 4. RESERVATION ENGINE (SAMA DENGAN V11)
// ---------------------------------------------------------
if(isset($_POST['run_reservation'])) {
    $so_num = sanitizeInput($_POST['so_number']);
    
    try {
        $pdo->beginTransaction();
        
        $head = safeGetOne($pdo, "SELECT status FROM wms_so_header WHERE so_number=? FOR UPDATE", [$so_num]);
        if($head['status'] != 'CREATED') throw new Exception("Status invalid.");

        $items = safeGetAll($pdo, "SELECT * FROM wms_so_items WHERE so_number=?", [$so_num]);
        
        foreach($items as $item) {
            $qty_need = $item['qty_ordered'];
            
            // Logic 3 Layer + FEFO
            // Logic 3 Layer + FEFO (DILARANG AMBIL DI GR & GI ZONE)
            $sql_stock = "SELECT quant_id, (qty - reserved_qty - picked_qty) as available_qty 
              FROM wms_quants 
              WHERE product_uuid = ? 
                AND stock_type = 'F1' 
                AND (qty - reserved_qty - picked_qty) > 0
                AND lgpla NOT IN ('GR-ZONE', 'GI-ZONE') -- 🛑 FIX: Jangan ambil barang di staging!
              ORDER BY COALESCE(expiry_date, '2999-12-31') ASC, gr_date ASC 
              FOR UPDATE";
            
            $stmt = $pdo->prepare($sql_stock);
            $stmt->execute([$item['product_uuid']]);
            
            $qty_left = $qty_need;
            
            while($qty_left > 0 && $stok = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $take = ($stok['available_qty'] >= $qty_left) ? $qty_left : $stok['available_qty'];
                safeQuery($pdo, "UPDATE wms_quants SET reserved_qty = reserved_qty + ? WHERE quant_id=?", [$take, $stok['quant_id']]);
                safeQuery($pdo, "INSERT INTO wms_stock_reservations (so_number, so_item_id, quant_id, qty_reserved) VALUES (?, ?, ?, ?)", 
                          [$so_num, $item['so_item_id'], $stok['quant_id'], $take]);
                $qty_left -= $take;
            }
            
            if($qty_left > 0) throw new Exception("Stock Shortage for Item ID {$item['product_uuid']}. Missing: $qty_left");
        }
        
        safeQuery($pdo, "UPDATE wms_so_header SET status='RESERVED' WHERE so_number=?", [$so_num]);
        catat_log($pdo, $user, 'RESERVE', 'SO', "Reserved Stock for SO: $so_num");
        
        $pdo->commit();
        $msg = "✅ Stock Reserved Successfully!"; $msg_type = "success";

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Reservation Failed: " . $e->getMessage(); $msg_type = "danger";
    }
}

// --- DATA FETCHING ---
$active_so = isset($_GET['so']) ? $_GET['so'] : null;
$so_data = null; $so_items = [];

if($active_so) {
    $so_data = safeGetOne($pdo, "SELECT * FROM wms_so_header WHERE so_number=?", [$active_so]);
    $so_items = safeGetAll($pdo, "SELECT i.*, p.product_code, p.description, p.base_uom 
                                  FROM wms_so_items i 
                                  JOIN wms_products p ON i.product_uuid = p.product_uuid 
                                  WHERE i.so_number=?", [$active_so]);
}

// SMART PRODUCT LIST (SHOW AVAILABLE STOCK)
// Menghitung stok F1 yang belum di-reserve
$prod_list = safeGetAll($pdo, "
    SELECT p.product_uuid, p.product_code, p.description, 
           COALESCE(SUM(q.qty - q.reserved_qty - q.picked_qty), 0) as avail_stock
    FROM wms_products p
    LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid 
         AND q.stock_type='F1' 
         AND q.lgpla NOT IN ('GR-ZONE', 'GI-ZONE') -- 🛑 FIX: Hitung stok di rak saja
    GROUP BY p.product_uuid
    ORDER BY p.product_code ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enterprise Sales Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; padding-bottom: 50px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .so-active { border-left: 4px solid #2563eb; background: #eff6ff; }
        .status-pill { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; }
        .bg-st-CREATED { background: #e2e8f0; color: #475569; }
        .bg-st-RESERVED { background: #dbeafe; color: #1e40af; }
        .prio-HIGH { color: #dc2626; font-weight: bold; }
        .prio-URGENT { color: #dc2626; font-weight: 900; text-decoration: underline; }
    </style>
</head>
<body>

<div class="container-fluid py-4" style="max-width: 1500px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-cart3 text-primary me-2"></i>Sales Order Management</h3>
            <p class="text-muted m-0">Create Order & Reserve Inventory</p>
        </div>
        <div>
            <a href="outbound.php" class="btn btn-outline-secondary fw-bold px-4">
                <i class="bi bi-arrow-right-circle me-2"></i>Go to Picking Release
            </a>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 shadow-sm mb-4"><i class="bi bi-info-circle me-2"></i> <?= $msg ?></div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-3">
            <div class="card card-custom h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <button class="btn btn-primary w-100 fw-bold py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#newSOModal">
                        <i class="bi bi-plus-lg me-2"></i> CREATE NEW ORDER
                    </button>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 70vh;">
                    <?php 
                    $list = safeGetAll($pdo, "SELECT * FROM wms_so_header ORDER BY created_at DESC LIMIT 20");
                    foreach($list as $r): 
                        $cls = ($active_so == $r['so_number']) ? 'so-active' : '';
                        $prio_icon = ($r['priority'] == 'URGENT') ? '🔥 ' : '';
                    ?>
                    <a href="?so=<?= $r['so_number'] ?>" class="list-group-item list-group-item-action <?= $cls ?> py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark"><?= $prio_icon . $r['so_number'] ?></span>
                            <span class="status-pill bg-st-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                        </div>
                        <div class="text-truncate text-secondary fw-medium" style="font-size:0.9rem;">
                            <?= substr($r['customer_name'],0,25) ?>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span><i class="bi bi-calendar4 me-1"></i> <?= date('d M', strtotime($r['expected_date'])) ?></span>
                            <span>Ref: <?= $r['po_reference'] ?: '-' ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if($so_data): ?>
                
                <div class="card card-custom mb-4">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <h2 class="fw-bold m-0 me-3"><?= $so_data['so_number'] ?></h2>
                                    <span class="status-pill bg-st-<?= $so_data['status'] ?> fs-6"><?= $so_data['status'] ?></span>
                                </div>
                                <h5 class="text-secondary"><?= $so_data['customer_name'] ?></h5>
                                <div class="text-muted mb-3"><i class="bi bi-geo-alt me-1"></i> <?= $so_data['ship_to'] ?: 'No address provided' ?></div>
                                
                                <div class="d-flex gap-4 small text-muted">
                                    <span><b class="text-dark">PO Ref:</b> <?= $so_data['po_reference'] ?></span>
                                    <span><b class="text-dark">Priority:</b> <span class="prio-<?= $so_data['priority'] ?>"><?= $so_data['priority'] ?></span></span>
                                    <span><b class="text-dark">Due Date:</b> <?= date('d F Y', strtotime($so_data['expected_date'])) ?></span>
                                </div>
                                <?php if($so_data['remarks']): ?>
                                    <div class="alert alert-warning border-0 p-2 mt-3 mb-0 small"><i class="bi bi-sticky me-1"></i> Note: <?= $so_data['remarks'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 text-end d-flex flex-column justify-content-between">
                                <div></div> <?php if($so_data['status'] == 'CREATED' && !empty($so_items)): ?>
                                <form method="POST" onsubmit="return confirm('Lock this order and reserve inventory?');">
                                    <?php echo csrfTokenField(); ?>
                                    <input type="hidden" name="so_number" value="<?= $active_so ?>">
                                    <button type="submit" name="run_reservation" class="btn btn-success btn-lg w-100 fw-bold shadow-sm">
                                        <i class="bi bi-lock-fill me-2"></i> RESERVE STOCK
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-custom">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-box-seam me-2"></i>Order Line Items</h6>
                    </div>
                    
                    <?php if($so_data['status'] == 'CREATED'): ?>
                    <div class="p-3 bg-light border-bottom">
                        <form method="POST" class="row g-2 align-items-center">
                            <input type="hidden" name="so_number" value="<?= $active_so ?>">
                            
                            <div class="col-md-7">
                                <select name="product_uuid" class="form-select form-select-sm shadow-none border-secondary" required>
                                    <option value="">-- Select Product (Showing Available Stock) --</option>
                                    <?php foreach($prod_list as $p): 
                                        $stock_cls = ($p['avail_stock'] > 0) ? 'text-success fw-bold' : 'text-danger';
                                    ?>
                                        <option value="<?= $p['product_uuid'] ?>" <?= ($p['avail_stock'] <= 0) ? 'disabled' : '' ?>>
                                            <?= $p['product_code'] ?> - <?= substr($p['description'],0,40) ?> 
                                            [Avail: <?= number_format($p['avail_stock']) ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="qty_ordered" class="form-control form-control-sm" placeholder="Qty" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="add_item" class="btn btn-sm btn-dark w-100 fw-bold">
                                    <i class="bi bi-plus-lg"></i> Add Item
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="ps-4">Product Code</th>
                                    <th>Description</th>
                                    <th class="text-center">UoM</th>
                                    <th class="text-end pe-4">Qty</th>
                                    <?php if($so_data['status']=='CREATED'): ?><th></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($so_items)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Order is empty. Add items above.</td></tr>
                                <?php endif; ?>

                                <?php foreach($so_items as $item): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= $item['product_code'] ?></td>
                                    <td><?= $item['description'] ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $item['base_uom'] ?></span></td>
                                    <td class="text-end pe-4 fs-5 fw-bold text-primary"><?= (float)$item['qty_ordered'] ?></td>
                                    <?php if($so_data['status']=='CREATED'): ?>
                                    <td class="text-end pe-3">
                                        <a href="?so=<?= $active_so ?>&del_item=<?= $item['so_item_id'] ?>" class="text-danger" onclick="return confirm('Remove item?')"><i class="bi bi-x-circle-fill"></i></a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <div class="h-100 d-flex align-items-center justify-content-center text-center p-5 rounded-3 bg-white border border-dashed">
                    <div>
                        <img src="https://cdn-icons-png.flaticon.com/512/743/743131.png" width="100" class="opacity-25 mb-3">
                        <h4 class="fw-bold text-muted">Sales Order Management</h4>
                        <p class="text-muted">Select an order from the list or create a new one.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSOModal">Create New Order</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="newSOModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>New Sales Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required placeholder="Company Name">
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">PO Reference</label>
                            <input type="text" name="po_reference" class="form-control" placeholder="PO-1234">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="NORMAL">Normal</option>
                                <option value="HIGH">High</option>
                                <option value="URGENT">URGENT</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ship To Address</label>
                        <textarea name="ship_to" class="form-control" rows="2" placeholder="Full Delivery Address..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Delivery Date <span class="text-danger">*</span></label>
                            <input type="date" name="expected_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Internal Notes</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional notes for warehouse...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" name="create_header" class="btn btn-primary w-100 fw-bold py-2">Create Header & Add Items</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>