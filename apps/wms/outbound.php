<?php
// apps/wms/outbound.php
// V11: ENTERPRISE OUTBOUND (Reservation Execution)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

$user = $_SESSION['wms_fullname'];
$msg = ""; $msg_type = "";

// ---------------------------------------------------------
// 🧠 LOGIC: RELEASE PICKING (FROM RESERVATION)
// ---------------------------------------------------------
if(isset($_POST['release_picking'])) {
    if (!verifyCSRFToken()) die("Security Alert: Invalid Token");

    $so_num = sanitizeInput($_POST['so_number']);
    
    try {
        $pdo->beginTransaction();

        // 1. Cek Status Wajib RESERVED
        $so = safeGetOne($pdo, "SELECT status FROM wms_so_header WHERE so_number=? FOR UPDATE", [$so_num]);
        if($so['status'] != 'RESERVED') throw new Exception("SO must be RESERVED first! Current status: {$so['status']}");

        // 2. Ambil Data Reservasi (Jembatan Enterprise)
        // Kita ubah Reservation -> Task
        $sql_res = "SELECT r.*, q.lgpla, q.batch, q.hu_id 
                    FROM wms_stock_reservations r 
                    JOIN wms_quants q ON r.quant_id = q.quant_id
                    WHERE r.so_number = ?";
        $reservations = safeGetAll($pdo, $sql_res, [$so_num]);

        if(empty($reservations)) throw new Exception("No stock reservation found. Please re-run reservation engine.");

        $count = 0;
        foreach($reservations as $res) {
            // Create Task Picking sesuai ID Stok yang sudah di-booking
            $sql_task = "INSERT INTO wms_warehouse_tasks 
                         (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at)
                         VALUES ('PICKING', (SELECT product_uuid FROM wms_quants WHERE quant_id=?), ?, ?, ?, 'GI-ZONE', ?, 'OPEN', NOW())";
            
            safeQuery($pdo, $sql_task, [$res['quant_id'], $res['batch'], $res['hu_id'], $res['lgpla'], $res['qty_reserved']]);
            $count++;
        }

        // 3. Update Status SO -> PICKING
        safeQuery($pdo, "UPDATE wms_so_header SET status='PICKING' WHERE so_number=?", [$so_num]);

        // 4. Update Stok Fisik: Pindahkan dari 'reserved' ke 'picked_qty' (Logical Move)
        // Note: Fisik beneran pindah nanti pas Scanner Confirm. 
        // Di sini kita tandai bahwa reservasi sudah "turun" jadi SPK.
        
        // (Optional: Clean up table reservasi jika task sudah jadi, atau biarkan sebagai history)
        // safeQuery($pdo, "DELETE FROM wms_stock_reservations WHERE so_number=?", [$so_num]); 

        $pdo->commit();
        $msg = "✅ <b>$count Picking Tasks Released!</b> Sent to RF Scanner.";
        $msg_type = "success";
        
        catat_log($pdo, $user, 'RELEASE', 'OUTBOUND', "Released Task for SO: $so_num");

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// QUERY MONITOR: Hanya tampilkan SO yang statusnya RESERVED (Siap Rilis) atau PICKING (Sedang Jalan)
$sql = "SELECT h.*, 
        COUNT(i.so_item_id) as total_sku,
        SUM(i.qty_ordered) as total_qty
        FROM wms_so_header h
        LEFT JOIN wms_so_items i ON h.so_number = i.so_number
        WHERE h.status IN ('RESERVED', 'PICKING')
        GROUP BY h.so_number
        ORDER BY FIELD(h.status, 'RESERVED', 'PICKING'), h.expected_date ASC";
$list = safeGetAll($pdo, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outbound Console V11</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, sans-serif; padding-bottom: 50px; }
        .card-order { border: none; border-radius: 12px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 15px; border-left: 5px solid transparent; transition: 0.2s; }
        .card-order:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        .st-RESERVED { border-left-color: #f59e0b; } /* Kuning: Siap Rilis */
        .st-PICKING { border-left-color: #3b82f6; }  /* Biru: Sedang Jalan */
        
        .badge-status { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .bg-reserved { background: #fef3c7; color: #b45309; }
        .bg-picking { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 1200px;">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-conveyor-belt text-primary me-2"></i>Outbound Console</h3>
            <p class="text-muted m-0">Release Tasks & Monitor Picking</p>
        </div>
        <div>
            <a href="sales_order.php" class="btn btn-outline-dark fw-bold me-2"><i class="bi bi-plus-circle me-2"></i>New Order</a>
            <a href="shipping.php" class="btn btn-outline-secondary fw-bold">Shipping</a>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-3 fs-3"></i>
            <div><?= $msg ?></div>
        </div>
    <?php endif; ?>

    <?php if(empty($list)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox display-1 opacity-25"></i>
            <p class="mt-3 fw-bold">No orders ready for release.</p>
            <a href="sales_order.php" class="btn btn-sm btn-primary">Create or Reserve Order</a>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach($list as $row): ?>
        <div class="col-12">
            <div class="card-order st-<?= $row['status'] ?>">
                <div class="card-body d-flex justify-content-between align-items-center">
                    
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <h5 class="fw-bold m-0 text-dark"><?= $row['so_number'] ?></h5>
                            <small class="text-muted"><i class="bi bi-building me-1"></i> <?= $row['customer_name'] ?></small>
                        </div>
                        <div class="vr"></div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Items</div>
                            <div class="fw-bold"><?= $row['total_sku'] ?> SKU / <?= number_format($row['total_qty']) ?> Pcs</div>
                        </div>
                        <div class="vr"></div>
                        <div>
                            <?php if($row['status'] == 'RESERVED'): ?>
                                <span class="badge-status bg-reserved"><i class="bi bi-lock-fill me-1"></i> Stock Reserved</span>
                            <?php else: ?>
                                <span class="badge-status bg-picking"><i class="bi bi-person-walking me-1"></i> Picking in Progress</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <?php if($row['status'] == 'RESERVED'): ?>
                            <form method="POST">
                                <?php echo csrfTokenField(); ?>
                                <input type="hidden" name="so_number" value="<?= $row['so_number'] ?>">
                                <button type="submit" name="release_picking" class="btn btn-primary fw-bold shadow-sm px-4">
                                    <i class="bi bi-box-seam me-2"></i> RELEASE TO WAREHOUSE
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="task.php" class="btn btn-outline-primary fw-bold px-4">
                                <i class="bi bi-eye me-2"></i> Monitor Tasks
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</body>
</html>