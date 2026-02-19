<?php
// apps/wms/physical_inventory.php
// V10: SECURE STOCK OPNAME (With Bin State Awareness)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$msg = ""; $alert = "";

// ---------------------------------------------------------
// 🧠 PROSES POSTING (ADJUSTMENT LOGIC)
// ---------------------------------------------------------
if(isset($_POST['post_count'])) {
    $qid = sanitizeInt($_POST['quant_id']);
    $qty_phys = (float)$_POST['qty_physical']; // Input User (Fisik)
    $qty_sys  = (float)$_POST['qty_system'];   // Stok Lama (Sistem)
    $prod_uuid= $_POST['product_uuid'];
    $bin      = $_POST['bin'];
    $hu       = $_POST['hu_id'];
    $user     = $_SESSION['wms_fullname'];

    $diff = $qty_phys - $qty_sys;

    if($diff != 0) {
        try {
            $pdo->beginTransaction();

            if($qty_phys < 0) throw new Exception("Physical quantity cannot be negative!");

            // 1. Lock quant record to prevent race conditions
            $stok = safeGetOne($pdo, "SELECT * FROM wms_quants WHERE quant_id=? FOR UPDATE", [$qid]);
            if(!$stok) throw new Exception("Stock record not found or already deleted.");

            // 2. Update Real Stock (Quant)
            if($qty_phys == 0) {
                // Barang hilang total -> Hapus barisnya
                safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$qid]);
                
                // Cek apakah rak ini sekarang kosong melompong?
                $cek_sisa = safeGetOne($pdo, "SELECT COUNT(*) as c FROM wms_quants WHERE lgpla=?", [$bin]);
                if($cek_sisa['c'] == 0) {
                    safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='EMPTY' WHERE lgpla=?", [$bin]);
                }
            } else {
                // Ada selisih -> Update angka qty-nya
                safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$qty_phys, $qid]);
                // Pastikan rak statusnya OCCUPIED (buat jaga-jaga kalau error sblmnya)
                safeQuery($pdo, "UPDATE wms_storage_bins SET status_bin='OCCUPIED' WHERE lgpla=?", [$bin]);
            }

            // 3. Audit Trail (Movement)
            $move_type = ($diff > 0) ? 'PI_GAIN' : 'PI_LOSS';
            safeQuery($pdo, "INSERT INTO wms_stock_movements (trx_ref, product_uuid, hu_id, qty_change, move_type, user, from_bin) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)", 
                             ["PI_ADJ", $prod_uuid, $hu, $diff, $move_type, $user, $bin]);

            // 4. Log Task (Buat jejak kalau ada yg nanya "kenapa stok berubah?")
            safeQuery($pdo, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, hu_id, source_bin, dest_bin, qty, status, created_at, confirmed_at) 
                             VALUES ('PI_ADJ', ?, ?, ?, 'SYSTEM', ?, 'CONFIRMED', NOW(), NOW())", 
                             [$prod_uuid, $hu, $bin, $diff]);

            $pdo->commit();
            $msg = "✅ Adjustment Posted for HU <b>$hu</b>. Difference: <b>$diff</b>.";
            $alert = "warning";

        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "⛔ Error: " . $e->getMessage();
            $alert = "danger";
        }
    } else {
        $msg = "✅ Count Match for HU <b>$hu</b>. No adjustment needed.";
        $alert = "success";
    }
}

