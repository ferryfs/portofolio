<?php
// apps/wms/task_confirm.php
// V18.3: UX POP-UP ENHANCEMENT
// Features: SweetAlert2 integrated for success/error feedback showing PO, HU, and Batch.

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// 🔥 INCLUDE SERVICE LAYER
require_once 'WMSPutawayService.php';

$id = sanitizeInt($_GET['id'] ?? 0);
$user_id = $_SESSION['wms_fullname'] ?? 'System';

// 1. Get Task Data
$task = safeGetOne($pdo, "SELECT t.*, p.product_code, p.description, p.base_uom, q.gr_ref, q.po_ref, q.batch, gh.remarks as admin_note 
                          FROM wms_warehouse_tasks t 
                          JOIN wms_products p ON t.product_uuid = p.product_uuid 
                          LEFT JOIN wms_quants q ON t.hu_id = q.hu_id
                          LEFT JOIN wms_gr_header gh ON q.gr_ref = gh.gr_number
                          WHERE t.tanum = ?", [$id]);

if(!$task) die("Task invalid or not found.");

// Langsung ambil dari DB!
$adminNote = $task['admin_note'] ?? '';

// Variables for SweetAlert Trigger
$show_alert = false;
$alert_status = '';
$alert_msg = '';

// ---------------------------------------------------------
// 🔥 EXECUTION BLOCK (REFACTORED)
// ---------------------------------------------------------
if(isset($_POST['confirm'])) {
    $putawayService = new WMSPutawayService($pdo, $user_id);
    
    $targetBin = $_POST['actual_bin'] ?? '';
    $qtyGood = $_POST['qty_good'] ?? 0;
    $qtyBad = $_POST['qty_dmg'] ?? 0; 
    $remarks = $_POST['remarks'] ?? '';

    $result = $putawayService->executePutaway($id, $targetBin, $qtyGood, $qtyBad, $remarks, 'DESKTOP');

    $show_alert = true;
    $alert_status = $result['status'];
    
    if ($result['status'] === 'success') {
        // Build success message explicitly mentioning PO, Batch, and HU
        $po_text = $task['po_ref'] ?? 'N/A';
        $batch_text = $task['batch'] ?? 'N/A';
        $hu_text = $task['hu_id'] ?? 'N/A';
        $alert_msg = "Task Confirmed!<br><br><small><b>PO:</b> $po_text<br><b>Batch:</b> $batch_text<br><b>HU:</b> $hu_text</small>";
    } else {
        $alert_msg = $result['msg'];
        $error = $result['msg']; // Fallback for standard alert UI
    }
}
// ---------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Execute Task | V18.3 (SOA)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f3f4f6; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; width: 100%; max-width: 600px; }
        .card-header { background: #fff; padding: 25px; border-bottom: 1px solid #e5e7eb; }
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
        <?php if(isset($error) && !$show_alert): ?>
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
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Catatan untuk <?= htmlspecialchars($task['po_ref']) ?>:</div>
                <div class="small" style="line-height: 1.4;"><?= htmlspecialchars($adminNote) ?></div>
            </div>
            <?php else: ?>
            <div class="admin-note shadow-sm" style="background-color: #f1f5f9; border-color: #94a3b8; color: #64748b;">
                <div class="fw-bold mb-0" style="font-size: 0.85rem;"><i class="bi bi-info-circle me-2"></i>Tidak ada catatan untuk <?= htmlspecialchars($task['po_ref']) ?></div>
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

<?php if($show_alert): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const status = "<?= $alert_status ?>";
        const msg = `<?= $alert_msg ?>`;

        if (status === 'success') {
            Swal.fire({
                title: 'Success!',
                html: msg,
                icon: 'success',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'OK, Next Task'
            }).then((result) => {
                if (result.isConfirmed || result.isDismissed) {
                    window.location.href = 'task.php?msg=success';
                }
            });
        } else {
            Swal.fire({
                title: 'Error Processing Task',
                text: msg,
                icon: 'error',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Try Again'
            });
        }
    });
</script>
<?php endif; ?>

</body>
</html>