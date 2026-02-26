<?php
// apps/wms/receiving.php
// V19.17: THE ABSOLUTE FINAL GATEKEEPER (DISCREPANCY ENGINE FIX)
// Features: Print HU Labels Modal, Partial Receiving, Auto-Close, Native DB Remarks, Full System Audit, Precise Discrepancy Tracking

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { 
    if(isset($_POST['action'])) { header('Content-Type: application/json'); echo json_encode(['status'=>'error', 'message'=>'Session Expired']); exit; }
    else { header("Location: login.php"); exit(); }
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user_id = $_SESSION['wms_fullname'] ?? 'System';

class InventoryModel {
    private $pdo;
    private $user;

    public function __construct($pdo, $user) {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function getOrCreateBin($binCode, $type='0010') {
        $binCode = strtoupper(trim($binCode));
        $stmt = $this->pdo->prepare("SELECT lgpla FROM wms_storage_bins WHERE lgpla = ?");
        $stmt->execute([$binCode]);
        if(!$stmt->fetch()) {
            $ins = $this->pdo->prepare("INSERT INTO wms_storage_bins (lgpla, lgtyp, max_weight, status_bin) VALUES (?, ?, 99999, 'EMPTY')");
            $ins->execute([$binCode, $type]);
        }
        return $binCode;
    }

    public function getPOHeader($poNum) {
        return safeGetOne($this->pdo, "SELECT * FROM wms_po_header WHERE po_number = ?", [$poNum]);
    }

    public function getNextSequence($name) {
        $stmt = $this->pdo->prepare("SELECT last_val FROM wms_sequences WHERE seq_name = ? FOR UPDATE");
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        $next = (!$row) ? 1 : $row['last_val'] + 1;
        if(!$row) { $this->pdo->prepare("INSERT INTO wms_sequences VALUES (?, 1)")->execute([$name]); } 
        else { $this->pdo->prepare("UPDATE wms_sequences SET last_val = ? WHERE seq_name = ?")->execute([$next, $name]); }
        return $next;
    }

    public function getPOItems($poNum) {
        // 🔥 FIX V19.17: Precise aggregation for discrepancy tracking
        $sql = "SELECT i.*, p.product_code, p.description, p.base_uom, p.conversion_qty,
                COALESCE((SELECT SUM(gi.qty_good) FROM wms_gr_items gi JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number WHERE gh.po_number = i.po_number AND gi.product_uuid = i.product_uuid), 0) as admin_good,
                COALESCE((SELECT SUM(gi.qty_damaged) FROM wms_gr_items gi JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number WHERE gh.po_number = i.po_number AND gi.product_uuid = i.product_uuid), 0) as admin_bad,
                COALESCE((SELECT SUM(gi.qty_actual_good) FROM wms_gr_items gi JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number WHERE gh.po_number = i.po_number AND gi.product_uuid = i.product_uuid), 0) as op_actual_good,
                COALESCE((SELECT SUM(gi.qty_actual_damaged) FROM wms_gr_items gi JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number WHERE gh.po_number = i.po_number AND gi.product_uuid = i.product_uuid), 0) as op_actual_bad,
                (SELECT COUNT(*) FROM wms_warehouse_tasks t WHERE t.hu_id IN (SELECT hu_id FROM wms_quants WHERE gr_ref IN (SELECT gr_number FROM wms_gr_header WHERE po_number=i.po_number)) AND t.status='OPEN') as pending_task
                FROM wms_po_items i JOIN wms_products p ON i.product_uuid = p.product_uuid WHERE i.po_number = ?";
        return safeGetAll($this->pdo, $sql, [$poNum]);
    }

    public function postGR($data) {
        try {
            $this->pdo->beginTransaction();
            
            $qty_good = isset($data['qty_good']) && $data['qty_good'] !== '' ? (float)$data['qty_good'] : 0;
            $qty_bad  = isset($data['qty_bad'])  && $data['qty_bad'] !== ''  ? (float)$data['qty_bad']  : 0;
            
            if($qty_good < 0 || $qty_bad < 0) {
                throw new Exception("CRITICAL ERROR: Negative quantities are strictly prohibited.");
            }
            if(($qty_good + $qty_bad) <= 0) {
                throw new Exception("Please input a valid quantity greater than zero.");
            }

            if(!empty($data['expiry'])) {
                $today = strtotime(date('Y-m-d'));
                $input_expiry = strtotime($data['expiry']);
                if($input_expiry < $today) {
                    throw new Exception("CRITICAL ERROR: Expiry date cannot be in the past.");
                }
            }

            $poHeader = safeGetOne($this->pdo, "SELECT status FROM wms_po_header WHERE po_number = ? FOR UPDATE", [$data['po_number']]);
            if(!$poHeader || $poHeader['status'] == 'CLOSED') {
                throw new Exception("TRANSACTION DENIED: This Purchase Order is already CLOSED or does not exist.");
            }

            $doCheck = safeGetOne($this->pdo, "SELECT 1 FROM wms_gr_header WHERE po_number = ? AND vendor_do = ?", [$data['po_number'], $data['vendor_do']]);
            if($doCheck) {
                throw new Exception("DUPLICATE ERROR: Vendor DO / SJ Number '{$data['vendor_do']}' has already been processed for this PO.");
            }

            $item = safeGetOne($this->pdo, "SELECT i.*, p.conversion_qty, p.base_uom FROM wms_po_items i JOIN wms_products p ON i.product_uuid = p.product_uuid WHERE i.po_item_id = ?", [$data['item_id']]);
            if(!$item) throw new Exception("Error: Product Data not found.");

            $conv = (isset($data['uom_mode']) && $data['uom_mode'] == 'PACK') ? (float)$item['conversion_qty'] : 1;
            
            $total_good = round($qty_good * $conv, 4);
            $total_bad  = round($qty_bad * $conv, 4);
            $total_recv = $total_good + $total_bad;

            $received_qty_db = (float)$item['received_qty'];
            $qty_ordered_db = (float)$item['qty_ordered'];

            if (($received_qty_db + $total_recv) > $qty_ordered_db) {
                $allowed = $qty_ordered_db - $received_qty_db;
                throw new Exception("OVER RECEIVING DETECTED: You are trying to receive $total_recv, but only $allowed {$item['base_uom']} are remaining on this order.");
            }

            $gr_num = "GR-" . date('ymd') . "-" . str_pad($this->getNextSequence('GR_NUM'), 6, '0', STR_PAD_LEFT);
            $batch_id = $data['batch_id'] ?: ("BATCH-" . date('ymd') . "-" . str_pad($this->getNextSequence('BATCH_NUM'), 4, '0', STR_PAD_LEFT));

            $this->getOrCreateBin('GR-ZONE', '9010');
            $this->getOrCreateBin('BLOCK-ZONE', '9010');

            $remarks_input = !empty($data['remarks']) ? trim($data['remarks']) : NULL;
            safeQuery($this->pdo, "INSERT INTO wms_gr_header (gr_number, po_number, vendor_do, received_by, status, gr_date, remarks) VALUES (?,?,?,?,'POSTED', NOW(), ?)", [$gr_num, $data['po_number'], $data['vendor_do'], $this->user, $remarks_input]);
            
            safeQuery($this->pdo, "INSERT INTO wms_gr_items (gr_number, po_item_id, product_uuid, batch_no, expiry_date, qty_good, qty_damaged, qty_reported, qty_actual_good, qty_actual_damaged, discrepancy_status) VALUES (?,?,?,?,?,?,?,?, 0, ?, 'BALANCED')", 
                     [$gr_num, $data['item_id'], $item['product_uuid'], $batch_id, $data['expiry'], $total_good, $total_bad, $total_recv, $total_bad]);

            $left = $total_good; 
            while($left > 0.0001) {
                $take = ($left >= $item['conversion_qty']) ? $item['conversion_qty'] : $left;
                $hu_id = "HU" . date('dy') . mt_rand(100000, 999999);
                safeQuery($this->pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, po_ref, gr_ref, is_putaway) VALUES (?, 'GR-ZONE', ?, ?, ?, 'Q4', NOW(), ?, ?, 0)", [$item['product_uuid'], $batch_id, $hu_id, $take, $data['po_number'], $gr_num]);
                safeQuery($this->pdo, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at) VALUES ('PUTAWAY', ?, ?, ?, 'GR-ZONE', 'SYSTEM', ?, 'OPEN', NOW())", [$item['product_uuid'], $batch_id, $hu_id, $take]);
                
                safeQuery($this->pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, from_bin, to_bin, qty_change, move_type, user, created_at, reason_code) VALUES (?, ?, ?, ?, NULL, 'GR-ZONE', ?, 'GR_IN', ?, NOW(), NULL)", [$gr_num, $item['product_uuid'], $batch_id, $hu_id, $take, $this->user]);

                $left = round($left - $take, 4);
            }

            if($total_bad > 0) {
                $hu_bad = "DMG" . date('dy') . mt_rand(1000, 9999);
                safeQuery($this->pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, po_ref, gr_ref) VALUES (?, 'BLOCK-ZONE', ?, ?, ?, 'B6', NOW(), ?, ?)", [$item['product_uuid'], $batch_id, $hu_bad, $total_bad, $data['po_number'], $gr_num]);
                
                safeQuery($this->pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, from_bin, to_bin, qty_change, move_type, user, created_at, reason_code) VALUES (?, ?, ?, ?, NULL, 'BLOCK-ZONE', ?, 'GR_BAD', ?, NOW(), NULL)", [$gr_num, $item['product_uuid'], $batch_id, $hu_bad, $total_bad, $this->user]);
            }

            $new_total_received = $received_qty_db + $total_recv;
            if ($new_total_received >= $qty_ordered_db) {
                safeQuery($this->pdo, "UPDATE wms_po_items SET received_qty = ?, status = 'CLOSED' WHERE po_item_id = ?", [$new_total_received, $data['item_id']]);
            } else {
                safeQuery($this->pdo, "UPDATE wms_po_items SET received_qty = ? WHERE po_item_id = ?", [$new_total_received, $data['item_id']]);
            }
            
            $msg = "Posted GR $gr_num. Good: $total_good, Bad: $total_bad.";
            safeQuery($this->pdo, "INSERT INTO wms_inbound_notif (po_number, message, severity, created_at) VALUES (?, ?, 'SUCCESS', NOW())", [$data['po_number'], $msg]);

            $logDesc = "Admin generated GR $gr_num for PO {$data['po_number']} (Item ID: {$data['item_id']}). Qty: $total_recv";
            safeQuery($this->pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) VALUES (?, 'RECEIVING', 'POST_GR', ?, ?, NOW())", [$this->user, $logDesc, $_SERVER['REMOTE_ADDR']]);

            $this->pdo->commit(); return ['status' => 'success', 'gr' => $gr_num];
            
        } catch (Throwable $e) { 
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return ['status' => 'error', 'message' => $e->getMessage()]; 
        }
    }

    public function reverseGR($grNum) {
        try {
            $this->pdo->beginTransaction();
            $items = safeGetAll($this->pdo, "SELECT * FROM wms_gr_items WHERE gr_number = ?", [$grNum]);
            foreach($items as $it) { if($it['qty_actual_good'] > 0) throw new Exception("Operator has already moved this stock."); }
            $poNum = safeGetOne($this->pdo, "SELECT po_number FROM wms_gr_header WHERE gr_number = ?", [$grNum])['po_number'];
            foreach($items as $it) { safeQuery($this->pdo, "UPDATE wms_po_items SET received_qty = received_qty - ?, status = 'OPEN' WHERE po_item_id = ?", [($it['qty_good']+$it['qty_damaged']), $it['po_item_id']]); }
            safeQuery($this->pdo, "UPDATE wms_po_header SET status = 'OPEN' WHERE po_number = ?", [$poNum]);
            safeQuery($this->pdo, "DELETE FROM wms_warehouse_tasks WHERE hu_id IN (SELECT hu_id FROM wms_quants WHERE gr_ref = ?)", [$grNum]);
            
            $quants = safeGetAll($this->pdo, "SELECT * FROM wms_quants WHERE gr_ref = ?", [$grNum]);
            foreach($quants as $q) {
                safeQuery($this->pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, from_bin, to_bin, qty_change, move_type, user, created_at, reason_code) VALUES (?, ?, ?, ?, ?, 'VOID', ?, 'REVERSAL', ?, NOW(), NULL)", [$grNum, $q['product_uuid'], $q['batch'], $q['hu_id'], $q['lgpla'], $q['qty'], $this->user]);
            }

            safeQuery($this->pdo, "DELETE FROM wms_quants WHERE gr_ref = ?", [$grNum]);
            safeQuery($this->pdo, "DELETE FROM wms_gr_items WHERE gr_number = ?", [$grNum]);
            safeQuery($this->pdo, "DELETE FROM wms_gr_header WHERE gr_number = ?", [$grNum]);
            
            $logDesc = "Admin voided GR $grNum for PO $poNum. Stock and tasks wiped.";
            safeQuery($this->pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) VALUES (?, 'RECEIVING', 'VOID_GR', ?, ?, NOW())", [$this->user, $logDesc, $_SERVER['REMOTE_ADDR']]);

            $this->pdo->commit(); return ['status' => 'success', 'message' => "GR $grNum reversed."];
        } catch (Throwable $e) { 
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()]; 
        }
    }
}

$model = new InventoryModel($pdo, $user_id);

// AJAX handlers
if(isset($_POST['action'])) {
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');
    
    try {
        if($_POST['action'] == 'get_auto_batch') { echo json_encode(['status'=>'success', 'batch'=>"BATCH-" . date('ymd') . "-" . str_pad($model->getNextSequence('BATCH_NUM'), 4, '0', STR_PAD_LEFT)]); exit; }
        if($_POST['action'] == 'post_gr') { echo json_encode($model->postGR($_POST)); exit; }
        if($_POST['action'] == 'calc_preview') {
            $item_id = sanitizeInt($_POST['item_id']); 
            $good = isset($_POST['qty_good']) && $_POST['qty_good'] !== '' ? (float)$_POST['qty_good'] : 0; 
            $bad = isset($_POST['qty_bad']) && $_POST['qty_bad'] !== '' ? (float)$_POST['qty_bad'] : 0;
            
            $prod = safeGetOne($pdo, "SELECT p.conversion_qty, p.base_uom, p.capacity_uom FROM wms_products p JOIN wms_po_items i ON i.product_uuid = p.product_uuid WHERE i.po_item_id=?", [$item_id]);
            $conv = (isset($_POST['uom_mode']) && $_POST['uom_mode'] == 'PACK') ? (float)$prod['conversion_qty'] : 1;
            $total = ($good + $bad) * $conv; $preview = []; $left = $good * $conv; $cap = (float)$prod['conversion_qty'] ?: 1;
            while($left > 0.0001) { $qty = ($left >= $cap) ? $cap : $left; $preview[] = ['type'=>$prod['capacity_uom'], 'qty'=>$qty]; $left -= $qty; }
            if($bad > 0) $preview[] = ['type'=>'DAMAGED', 'qty'=>$bad*$conv];
            echo json_encode(['status'=>'success', 'data'=>$preview, 'total_base'=>$total, 'uom'=>$prod['base_uom']]); exit;
        }
        if($_POST['action'] == 'get_gr_hus') {
            $grNum = sanitizeInput($_POST['gr_number']);
            $sql = "SELECT q.hu_id, p.product_code, q.qty, p.base_uom, q.stock_type 
                    FROM wms_quants q 
                    JOIN wms_products p ON q.product_uuid = p.product_uuid 
                    WHERE q.gr_ref = ?";
            $hus = safeGetAll($pdo, $sql, [$grNum]);
            echo json_encode(['status'=>'success', 'data'=>$hus]); exit;
        }
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit;
    }
}

$view_mode = $_GET['view'] ?? 'active';
$selected_po = isset($_GET['po']) ? sanitizeInput($_GET['po']) : '';
$po_list = safeGetAll($pdo, "SELECT * FROM wms_po_header WHERE status = ? ORDER BY expected_date DESC", [($view_mode == 'history' ? 'CLOSED' : 'OPEN')]);
$po_header = $selected_po ? $model->getPOHeader($selected_po) : null;
$po_items = $selected_po ? $model->getPOItems($selected_po) : [];
$po_notifs = $selected_po ? safeGetAll($pdo, "SELECT * FROM wms_inbound_notif WHERE po_number = ? ORDER BY created_at DESC LIMIT 15", [$selected_po]) : [];
$gr_list = $selected_po ? safeGetAll($pdo, "SELECT * FROM wms_gr_header WHERE po_number = ? ORDER BY gr_date DESC", [$selected_po]) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbound Control | V19.17 Print Label</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #4f46e5; --bg: #f8fafc; --sidebar: #ffffff; --card: #ffffff; --text-main: #0f172a; --text-muted: #64748b; }
        body { background-color: var(--bg); color: var(--text-main); font-family: 'Inter', sans-serif; height: 100vh; overflow: hidden; }
        .app-shell { display: flex; height: 100vh; }
        
        .app-sidebar { width: 380px; background: var(--sidebar); border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: relative; }
        .sidebar-brand { padding: 30px; border-bottom: 1px solid #f1f5f9; }
        
        .search-box { position: relative; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; background: #fff; }
        .search-box i { position: absolute; left: 35px; top: 28px; color: var(--text-muted); }
        .search-box input { padding-left: 45px; border-radius: 12px; background: #f1f5f9; border: 1px solid #e2e8f0; height: 45px; width: 100%; font-size: 0.9rem; transition: 0.2s; }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        
        .po-list { flex: 1; overflow-y: auto; padding: 15px; }
        .po-item { padding: 18px; border-radius: 16px; margin-bottom: 12px; cursor: pointer; border: 1px solid transparent; transition: 0.3s; background: #fff; }
        .po-item.active { border-color: var(--primary); background: #eef2ff; }
        
        .app-main { flex: 1; padding: 40px; overflow-y: auto; position: relative; }
        
        .stat-card { background: #fff; border-radius: 20px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); height: 100%; transition: 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .stat-icon { width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        .glass-card { background: var(--card); border-radius: 24px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .main-table-card { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.03); }
        .table thead th { background: #f8fafc; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 700; padding: 20px; border: none; letter-spacing: 1px; }
        .table tbody td { padding: 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .qty-badge { font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 0.9rem; border-radius: 8px; padding: 6px 12px; display: inline-block; }
        .admin-in { color: var(--primary); background: #eef2ff; }
        .op-actual { color: #10b981; background: #ecfdf5; }
        
        .modal-content { border-radius: 32px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { background: #111827; color: #fff; padding: 30px; border: none; }
        .modal-body { padding: 40px; }
        .form-label { font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; }
        .form-control-lg, .form-select-lg { border-radius: 12px; border: 2px solid #f1f5f9; font-size: 1rem; transition: 0.2s; }
        .form-control-lg:focus { border-color: var(--primary); box-shadow: none; background: #fff; }
        .mandatory { color: #ef4444; font-weight: 800; margin-left: 3px; }
        
        .pallet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
        .pallet-card { background: #fff; border: 2px solid var(--primary); border-radius: 12px; padding: 12px; text-align: center; position: relative; overflow: hidden; }
        .pallet-card::before { content: 'HU'; position: absolute; top: -5px; right: -5px; background: var(--primary); color: #fff; font-size: 0.5rem; padding: 5px 10px; transform: rotate(45deg); }
        .pallet-card.DAMAGED { border-color: #ef4444; }
        .pallet-card.DAMAGED::before { background: #ef4444; content: 'DMG'; }
        
        .live-feed { max-height: 400px; overflow-y: auto; }
        .feed-card { padding: 15px; border-radius: 16px; background: #f8fafc; margin-bottom: 12px; border-left: 4px solid var(--primary); }
    </style>
</head>
<body>

<div class="app-shell">
    <div class="app-sidebar">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary p-2 rounded-3 text-white"><i class="bi bi-shield-lock-fill fs-4"></i></div>
                <div><h5 class="fw-bold m-0">Enterprise Inbound</h5><span class="text-muted small">WMS System V19.17</span></div>
            </div>
            <div class="btn-group w-100 mt-4 p-1 bg-light rounded-pill border">
                <a href="?view=active" class="btn btn-sm rounded-pill <?= $view_mode=='active'?'btn-primary shadow-sm fw-bold':'' ?>">Active</a>
                <a href="?view=history" class="btn btn-sm rounded-pill <?= $view_mode=='history'?'btn-warning shadow-sm fw-bold':'' ?>">History</a>
            </div>
        </div>
        
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="poSearch" placeholder="Search PO or Vendor..." onkeyup="filterPOs()">
        </div>

        <div class="po-list" id="poList">
            <?php foreach($po_list as $po): ?>
                <div class="po-item <?= ($selected_po == $po['po_number']) ? 'active' : '' ?>" onclick="window.location.href='?po=<?= $po['po_number'] ?>&view=<?= $view_mode ?>'">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold font-monospace"><?= $po['po_number'] ?></span>
                        <span class="badge bg-light text-dark border"><?= $po['status'] ?></span>
                    </div>
                    <div class="text-muted small text-truncate"><?= $po['vendor_name'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-3 border-top bg-white mt-auto">
            <a href="inbound.php" class="btn btn-outline-secondary w-100 rounded-pill fw-bold py-2 shadow-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Inbound
            </a>
        </div>
    </div>

    <div class="app-main">
        <?php if($selected_po && $po_header): ?>
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h1 class="fw-bold m-0"><?= $selected_po ?></h1>
                    <div class="text-muted mt-1"><i class="bi bi-building me-2"></i><?= $po_header['vendor_name'] ?> &bull; Authorized Gate Station</div>
                </div>
                <button onclick="location.reload()" class="btn btn-white border shadow-sm rounded-circle p-3"><i class="bi bi-arrow-clockwise"></i></button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-layers"></i></div>
                        <div><div class="text-muted small fw-bold text-uppercase">SKU Items</div><h3 class="fw-bold m-0"><?= count($po_items) ?></h3></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-truck-flatbed"></i></div>
                        <div><div class="text-muted small fw-bold text-uppercase">Pending Tasks</div><h3 class="fw-bold m-0"><?= array_sum(array_column($po_items, 'pending_task')) ?></h3></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-all"></i></div>
                        <div><div class="text-muted small fw-bold text-uppercase">PO Status</div><h3 class="fw-bold m-0"><?= $po_header['status'] ?></h3></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-9">
                    <div class="main-table-card">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>SKU & Product</th>
                                    <th class="text-center">Order Qty</th>
                                    <th class="text-center">Admin Scan (G/B)</th>
                                    <th class="text-center">Operator Actual (G/B)</th>
                                    <th class="text-end">Command</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($po_items as $item): 
                                    $is_closed = $item['status'] == 'CLOSED';
                                    $invalid = ($item['qty_ordered'] <= 0); 
                                ?>
                                <tr style="<?= ($is_closed || $invalid) ? 'background-color: #f8fafc;' : '' ?>">
                                    <td><div class="fw-bold text-primary"><?= $item['product_code'] ?></div><div class="small text-muted"><?= $item['description'] ?></div></td>
                                    <td class="text-center fw-bold fs-5"><?= number_format($item['qty_ordered']) ?></td>
                                    <td class="text-center">
                                        <div class="qty-badge admin-in"><?= (float)$item['admin_good'] ?> / <span class="text-danger"><?= (float)$item['admin_bad'] ?></span></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="qty-badge op-actual"><?= (float)$item['op_actual_good'] ?> / <span class="text-danger"><?= (float)$item['op_actual_bad'] ?></span></div>
                                    </td>
                                    <td class="text-end">
                                        <?php if(!$is_closed && !$invalid): ?>
                                            <button class="btn btn-primary rounded-pill fw-bold px-4 shadow-sm" onclick='openModal(<?= json_encode($item) ?>)'>Process</button>
                                        <?php elseif($invalid): ?>
                                            <i class="bi bi-x-circle-fill text-danger fs-5" title="Invalid Qty from External"></i>
                                        <?php else: ?>
                                            <i class="bi bi-check-circle-fill text-success fs-4" title="Fully Received"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="glass-card mt-4 border-primary border-opacity-10 shadow-sm">
                        <h6 class="fw-bold mb-3"><i class="bi bi-ui-checks-grid me-2 text-primary"></i>Generated Goods Receipts</h6>
                        <?php if(empty($gr_list)): ?>
                            <div class="text-muted small">No GR documents generated yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <?php foreach($gr_list as $gr): ?>
                                        <tr>
                                            <td class="fw-bold text-dark align-middle"><?= $gr['gr_number'] ?></td>
                                            <td class="small text-muted align-middle"><?= date('d M Y H:i', strtotime($gr['gr_date'])) ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm me-1" onclick="openLabelSelectModal('<?= $gr['gr_number'] ?>')"><i class="bi bi-upc-scan me-1"></i> Labels</button>
                                                <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" onclick="window.open('print_gr.php?gr_number=<?= $gr['gr_number'] ?>', '_blank')"><i class="bi bi-file-earmark-text me-1"></i> Doc</button>
                                                <button class="btn btn-sm btn-outline-danger px-3 rounded-pill ms-1" onclick="reverseGR('<?= $gr['gr_number'] ?>')"><i class="bi bi-arrow-counterclockwise"></i> Void</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-xl-3">
                    <div class="glass-card">
                        <h6 class="fw-bold mb-4">Live Activity Feed</h6>
                        <div class="live-feed">
                            <?php foreach($po_notifs as $nt): ?>
                                <div class="feed-card <?= $nt['severity'] ?>"><div class="xsmall text-muted mb-1"><?= date('H:i', strtotime($nt['created_at'])) ?></div><div class="small fw-semibold"><?= $nt['message'] ?></div></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="h-100 d-flex flex-column align-items-center justify-content-center opacity-25"><i class="bi bi-box-arrow-in-down display-1 mb-4"></i><h2>Select Document</h2></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="grModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <div><h4 class="fw-bold m-0"><i class="bi bi-box-seam me-2"></i>SKU Reception Protocol</h4><div id="modalSub" class="small opacity-50 font-monospace">SKU-000</div></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="grForm">
                    <input type="hidden" name="action" value="post_gr">
                    <input type="hidden" name="po_number" value="<?= $selected_po ?>">
                    <input type="hidden" name="item_id" id="inp_item_id">

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Batch Identifier (System Gen)</label>
                            <input type="text" name="batch_id" id="inp_batch" class="form-control form-control-lg bg-light border-0 fw-bold font-monospace" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor DO / SJ Number <span class="mandatory">*</span></label>
                            <input type="text" name="vendor_do" class="form-control form-control-lg border-primary border-2" placeholder="e.g. SJ-12345" required>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Packaging Mode</label>
                            <select class="form-select form-select-lg" name="uom_mode" id="inp_uom" onchange="calcPreview()">
                                <option value="BASE">Pieces / Loose</option>
                                <option value="PACK">Bulk / Palletized</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-success">Good Quantity <span class="mandatory">*</span></label>
                            <input type="number" name="qty_good" id="qty_good" class="form-control form-control-lg border-success border-2 fw-bold" placeholder="0" oninput="calcPreview()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-danger">Damaged Quantity</label>
                            <input type="number" name="qty_bad" id="qty_bad" class="form-control form-control-lg border-danger border-2 fw-bold" placeholder="0" oninput="calcPreview()">
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Expiration Date <span class="text-muted small">(Optional)</span></label>
                            <input type="date" name="expiry" class="form-control form-control-lg" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipment Remarks</label>
                            <input type="text" name="remarks" class="form-control form-control-lg" placeholder="Short note for operator...">
                        </div>
                    </div>

                    <div class="p-4 rounded-4 bg-light border border-2 border-dashed">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Palletization Visualization</h6>
                            <div id="totalInfo" class="badge bg-dark">Total: 0 PCS</div>
                        </div>
                        <div id="preview_content" class="pallet-grid text-center text-muted py-3">
                            Awaiting quantity metrics...
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer p-4 border-0">
                <button type="button" class="btn btn-primary w-100 rounded-pill btn-lg fw-bold shadow-lg py-3" onclick="submitForm()">CONFIRM & GENERATE FLOOR TASKS <i class="bi bi-arrow-right-circle ms-2"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="printLabelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-info text-white" style="border-radius: 20px 20px 0 0; border: none;">
                <h5 class="fw-bold m-0"><i class="bi bi-upc-scan me-2"></i> Print HU Labels</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-muted small">Generated Pallets (HU)</span>
                    <button id="btnPrintAll" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold shadow-sm">Print All Labels</button>
                </div>
                <div id="labelListContainer" style="max-height: 300px; overflow-y: auto;">
                    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    try {
        if(localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
    } catch(e) { console.warn("Local storage disabled by browser"); }

    let grModal;
    let labelModal;
    document.addEventListener("DOMContentLoaded", function() { 
        grModal = new bootstrap.Modal(document.getElementById('grModal')); 
        labelModal = new bootstrap.Modal(document.getElementById('printLabelModal')); 
    });

    function openModal(item) {
        document.getElementById('inp_item_id').value = item.po_item_id;
        document.getElementById('modalSub').innerText = item.product_code + " - " + item.description;
        document.getElementById('qty_good').value = '';
        document.getElementById('qty_bad').value = '';
        document.getElementById('preview_content').innerHTML = "Awaiting quantity metrics...";
        fetch('receiving.php', { method: 'POST', body: new URLSearchParams({action: 'get_auto_batch'}) })
            .then(r => r.json()).then(d => document.getElementById('inp_batch').value = d.batch);
        grModal.show();
    }

    function calcPreview() {
        let good = parseFloat(document.getElementById('qty_good').value) || 0;
        let bad = parseFloat(document.getElementById('qty_bad').value) || 0;
        if ((good + bad) <= 0) {
            document.getElementById('preview_content').innerHTML = "Awaiting quantity metrics...";
            document.getElementById('totalInfo').innerText = "Total: 0 PCS";
            return;
        }

        let fd = new FormData(document.getElementById('grForm'));
        fd.set('action', 'calc_preview');
        fetch('receiving.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
            document.getElementById('totalInfo').innerText = `Total: ${d.total_base} ${d.uom}`;
            let h = '';
            d.data.forEach(x => {
                let cls = x.type === 'DAMAGED' ? 'DAMAGED' : '';
                h += `<div class='pallet-card ${cls}'>
                        <div class='small text-muted'>${x.type}</div>
                        <div class='fw-bold'>${x.qty}</div>
                        <div class='xsmall opacity-50'>${d.uom}</div>
                      </div>`;
            });
            document.getElementById('preview_content').innerHTML = h;
        });
    }

    function submitForm() {
        const form = document.getElementById('grForm');
        const fd = new FormData(form);
        
        if(!fd.get('vendor_do')) return Swal.fire('Mandatory Field', 'Vendor Delivery Number (SJ) is required.', 'error');
        if(!fd.get('qty_good') && !fd.get('qty_bad')) return Swal.fire('Input Required', 'Please enter at least one quantity.', 'warning');

        Swal.fire({ title: 'Securing Transaction...', didOpen: () => Swal.showLoading() });
        fetch('receiving.php', { method: 'POST', body: fd })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); } catch(e) { throw new Error("Invalid Server Response. Check Console."); }
        })
        .then(d => {
            if (d.status === 'success') {
                Swal.fire({icon: 'success', title: 'Transaction Secured', text: `GR ${d.gr} Processed.`}).then(() => location.reload());
            } else {
                Swal.fire('Validation Error', d.message, 'error');
            }
        })
        .catch(e => Swal.fire('System Error', e.message, 'error'));
    }

    function reverseGR(grNum) {
        Swal.fire({
            title: 'Reverse Document?',
            text: "This will WIPE ALL staging stock and open tasks. You cannot undo this!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, Void it!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Processing Void...', didOpen: () => Swal.showLoading() });
                let fd = new FormData();
                fd.append('gr_number', grNum);
                fetch('reverse_gr_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') Swal.fire('Voided!', d.message, 'success').then(() => location.reload());
                    else Swal.fire('Reverse Blocked', d.message, 'error');
                }).catch(e => Swal.fire('System Error', 'Check connection', 'error'));
            }
        });
    }

    // 🔥 FIX V19.16: FUNGSI BUKA MODAL LABEL
    function openLabelSelectModal(grNum) {
        document.getElementById('labelListContainer').innerHTML = '<div class="text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Loading HUs...</div>';
        
        // Setup tombol Print All buat GR spesifik ini
        document.getElementById('btnPrintAll').onclick = function() {
            window.open('print_label.php?gr=' + grNum, '_blank');
        };

        let fd = new FormData();
        fd.append('action', 'get_gr_hus');
        fd.append('gr_number', grNum);

        fetch('receiving.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') {
                let html = '';
                if(res.data.length === 0) {
                    html = '<div class="alert alert-light text-center">No Handling Units found for this GR.</div>';
                } else {
                    res.data.forEach(item => {
                        let isBad = item.stock_type === 'B6';
                        let badge = isBad ? '<span class="badge bg-danger ms-2">DMG</span>' : '';
                        html += `
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded mb-2 ${isBad ? 'bg-danger bg-opacity-10' : 'bg-light'}">
                            <div>
                                <div class="fw-bold font-monospace">${item.hu_id} ${badge}</div>
                                <div class="small text-muted">${item.product_code} &bull; Qty: ${parseFloat(item.qty)} ${item.base_uom}</div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm rounded-pill" onclick="window.open('print_label.php?hu=${item.hu_id}', '_blank')">Print</button>
                        </div>`;
                    });
                }
                document.getElementById('labelListContainer').innerHTML = html;
            } else {
                document.getElementById('labelListContainer').innerHTML = '<div class="text-danger">Failed to load data.</div>';
            }
        });

        labelModal.show();
    }

    function filterPOs() {
        let q = document.getElementById('poSearch').value.toUpperCase();
        document.querySelectorAll('.po-item').forEach(el => { el.style.display = el.innerText.toUpperCase().includes(q) ? 'block' : 'none'; });
    }
</script>
</body>
</html>