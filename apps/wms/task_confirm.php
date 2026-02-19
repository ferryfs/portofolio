<?php
// apps/wms/task_confirm.php
// V18: SOA REFACTORING (Service-Oriented Architecture)
// Features: UI/UX preserved, execution logic offloaded to WMSPutawayService.

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// 🔥 INCLUDE SERVICE LAYER KITA
require_once 'WMSPutawayService.php';

$id = sanitizeInt($_GET['id'] ?? 0);
$user_id = $_SESSION['wms_fullname'] ?? 'System';

// 1. Get Task Data (Hanya untuk keperluan render UI)
$task = safeGetOne($pdo, "SELECT t.*, p.product_code, p.description, p.base_uom, q.gr_ref, q.po_ref, q.batch 
                          FROM wms_warehouse_tasks t 
                          JOIN wms_products p ON t.product_uuid = p.product_uuid 
                          LEFT JOIN wms_quants q ON t.hu_id = q.hu_id
                          WHERE t.tanum = ?", [$id]);

if(!$task) die("Task invalid or not found.");

// 🔥 FETCH ADMIN NOTES (Untuk UI Kotak Kuning)
$adminNote = "";
if($task['po_ref']) {
    $noteQ = safeGetOne($pdo, "SELECT message FROM wms_inbound_notif WHERE po_number = ? AND (severity = 'SUCCESS' OR severity = 'INFO') ORDER BY created_at DESC LIMIT 1", [$task['po_ref']]);
    if($noteQ) {
        $parts = explode(".", $noteQ['message']);
        if(isset($parts[2]) && trim($parts[2]) != '') {
            $adminNote = trim($parts[2]);
        }
    }
}

// ---------------------------------------------------------
// 🔥 EXECUTION BLOCK (REFACTORED)
// ---------------------------------------------------------
if(isset($_POST['confirm'])) {
    // Kita panggil ahlinya: WMSPutawayService
    $putawayService = new WMSPutawayService($pdo, $user_id);
    
    // Ambil input dari form HTML
    $targetBin = $_POST['actual_bin'] ?? '';
    $qtyGood = $_POST['qty_good'] ?? 0;
    // Note: di form UI lo name-nya 'qty_dmg', bukan 'qty_bad'
    $qtyBad = $_POST['qty_dmg'] ?? 0; 
    $remarks = $_POST['remarks'] ?? '';

    // Lemparkan tugas ke Service Layer dengan penanda 'DESKTOP'
    $result = $putawayService->executePutaway($id, $targetBin, $qtyGood, $qtyBad, $remarks, 'DESKTOP');

    if ($result['status'] === 'success') {
        header("Location: task.php?msg=success"); 
        exit;
    } else {
        // Kalau gagal (misal kena regex rak atau phantom stock), lempar error ke UI
        $error = $result['msg'];
    }
}
// ---------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Execute Task | V18 (SOA)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f3f4f6; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; width: 100%; max-width: 600px; }
        .card-header { background: #fff; padding: 25px; border-bottom: 1px solid #e5e7eb; }
        /* 🔥 New style for Admin Note */
        .admin-note { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px; border-radius: 0 8px 8px 0; color: #b45309; }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold m-0 text-primary">TASK-<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></h5>
            <div class="small text-muted">Process: <?= $task['process_type'] ?></div>
        </div>
        <a href="task.php" class="btn btn-light rounded-circle shadow-sm"><i class="bi bi-x-lg"></i></a>
    </div>
    <div class="card-body p-4">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3"><i class="bi bi-box-seam fs-3"></i></div>
                    <div>
                        <div class="fw-bold text-dark fs-5"><?= $task['product_code'] ?></div>
                        <div class="small text-muted"><?= $task['description'] ?></div>
                        <div class="badge bg-light text-dark border mt-1">HU: <?= $task['hu_id'] ?></div>
                    </div>
                </div>
            </div>

            <?php if($adminNote): ?>
            <div class="admin-note shadow-sm">
                <div class="fw-bold mb-1"><i class="bi bi-megaphone-fill me-2"></i>Note From Admin:</div>
                <div class="small"><?= htmlspecialchars($adminNote) ?></div>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="small fw-bold text-muted">SOURCE</label>
                    <div class="p-2 border rounded text-center bg-light fw-bold font-monospace"><?= $task['source_bin'] ?></div>
                </div>
                <div class="col-6">
                    <label class="small fw-bold text-primary">DESTINATION</label>
                    <input type="text" name="actual_bin" class="form-control fw-bold border-primary text-center text-uppercase font-monospace" 
                           value="<?= $task['dest_bin'] == 'SYSTEM' ? '' : $task['dest_bin'] ?>" 
                           placeholder="SCAN BIN" required autofocus>
                </div>
            </div>

            <?php if($task['process_type'] == 'PUTAWAY'): ?>
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="small fw-bold text-dark m-0">QUALITY CHECK</label>
                        <span class="badge bg-info text-dark">Target: <?= (float)$task['qty'] ?></span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white border-success"><i class="bi bi-check"></i></span>
                                <input type="number" name="qty_good" class="form-control border-success fw-bold text-success" value="<?= (float)$task['qty'] ?>" step="0.01">
                            </div>
                            <div class="form-text text-success small">Good (F1)</div>
                        </div>
                        <div class="col-6">
                            <div class="input-group">
                                <span class="input-group-text bg-danger text-white border-danger"><i class="bi bi-x"></i></span>
                                <input type="number" name="qty_dmg" class="form-control border-danger fw-bold text-danger" value="0" step="0.01">
                            </div>
                            <div class="form-text text-danger small">Damaged (B6)</div>
                        </div>
                    </div>
                    <input type="text" name="remarks" class="form-control mt-2 form-control-sm" placeholder="Operator Remarks / Notes...">
                </div>
            <?php endif; ?>

            <button type="submit" name="confirm" class="btn btn-primary w-100 btn-lg rounded-pill fw-bold shadow-sm">
                CONFIRM TASK <i class="bi bi-check-lg ms-2"></i>
            </button>
        </form>
    </div>
</div>

</body>
</html>