<?php
// apps/wms/task.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}
require_once __DIR__ . '/../../config/database.php';

// TIME AGO FUNCTION
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'thn', 'm' => 'bln', 'w' => 'mgu', 'd' => 'hr', 'h' => 'jam', 'i' => 'mnt', 's' => 'dtk');
    foreach ($string as $k => &$v) {
        if ($diff->$k) $v = $diff->$k . ' ' . $v; else unset($string[$k]);
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yg lalu' : 'Baru saja';
}
?>

<!DOCTYPE html>
<html lang="id">
<head> 
    <title>Live Task Monitor</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #fff; font-family: 'Segoe UI', Roboto, Helvetica, sans-serif; }
        .header-title { font-weight: 800; letter-spacing: -0.5px; color: #344767; }
        .table-pro thead th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.8px; background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; padding: 12px 10px; font-weight: 700; color: #8392ab; }
        .table-pro tbody td { vertical-align: middle; padding: 10px; font-size: 0.85rem; border-bottom: 1px solid #f2f2f2; color: #344767; }
        .font-mono { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; letter-spacing: -0.3px; }
        .text-label { font-size: 0.7rem; color: #8392ab; display: block; margin-bottom: 2px; }
        .badge-type { font-size: 0.65rem; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; }
        .icon-box { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1.1rem; }
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca, #ff0080); color: white; } 
        .bg-gradient-success { background: linear-gradient(310deg, #17ad37, #98ec2d); color: white; }
        .empty-state { padding: 50px 20px; text-align: center; background: #fff; border: 2px dashed #e9ecef; border-radius: 16px; margin-top: 20px; }
    </style>
</head>
<body class="p-3">
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4" id="header-area">
        <div>
            <h4 class="header-title mb-0"><i class="bi bi-activity text-primary me-2"></i>Live Operations</h4>
            <small class="text-muted">Real-time warehouse task monitoring</small>
        </div>
        <a href="index.php" class="btn btn-outline-dark btn-sm px-3 rounded-pill fw-bold" id="btn-back"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
    </div>

    <div class="mb-5">
        <?php 
        $stmt = $pdo->query("SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.status = 'OPEN' ORDER BY t.tanum ASC");
        
        if($stmt->rowCount() > 0): 
        ?>
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-warning bg-opacity-10 py-3 d-flex align-items-center">
                    <span class="spinner-grow spinner-grow-sm text-warning me-2" role="status"></span>
                    <h6 class="mb-0 fw-bold text-warning-emphasis text-uppercase ls-1">Outstanding Tasks (Action Required)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-pro mb-0">
                        <thead><tr><th>Task Detail</th><th>HU ID</th><th>Product</th><th>Qty</th><th>Directive</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <span class="text-label">WT NUMBER</span>
                                    <span class="font-mono fw-bold text-primary">WT-<?= str_pad($row['tanum'], 8, "0", STR_PAD_LEFT) ?></span>
                                    <div class="mt-1"><span class="badge bg-dark badge-type"><?= $row['process_type'] ?></span></div>
                                </td>
                                <td><span class="text-label">HU / PALLET ID</span><span class="font-mono text-dark fw-bold"><?= !empty($row['hu_id']) ? $row['hu_id'] : '-' ?></span></td>
                                <td>
                                    <span class="fw-bold d-block text-dark"><?= $row['product_code'] ?></span>
                                    <small class="text-muted font-mono" style="font-size:0.75em">BATCH: <?= isset($row['batch']) ? $row['batch'] : 'N/A' ?></small>
                                </td>
                                <td><span class="fw-bold fs-5 text-dark"><?= (float)$row['qty'] ?></span></td>
                                <td><span class="text-label">SUGG. BIN</span><div class="text-primary fw-bold"><?= $row['dest_bin'] ?></div></td>
                                <td class="text-end">
                                    <a href="task_confirm.php?id=<?= $row['tanum'] ?>" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">Confirm</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="d-inline-block p-3 rounded-circle bg-success bg-opacity-10 mb-3"><i class="bi bi-check-lg text-success display-4"></i></div>
                <h5 class="fw-bold text-dark">All Caught Up!</h5>
                <p class="text-muted mb-0">No pending tasks available.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h6 class="text-uppercase text-muted fw-bold small mb-1">Execution History (Last 10)</h6>
        <div class="card shadow-sm border-0 rounded-3">
            <div class="table-responsive">
                <table class="table table-pro mb-0 align-middle">
                    <thead><tr><th width="5%">Type</th><th>Time</th><th>Reference</th><th>Material</th><th>Route</th><th class="text-end">Status</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt_hist = $pdo->query("SELECT t.*, p.product_code, p.base_uom FROM wms_warehouse_tasks t LEFT JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.status = 'CONFIRMED' ORDER BY t.tanum DESC LIMIT 10");
                        while($row = $stmt_hist->fetch(PDO::FETCH_ASSOC)):
                            $icon_cls = ($row['process_type'] == 'PICKING') ? 'bg-gradient-primary' : 'bg-gradient-success';
                            $icon_nm  = ($row['process_type'] == 'PICKING') ? 'bi-box-arrow-up' : 'bi-box-arrow-in-down';
                        ?>
                        <tr>
                            <td><div class="icon-box <?= $icon_cls ?> shadow-sm"><i class="bi <?= $icon_nm ?>"></i></div></td>
                            <td><span class="fw-bold text-dark"><?= time_elapsed_string($row['created_at']) ?></span></td>
                            <td><span class="font-mono text-dark fw-bold">WT-<?= str_pad($row['tanum'], 8, "0", STR_PAD_LEFT) ?></span></td>
                            <td><span class="fw-bold text-dark"><?= $row['product_code'] ?></span><br><small><?= (float)$row['qty'] ?> <?= $row['base_uom'] ?></small></td>
                            <td><?= $row['source_bin'] ?> <i class="bi bi-arrow-right mx-1 text-muted"></i> <?= $row['dest_bin'] ?></td>
                            <td class="text-end"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 fw-bold">CONFIRMED</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    if (window.self !== window.top) {
        document.getElementById('btn-back').style.display = 'none';
        document.getElementById('header-area').classList.remove('mb-4');
        document.getElementById('header-area').classList.add('mb-2');
        document.body.classList.remove('p-3');
        document.body.classList.add('p-2');
    }
</script>
</body>
</html>