<?php include '../../koneksi.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RF Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Gaya Layar Scanner Jadul/Industrial */
        body { background-color: #333; color: #0f0; font-family: 'Courier New', monospace; }
        .rf-screen { 
            background-color: #000; 
            border: 15px solid #555; 
            border-radius: 10px; 
            max-width: 400px; 
            margin: 20px auto; 
            padding: 20px; 
            min-height: 600px; 
            box-shadow: 0 0 20px rgba(0,255,0,0.1);
        }
        .rf-header { border-bottom: 2px solid #0f0; margin-bottom: 20px; padding-bottom: 10px; }
        .btn-rf { 
            background: #000; 
            border: 2px solid #0f0; 
            color: #0f0; 
            width: 100%; 
            text-align: left; 
            margin-bottom: 10px; 
            font-weight: bold; 
            border-radius: 0;
        }
        .btn-rf:hover, .btn-rf:active { background: #0f0; color: #000; }
        .f-key { color: #ff0; float: right; }
        input { background: #000 !important; border: 1px solid #0f0 !important; color: #0f0 !important; border-radius: 0 !important; }
        label { color: #0f0; }
    </style>
</head>
<body>

<div class="rf-screen">
    <div class="rf-header d-flex justify-content-between">
        <span>RF01</span>
        <span>Whse: 0001</span>
    </div>

    <?php if(!isset($_GET['menu'])): ?>
    <h5 class="text-center mb-4">SAP EWM RF MENU</h5>
    
    <a href="?menu=inbound" class="btn btn-rf">1. Inbound Process <span class="f-key">F1</span></a>
    <a href="?menu=outbound" class="btn btn-rf">2. Outbound Process <span class="f-key">F2</span></a>
    <a href="?menu=stock" class="btn btn-rf">3. Stock Inquiry <span class="f-key">F3</span></a>
    <a href="index.php" class="btn btn-rf mt-4">4. Logoff <span class="f-key">F4</span></a>

    <?php elseif($_GET['menu'] == 'stock'): ?>
    <h6 class="text-uppercase border-bottom border-success mb-3">Stock Inquiry</h6>
    <form method="POST">
        <div class="mb-3">
            <label>Scan Product / Bin</label>
            <input type="text" name="scan_query" class="form-control" autofocus>
        </div>
        <button type="submit" name="check_stock" class="btn btn-rf text-center">ENTER</button>
        <a href="rf_scanner.php" class="btn btn-rf text-center mt-2">BACK (F7)</a>
    </form>

    <?php 
    if(isset($_POST['check_stock'])): 
        $scan = $_POST['scan_query'];
        // Simple search query
        $q = mysqli_query($conn, "SELECT q.qty, s.lgpla FROM wms_quants q JOIN wms_storage_bins s ON q.lgpla = s.lgpla WHERE q.product_uuid LIKE '%$scan%' OR s.lgpla = '$scan'");
    ?>
        <div class="mt-3 border border-success p-2 small">
            <?php if(mysqli_num_rows($q) > 0): while($d = mysqli_fetch_assoc($q)): ?>
                Bin: <?= $d['lgpla'] ?><br>Qty: <?= $d['qty'] ?><hr style="border-color:#0f0; margin:5px 0;">
            <?php endwhile; else: echo "DATA NOT FOUND"; endif; ?>
        </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center mt-5">
        <p>Menu Coming Soon...</p>
        <a href="rf_scanner.php" class="btn btn-rf text-center">BACK (F7)</a>
    </div>
    <?php endif; ?>

</div>

</body>
</html>