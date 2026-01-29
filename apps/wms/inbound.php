<?php
// 🔥 1. PASANG SESSION DI PALING ATAS
session_name("WMS_APP_SESSION");
session_start();

// 🔥 2. CEK KEAMANAN (Opsional tapi PENTING)
// Biar orang gak bisa buka file ini langsung lewat URL tanpa login
if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}
include '../../koneksi.php';

if(isset($_POST['post_gr'])) {
    $prod_uuid = $_POST['product_uuid'];
    $qty_input = $_POST['qty'];
    $uom_mode  = $_POST['uom_mode']; // User pilih: 'BASE' (Pcs) atau 'PACK' (Pallet)
    $vendor    = $_POST['vendor'];

    // 1. Ambil Master Data Produk & Konversi
    $q_prod = mysqli_query($conn, "SELECT * FROM wms_products WHERE product_uuid = '$prod_uuid'");
    $d_prod = mysqli_fetch_assoc($q_prod);
    
    // Logic Konversi Satuan & Loop
    if($uom_mode == 'PACK') {
        // User input misal 2 Pallet. 1 Pallet = 50 Pcs.
        $qty_per_hu = $d_prod['conversion_qty']; // 50
        $loop_count = $qty_input;                // Loop 2 kali (Buat 2 HU)
        $qty_save   = $qty_per_hu;               // Setiap HU isinya 50 Pcs
    } else {
        // User input 100 Pcs (Eceran/Loose)
        $qty_per_hu = $qty_input; 
        $loop_count = 1;                         // Loop 1 kali (1 HU besar/Loose)
        $qty_save   = $qty_input;
    }

    $batch   = "BATCH-" . date('ymd');
    $gr_date = date('Y-m-d');
    $status  = 'F1'; // Default Available

    // 2. Loop Generate HU & Putaway
    $success_count = 0;
    
    for($i=1; $i<=$loop_count; $i++) {
        $hu_id = "HU" . rand(100000,999999); // Generate ID Pallet
        
        // Cari Bin Kosong (Empty Bin Strategy)
        $cari_bin = mysqli_query($conn, "SELECT b.lgpla FROM wms_storage_bins b LEFT JOIN wms_quants q ON b.lgpla = q.lgpla WHERE b.lgtyp = '0010' AND q.quant_id IS NULL LIMIT 1");
        $bin_data = mysqli_fetch_assoc($cari_bin);

        if($bin_data) {
            $target_bin = $bin_data['lgpla'];
            
            // Simpan ke Stok (Selalu dalam Base UoM / Pcs)
            $sql = "INSERT INTO wms_quants (product_uuid, lgpla, batch, qty, gr_date, stock_type, hu_id) 
                    VALUES ('$prod_uuid', '$target_bin', '$batch', '$qty_save', '$gr_date', '$status', '$hu_id')";
            
            if(mysqli_query($conn, $sql)) {
                // Log Warehouse Task (Activity: PUTAWAY)
                mysqli_query($conn, "INSERT INTO wms_warehouse_tasks 
                (process_type, product_uuid, batch, hu_id, source_bin, dest_bin, qty, status)
                VALUES ('PUTAWAY', '$prod_uuid', '$batch', '$hu_id', 'VENDOR: $vendor', '$target_bin', '$qty_save', 'CONFIRMED')");
                $success_count++;
            }
        }
    }

    if($success_count > 0) {
        $total_pcs = $success_count * $qty_save;
        $msg = "✅ <b>Inbound Sukses!</b><br>Berhasil membuat $success_count Handling Unit (Total $total_pcs Pcs) dan melakukan Putaway otomatis.";
        $msg_type = "success";
    } else {
        $msg = "⚠️ <b>Gagal Putaway:</b> Gudang Penuh (Tidak ada Bin kosong).";
        $msg_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head> 
    <title>Inbound Delivery</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Inbound Process (GR)</h4>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Monitor</a>
    </div>

    <div class="card shadow border-primary col-md-8 mx-auto">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-box-arrow-in-down"></i> Goods Receipt</h5>
        </div>
        <div class="card-body">
            
            <div class="alert alert-info small">
                <strong><i class="bi bi-info-circle"></i> Alur Proses:</strong><br>
                1. Pilih Vendor & Produk.<br>
                2. Pilih Satuan: <strong>Unit (Pcs)</strong> atau <strong>Packing (Pallet)</strong>.<br>
                3. Sistem akan otomatis:
                <ul>
                    <li>Mengkonversi satuan Packing ke Pcs (sesuai Master Data).</li>
                    <li>Membuat Handling Unit (HU) unik.</li>
                    <li>Mencari Bin kosong dan melakukan <strong>Putaway</strong>.</li>
                </ul>
            </div>

            <?php if(isset($msg)) echo "<div class='alert alert-$msg_type'>$msg</div>"; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="fw-bold">1. Vendor</label>
                    <input type="text" name="vendor" class="form-control" placeholder="e.g. PT. Supplier Jaya" required>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">2. Product</label>
                    <select name="product_uuid" class="form-select" id="prodSelect" onchange="updateInfo()" required>
                        <option value="">-- Pilih Material --</option>
                        <?php 
                        $p = mysqli_query($conn, "SELECT * FROM wms_products");
                        while($row = mysqli_fetch_assoc($p)) {
                            // Simpan data konversi di atribut HTML
                            echo "<option value='".$row['product_uuid']."' data-uom='".$row['base_uom']."' data-pack='".$row['capacity_uom']."' data-conv='".$row['conversion_qty']."'>".$row['product_code']." - ".$row['description']."</option>";
                        }
                        ?>
                    </select>
                    <div id="convInfo" class="form-text text-primary fw-bold mt-1"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">3. Input Type</label>
                        <select name="uom_mode" id="uomMode" class="form-select" onchange="updateLabel()">
                            <option value="PACK">Per Packing (HU/Pallet)</option>
                            <option value="BASE">Per Unit (Pcs)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold" id="qtyLabel">4. Quantity</label>
                        <input type="number" name="qty" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="post_gr" class="btn btn-primary w-100 fw-bold">POST GOODS RECEIPT</button>
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
        document.getElementById('convInfo').innerText = `ℹ️ Master Data: 1 ${pack} berisi ${conv} ${base}`;
        updateLabel();
    }
}

function updateLabel() {
    let mode = document.getElementById('uomMode').value;
    let sel = document.getElementById('prodSelect');
    let opt = sel.options[sel.selectedIndex];
    
    if(opt.value) {
        let label = (mode === 'PACK') ? opt.getAttribute('data-pack') : opt.getAttribute('data-uom');
        document.getElementById('qtyLabel').innerText = "4. Quantity (" + label + ")";
    }
}
</script>
</body>
</html>