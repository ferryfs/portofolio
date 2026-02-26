<?php
// apps/wms/internal.php
// V11: ENTERPRISE INTERNAL MOVEMENTS (With Reason Codes, Double-Entry Audit, and System Logs)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$msg = ""; $alert = ""; $user = $_SESSION['wms_fullname'];

// =========================================================================
// A. SMART BIN TRANSFER (Bin-to-Bin Move)
// =========================================================================
if(isset($_POST['post_transfer'])) {
    $qid = sanitizeInt($_POST['quant_id']);
    $bin = strtoupper(sanitizeInput($_POST['dest_bin']));
    $qty_move = (float)$_POST['qty_move'];
    $remarks = sanitizeInput($_POST['tf_remarks'] ?? 'No reason provided'); // 🔥 Fitur Baru Enterprise

    try {
        $pdo->beginTransaction();
        
        // 1. Lock Stock Asal (Pessimistic Lock)
        $src = safeGetOne($pdo, "SELECT q.*, p.product_code FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE q.quant_id=? FOR UPDATE", [$qid]);
        if(!$src) throw new Exception("Stock not found or already moved by another user.");
        if($qty_move <= 0 || $qty_move > $src['qty']) throw new Exception("Invalid quantity to move! Max: {$src['qty']}");
        if($src['lgpla'] == $bin) throw new Exception("Destination bin is the same as the source bin.");

        // 2. Validasi Rak Tujuan
        $bin_check = safeGetOne($pdo, "SELECT 1 FROM wms_storage_bins WHERE lgpla=?", [$bin]);
        if(!$bin_check) throw new Exception("Bin '$bin' does not exist in Master Data!");

        $target_hu = $src['hu_id']; // Default HU

        // 3. Eksekusi Transfer (Partial vs Full)
        if($qty_move == $src['qty']) {
            // FULL MOVE: Pindah seluruh isi Pallet
            safeQuery($pdo, "UPDATE wms_quants SET lgpla=? WHERE quant_id=?", [$bin, $qid]);
        } else {
            // PARTIAL MOVE: Pecah Pallet, Bikin Pallet (HU) anak baru di rak tujuan
            $sisa = $src['qty'] - $qty_move;
            safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$sisa, $qid]);
            
            $target_hu = "SPL-" . $src['hu_id'] . "-" . mt_rand(10,99);
            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, is_putaway) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 1)", 
                             [$src['product_uuid'], $bin, $src['batch'], $target_hu, $qty_move, $src['stock_type'], $src['gr_date']]);
        }

        // 4. Update Bin Status
        safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='OCCUPIED' WHERE lgpla=?", [$bin]);
        $cek_sisa = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_quants WHERE lgpla=?", [$src['lgpla']]);
        if($cek_sisa['c'] == 0) {
            safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='EMPTY' WHERE lgpla=?", [$src['lgpla']]);
        }

        // 🔥 5. AUDIT FISIK (DOUBLE ENTRY SYSTEM LOGIC)
        $trx_ref = "INT-" . date('ymdHis');
        
        // Log Keluar dari Rak Asal (Minus)
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, to_bin, reason_code) 
                         VALUES (?, ?, ?, ?, 'BIN_OUT', ?, ?, NULL, ?)", 
                         [$trx_ref, $src['product_uuid'], $src['hu_id'], -$qty_move, $user, $src['lgpla'], $remarks]);
                         
        // Log Masuk ke Rak Tujuan (Plus)
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, to_bin, reason_code) 
                         VALUES (?, ?, ?, ?, 'BIN_IN', ?, NULL, ?, ?)", 
                         [$trx_ref, $src['product_uuid'], $target_hu, $qty_move, $user, $bin, $remarks]);

        // 🔥 6. AUDIT IT (SYSTEM LOG)
        $sys_desc = "BIN TRANSFER: Moved $qty_move of {$src['product_code']} from {$src['lgpla']} to $bin. Remarks: $remarks";
        safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) 
                         VALUES (?, 'INTERNAL', 'BIN_TRANSFER', ?, ?, NOW())", [$user, $sys_desc, $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        $msg = "✅ Success: Moved <b>$qty_move</b> items to <b>$bin</b>."; $alert = "success";

    } catch(Exception $e) { 
        $pdo->rollBack(); 
        $msg = "⛔ " . $e->getMessage(); $alert="danger"; 
    }
}

