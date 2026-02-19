<?php
// apps/wms/internal.php
// V10: SMART INTERNAL MOVEMENTS (With Bin Validation & Partial Split)

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
// A. SMART BIN TRANSFER (Full / Partial Move)
// =========================================================================
if(isset($_POST['post_transfer'])) {
    $qid = sanitizeInt($_POST['quant_id']);
    $bin = strtoupper(sanitizeInput($_POST['dest_bin']));
    $qty_move = (float)$_POST['qty_move']; // Tambahan: Berapa banyak yang mau dipindah?

    try {
        $pdo->beginTransaction();
        
        // 1. Lock Stock Asal
        $src = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=? FOR UPDATE", [$qid]);
        if(!$src) throw new Exception("Stock not found or already moved.");
        if($qty_move <= 0 || $qty_move > $src['qty']) throw new Exception("Invalid quantity to move! Max: {$src['qty']}");
        if($src['lgpla'] == $bin) throw new Exception("Destination bin is same as source bin.");

        // 2. Validasi Rak Tujuan
        $bin_check = safeGetOne($pdo, "SELECT 1 FROM wms_storage_bins WHERE lgpla=?", [$bin]);
        if(!$bin_check) throw new Exception("Bin '$bin' does not exist in Master Data!");

        // 3. Eksekusi Transfer (Partial vs Full)
        if($qty_move == $src['qty']) {
            // FULL MOVE: Langsung ganti alamat raknya aja
            safeQuery($pdo, "UPDATE wms_quants SET lgpla=? WHERE quant_id=?", [$bin, $qid]);
        } else {
            // PARTIAL MOVE (SPLIT): Kurangi asal, Bikin baris baru di tujuan
            $sisa = $src['qty'] - $qty_move;
            safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$sisa, $qid]);
            
            // Bikin HU baru (karena dipecah)
            $new_hu = "SPL-" . $src['hu_id'] . "-" . mt_rand(10,99);
            safeQuery($pdo, "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, stock_type, gr_date, is_putaway) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 1)", 
                             [$src['product_uuid'], $bin, $src['batch'], $new_hu, $qty_move, $src['stock_type'], $src['gr_date']]);
        }

        // 4. Update Bin Status Logic
        // Rak Tujuan -> OCCUPIED
        safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='OCCUPIED' WHERE lgpla=?", [$bin]);
        
        // Rak Asal -> Cek apakah masih ada barang lain? Kalau kosong, set EMPTY
        $cek_sisa = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_quants WHERE lgpla=?", [$src['lgpla']]);
        if($cek_sisa['c'] == 0) {
            safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='EMPTY' WHERE lgpla=?", [$src['lgpla']]);
        }

        // 5. Audit Trail
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin, to_bin) 
                         VALUES (?, ?, ?, ?, 'BIN_MOVE', ?, ?, ?)", 
                         ["INT_TF", $src['product_uuid'], $src['hu_id'], $qty_move, $user, $src['lgpla'], $bin]);

        $pdo->commit();
        $msg = "✅ Moved <b>$qty_move</b> items to <b>$bin</b> successfully."; $alert = "success";

    } catch(Exception $e) { 
        $pdo->rollBack(); 
        $msg = "⛔ " . $e->getMessage(); $alert="danger"; 
    }
}

// =========================================================================
// B. QC STATUS CHANGE
// =========================================================================
if(isset($_POST['post_status'])) {
    $qid = sanitizeInt($_POST['quant_id_stat']);
    $stat = sanitizeInput($_POST['new_status']);
    
    try {
        $pdo->beginTransaction();
        
        $old = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=? FOR UPDATE", [$qid]);
        if(!$old) throw new Exception("Stock not found!");
        if($old['stock_type'] == $stat) throw new Exception("Status is already $stat.");

        // Update Status
        safeQuery($pdo, "UPDATE wms_quants SET stock_type=? WHERE quant_id=?", [$stat, $qid]);
        
        // Audit Trail
        safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin) 
                         VALUES (?, ?, ?, ?, 'STAT_CHG', ?, ?)", 
                         ["STAT-TO-$stat", $old['product_uuid'], $old['hu_id'], 0, $user, $old['lgpla']]);

        $pdo->commit();
        $msg = "✅ Status of HU <b>{$old['hu_id']}</b> changed to <b>$stat</b>."; $alert = "warning";

    } catch(Exception $e) { 
        $pdo->rollBack(); 
        $msg = "⛔ " . $e->getMessage(); $alert="danger"; 
    }
}

