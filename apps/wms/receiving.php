<?php
// apps/wms/receiving.php
// V8.2: FOCUSED MODE (Single PO View from Inbound) + BROWSER SAFE

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

function sendJson($status, $msg, $data=[]) {
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');
    echo json_encode(array_merge(['status'=>$status, 'message'=>$msg], $data));
    exit;
}

if(!isset($_SESSION['wms_login'])) { 
    if(isset($_POST['action'])) sendJson('error', 'Session Expired');
    else { header("Location: login.php"); exit(); }
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user_id = $_SESSION['wms_fullname'];

// --- HELPER ---
function getNextSequence($pdo, $name) {
    $stmt = $pdo->prepare("SELECT last_val FROM wms_sequences WHERE seq_name = ? FOR UPDATE");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if(!$row) {
        $pdo->prepare("INSERT INTO wms_sequences VALUES (?, 0)")->execute([$name]);
        $next = 1;
    } else {
        $next = $row['last_val'] + 1;
    }
    $pdo->prepare("UPDATE wms_sequences SET last_val = ? WHERE seq_name = ?")->execute([$next, $name]);
    return $next;
}

// --- API ACTIONS ---
if(isset($_POST['action'])) {
    try {
        if($_POST['action'] == 'get_auto_batch') {
            sendJson('success', 'OK', ['batch' => "BATCH-" . date('Ymd') . "-###"]);
        }

        if($_POST['action'] == 'calc_preview') {
            $item_id = sanitizeInt($_POST['item_id']);
            $qty_input = (float)$_POST['qty']; 
            $uom_mode = $_POST['uom_mode']; 

            $item = safeGetOne($pdo, "SELECT i.*, p.conversion_qty, p.base_uom FROM wms_po_items i JOIN wms_products p ON i.product_uuid = p.product_uuid WHERE i.po_item_id = ?", [$item_id]);
            
            $conv = ($uom_mode == 'PACK') ? $item['conversion_qty'] : 1;
            if($conv <= 0) $conv = 1;

            $total = $qty_input * $conv;
            $preview = [];
            $left = $total;
            $cap = ($item['conversion_qty'] > 0) ? $item['conversion_qty'] : 1;
            $limit = 50;

            while($left > 0.0001 && $limit-- > 0) {
                $qty = ($left >= $cap) ? $cap : $left;
                $preview[] = ['type'=>'HU', 'qty'=>$qty, 'uom'=>$item['base_uom']];
                $left -= $qty;
            }
            if($left > 0) $preview[] = ['type'=>'Bulk', 'qty'=>$left, 'uom'=>$item['base_uom']];

            sendJson('success', 'OK', ['data'=>$preview, 'total_base'=>$total, 'uom'=>$item['base_uom']]);
        }

        if($_POST['action'] == 'lock_po') {
            $po_num = sanitizeInput($_POST['po_number']);
            $cek = safeGetOne($pdo, "SELECT is_locked, locked_by FROM wms_po_header WHERE po_number=?", [$po_num]);
            if($cek && $cek['is_locked'] == 1 && $cek['locked_by'] != $user_id) sendJson('error', "Locked by: " . $cek['locked_by']);
            
            safeQuery($pdo, "UPDATE wms_po_header SET is_locked=1, locked_by=?, lock_time=NOW() WHERE po_number=?", [$user_id, $po_num]);
            sendJson('success', 'Locked');
        }

        if($_POST['action'] == 'post_gr') {
            $pdo->beginTransaction();

            $po_num     = sanitizeInput($_POST['po_number']);
            $item_id    = sanitizeInt($_POST['item_id']);
            $qty_good   = (float)$_POST['qty_good'];
            $qty_bad    = (float)$_POST['qty_bad'];
            $vendor_do  = sanitizeInput($_POST['vendor_do']);
            $uom_mode   = $_POST['uom_mode']; 
            $expiry     = !empty($_POST['expiry']) ? $_POST['expiry'] : NULL;

            if(($qty_good + $qty_bad) <= 0) throw new Exception("Qty cannot be 0");
            if($qty_good < 0 || $qty_bad < 0) throw new Exception("Negative Qty!");

            // Lock Header & Item
            $head = safeGetOne($pdo, "SELECT status FROM wms_po_header WHERE po_number = ? FOR UPDATE", [$po_num]);
            if(!$head || $head['status'] != 'OPEN') throw new Exception("PO Closed");

            $sql_item = "SELECT i.*, p.conversion_qty, p.base_uom, p.capacity_uom, COALESCE(i.qc_required, 0) as qc_rule 
                         FROM wms_po_items i JOIN wms_products p ON i.product_uuid = p.product_uuid 
                         WHERE i.po_item_id = ? FOR UPDATE";
            $item = safeGetOne($pdo, $sql_item, [$item_id]);

            if(!$item || $item['po_number'] !== $po_num) throw new Exception("Invalid Item");

            // Calc
            $conv = ($uom_mode == 'PACK') ? $item['conversion_qty'] : 1;
            if($conv <= 0) $conv = 1;
            $total_good = $qty_good * $conv;
            $total_bad = $qty_bad * $conv;
            $total_recv = $total_good + $total_bad;

            // Tolerance
            $max = ($item['qty_ordered'] - $item['received_qty']) * (1 + $item['tolerance_pct']/100);
            if($total_recv > $max) throw new Exception("Over Delivery!");

            // Execution
            $seq_gr = getNextSequence($pdo, 'GR_NUM');
            $gr_num = "GR-" . date('ymd') . "-" . str_pad($seq_gr, 6, '0', STR_PAD_LEFT);
            
            $seq_batch = getNextSequence($pdo, 'BATCH_NUM');
            $batch_final = "BATCH-" . date('ymd') . "-" . str_pad($seq_batch, 4, '0', STR_PAD_LEFT);

            safeQuery($pdo, "INSERT INTO wms_gr_header (gr_number, po_number, vendor_do, received_by, status) VALUES (?,?,?,?,'POSTED')", [$gr_num, $po_num, $vendor_do, $user_id]);

            // Good Stock
            $status_good = ($item['qc_rule'] == 1) ? 'Q4' : 'F1';
            $left = round($total_good, 4);
            $cap = ($item['conversion_qty'] > 0) ? $item['conversion_qty'] : 1;
            $guard = 0;

            while($left > 0.0001) {
                if($guard++ > 1000) throw new Exception("Loop Limit");
                $qty = ($left >= $cap) ? $cap : $left;
                $hu = "HU" . date('dy') . mt_rand(100000,999999);

                safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, expiry_date, po_ref, gr_ref) VALUES (?, 'GR-ZONE', ?, ?, ?, ?, NOW(), ?, ?, ?)", [$item['product_uuid'], $batch_final, $hu, $qty, $status_good, $expiry, $po_num, $gr_num]);
                safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, ?, ?, 'GR_IN', ?)", [$gr_num, $item['product_uuid'], $batch_final, $hu, $qty, $user_id]);
                safeQuery($pdo, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at) VALUES ('PUTAWAY', ?, ?, ?, 'GR-ZONE', ?, ?, 'OPEN', NOW())", [$item['product_uuid'], $batch_final, $hu, ($status_good=='Q4'?'QC-AREA':'SYSTEM'), $qty]);

                $left = round($left - $qty, 4);
            }

            // Bad Stock
            if($total_bad > 0) {
                $hu_bad = "DMG" . date('dy') . mt_rand(1000,9999);
                safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, po_ref, gr_ref) VALUES (?, 'GR-ZONE', ?, ?, ?, 'B6', NOW(), ?, ?)", [$item['product_uuid'], $batch_final, $hu_bad, $total_bad, $po_num, $gr_num]);
                safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, batch, hu_id, qty_change, move_type, user) VALUES (?, ?, ?, ?, ?, 'GR_BAD', ?)", [$gr_num, $item['product_uuid'], $batch_final, $hu_bad, $total_bad, $user_id]);
            }

            // Update PO
            $new_recv = $item['received_qty'] + $total_recv;
            $new_stat = ($new_recv >= $item['qty_ordered']) ? 'CLOSED' : 'OPEN';
            safeQuery($pdo, "UPDATE wms_po_items SET received_qty=?, status=? WHERE po_item_id=?", [$new_recv, $new_stat, $item_id]);

            // Close Header
            $chk = safeGetOne($pdo, "SELECT count(*) as c FROM wms_po_items WHERE po_number=? AND status='OPEN'", [$po_num]);
            $po_stat = 'OPEN';
            if($chk['c'] == 0) {
                safeQuery($pdo, "UPDATE wms_po_header SET status='CLOSED', is_locked=0 WHERE po_number=?", [$po_num]);
                $po_stat = 'CLOSED';
            }

            safeQuery($pdo, "INSERT INTO wms_gr_items (gr_number, po_item_id, product_uuid, batch_no, expiry_date, qty_good, qty_damaged) VALUES (?,?,?,?,?,?,?)", [$gr_num, $item_id, $item['product_uuid'], $batch_final, $expiry, $total_good, $total_bad]);

            $pdo->commit();
            sendJson('success', 'GR Posted', ['gr_number'=>$gr_num, 'batch_generated'=>$batch_final, 'po_status'=>$po_stat]);
        }

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        sendJson('error', $e->getMessage());
    }
}