// =========================================================================
// B. QUALITY STATUS CHANGE (QC Hold / Release)
// =========================================================================
if(isset($_POST['post_status'])) {
    $qid = sanitizeInt($_POST['quant_id_stat']);
    $stat = sanitizeInput($_POST['new_status']);
    $remarks = sanitizeInput($_POST['qc_remarks'] ?? 'No reason provided'); // 🔥 Fitur Baru Enterprise
    
    try {
        $pdo->beginTransaction();
        
        $old = safeGetOne($pdo, "SELECT q.*, p.product_code FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE q.quant_id=? FOR UPDATE", [$qid]);
        if(!$old) throw new Exception("Stock not found!");
        if($old['stock_type'] == $stat) throw new Exception("Status is already $stat.");

        $old_stat = $old['stock_type'];

        // 1. Eksekusi Update Status
        safeQuery($pdo, "UPDATE wms_quants SET stock_type=? WHERE quant_id=?", [$stat, $qid]);
        
        // 🔥 2. AUDIT FISIK (STATUS CHANGE)
        $trx_ref = "QC-" . date('ymdHis');
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, reason_code) 
                         VALUES (?, ?, ?, ?, 'STAT_CHG', ?, ?, ?)", 
                         [$trx_ref, $old['product_uuid'], $old['hu_id'], 0, $user, $old['lgpla'], "From $old_stat to $stat: $remarks"]);

        // 🔥 3. AUDIT IT (SYSTEM LOG)
        $sys_desc = "QC STATUS: Changed {$old['hu_id']} ({$old['product_code']}) from $old_stat to $stat. Remarks: $remarks";
        safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) 
                         VALUES (?, 'INTERNAL', 'STATUS_CHANGE', ?, ?, NOW())", [$user, $sys_desc, $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        $msg = "✅ Success: Status of Pallet <b>{$old['hu_id']}</b> updated to <b>$stat</b>."; $alert = "warning";

    } catch(Exception $e) { 
        $pdo->rollBack(); 
        $msg = "⛔ " . $e->getMessage(); $alert="danger"; 
    }
}

