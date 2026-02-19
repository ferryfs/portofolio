<?php
// apps/wms/rf_scanner.php
// V18: SOA REFACTORING (Service-Oriented Architecture)
// Features: Execution logic offloaded to WMSPutawayService, UI preserved.

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: ../../login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

// 🔥 INCLUDE SERVICE LAYER KITA
require_once 'WMSPutawayService.php';

$user_id = $_SESSION['wms_fullname'];
$page = $_GET['page'] ?? 'menu';
$msg = ""; $msg_type = "";

class RFModel {
    private $pdo;
    private $user;

    public function __construct($pdo, $user) {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function getOpenTasks($type) {
        return safeGetAll($this->pdo, 
            "SELECT t.*, p.product_code, p.description 
             FROM wms_warehouse_tasks t 
             JOIN wms_products p ON t.product_uuid = p.product_uuid 
             WHERE t.status='OPEN' AND t.process_type=? 
             ORDER BY t.priority DESC, t.created_at ASC LIMIT 20", [$type]);
    }

    public function checkStock($query) {
        $query = trim($query);
        $res = [];
        
        $byBin = safeGetAll($this->pdo, "SELECT q.*, p.product_code, p.description FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE q.lgpla = ?", [$query]);
        if($byBin) {
            $res = ['type' => 'BIN', 'data' => $byBin];
        } else {
            $byProd = safeGetAll($this->pdo, "SELECT q.*, p.product_code, p.description FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE p.product_code LIKE ?", ["%$query%"]);
            if($byProd) {
                $res = ['type' => 'PRODUCT', 'data' => $byProd];
            }
        }

        if(!empty($res)) {
            $desc = "RF Stock Check: Found " . count($res['data']) . " records for '$query'";
            safeQuery($this->pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) VALUES (?, 'RF_SCANNER', 'STOCK_CHECK', ?, ?, NOW())", [$this->user, $desc, $_SERVER['REMOTE_ADDR']]);
        } else {
            $desc = "RF Stock Check: Not Found for '$query'";
            safeQuery($this->pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) VALUES (?, 'RF_SCANNER', 'STOCK_CHECK_FAIL', ?, ?, NOW())", [$this->user, $desc, $_SERVER['REMOTE_ADDR']]);
        }

        return $res;
    }

    // 🔥 Fungsi executeTask DIHAPUS, dipindah ke WMSPutawayService
}

$rf = new RFModel($pdo, $user_id);

// ---------------------------------------------------------
// 🔥 EXECUTION BLOCK (REFACTORED)
// ---------------------------------------------------------
if(isset($_POST['btn_exec'])) {
    // Kita panggil ahlinya: WMSPutawayService
    $putawayService = new WMSPutawayService($pdo, $user_id);
    
    // Ambil input
    $taskId = $_POST['task_id'];
    $targetBin = $_POST['scan_bin'];
    $qtyGood = $_POST['qty_good'] ?? 0;
    $qtyBad = $_POST['qty_bad'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';

    // Lemparkan tugas ke Service Layer dengan penanda 'RF'
    $res = $putawayService->executePutaway($taskId, $targetBin, $qtyGood, $qtyBad, $remarks, 'RF');
    
    $msg = $res['msg'];
    $msg_type = $res['status'];
    $page = ($res['status'] == 'success') ? 'list' : 'exec';
    
    if($res['status'] == 'success') { 
        header("Location: ?page=list&type={$_POST['type']}&msg=".urlencode($msg)); 
        exit; 
    }
}
// ---------------------------------------------------------

if(isset($_POST['btn_check'])) {
    $stock_res = $rf->checkStock($_POST['scan_query']);
    $page = 'stock_result';
}

if(isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>RF Enterprise V18 (SOA)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --text: #f8fafc; --primary: #3b82f6; --success: #22c55e; --danger: #ef4444; --warning: #f59e0b; }
        body { background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; display: flex; justify-content: center; min-height: 100vh; margin: 0; }
        .mobile-app { width: 100%; max-width: 480px; background-color: var(--bg); min-height: 100vh; display: flex; flex-direction: column; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .app-header { background: var(--card); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; position: sticky; top: 0; z-index: 10; }
        .app-content { flex: 1; padding: 20px; overflow-y: auto; }
        .menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .menu-card { background: var(--card); border: 1px solid #334155; border-radius: 16px; padding: 20px; text-align: center; color: var(--text); text-decoration: none; transition: 0.2s; }
        .menu-card:active { transform: scale(0.95); background: #334155; }
        .menu-icon { font-size: 2rem; margin-bottom: 10px; display: block; }
        .task-item { background: var(--card); border-radius: 12px; padding: 15px; margin-bottom: 12px; border-left: 5px solid var(--primary); text-decoration: none; color: white; display: block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .task-meta { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.75rem; color: #94a3b8; font-family: 'JetBrains Mono', monospace; }
        .task-title { font-weight: 800; font-size: 1.1rem; margin-bottom: 5px; }
        .task-qty { font-size: 1.2rem; font-weight: 700; color: var(--success); }
        .scan-box { background: var(--card); padding: 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid #334155; }
        
        /* 🔥 Admin Notes Box Styling */
        .admin-note-box { background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); border-radius: 12px; padding: 15px; margin-bottom: 20px; color: #fcd34d; }
        
        .form-label { color: #94a3b8; font-size: 0.8rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
        .form-control-rf { background: #0f172a; border: 2px solid #334155; color: white; border-radius: 10px; padding: 12px; font-size: 1.1rem; width: 100%; font-family: 'JetBrains Mono', monospace; text-align: center; transition: 0.2s; }
        .form-control-rf:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); }
        .btn-rf { width: 100%; padding: 15px; border-radius: 50px; border: none; font-weight: 800; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }
        .btn-primary:active { transform: scale(0.98); }
        .alert-rf { background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success); color: var(--success); padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; }
        .alert-err { background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger); }
        .stock-item { border-bottom: 1px solid #334155; padding: 10px 0; }
        .stock-item:last-child { border-bottom: none; }
    </style>
</head>
<body>

<div class="mobile-app">
    <div class="app-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-upc-scan text-primary fs-4"></i>
            <div>
                <div style="font-weight: 800; line-height: 1;">RF-V18</div>
                <div style="font-size: 0.7rem; color: #64748b;">Enterprise Mobile</div>
            </div>
        </div>
        <div class="d-flex gap-3">
            <a href="?page=menu" class="text-white"><i class="bi bi-grid-fill fs-4"></i></a>
            <a href="index.php" class="text-secondary"><i class="bi bi-power fs-4"></i></a>
        </div>
    </div>

    <div class="app-content">
        <?php if($msg): ?>
            <div class="alert-rf <?= $msg_type=='error'?'alert-err':'' ?>">
                <i class="bi <?= $msg_type=='error'?'bi-exclamation-triangle-fill':'bi-check-circle-fill' ?>"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if($page == 'menu'): ?>
            <div class="menu-grid">
                <a href="?page=list&type=PUTAWAY" class="menu-card">
                    <i class="bi bi-box-arrow-in-down menu-icon text-primary"></i>
                    <div class="fw-bold">PUTAWAY</div>
                    <div class="small text-muted mt-1">Inbound Process</div>
                </a>
                <a href="?page=list&type=PICKING" class="menu-card">
                    <i class="bi bi-box-arrow-up menu-icon text-warning"></i>
                    <div class="fw-bold">PICKING</div>
                    <div class="small text-muted mt-1">Outbound Process</div>
                </a>
                <a href="?page=stock" class="menu-card">
                    <i class="bi bi-search menu-icon text-info"></i>
                    <div class="fw-bold">STOCK INFO</div>
                    <div class="small text-muted mt-1">Check Bin/Item</div>
                </a>
                <a href="index.php" class="menu-card" style="border-color: var(--danger);">
                    <i class="bi bi-box-arrow-right menu-icon text-danger"></i>
                    <div class="fw-bold text-danger">LOGOUT</div>
                </a>
            </div>

        <?php elseif($page == 'list'): 
            $type = $_GET['type'] ?? 'PUTAWAY';
            $tasks = $rf->getOpenTasks($type);
        ?>
            <h5 class="fw-bold mb-3 text-white"><i class="bi bi-list-task me-2"></i><?= $type ?> TASKS</h5>
            <?php if(empty($tasks)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clipboard-check fs-1 opacity-25"></i>
                    <p class="mt-2">No pending tasks.</p>
                    <a href="?page=menu" class="btn btn-sm btn-outline-secondary rounded-pill px-4">Back to Menu</a>
                </div>
            <?php else: ?>
                <?php foreach($tasks as $t): ?>
                    <a href="?page=exec&id=<?= $t['tanum'] ?>&type=<?= $type ?>" class="task-item">
                        <div class="task-meta">
                            <span>TASK #<?= $t['tanum'] ?></span>
                            <span>HU: <?= $t['hu_id'] ?></span>
                        </div>
                        <div class="task-title"><?= $t['product_code'] ?></div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="small text-muted"><?= substr($t['description'], 0, 20) ?>...</div>
                            <div class="task-qty"><?= (float)$t['qty'] ?> <small class="fs-6 text-muted">PCS</small></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif($page == 'exec'): 
            $id = $_GET['id'];
            $t = safeGetOne($pdo, "SELECT t.*, p.product_code, p.description, q.po_ref 
                                   FROM wms_warehouse_tasks t 
                                   JOIN wms_products p ON t.product_uuid=p.product_uuid 
                                   LEFT JOIN wms_quants q ON t.hu_id = q.hu_id
                                   WHERE t.tanum=?", [$id]);
                                   
            // Fetching Admin Note for this Task's PO
            $adminNote = "";
            if($t['po_ref']) {
                $noteQ = safeGetOne($pdo, "SELECT message FROM wms_inbound_notif WHERE po_number = ? AND severity = 'SUCCESS' ORDER BY created_at DESC LIMIT 1", [$t['po_ref']]);
                if($noteQ) {
                    $parts = explode(".", $noteQ['message']);
                    if(isset($parts[2]) && trim($parts[2]) != '') {
                        $adminNote = trim($parts[2]);
                    }
                }
            }
        ?>
            <form method="POST">
                <input type="hidden" name="task_id" value="<?= $id ?>">
                <input type="hidden" name="type" value="<?= $_GET['type'] ?>">

                <div class="scan-box" style="border-left: 4px solid var(--primary);">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-primary">TASK #<?= $id ?></span>
                        <span class="text-muted small font-monospace"><?= $t['hu_id'] ?></span>
                    </div>
                    <h4 class="fw-bold mb-1"><?= $t['product_code'] ?></h4>
                    <p class="small text-muted mb-3"><?= $t['description'] ?></p>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: #0f172a;">
                        <span class="small text-muted">TARGET QTY</span>
                        <span class="fs-4 fw-bold text-white"><?= (float)$t['qty'] ?></span>
                    </div>
                </div>
                
                <?php if($adminNote): ?>
                <div class="admin-note-box">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Note From Admin:</div>
                    <div class="small" style="line-height: 1.4;"><?= htmlspecialchars($adminNote) ?></div>
                </div>
                <?php endif; ?>

                <div class="scan-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">ACTUAL COUNT</label>
                        <span class="badge bg-secondary">Target: <?= (float)$t['qty'] ?></span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-success">Good Qty</label>
                            <input type="number" name="qty_good" class="form-control-rf" style="border-color: var(--success);" value="<?= (float)$t['qty'] ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-danger">Bad Qty</label>
                            <input type="number" name="qty_bad" class="form-control-rf" style="border-color: var(--danger);" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-primary">Target Bin</label>
                        <?php if($t['process_type']=='PUTAWAY' && $t['dest_bin']=='SYSTEM'): ?>
                            <div class="text-center text-primary mb-2 small fw-bold animate-pulse">[ SCAN ANY EMPTY BIN ]</div>
                        <?php endif; ?>
                        <input type="text" name="scan_bin" class="form-control-rf" placeholder="SCAN BIN LABEL" autofocus required>
                    </div>
                    
                    <div class="mb-2">
                        <input type="text" name="remarks" class="form-control-rf" style="font-size: 0.9rem;" placeholder="Operator Notes...">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="btn_exec" class="btn-rf btn-primary">CONFIRM TASK</button>
                    <a href="?page=list&type=<?= $_GET['type'] ?>" class="btn btn-outline-secondary rounded-pill py-2 fw-bold text-center text-decoration-none">CANCEL</a>
                </div>
            </form>

        <?php elseif($page == 'stock'): ?>
            <div class="scan-box text-center">
                <i class="bi bi-search text-primary fs-1 mb-3"></i>
                <h5 class="fw-bold mb-3">Check Stock</h5>
                <form method="POST">
                    <input type="text" name="scan_query" class="form-control-rf mb-3" placeholder="SCAN BIN / PRODUCT" autofocus required>
                    <button type="submit" name="btn_check" class="btn-rf btn-primary">SEARCH</button>
                </form>
            </div>
            <a href="?page=menu" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-bold text-center text-decoration-none">BACK TO MENU</a>

        <?php elseif($page == 'stock_result'): 
            $data = $stock_res ?? [];
        ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0"><i class="bi bi-list-check me-2"></i>Results</h5>
                <a href="?page=stock" class="btn btn-sm btn-outline-secondary rounded-pill">New Search</a>
            </div>

            <?php if(empty($data)): ?>
                <div class="alert-rf alert-err">No data found.</div>
            <?php else: ?>
                <div class="scan-box">
                    <div class="text-center mb-3">
                        <span class="badge bg-primary">BY <?= $data['type'] ?></span>
                    </div>
                    <?php foreach($data['data'] as $r): ?>
                        <div class="stock-item">
                            <div class="d-flex justify-content-between fw-bold">
                                <span><?= $r['lgpla'] ?></span>
                                <span class="text-success"><?= (float)$r['qty'] ?></span>
                            </div>
                            <div class="text-white small"><?= $r['product_code'] ?></div>
                            <div class="text-muted small"><?= $r['description'] ?></div>
                            <div class="text-muted small font-monospace">Batch: <?= $r['batch'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>