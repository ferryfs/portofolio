<?php 
include '../../koneksi.php'; 

// HANDLING LOGIC (PHP DI ATAS HTML BIAR RAPI)
$msg = "";
$msg_type = ""; // green or red

// 1. LOGIC INBOUND VIA RF
if(isset($_POST['rf_inbound'])) {
    $code = $_POST['prod_code'];
    $qty  = $_POST['qty'];
    
    // Cari UUID dari Kode Barang
    $cek_prod = mysqli_query($conn, "SELECT product_uuid FROM wms_products WHERE product_code='$code'");
    $d_prod   = mysqli_fetch_assoc($cek_prod);

    if($d_prod) {
        $uuid = $d_prod['product_uuid'];
        $batch = "RF-" . date('ymd');
        $date  = date('Y-m-d');
        
        // Auto Putaway (Cari Bin Kosong di 0010)
        $cari_bin = mysqli_query($conn, "SELECT b.lgpla FROM wms_storage_bins b LEFT JOIN wms_quants q ON b.lgpla = q.lgpla WHERE b.lgtyp = '0010' AND q.quant_id IS NULL LIMIT 1");
        $bin_data = mysqli_fetch_assoc($cari_bin);

        if($bin_data) {
            $bin = $bin_data['lgpla'];
            mysqli_query($conn, "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date) VALUES ('$uuid', '$bin', '$batch', '$qty', '$date')");
            // Log Task
            mysqli_query($conn, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, source_bin, dest_bin, qty, status) VALUES ('PUTAWAY', '$uuid', 'GR-ZONE', '$bin', '$qty', 'CONFIRMED')");
            
            $msg = "SUKSES! PUTAWAY KE BIN: $bin";
            $msg_type = "green";
        } else {
            $msg = "ERROR: BIN PENUH!";
            $msg_type = "red";
        }
    } else {
        $msg = "ERROR: PRODUK TIDAK DITEMUKAN";
        $msg_type = "red";
    }
}