// LOAD LIST STOK (Untuk Dropdown) - Hanya yg ada fisiknya (>0)
$stok_list = safeGetAll($pdo, "SELECT q.quant_id, q.hu_id, q.lgpla, q.stock_type, q.qty, p.product_code 
                               FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid 
                               WHERE q.qty > 0 AND q.lgpla NOT IN ('SYSTEM')
                               ORDER BY q.lgpla ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internal Operations | WMS Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { background: #f8fafc; font-family: 'Inter', sans-serif;} 
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden;} 
        .card-header { padding: 20px 25px; font-weight: 700; border-bottom: 1px solid #e2e8f0; font-size: 1.1rem;}
        .form-control, .form-select { border-radius: 12px; padding: 12px 15px; border-color: #e2e8f0; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(79,70,229,0.1); border-color: #4f46e5; }
        .btn-custom { padding: 12px; font-weight: 700; letter-spacing: 0.5px; border-radius: 12px; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-arrow-left-right text-primary me-2"></i>Internal Operations</h3>
            <p class="text-muted m-0">Bin-to-bin transfers and Quality Control adjustments</p>
        </div>
        <a href="stock_master.php" class="btn btn-white border rounded-pill px-4 fw-bold shadow-sm hover-bg-light">Back to Inventory</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $alert ?> fw-bold shadow-sm rounded-4 mb-4 border-0 d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-3 fs-5"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card h-100 border-top border-primary border-4">
                <div class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam text-primary me-2"></i> Bin Transfer</span>
                    <span class="badge bg-light text-muted border">Movement</span>
                </div>
                <div class="card-body p-4 bg-white">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Source Pallet (Handling Unit)</label>
                            <select name="quant_id" id="sel_transfer" class="form-select bg-light" required onchange="updateMaxQty()">
                                <option value="" data-qty="0">-- Select Physical Stock --</option>
                                <?php foreach($stok_list as $r): ?>
                                    <option value="<?= $r['quant_id'] ?>" data-qty="<?= (float)$r['qty'] ?>">
                                        [<?= $r['lgpla'] ?>] <?= $r['product_code'] ?> &bull; <?= (float)$r['qty'] ?> Units (<?= $r['stock_type'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-sm-5">
                                <label class="form-label fw-bold small text-muted text-uppercase">Transfer Qty</label>
                                <input type="number" name="qty_move" id="qty_move" class="form-control fw-bold text-primary" placeholder="0" step="0.01" required>
                                <div class="form-text small" id="max_hint">Select source first</div>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label fw-bold small text-muted text-uppercase">Destination Bin</label>
                                <input type="text" name="dest_bin" class="form-control text-uppercase fw-bold" placeholder="e.g. A-02-05" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Reason for Transfer <span class="text-danger">*</span></label>
                            <input type="text" name="tf_remarks" class="form-control" placeholder="e.g. Consolidation, Racking damaged..." required>
                        </div>

                        <button type="submit" name="post_transfer" class="btn btn-primary w-100 btn-custom shadow-sm">EXECUTE TRANSFER <i class="bi bi-arrow-right ms-2"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-top border-warning border-4">
                <div class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-check text-warning me-2"></i> Quality Status</span>
                    <span class="badge bg-light text-muted border">Adjustment</span>
                </div>
                <div class="card-body p-4 bg-white">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Pallet for QC</label>
                            <select name="quant_id_stat" class="form-select bg-light" required>
                                <option value="">-- Select Physical Stock --</option>
                                <?php foreach($stok_list as $r): ?>
                                    <option value="<?= $r['quant_id'] ?>">
                                        [<?= $r['lgpla'] ?>] <?= $r['hu_id'] ?> (<?= $r['product_code'] ?>) &bull; <?= $r['stock_type'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">New Target Status</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="new_status" id="s1" value="F1" required>
                                <label class="btn btn-outline-success w-100 fw-bold" for="s1">F1 <br><small class="fw-normal">Unrestricted</small></label>

                                <input type="radio" class="btn-check" name="new_status" id="s2" value="Q4">
                                <label class="btn btn-outline-warning w-100 fw-bold" for="s2">Q4 <br><small class="fw-normal">In Quality</small></label>

                                <input type="radio" class="btn-check" name="new_status" id="s3" value="B6">
                                <label class="btn btn-outline-danger w-100 fw-bold" for="s3">B6 <br><small class="fw-normal">Blocked</small></label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">QC Findings / Remarks <span class="text-danger">*</span></label>
                            <input type="text" name="qc_remarks" class="form-control" placeholder="e.g. Water damage, Passed visual inspection..." required>
                        </div>

                        <button type="submit" name="post_status" class="btn btn-warning w-100 btn-custom shadow-sm text-dark">UPDATE PALLET STATUS <i class="bi bi-check2-circle ms-2"></i></button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Auto-fill max qty & feedback for partial transfer
function updateMaxQty() {
    let sel = document.getElementById('sel_transfer');
    let maxQty = sel.options[sel.selectedIndex].getAttribute('data-qty');
    let inputQty = document.getElementById('qty_move');
    let hint = document.getElementById('max_hint');
    
    if(maxQty > 0) {
        inputQty.max = maxQty;
        inputQty.value = maxQty;
        hint.innerHTML = `<span class="text-success fw-bold"><i class="bi bi-info-circle"></i> Max allowed: ${maxQty}</span>`;
    } else {
        inputQty.value = "";
        hint.innerHTML = "Select source first";
    }
}
</script>

</body>
</html>