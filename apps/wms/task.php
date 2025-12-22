<?php 
include '../../koneksi.php'; 

// FUNCTION: TIME AGO
function time_elapsed_string($datetime, $full = false) {
    if(empty($datetime)) return "Recently";
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array('y' => 'thn', 'm' => 'bln', 'w' => 'mgu', 'd' => 'hr', 'h' => 'jam', 'i' => 'mnt', 's' => 'dtk');
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
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

        /* HEADER STYLES */
        .header-title { font-weight: 800; letter-spacing: -0.5px; color: #344767; }
        
        /* TABLE STYLES */
        .table-pro thead th { 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            letter-spacing: 0.8px; 
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef; 
            padding: 12px 10px; 
            font-weight: 700; 
            color: #8392ab; 
        }
        .table-pro tbody td { 
            vertical-align: middle; 
            padding: 10px; 
            font-size: 0.85rem; 
            border-bottom: 1px solid #f2f2f2;
            color: #344767;
        }
        .table-pro tbody tr:hover { background-color: #fcfcfc; }

        /* DATA FORMATTING */
        .font-mono { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; letter-spacing: -0.3px; }
        .text-label { font-size: 0.7rem; color: #8392ab; display: block; margin-bottom: 2px; }
        
        /* BADGES & ICONS */
        .badge-type { font-size: 0.65rem; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; }
        .icon-box { 
            width: 36px; height: 36px; 
            display: flex; align-items: center; justify-content: center; 
            border-radius: 8px; font-size: 1.1rem; 
        }
        
        /* COLOR GRADIENTS (Enterprise Style) */
        .bg-gradient-primary { background: linear-gradient(310deg, #7928ca, #ff0080); color: white; } /* Picking */
        .bg-gradient-success { background: linear-gradient(310deg, #17ad37, #98ec2d); color: white; } /* Putaway */
        .bg-gradient-warning { background: linear-gradient(310deg, #f53939, #fbcf33); color: white; } /* Return */
        .bg-gradient-info    { background: linear-gradient(310deg, #2152ff, #21d4fd); color: white; } /* Transfer */
        .bg-gradient-dark    { background: linear-gradient(310deg, #344767, #344767); color: white; } /* Audit */

        /* EMPTY STATE */
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
        <a href="index.php" class="btn btn-outline-dark btn-sm px-3 rounded-pill fw-bold" id="btn-back">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <div class="mb-5">
        <?php 
        $q_open = mysqli_query($conn, "SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.status = 'OPEN' ORDER BY t.tanum ASC");
        
        if(mysqli_num_rows($q_open) > 0): 
        ?>
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-warning bg-opacity-10 py-3 d-flex align-items-center">
                    <span class="spinner-grow spinner-grow-sm text-warning me-2" role="status"></span>
                    <h6 class="mb-0 fw-bold text-warning-emphasis text-uppercase ls-1">Outstanding Tasks (Action Required)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-pro mb-0">
                        <thead>
                            <tr>
                                <th>Task Detail</th>
                                <th>Handling Unit (HU)</th>
                                <th>Product / Batch</th>
                                <th>Quantity</th>
                                <th>Directive</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($q_open)): ?>
                            <tr>
                                <td>
                                    <span class="text-label">WT NUMBER</span>
                                    <span class="font-mono fw-bold text-primary">WT-<?= str_pad($row['tanum'], 8, "0", STR_PAD_LEFT) ?></span>
                                    <div class="mt-1"><span class="badge bg-dark badge-type"><?= $row['process_type'] ?></span></div>
                                </td>
                                
                                <td>
                                    <span class="text-label">HU / PALLET ID</span>
                                    <span class="font-mono text-dark fw-bold"><?= !empty($row['hu_id']) ? $row['hu_id'] : '-' ?></span>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-box-seam text-secondary me-2 fs-5"></i>
                                        <div>
                                            <span class="fw-bold d-block text-dark"><?= $row['product_code'] ?></span>
                                            <small class="text-muted font-mono" style="font-size:0.75em">BATCH: <?= isset($row['batch']) ? $row['batch'] : 'N/A' ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="fw-bold fs-5 text-dark"><?= (float)$row['qty'] ?></span>
                                </td>

                                <td>
                                    <span class="text-label">SUGG. BIN</span>
                                    <div class="d-flex align-items-center text-primary fw-bold">
                                        <i class="bi bi-geo-alt-fill me-1"></i> <?= $row['dest_bin'] ?>
                                    </div>
                                </td>

                                <td class="text-end">
                                    <a href="task_confirm.php?id=<?= $row['tanum'] ?>" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                                        Confirm
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="d-inline-block p-3 rounded-circle bg-success bg-opacity-10 mb-3">
                    <i class="bi bi-check-lg text-success display-4"></i>
                </div>
                <h5 class="fw-bold text-dark">All Caught Up!</h5>
                <p class="text-muted mb-0">No pending tasks available. Great operational efficiency.</p>
            </div>
        <?php endif; ?>
    </div>


    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <h6 class="text-uppercase text-muted fw-bold small mb-1">Execution History</h6>
                <h5 class="fw-bold text-dark mb-0">Recently Completed</h5>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">Last 10 Transactions</span>
        </div>
        
        <div class="card shadow-sm border-0 rounded-3">
            <div class="table-responsive">
                <table class="table table-pro mb-0 align-middle">
                    <thead>
                        <tr>
                            <th width="5%">Type</th>
                            <th width="15%">Time Log</th>
                            <th width="15%">Reference</th>
                            <th width="25%">Material Info</th>
                            <th width="25%">Logistics Route</th>
                            <th width="15%" class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $q = mysqli_query($conn, "SELECT t.*, p.product_code, p.base_uom FROM wms_warehouse_tasks t LEFT JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.status = 'CONFIRMED' ORDER BY t.tanum DESC LIMIT 10");
                        
                        while($row = mysqli_fetch_assoc($q)):
                            
                            // --- SMART VISUAL LOGIC (Warnanya menyesuaikan Tipe) ---
                            $p_type = strtoupper($row['process_type']);
                            switch ($p_type) {
                                case 'PICKING':     
                                    $icon_cls = 'bg-gradient-primary'; // Ungu
                                    $icon_nm  = 'bi-box-arrow-up';
                                    $arrow_ic = 'bi-arrow-right';
                                    $arrow_cl = 'text-secondary';
                                    break;
                                case 'RETURN':      
                                    $icon_cls = 'bg-gradient-warning'; // Merah-Kuning
                                    $icon_nm  = 'bi-arrow-counterclockwise';
                                    $arrow_ic = 'bi-arrow-left'; // Panah balik
                                    $arrow_cl = 'text-warning';
                                    break;
                                case 'TRANSFER':    
                                case 'REPLENISH':   
                                    $icon_cls = 'bg-gradient-info'; // Biru Muda
                                    $icon_nm  = 'bi-arrow-left-right';
                                    $arrow_ic = 'bi-arrow-left-right';
                                    $arrow_cl = 'text-info';
                                    break;
                                case 'AUDIT':       
                                    $icon_cls = 'bg-gradient-dark'; // Hitam
                                    $icon_nm  = 'bi-clipboard-check';
                                    $arrow_ic = 'bi-dot';
                                    $arrow_cl = 'text-dark';
                                    break;
                                default: // PUTAWAY
                                    $icon_cls = 'bg-gradient-success'; // Hijau
                                    $icon_nm  = 'bi-box-arrow-in-down';
                                    $arrow_ic = 'bi-arrow-right';
                                    $arrow_cl = 'text-secondary';
                                    break;
                            }
                            
                            // Waktu
                            $time_ago = isset($row['created_at']) ? time_elapsed_string($row['created_at']) : '-';
                            $full_time = isset($row['created_at']) ? date('H:i:s', strtotime($row['created_at'])) : '';
                        ?>
                        <tr>
                            <td>
                                <div class="icon-box <?= $icon_cls ?> shadow-sm">
                                    <i class="bi <?= $icon_nm ?>"></i>
                                </div>
                            </td>

                            <td>
                                <span class="fw-bold text-dark d-block"><?= $time_ago ?></span>
                                <small class="text-muted font-mono" style="font-size:0.7em;" title="Exact Time: <?= $full_time ?>">
                                    <?= $full_time ?>
                                </small>
                            </td>

                            <td>
                                <span class="text-label">WT NUMBER</span>
                                
                                <span class="font-mono text-dark fw-bold d-block">WT-<?= str_pad($row['tanum'], 8, "0", STR_PAD_LEFT) ?></span>
                                
                                <?php if(!empty($row['hu_id'])): ?>
                                    <span class="text-label mt-1">HU ID</span>
                                    <span class="font-mono text-primary small"><?= $row['hu_id'] ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="fw-bold text-dark d-block"><?= $row['product_code'] ?></span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-light text-secondary border font-mono">
                                        Batch: <?= isset($row['batch']) ? $row['batch'] : '-' ?>
                                    </span>
                                    <span class="fw-bold small text-dark">
                                        Qty: <?= (float)$row['qty'] ?> <?= $row['base_uom'] ?>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="text-center">
                                        <span class="text-label">SOURCE</span>
                                        <span class="fw-bold small text-muted"><?= $row['source_bin'] ?></span>
                                    </div>
                                    <i class="bi <?= $arrow_ic ?> mx-3 <?= $arrow_cl ?> opacity-75"></i>
                                    <div class="text-center">
                                        <span class="text-label">DESTINATION</span>
                                        <span class="fw-bold small text-dark bg-light px-2 py-1 rounded border"><?= $row['dest_bin'] ?></span>
                                    </div>
                                </div>
                            </td>

                            <td class="text-end">
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 fw-bold">
                                    CONFIRMED
                                </span>
                            </td>
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