// LOAD LIST STOK (Untuk Dropdown)
$stok_list = safeGetAll($pdo, "SELECT q.quant_id, q.hu_id, q.lgpla, q.stock_type, q.qty, p.product_code 
                               FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid 
                               ORDER BY q.lgpla ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internal Operations | WMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style> 
        body { background: #f8fafc; font-family: system-ui, sans-serif;} 
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden;} 
        .card-header { padding: 15px 20px; font-weight: bold; border-bottom: 0;}
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="bi bi-arrows-move me-2 text-primary"></i>Internal Operations</h4>
            <p class="text-muted m-0 small">Transfer stock and update quality status</p>
        </div>
        <a href="stock_master.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">Back to Master</a>
    </div>

    <?php if($msg): ?><div class="alert alert-<?= $alert ?> fw-bold shadow-sm rounded-4"><?= $msg ?></div><?php endif; ?>

    <div class="row g-4">
        
        <div class="col-md-6">
            <div class="card h-100 border border-primary border-opacity-25">
                <div class="card-header bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-box-seam me-2"></i> Smart Bin Transfer
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Source Stock (Handling Unit)</label>
                            <select name="quant_id" id="sel_transfer" class="form-select border-primary" required onchange="updateMaxQty()">
                                <option value="" data-qty="0">-- Select Stock --</option>
                                <?php foreach($stok_list as $r): ?>
                                    <option value="<?= $r['quant_id'] ?>" data-qty="<?= (float)$r['qty'] ?>">
                                        [<?= $r['lgpla'] ?>] <?= $r['product_code'] ?> (<?= (float)$r['qty'] ?> Pcs)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">Qty to Move</label>
                                <input type="number" name="qty_move" id="qty_move" class="form-control fw-bold" placeholder="0" step="0.01" required>
                                <div class="form-text small text-primary" id="max_hint">Select item first</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">Destination Bin</label>
                                <input type="text" name="dest_bin" class="form-control text-uppercase fw-bold border-primary" placeholder="e.g. A-01-02" required>
                            </div>
                        </div>
                        <button type="submit" name="post_transfer" class="btn btn-primary w-100 fw-bold rounded-pill">Execute Transfer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border border-warning border-opacity-50">
                <div class="card-header bg-warning bg-opacity-10 text-dark">
                    <i class="bi bi-shield-check me-2 text-warning"></i> Quality Control (QC) Change
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Select Target Stock</label>
                            <select name="quant_id_stat" class="form-select border-warning" required>
                                <option value="">-- Select Stock --</option>
                                <?php foreach($stok_list as $r): ?>
                                    <option value="<?= $r['quant_id'] ?>">
                                        [<?= $r['lgpla'] ?>] <?= $r['product_code'] ?> (Curr: <?= $r['stock_type'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">New Status Decision</label>
                            <div class="btn-group w-100 shadow-sm" role="group">
                                <input type="radio" class="btn-check" name="new_status" id="s1" value="F1" checked>
                                <label class="btn btn-outline-success fw-bold" for="s1">F1 (Good)</label>

                                <input type="radio" class="btn-check" name="new_status" id="s2" value="Q4">
                                <label class="btn btn-outline-warning text-dark fw-bold" for="s2">Q4 (QC)</label>

                                <input type="radio" class="btn-check" name="new_status" id="s3" value="B6">
                                <label class="btn btn-outline-danger fw-bold" for="s3">B6 (Block)</label>
                            </div>
                        </div>
                        <button type="submit" name="post_status" class="btn btn-warning w-100 fw-bold rounded-pill shadow-sm">Update Status</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Dynamic Script biar gak bisa masukin Qty melebihi stok
function updateMaxQty() {
    let sel = document.getElementById('sel_transfer');
    let maxQty = sel.options[sel.selectedIndex].getAttribute('data-qty');
    let inputQty = document.getElementById('qty_move');
    
    if(maxQty > 0) {
        inputQty.max = maxQty;
        inputQty.value = maxQty; // Default auto fill max
        document.getElementById('max_hint').innerText = "Max allowed: " + maxQty;
    } else {
        inputQty.value = "";
        document.getElementById('max_hint').innerText = "Select item first";
    }
}
</script>

</body>
</html>