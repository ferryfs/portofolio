<?php
// apps/wms/receiving.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; // Helper Log

// --- LOGIC POST GR ---
if(isset($_POST['post_gr'])) {
    if (!verifyCSRFToken()) die("Invalid Token");

    $prod_uuid = sanitizeInput($_POST['product_uuid']);
    $qty_input = sanitizeInt($_POST['qty']);
    $uom_mode  = sanitizeInput($_POST['uom_mode']); // BASE (Pcs) atau PACK (Pallet)
    $vendor    = sanitizeInput($_POST['vendor']);
    $po_num    = sanitizeInput($_POST['po_number']); // Dari hidden input
    $user      = $_SESSION['wms_fullname'];

    // 1. Ambil Master Data Produk untuk Konversi
    $stmt = $pdo->prepare("SELECT * FROM wms_products WHERE product_uuid = ?");
    $stmt->execute([$prod_uuid]);
    $d_prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$d_prod) die("Produk tidak valid.");
    
    // Logic Konversi
    if($uom_mode == 'PACK') {
        $qty_per_hu = $d_prod['conversion_qty']; 
        $loop_count = $qty_input; // Jumlah Pallet
        $qty_save   = $qty_per_hu; // Qty per Pallet
    } else {
        $qty_per_hu = $qty_input; 
        $loop_count = 1; // 1 Loose Carton
        $qty_save   = $qty_input;
    }

    $batch   = "BATCH-" . date('ymd');
    $gr_date = date('Y-m-d');
    $status  = 'F1'; // Unrestricted Use

    $success_count = 0;
    
    try {
        $pdo->beginTransaction();

        for($i=1; $i<=$loop_count; $i++) {
            $hu_id = "HU" . rand(100000,999999); 
            
            // Cari Bin Kosong (Strategi: Random Bin di Rak 0010)
            // Di real case, ini logika kompleks (near picking bin, fixed bin, etc)
            $bin = $pdo->query("SELECT b.lgpla FROM wms_storage_bins b 
                                LEFT JOIN wms_quants q ON b.lgpla = q.lgpla 
                                WHERE b.lgtyp = '0010' AND q.quant_id IS NULL 
                                LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            
            $target_bin = $bin ? $bin['lgpla'] : 'GR-ZONE'; // Kalau penuh masuk GR-ZONE

            // 1. Simpan Stok (Langsung F1 / Available)
            $sql_ins = "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date, stock_type, hu_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            safeQuery($pdo, $sql_ins, [$prod_uuid, $target_bin, $batch, $qty_save, $gr_date, $status, $hu_id]);

            // 2. Buat Task (Auto Confirmed karena Direct Receiving)
            // Kalau mau pakai RF Scanner buat putaway, statusnya 'OPEN' dan bin tujuannya 'GR-ZONE'
            $src_info = "PO: $po_num ($vendor)";
            $sql_task = "INSERT INTO wms_warehouse_tasks 
                         (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status, created_at)
                         VALUES ('PUTAWAY', ?, ?, ?, ?, ?, ?, 'CONFIRMED', NOW())";
            
            safeQuery($pdo, $sql_task, [$prod_uuid, $batch, $hu_id, $src_info, $target_bin, $qty_save]);
            
            $success_count++;
        }

        $pdo->commit();
        catat_log($pdo, $user, 'CREATE', 'INBOUND', "GR Posted: $success_count HU ($po_num)");
        
        $total_pcs = $success_count * $qty_save;
        $msg = "✅ <b>Success!</b> $success_count HU Created. Total $total_pcs Pcs. Location: $target_bin";
        $msg_type = "success";

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Ambil Parameter PO dari URL (dari Inbound Dashboard)
$po_param = isset($_GET['po']) ? sanitizeInput($_GET['po']) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head> 
    <title>Receiving Form</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="bi bi-box-arrow-in-down"></i> Goods Receipt Processing</h4>
        <a href="inbound.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>

    <div class="card shadow border-primary col-md-8 mx-auto">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Form Penerimaan</h5></div>
        <div class="card-body">
            
            <?php if(isset($msg)) echo "<div class='alert alert-$msg_type'>$msg</div>"; ?>

            <form method="POST">
                <?php echo csrfTokenField(); ?>
                
                <div class="mb-3">
                    <label class="fw-bold">PO Number</label>
                    <input type="text" name="po_number" class="form-control" value="<?= $po_param ?>" <?= $po_param ? 'readonly' : '' ?> placeholder="Scan or Type PO...">
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Vendor</label>
                    <input type="text" name="vendor" class="form-control" placeholder="Vendor Name" required>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Product</label>
                    <select name="product_uuid" class="form-select" id="prodSelect" onchange="updateInfo()" required>
                        <option value="">-- Select Product --</option>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM wms_products ORDER BY product_code ASC");
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$row['product_uuid']}' 
                                   data-uom='{$row['base_uom']}' 
                                   data-pack='{$row['capacity_uom']}' 
                                   data-conv='{$row['conversion_qty']}'>
                                   {$row['product_code']} - {$row['description']}
                                  </option>";
                        }
                        ?>
                    </select>
                    <div id="convInfo" class="form-text text-primary fw-bold mt-1"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Input Type</label>
                        <select name="uom_mode" id="uomMode" class="form-select" onchange="updateLabel()">
                            <option value="PACK">Per Packing (HU/Pallet)</option>
                            <option value="BASE">Per Unit (Pcs)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold" id="qtyLabel">Quantity</label>
                        <input type="number" name="qty" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="post_gr" class="btn btn-primary w-100 fw-bold py-2">POST GOODS RECEIPT</button>
            </form>
        </div>
    </div>
</div>

<script>
function updateInfo() {
    let sel = document.getElementById('prodSelect');
    let opt = sel.options[sel.selectedIndex];
    if(opt.value) {
        let base = opt.getAttribute('data-uom');
        let pack = opt.getAttribute('data-pack');
        let conv = opt.getAttribute('data-conv');
        document.getElementById('convInfo').innerText = `ℹ️ Info: 1 ${pack} = ${conv} ${base}`;
        updateLabel();
    }
}
function updateLabel() {
    let mode = document.getElementById('uomMode').value;
    let sel = document.getElementById('prodSelect');
    let opt = sel.options[sel.selectedIndex];
    if(opt.value) {
        let label = (mode === 'PACK') ? opt.getAttribute('data-pack') : opt.getAttribute('data-uom');
        document.getElementById('qtyLabel').innerText = "Quantity (" + label + ")";
    }
}
</script>
</body>
</html>