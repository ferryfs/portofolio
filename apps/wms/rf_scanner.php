<?php 
include '../../koneksi.php'; 

$msg = "";
$msg_type = "";
$current_page = isset($_GET['page']) ? $_GET['page'] : 'task'; 

// --- 1. LOGIC CONFIRM TASK (DIPERBAIKI) ---
if(isset($_POST['btn_exec'])) {
    $task_id = $_POST['task_id'];
    $scanned_bin = strtoupper(trim($_POST['scan_check'])); // Ambil input scan operator

    $q_t = mysqli_query($conn, "SELECT * FROM wms_warehouse_tasks WHERE tanum = '$task_id' AND status='OPEN'");
    $task = mysqli_fetch_assoc($q_t);

    if($task) {
        $prod_uuid = $task['product_uuid'];
        $qty       = $task['qty'];
        $batch     = isset($task['batch']) ? $task['batch'] : '-';
        $hu_id     = isset($task['hu_id']) ? $task['hu_id'] : '';
        $type      = $task['process_type']; 
        
        $success = false;

        // A. JIKA PUTAWAY (Barang Masuk)
        if($type == 'PUTAWAY') {
            // Logic: Gunakan Bin yang DI-SCAN operator sebagai lokasi akhir (Actual Bin)
            // Walaupun sistem saranin A, kalau operator scan B, stok masuk ke B.
            $actual_dest = $scanned_bin; 

            // Insert Stok ke Rak Aktual
            $sql_stock = "INSERT INTO wms_quants (product_uuid, lgpla, batch, hu_id, qty, gr_date) 
                          VALUES ('$prod_uuid', '$actual_dest', '$batch', '$hu_id', '$qty', NOW())";
            
            if(mysqli_query($conn, $sql_stock)) {
                // Update dest_bin di task sesuai realisasi
                mysqli_query($conn, "UPDATE wms_warehouse_tasks SET dest_bin='$actual_dest' WHERE tanum='$task_id'");
                $success = true;
            }
        } 
        
        // B. JIKA PICKING (Barang Keluar)
        else if($type == 'PICKING') {
            $target_source = strtoupper($task['source_bin']);

            // VALIDASI KERAS: Bin yang discan HARUS SAMA dengan Source Bin
            if($scanned_bin !== $target_source) {
                $msg = "SALAH LOKASI! HARUSNYA: $target_source"; 
                $msg_type = "red";
            } else {
                // Kalau lokasi benar, baru potong stok
                $q_cek = mysqli_query($conn, "SELECT quant_id, qty FROM wms_quants WHERE product_uuid='$prod_uuid' AND lgpla='$target_source' LIMIT 1");
                $d_cek = mysqli_fetch_assoc($q_cek);
                
                if($d_cek) {
                    $new_qty = $d_cek['qty'] - $qty;
                    $qid = $d_cek['quant_id'];
                    
                    if($new_qty <= 0) mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='$qid'");
                    else mysqli_query($conn, "UPDATE wms_quants SET qty='$new_qty' WHERE quant_id='$qid'");
                    
                    $success = true;
                } else {
                    $msg = "STOK GHOST (DATA ADA TAPI FISIK TIDAK ADA)"; 
                    $msg_type = "red";
                }
            }
        }

        // FINISHING
        if($success) {
            mysqli_query($conn, "UPDATE wms_warehouse_tasks SET status='CONFIRMED', updated_at=NOW() WHERE tanum='$task_id'");
            $msg = "TASK #$task_id COMPLETED!"; 
            $msg_type = "green";
            
            // Refresh halaman biar langsung cari tugas baru
            header("Refresh:1"); 
        }
    } else {
        $msg = "TASK INVALID"; $msg_type = "red";
    }
}