// 2. LOGIC OUTBOUND VIA RF (SIMPLE FIFO)
if(isset($_POST['rf_outbound'])) {
    $code = $_POST['prod_code'];
    $req_qty = $_POST['qty'];

    // Cari UUID
    $cek_prod = mysqli_query($conn, "SELECT product_uuid FROM wms_products WHERE product_code='$code'");
    $d_prod   = mysqli_fetch_assoc($cek_prod);

    if($d_prod) {
        $uuid = $d_prod['product_uuid'];
        
        // Cari Stok (FIFO)
        $cari_stok = mysqli_query($conn, "SELECT * FROM wms_quants WHERE product_uuid = '$uuid' ORDER BY gr_date ASC LIMIT 1");
        $d_stok = mysqli_fetch_assoc($cari_stok);

        if($d_stok && $d_stok['qty'] >= $req_qty) {
            $new_qty = $d_stok['qty'] - $req_qty;
            $qid = $d_stok['quant_id'];
            $bin = $d_stok['lgpla'];

            if($new_qty == 0) mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='$qid'");
            else mysqli_query($conn, "UPDATE wms_quants SET qty='$new_qty' WHERE quant_id='$qid'");

            // Log Task
            mysqli_query($conn, "INSERT INTO wms_warehouse_tasks (process_type, product_uuid, source_bin, dest_bin, qty, status) VALUES ('PICKING', '$uuid', '$bin', 'GI-ZONE', '$req_qty', 'CONFIRMED')");

            $msg = "PICKING SUKSES DARI: $bin";
            $msg_type = "green";
        } else {
            $msg = "ERROR: STOK TIDAK CUKUP/TIDAK ADA";
            $msg_type = "red";
        }
    } else {
        $msg = "ERROR: PRODUK TIDAK DITEMUKAN";
        $msg_type = "red";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RF Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* GAYA LAYAR SCANNER INDUSTRIAL */
        body { background-color: #222; color: #0f0; font-family: 'Courier New', monospace; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .rf-screen { 
            background-color: #000; 
            border: 20px solid #444; 
            border-bottom-width: 40px; /* Bagian bawah tebal kayak gagang */
            border-radius: 15px; 
            width: 360px; 
            height: 640px; 
            padding: 20px; 
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            position: relative;
            overflow-y: auto;
        }
        /* Efek garis layar jadul */
        .rf-screen::before {
            content: " ";
            display: block;
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 2;
            background-size: 100% 2px, 3px 100%;
            pointer-events: none;
        }
        .rf-header { border-bottom: 2px solid #0f0; margin-bottom: 20px; padding-bottom: 5px; font-weight: bold; }
        .btn-rf { 
            background: #000; border: 2px solid #0f0; color: #0f0; 
            width: 100%; text-align: left; margin-bottom: 12px; 
            font-weight: bold; border-radius: 4px; padding: 10px;
            text-transform: uppercase; transition: 0.1s;
        }
        .btn-rf:hover, .btn-rf:active { background: #0f0; color: #000; cursor: pointer; }
        .f-key { float: right; color: #ff0; }
        
        input { 
            background: #000 !important; border: 1px solid #0f0 !important; 
            color: #0f0 !important; border-radius: 0 !important; 
            font-family: 'Courier New', monospace; font-weight: bold; text-transform: uppercase;
        }
        input:focus { box-shadow: none !important; border-color: #fff !important; }
        label { color: #0f0; font-size: 0.8rem; margin-bottom: 5px; display: block; }
        
        .msg-box { border: 1px solid; padding: 5px; margin-bottom: 15px; font-size: 0.8rem; font-weight: bold; }
        .green { border-color: #0f0; color: #0f0; }
        .red { border-color: #f00; color: #f00; }
    </style>
</head>
<body>

<div class="rf-screen">
    <div class="rf-header d-flex justify-content-between">
        <span>RF-01</span>
        <span>WH: 0001</span>
    </div>

    <?php if($msg != ""): ?>
        <div class="msg-box <?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <?php if(!isset($_GET['menu'])): ?>
    <div class="text-center mb-4 text-decoration-underline">MAIN MENU</div>
    
    <a href="?menu=inbound" class="btn btn-rf">1. Inbound <span class="f-key">F1</span></a>
    <a href="?menu=outbound" class="btn btn-rf">2. Outbound <span class="f-key">F2</span></a>
    <a href="?menu=stock" class="btn btn-rf">3. Stock Info <span class="f-key">F3</span></a>
    <a href="index.php" class="btn btn-rf mt-4 text-center">LOGOFF <span class="f-key">F4</span></a>

    <?php elseif($_GET['menu'] == 'inbound'): ?>
    <div class="border-bottom border-success mb-3">INBOUND PROCESS</div>
    <form method="POST">
        <div class="mb-2">
            <label>SCAN PRODUCT (SKU)</label>
            <input type="text" name="prod_code" class="form-control form-control-sm" placeholder="e.g. MAT-LPT-X1" required autofocus>
        </div>
        <div class="mb-3">
            <label>QUANTITY</label>
            <input type="number" name="qty" class="form-control form-control-sm" required>
        </div>
        <button type="submit" name="rf_inbound" class="btn btn-rf text-center">CONFIRM (ENT)</button>
        <a href="rf_scanner.php" class="btn btn-rf text-center mt-2" style="border-color:#555; color:#777;">BACK (F7)</a>
    </form>

    <?php elseif($_GET['menu'] == 'outbound'): ?>
    <div class="border-bottom border-success mb-3">OUTBOUND (PICKING)</div>
    <form method="POST">
        <div class="mb-2">
            <label>SCAN PRODUCT (SKU)</label>
            <input type="text" name="prod_code" class="form-control form-control-sm" placeholder="e.g. MAT-LPT-X1" required autofocus>
        </div>
        <div class="mb-3">
            <label>QTY NEEDED</label>
            <input type="number" name="qty" class="form-control form-control-sm" required>
        </div>
        <button type="submit" name="rf_outbound" class="btn btn-rf text-center">CONFIRM (ENT)</button>
        <a href="rf_scanner.php" class="btn btn-rf text-center mt-2" style="border-color:#555; color:#777;">BACK (F7)</a>
    </form>

    <?php elseif($_GET['menu'] == 'stock'): ?>
    <div class="border-bottom border-success mb-3">STOCK INQUIRY</div>
    <form method="POST">
        <div class="mb-3">
            <label>SCAN BIN / PRODUCT</label>
            <input type="text" name="scan_query" class="form-control form-control-sm" placeholder="BIN or SKU..." autofocus>
        </div>
        <button type="submit" name="check_stock" class="btn btn-rf text-center">SEARCH (ENT)</button>
        <a href="rf_scanner.php" class="btn btn-rf text-center mt-2" style="border-color:#555; color:#777;">BACK (F7)</a>
    </form>

    <?php 
    if(isset($_POST['check_stock'])): 
        $scan = $_POST['scan_query'];
        // JOIN KE TABEL PRODUCTS BIAR BISA SEARCH PAKE SKU (BUKAN UUID)
        $q = mysqli_query($conn, "
            SELECT q.qty, q.lgpla, p.product_code 
            FROM wms_quants q 
            JOIN wms_products p ON q.product_uuid = p.product_uuid 
            WHERE p.product_code LIKE '%$scan%' OR q.lgpla = '$scan'
        ");
    ?>
        <div class="mt-3 border border-success p-2 small" style="max-height: 200px; overflow-y: auto;">
            <?php if(mysqli_num_rows($q) > 0): while($d = mysqli_fetch_assoc($q)): ?>
                <div class="mb-2 border-bottom border-secondary pb-1">
                    <span style="color:#fff;">BIN:</span> <?= $d['lgpla'] ?><br>
                    <span style="color:#fff;">MAT:</span> <?= $d['product_code'] ?><br>
                    <span style="color:#fff;">QTY:</span> <?= number_format($d['qty']) ?>
                </div>
            <?php endwhile; else: echo "<span style='color:red'>DATA NOT FOUND</span>"; endif; ?>
        </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>