<?php
// apps/wms/shipping.php
// V12: SHIPPING COCKPIT (Progress Tracking + Rich Data)

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
if(isset($_GET['notif']) && $_GET['notif'] == 'pgi_success') {
    $msg = "✅ Shipment <b>{$_GET['ref']}</b> Closed. Stock Deducted.";
    $msg_type = "success";
}

// ---------------------------------------------------------
// 🧠 LOGIC: POST GOODS ISSUE (FINAL EXECUTION)
// ---------------------------------------------------------
if(isset($_POST['post_gi'])) {
    if (!verifyCSRFToken()) die("Security Alert: Invalid Token");

    $so_number = sanitizeInput($_POST['so_number']);
    
    try {
        $pdo->beginTransaction();

        // 1. Validasi Status & Picking Completeness
        // Kita hitung apakah qty_picked sudah >= qty_ordered
        // (Logic V12: Cek reservation table atau Task Picking)
        
        // Cek Item SO
        $items = safeGetAll($pdo, "SELECT product_uuid, qty_ordered FROM wms_so_items WHERE so_number=?", [$so_number]);
        
        foreach($items as $item) {
            $prod = $item['product_uuid'];
            $qty_order = $item['qty_ordered'];
            
            // Cek fisik di GI-ZONE (Area Loading)
            $stok_gi = safeGetOne($pdo, "SELECT SUM(qty) as total FROM wms_quants WHERE lgpla='GI-ZONE' AND product_uuid=?", [$prod]);
            $qty_ready = $stok_gi['total'] ?? 0;

            if($qty_ready < $qty_order) {
                // Allow Partial PGI? Untuk V12 kita Strict dulu.
                throw new Exception("Loading Incomplete! Item mismatch in GI-ZONE. Needed: $qty_order, Ready: $qty_ready");
            }
        }

        // 2. Potong Stok (Hard Delete dari GI-ZONE)
        foreach($items as $item) {
            $prod = $item['product_uuid'];
            $qty_rem = $item['qty_ordered'];
            
            $stmt_stok = $pdo->prepare("SELECT * FROM wms_quants WHERE lgpla='GI-ZONE' AND product_uuid=? ORDER BY gr_date ASC FOR UPDATE");
            $stmt_stok->execute([$prod]);
            
            while($qty_rem > 0 && $d = $stmt_stok->fetch(PDO::FETCH_ASSOC)) {
                $qty_bin = $d['qty'];
                if($qty_bin <= $qty_rem) {
                    safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$d['quant_id']]);
                    $qty_rem -= $qty_bin;
                } else {
                    safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [($qty_bin - $qty_rem), $d['quant_id']]);
                    $qty_rem = 0;
                }
            }
        }

        // 3. Update SO Status
        safeQuery($pdo, "UPDATE wms_so_header SET status='COMPLETED', updated_at=NOW() WHERE so_number=?", [$so_number]);
        
        // 4. Clean Up Reservations (Stok sudah keluar, reservasi harus dihapus)
        safeQuery($pdo, "DELETE FROM wms_stock_reservations WHERE so_number=?", [$so_number]);

        catat_log($pdo, $user, 'PGI', 'SHIPPING', "Post Goods Issue: $so_number");
        
        $pdo->commit();
        
        // 🚀 INI OBATNYA: Tendang balik ke halaman shipping yang bersih
        header("Location: shipping.php?notif=pgi_success&ref=$so_number");
        exit; // WAJIB ada exit biar script bawahnya gak jalan

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "PGI Failed: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// ---------------------------------------------------------
// 🔍 VIEW DATA
// ---------------------------------------------------------
$active_so = isset($_GET['so']) ? $_GET['so'] : null;
$so_data = null; $so_items = [];

// List Shipment (Exclude COMPLETED)
// Sort by Priority (URGENT First) -> Due Date
$list_sql = "SELECT h.*, 
             (SELECT COUNT(*) FROM wms_so_items WHERE so_number=h.so_number) as total_sku
             FROM wms_so_header h
             WHERE h.status IN ('RESERVED', 'PICKING', 'PACKED') 
             ORDER BY FIELD(h.priority, 'URGENT', 'HIGH', 'NORMAL'), h.expected_date ASC";