// ==================================================================================
// 🖥️ VIEW LOGIC (FOCUSED MODE)
// ==================================================================================
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'active';
$status_filter = ($view_mode == 'history') ? 'CLOSED' : 'OPEN';
$selected_po = isset($_GET['po']) ? sanitizeInput($_GET['po']) : '';

// 🔥 LOGIC FILTER SIDEBAR:
// Jika ada PO tertentu (dari Inbound), TAMPILKAN ITU SAJA.
// Jika tidak ada, tampilkan semua sesuai filter status.
if($selected_po) {
    $po_list = safeGetAll($pdo, "SELECT * FROM wms_po_header WHERE po_number = ?", [$selected_po]);
} else {
    $po_list = safeGetAll($pdo, "SELECT * FROM wms_po_header WHERE status = ? ORDER BY expected_date DESC", [$status_filter]);
}

$po_items = []; 
$po_header = null;

if($selected_po) {
    $po_header = safeGetOne($pdo, "SELECT * FROM wms_po_header WHERE po_number = ?", [$selected_po]);
    $po_items = safeGetAll($pdo, "SELECT i.*, p.product_code, p.description, p.base_uom, p.capacity_uom, p.conversion_qty, i.qc_required FROM wms_po_items i JOIN wms_products p ON i.product_uuid = p.product_uuid WHERE i.po_number = ?", [$selected_po]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbound Workstation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #4f46e5; --bg: #f3f4f6; --sidebar: #ffffff; --text: #1f2937; }
        body { background-color: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; height: 100vh; overflow: hidden; }
        .layout { display: flex; height: 100vh; }
        .sidebar { width: 320px; background: var(--sidebar); border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #e5e7eb; }
        .po-list { overflow-y: auto; flex: 1; }
        .main { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; }
        
        .po-card { padding: 15px 20px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: 0.2s; }
        .po-card:hover { background: #f9fafb; }
        .po-card.active { background: #eef2ff; border-left: 4px solid var(--primary); }
        
        .header-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
        .table thead th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; color: #6b7280; padding: 12px 20px; }
        .table tbody td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
        
        .btn-receive { background: var(--primary); color: white; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .btn-receive:hover { background: #4338ca; color: white; }
    </style>
</head>
<body>

<div class="layout">
    <div class="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold mb-0"><i class="bi bi-box-seam-fill text-primary me-2"></i>Receiving</h5>
            <?php if(!$selected_po): ?>
                <div class="mt-3 d-flex gap-2">
                    <a href="?view=active" class="btn btn-sm btn-light w-50 <?= $view_mode=='active'?'active border-primary':'' ?>">Active</a>
                    <a href="?view=history" class="btn btn-sm btn-light w-50 <?= $view_mode=='history'?'active border-primary':'' ?>">History</a>
                </div>
            <?php else: ?>
                <div class="mt-2 text-muted small"><i class="bi bi-funnel-fill"></i> Filtered by Selection</div>
            <?php endif; ?>
        </div>
        
        <div class="po-list">
            <?php foreach($po_list as $po): ?>
                <div class="po-card <?= ($selected_po == $po['po_number']) ? 'active' : '' ?>" onclick="window.location.href='?po=<?= $po['po_number'] ?>'">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold"><?= $po['po_number'] ?></h6>
                        <span class="badge <?= $po['status']=='OPEN'?'bg-success-subtle text-success':'bg-secondary-subtle text-secondary' ?>"><?= $po['status'] ?></span>
                    </div>
                    <div class="small text-muted mt-1"><?= substr($po['vendor_name'],0,25) ?>...</div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-3 border-top bg-white">
            <?php if($selected_po): ?>
                <a href="receiving.php" class="btn btn-outline-secondary w-100 btn-sm mb-2">Show All POs</a>
            <?php endif; ?>
            <a href="inbound.php" class="btn btn-light w-100 btn-sm fw-bold text-muted">Back to Dashboard</a>
        </div>
    </div>

    <div class="main">
        <?php if($selected_po && $po_header): ?>
            <div class="header-card d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1"><?= $po_header['po_number'] ?></h4>
                    <p class="text-muted mb-0 small"><?= $po_header['vendor_name'] ?> &bull; <?= $po_header['po_type'] ?></p>
                </div>
                <div>
                    <span class="badge bg-light text-dark border fs-6"><?= $po_header['status'] ?></span>
                </div>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th class="ps-4">Item</th><th class="text-center">Ord</th><th class="text-center">Recv</th><th class="text-center">Bal</th><th class="text-end pe-4">Action</th></tr></thead>
                        <tbody>
                            <?php foreach($po_items as $item): 
                                $bal = $item['qty_ordered'] - $item['received_qty'];
                                $is_closed = $item['status'] == 'CLOSED';
                            ?>
                            <tr class="<?= $is_closed ? 'bg-light opacity-50' : '' ?>">
                                <td class="ps-4">
                                    <div class="fw-bold"><?= $item['product_code'] ?></div>
                                    <div class="small text-muted"><?= $item['description'] ?></div>
                                </td>
                                <td class="text-center fw-bold"><?= number_format($item['qty_ordered']) ?></td>
                                <td class="text-center"><?= number_format($item['received_qty']) ?></td>
                                <td class="text-center fw-bold text-primary"><?= number_format($bal) ?></td>
                                <td class="text-end pe-4">
                                    <?php if(!$is_closed && $po_header['status'] == 'OPEN'): ?>
                                        <button class="btn-receive" onclick='openModal(<?= json_encode($item) ?>)'>Receive</button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border"><i class="bi bi-check-lg"></i> Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-center text-muted">
                <div>
                    <i class="bi bi-arrow-left-square fs-1"></i>
                    <p class="mt-2">Select a Purchase Order to begin receiving.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="grModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Input Receiving</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="grForm">
                    <input type="hidden" name="action" value="post_gr">
                    <input type="hidden" name="po_number" value="<?= $selected_po ?>">
                    <input type="hidden" name="item_id" id="inp_item_id">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label small fw-bold">Batch ID</label><input class="form-control bg-light" id="inp_batch" readonly></div>
                        <div class="col-6"><label class="form-label small fw-bold">Vendor DO</label><input class="form-control" name="vendor_do" required></div>
                        <div class="col-6"><label class="form-label small fw-bold">Unit</label><select class="form-select" name="uom_mode" id="inp_uom" onchange="calcPreview()"><option value="BASE">Pcs</option><option value="PACK">Pallet</option></select></div>
                        <div class="col-6"><label class="form-label small fw-bold">Expiry</label><input type="date" class="form-control" name="expiry"></div>
                        <div class="col-6"><label class="form-label small fw-bold text-success">Good Qty</label><input type="number" class="form-control fw-bold" name="qty_good" id="qty_good" placeholder="0" oninput="calcPreview()"></div>
                        <div class="col-6"><label class="form-label small fw-bold text-danger">Bad Qty</label><input type="number" class="form-control fw-bold" name="qty_bad" id="qty_bad" placeholder="0" oninput="calcPreview()"></div>
                        <div class="col-12"><div class="p-3 bg-light rounded border border-dashed text-center small text-muted" id="preview_box">Enter quantity to see handling units...</div></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary w-100 fw-bold" onclick="submitForm()">Confirm & Post</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalElement = document.getElementById('grModal');
const modal = new bootstrap.Modal(modalElement, { focus: false, keyboard: false });
let currentItem = null;

function openModal(item) {
    currentItem = item;
    document.getElementById('inp_item_id').value = item.po_item_id;
    document.getElementById('inp_batch').value = "Generating...";
    
    let fd = new FormData(); fd.append('action', 'get_auto_batch');
    fetch('receiving.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
        if(d.status=='success') document.getElementById('inp_batch').value = d.batch;
    });
    modal.show();
}

function calcPreview() {
    let good = parseFloat(document.getElementById('qty_good').value)||0;
    let mode = document.getElementById('inp_uom').value;
    if(good<=0) { document.getElementById('preview_box').innerText='Enter quantity...'; return; }
    
    let fd = new FormData(); 
    fd.append('action', 'calc_preview'); fd.append('item_id', currentItem.po_item_id);
    fd.append('qty', good); fd.append('uom_mode', mode);
    
    fetch('receiving.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
        let h = `<div class="fw-bold mb-1 text-dark">Total: ${d.total_base} ${d.uom}</div>`;
        d.data.forEach(x => h+=`<span class="badge bg-primary me-1 mb-1">${x.qty} ${x.uom}</span>`);
        document.getElementById('preview_box').innerHTML = h;
    });
}

function submitForm() {
    const fd = new FormData(document.getElementById('grForm'));
    if(!fd.get('vendor_do')) return Swal.fire('Error', 'Vendor DO required', 'warning');
    if(parseFloat(fd.get('qty_good')) <= 0 && parseFloat(fd.get('qty_bad')) <= 0) return Swal.fire('Error', 'Input Qty', 'warning');
    
    Swal.fire({title:'Processing...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
    
    fetch('receiving.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
        if(d.status=='success') {
            modal.hide();
            Swal.fire({
                icon:'success', title:'Success', html:`GR: <b>${d.gr_number}</b>`, 
                confirmButtonText:'Print GR', showCancelButton:true
            }).then(r=>{ 
                if(r.isConfirmed) window.open('print_gr.php?gr='+d.gr_number, '_blank');
                location.reload(); 
            });
        } else {
            Swal.fire('Error', d.message, 'error');
        }
    }).catch(e => Swal.fire('System Error', 'Check console', 'error'));
}
</script>
</body>
</html>