// --- 2. LOGIC CEK STOK (Stock Inquiry) ---
$stock_result = null;
if(isset($_POST['btn_check_stock'])) {
    $keyword = $_POST['keyword'];
    // Search by Bin atau Product Code
    $q_stock = mysqli_query($conn, "
        SELECT q.lgpla, q.qty, p.product_code 
        FROM wms_quants q 
        JOIN wms_products p ON q.product_uuid = p.product_uuid 
        WHERE q.lgpla = '$keyword' OR p.product_code LIKE '%$keyword%'
        LIMIT 5
    ");
}

// --- 3. QUERY GET TASK (Hanya jalan kalau di halaman 'task') ---
$current_task = null;
if($current_page == 'task') {
    $q_open = mysqli_query($conn, "SELECT t.*, p.product_code FROM wms_warehouse_tasks t JOIN wms_products p ON t.product_uuid = p.product_uuid WHERE t.status = 'OPEN' ORDER BY t.tanum ASC LIMIT 1");
    if($q_open && mysqli_num_rows($q_open) > 0) {
        $current_task = mysqli_fetch_assoc($q_open);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RF Scanner Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #222; color: #0f0; font-family: 'Courier New', monospace; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .rf-screen { 
            background-color: #000; border: 15px solid #333; border-bottom-width: 40px; border-radius: 12px; 
            width: 350px; height: 650px; padding: 15px; display: flex; flex-direction: column; position: relative; overflow: hidden;
        }
        .header { border-bottom: 2px solid #0f0; padding-bottom: 5px; margin-bottom: 10px; font-weight: bold; display: flex; justify-content: space-between; font-size: 0.9rem; }
        .btn-menu { border: 1px solid #0f0; color: #0f0; background: #000; padding: 2px 8px; text-decoration: none; font-size: 0.8rem; }
        .btn-menu:hover { background: #0f0; color: #000; }
        
        /* Elements */
        .task-card { border: 2px dashed #0f0; padding: 10px; background: #001100; margin-bottom: 15px; }
        .lbl { font-size: 0.7rem; color: #006400; font-weight: bold; display: block; }
        .val { font-size: 1.1rem; font-weight: bold; color: #fff; display: block; margin-bottom: 5px; }
        .big-val { font-size: 1.6rem; color: #ff0; text-align: center; border: 1px solid #0f0; padding: 5px; margin: 5px 0; }
        
        .msg { padding: 5px; text-align: center; font-weight: bold; font-size: 0.8rem; margin-bottom: 10px; }
        .green { background: #002200; color: #0f0; border: 1px solid #0f0; }
        .red { background: #220000; color: #f00; border: 1px solid #f00; }

        .btn-scan { background: #0f0; color: #000; border: none; width: 100%; padding: 12px; font-weight: bold; font-size: 1.1rem; cursor: pointer; text-transform: uppercase; margin-top: 10px; }
        .input-dark { background: #000; border: 1px solid #0f0; color: #fff; text-align: center; text-transform: uppercase; width: 100%; padding: 8px; font-family: monospace; }
        
        .menu-list a { display: block; border: 1px solid #0f0; padding: 10px; margin-bottom: 10px; color: #0f0; text-decoration: none; font-weight: bold; }
        .menu-list a:hover { background: #0f0; color: #000; }
    </style>
</head>
<body>

<div class="rf-screen">
    
    <div class="header">
        <span>RF-01</span>
        <div>
            <?php if($current_page != 'menu'): ?>
                <a href="?page=menu" class="btn-menu">MENU [F1]</a>
            <?php else: ?>
                <a href="?page=task" class="btn-menu">BACK [ESC]</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="msg <?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <?php if($current_page == 'task'): ?>
        
        <?php if($current_task): ?>
            <div class="text-center mb-2">
                <span style="background:#0f0; color:#000; px:5px; font-weight:bold; font-size:0.8rem;">⚠ TASK ASSIGNED</span>
            </div>
            
            <div class="task-card">
                <div class="row">
                    <div class="col-6">
                        <span class="lbl">TASK ID</span><span class="val">#<?= $current_task['tanum'] ?></span>
                    </div>
                    <div class="col-6 text-end">
                        <span class="lbl">TYPE</span><span class="val" style="color:#0ff"><?= $current_task['process_type'] ?></span>
                    </div>
                </div>
                <span class="lbl">PRODUCT</span>
                <span class="val"><?= $current_task['product_code'] ?></span>
                
                <hr style="border-color:#0f0; margin: 5px 0;">
                
                <div class="row">
                    <div class="col-5">
                        <span class="lbl">QTY</span>
                        <span class="val" style="font-size:1.4rem"><?= (float)$current_task['qty'] ?></span>
                    </div>
                    <div class="col-7">
                        <span class="lbl">LOCATION</span>
                        <div class="big-val">
                            <?= $current_task['process_type'] == 'PUTAWAY' ? $current_task['dest_bin'] : $current_task['source_bin'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-auto">
                <form method="POST">
                    <input type="hidden" name="task_id" value="<?= $current_task['tanum'] ?>">
                    <span class="lbl text-center mb-1">SCAN BIN TO CONFIRM:</span>
                    <input type="text" name="scan_check" class="input-dark" placeholder="SCAN HERE..." autofocus autocomplete="off">
                    <button type="submit" name="btn_exec" class="btn-scan">CONFIRM</button>
                </form>
            </div>

        <?php else: ?>
            <div class="d-flex flex-column justify-content-center align-items-center h-100 text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3 text-secondary">SYSTEM IDLE</h4>
                <p style="color:#666; font-size:0.8rem">No Pending Tasks.<br>You can check stock in Menu.</p>
                <a href="?page=task" class="btn-menu mt-3">REFRESH</a>
            </div>
        <?php endif; ?>

    <?php elseif($current_page == 'menu'): ?>
        <h5 class="text-center text-decoration-underline mb-4">MAIN MENU</h5>
        <div class="menu-list">
            <a href="?page=task">1. TASK MODE (AUTO)</a>
            <a href="?page=stock">2. STOCK INQUIRY</a>
            <a href="index.php" style="border-color:#666; color:#888;">0. LOGOFF</a>
        </div>
        <div class="mt-auto text-center text-muted" style="font-size:0.7rem">
            PT. MAJU MUNDUR WMS v1.0
        </div>

    <?php elseif($current_page == 'stock'): ?>
        <h6 class="border-bottom border-secondary pb-2 mb-3">STOCK INQUIRY</h6>
        
        <form method="POST">
            <span class="lbl mb-1">SCAN BIN / PRODUCT:</span>
            <div class="d-flex gap-2">
                <input type="text" name="keyword" class="input-dark" autofocus placeholder="...">
                <button type="submit" name="btn_check_stock" class="btn-menu" style="background:#000;">GO</button>
            </div>
        </form>

        <div class="mt-3" style="overflow-y: auto; max-height: 400px;">
            <?php if(isset($q_stock)): ?>
                <?php if(mysqli_num_rows($q_stock) > 0): ?>
                    <?php while($s = mysqli_fetch_assoc($q_stock)): ?>
                        <div class="border-bottom border-secondary pb-2 mb-2">
                            <span style="color:#fff">BIN: <?= $s['lgpla'] ?></span><br>
                            <span class="small text-muted">PROD: <?= $s['product_code'] ?></span><br>
                            <span style="color:#ff0; font-weight:bold;">QTY: <?= (float)$s['qty'] ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-danger text-center mt-3">DATA NOT FOUND</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

</body>
</html>