$list = safeGetAll($pdo, $list_sql);

if($active_so) {
    $so_data = safeGetOne($pdo, "SELECT * FROM wms_so_header WHERE so_number=?", [$active_so]);
    
    // Ambil Item + Status Picking Real-time
    // Logic: Join SO Item dengan Stok di GI-ZONE untuk hitung progress
    $item_sql = "SELECT i.*, p.product_code, p.description, p.base_uom,
                 COALESCE((SELECT SUM(qty) FROM wms_quants WHERE lgpla='GI-ZONE' AND product_uuid=i.product_uuid), 0) as qty_picked
                 FROM wms_so_items i
                 JOIN wms_products p ON i.product_uuid = p.product_uuid
                 WHERE i.so_number=?";
    $so_items = safeGetAll($pdo, $item_sql, [$active_so]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipping Cockpit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 50px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); background: white; }
        
        /* List Item Styling */
        .ship-item { border-left: 4px solid transparent; transition: 0.2s; cursor: pointer; }
        .ship-item:hover { background: #f8fafc; }
        .ship-item.active { background: #eff6ff; border-left-color: #2563eb; }
        
        .prio-URGENT { border-left-color: #dc2626 !important; background: #fef2f2; }
        .prio-HIGH { border-left-color: #f59e0b !important; }
        
        .badge-prio { font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-URGENT { background: #dc2626; color: white; }
        .badge-HIGH { background: #f59e0b; color: white; }
        .badge-NORMAL { background: #e2e8f0; color: #64748b; }

        .progress-thin { height: 6px; border-radius: 10px; background: #e2e8f0; width: 100px; }
    </style>
</head>
<body>

<div class="container-fluid py-4" style="max-width: 1600px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-truck-front text-primary me-2"></i>Shipping Console</h3>
            <p class="text-muted m-0">Load Planning & Goods Issue</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary fw-bold">Dashboard</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 shadow-sm mb-4"><i class="bi bi-info-circle me-2"></i> <?= $msg ?></div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-3">
            <div class="card card-custom h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-secondary">Active Shipments (<?= count($list) ?>)</h6>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 75vh;">
                    <?php if(empty($list)): ?>
                        <div class="text-center py-5 text-muted small">No active shipments.</div>
                    <?php endif; ?>

                    <?php foreach($list as $r): 
                        $isActive = ($active_so == $r['so_number']) ? 'active' : '';
                        $isUrgent = ($r['priority'] == 'URGENT') ? 'prio-URGENT' : '';
                    ?>
                    <a href="?so=<?= $r['so_number'] ?>" class="list-group-item p-3 border-bottom ship-item <?= $isActive ?> <?= $isUrgent ?>">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="fw-bold text-dark"><?= $r['so_number'] ?></span>
                            <span class="badge-prio badge-<?= $r['priority'] ?>"><?= $r['priority'] ?></span>
                        </div>
                        <div class="small text-secondary mb-2 text-truncate"><?= $r['customer_name'] ?></div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span><i class="bi bi-box me-1"></i> <?= $r['total_sku'] ?> Items</span>
                            <span>Due: <?= date('d M', strtotime($r['expected_date'])) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if($so_data): 
                // Hitung Global Progress
                $total_ord = 0; $total_pick = 0;
                foreach($so_items as $itm) { 
                    $total_ord += $itm['qty_ordered']; 
                    $total_pick += ($itm['qty_picked'] > $itm['qty_ordered']) ? $itm['qty_ordered'] : $itm['qty_picked']; // Cap at order
                }
                $pct_global = ($total_ord > 0) ? ($total_pick / $total_ord) * 100 : 0;
                $ready_to_ship = ($pct_global >= 100);
            ?>
            
            <div class="row g-4">
                <div class="col-12">
                    <div class="card card-custom p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <h2 class="fw-bold m-0"><?= $so_data['so_number'] ?></h2>
                                    <span class="badge bg-light text-dark border"><?= $so_data['status'] ?></span>
                                </div>
                                <h5 class="text-primary mb-1"><?= $so_data['customer_name'] ?></h5>
                                <div class="text-muted small"><i class="bi bi-geo-alt-fill me-1"></i> <?= $so_data['ship_to'] ?: 'Address Not Set' ?></div>
                            </div>
                            
                            <div class="text-end" style="min-width: 250px;">
                                <div class="small fw-bold text-muted mb-1 text-uppercase">Picking Progress</div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: <?= $pct_global ?>%">
                                        <?= round($pct_global) ?>%
                                    </div>
                                </div>
                                <div class="mt-2 small text-muted">
                                    <?= number_format($total_pick) ?> of <?= number_format($total_ord) ?> Units Ready at GI-ZONE
                                </div>
                            </div>
                        </div>
                        
                        <?php if($so_data['remarks']): ?>
                            <div class="alert alert-warning border-0 py-2 px-3 mt-3 mb-0 small d-inline-block">
                                <i class="bi bi-sticky-fill me-2"></i> <b>Note:</b> <?= $so_data['remarks'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-custom h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-list-check me-2"></i>Load Manifest</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Product</th>
                                        <th class="text-center">Ord Qty</th>
                                        <th class="text-center">Picked (GI)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($so_items as $itm): 
                                        $pct = ($itm['qty_ordered'] > 0) ? ($itm['qty_picked'] / $itm['qty_ordered']) * 100 : 0;
                                        $is_full = ($pct >= 100);
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= $itm['product_code'] ?></div>
                                            <div class="small text-muted"><?= $itm['description'] ?></div>
                                        </td>
                                        <td class="text-center fw-bold"><?= (float)$itm['qty_ordered'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $is_full?'success':'warning text-dark' ?> bg-opacity-10 border border-<?= $is_full?'success':'warning' ?> text-<?= $is_full?'success':'dark' ?>">
                                                <?= (float)$itm['qty_picked'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($is_full): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php else: ?>
                                                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-custom h-100">
                        <div class="card-header bg-dark text-white py-3">
                            <h6 class="m-0 fw-bold"><i class="bi bi-rocket-takeoff me-2"></i>Actions</h6>
                        </div>
                        <div class="card-body d-flex flex-column gap-3">
                            
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold small text-muted mb-3">DOCUMENTATION</h6>
                                <a href="print_sj.php?so=<?= $active_so ?>" target="_blank" class="btn btn-outline-dark w-100 fw-bold mb-2">
                                    <i class="bi bi-printer me-2"></i>Print Delivery Note (SJ)
                                </a>
                                <button class="btn btn-outline-secondary w-100 btn-sm" disabled>Print Packing List</button>
                            </div>

                            <div class="mt-auto">
                                <h6 class="fw-bold small text-muted mb-2">EXECUTION</h6>
                                <form method="POST" onsubmit="return confirm('FINAL WARNING:\n\nThis will remove stock from inventory and close the order.\nAre you sure physical goods are loaded?');">
                                    <?php echo csrfTokenField(); ?>
                                    <input type="hidden" name="so_number" value="<?= $active_so ?>">
                                    
                                    <?php if($ready_to_ship): ?>
                                        <button type="submit" name="post_gi" class="btn btn-success w-100 btn-lg fw-bold shadow">
                                            <i class="bi bi-box-arrow-right me-2"></i> POST GOODS ISSUE
                                        </button>
                                        <div class="text-center mt-2 small text-success fw-bold"><i class="bi bi-check-all"></i> Ready for Dispatch</div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary w-100 btn-lg" disabled>
                                            <i class="bi bi-hourglass-split me-2"></i> WAITING PICKING
                                        </button>
                                        <div class="text-center mt-2 small text-danger fw-bold">Items not fully available in GI-ZONE</div>
                                    <?php endif; ?>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <?php else: ?>
                <div class="h-100 d-flex align-items-center justify-content-center text-center p-5 bg-white card-custom">
                    <div>
                        <i class="bi bi-cursor display-1 text-primary opacity-25"></i>
                        <h4 class="mt-3 fw-bold text-muted">Select a Shipment</h4>
                        <p class="text-muted">Choose an active order from the list to view details & execute PGI.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>