// ---------------------------------------------------------
// 📊 QUERY STOK PER BIN (Untuk Opname)
// ---------------------------------------------------------
// Tampilkan semua stok yang ada di rak (hindari GR-ZONE kalau bisa, biar fokus ke rak utama)
$stocks = safeGetAll($pdo, "
    SELECT q.*, p.product_code, p.description, p.base_uom 
    FROM wms_quants q 
    JOIN wms_products p ON q.product_uuid = p.product_uuid 
    ORDER BY q.lgpla ASC, p.product_code ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Physical Inventory | WMS Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; padding-bottom: 50px; }
        .card-pi { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table thead th { background: #1e293b; color: #f8fafc; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 16px 20px; letter-spacing: 0.5px; }
        .table tbody td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        .bin-highlight { font-family: 'Consolas', monospace; font-size: 1.1rem; color: #4f46e5; background: #e0e7ff; padding: 6px 12px; border-radius: 6px; }
        .diff-hint { font-size: 0.75rem; font-weight: bold; margin-top: 4px; display: none; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 1400px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-clipboard2-check-fill text-primary me-2"></i>Physical Inventory (PI)</h3>
            <p class="text-muted m-0">Cycle Count & Adjustment Center</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary fw-bold bg-white" onclick="location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
            <a href="stock_master.php" class="btn btn-secondary fw-bold">Back to Master</a>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $alert ?> shadow-sm border-0 d-flex align-items-center rounded-4 fw-bold">
            <i class="bi <?= $alert=='success'?'bi-check-circle-fill':'bi-exclamation-triangle-fill' ?> me-3 fs-4"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="card-pi bg-white">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="15%">Bin Location</th>
                        <th width="25%">Product / Desc</th>
                        <th width="20%">HU / Batch</th>
                        <th class="text-end" width="10%">Sys Qty</th>
                        <th width="20%">Physical Count</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($stocks)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">Inventory is empty. Nothing to count.</td></tr>
                    <?php endif; ?>

                    <?php foreach($stocks as $idx => $row): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="quant_id" value="<?= $row['quant_id'] ?>">
                            <input type="hidden" name="qty_system" id="sys_<?= $idx ?>" value="<?= (float)$row['qty'] ?>">
                            <input type="hidden" name="product_uuid" value="<?= $row['product_uuid'] ?>">
                            <input type="hidden" name="bin" value="<?= $row['lgpla'] ?>">
                            <input type="hidden" name="hu_id" value="<?= $row['hu_id'] ?>">

                            <td><span class="bin-highlight fw-bold border border-primary border-opacity-25"><?= $row['lgpla'] ?></span></td>
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= $row['product_code'] ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 300px;"><?= $row['description'] ?></div>
                            </td>
                            <td>
                                <div class="small fw-bold font-monospace bg-light p-1 rounded border text-dark mb-1">HU: <?= $row['hu_id'] ?></div>
                                <div class="small text-muted font-monospace">BT: <?= $row['batch'] ?></div>
                            </td>
                            <td class="text-end fw-bold fs-5 text-secondary"><?= (float)$row['qty'] ?></td>
                            <td>
                                <div class="input-group shadow-sm">
                                    <input type="number" name="qty_physical" class="form-control fw-bold border-dark" 
                                           value="<?= (float)$row['qty'] ?>" step="0.01" min="0" required
                                           oninput="calcDiff(<?= $idx ?>, this.value)">
                                    <span class="input-group-text bg-light text-muted fw-bold small"><?= $row['base_uom'] ?></span>
                                </div>
                                <div id="hint_<?= $idx ?>" class="diff-hint"></div>
                            </td>
                            <td>
                                <button type="submit" name="post_count" class="btn btn-dark w-100 fw-bold shadow-sm">
                                    POST
                                </button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// Visual Feedback biar Admin tau dia lagi input selisih (Minus atau Plus)
function calcDiff(idx, physVal) {
    let sysVal = parseFloat(document.getElementById('sys_' + idx).value) || 0;
    let phys = parseFloat(physVal);
    let hint = document.getElementById('hint_' + idx);
    
    if(isNaN(phys)) { hint.style.display = 'none'; return; }
    
    let diff = phys - sysVal;
    
    if(diff === 0) {
        hint.innerHTML = '<span class="text-success"><i class="bi bi-check"></i> Match</span>';
        hint.style.display = 'block';
    } else if(diff > 0) {
        hint.innerHTML = '<span class="text-primary"><i class="bi bi-arrow-up-right"></i> Gain: +' + diff + '</span>';
        hint.style.display = 'block';
    } else {
        hint.innerHTML = '<span class="text-danger"><i class="bi bi-arrow-down-right"></i> Loss: ' + diff + '</span>';
        hint.style.display = 'block';
    }
}
</script>

</body>
</html>