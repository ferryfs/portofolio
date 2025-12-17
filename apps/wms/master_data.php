<?php
include '../../koneksi.php';

// 1. CREATE PRODUCT
if(isset($_POST['add_product'])) {
    $uuid = "PROD-" . rand(1000,9999);
    $code = $_POST['product_code'];
    $desc = $_POST['description'];
    $uom  = $_POST['base_uom'];
    
    $sql = "INSERT INTO wms_products VALUES ('$uuid', '$code', '$desc', '$uom')";
    if(mysqli_query($conn, $sql)) $msg = "✅ Product Master Created: $code";
}

// 2. CREATE STORAGE BIN
if(isset($_POST['add_bin'])) {
    $bin  = $_POST['lgpla'];
    $type = $_POST['lgtyp'];
    $max  = $_POST['max_weight'];
    
    $sql = "INSERT INTO wms_storage_bins (lgpla, lgtyp, max_weight) VALUES ('$bin', '$type', '$max')";
    if(mysqli_query($conn, $sql)) $msg = "✅ Storage Bin Created: $bin";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Master Data EWM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h3><i class="bi bi-database-gear"></i> Master Data Management</h3>
        <a href="index.php" class="btn btn-secondary">Back to Monitor</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark fw-bold">1. Product Master (MAT1)</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-2">
                            <label>Product Code (SKU)</label>
                            <input type="text" name="product_code" class="form-control" placeholder="E.g. MAT-LPT-X1" required>
                        </div>
                        <div class="mb-2">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" placeholder="E.g. Laptop Thinkpad" required>
                        </div>
                        <div class="mb-3">
                            <label>Base UoM</label>
                            <select name="base_uom" class="form-select">
                                <option value="PCS">PCS (Pieces)</option>
                                <option value="BOX">BOX (Carton)</option>
                                <option value="KG">KG (Kilogram)</option>
                            </select>
                        </div>
                        <button type="submit" name="add_product" class="btn btn-warning w-100">Create Product</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white fw-bold">2. Storage Bin Master (/SCWM/LS01)</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-2">
                            <label>Storage Bin (Lgpla)</label>
                            <input type="text" name="lgpla" class="form-control" placeholder="E.g. A-01-01" required>
                        </div>
                        <div class="mb-2">
                            <label>Storage Type</label>
                            <select name="lgtyp" class="form-select">
                                <?php
                                $st = mysqli_query($conn, "SELECT * FROM wms_storage_types");
                                while($r = mysqli_fetch_array($st)) {
                                    echo "<option value='".$r['lgtyp']."'>".$r['lgtyp']." - ".$r['description']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Max Weight (KG)</label>
                            <input type="number" name="max_weight" class="form-control" value="1000">
                        </div>
                        <button type="submit" name="add_bin" class="btn btn-info text-white w-100">Create Bin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Current Master Data Overview</div>
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h6>Last 5 Products</h6>
                    <ul class="list-group">
                        <?php 
                        $p = mysqli_query($conn, "SELECT * FROM wms_products ORDER BY product_code DESC LIMIT 5");
                        while($row = mysqli_fetch_assoc($p)) echo "<li class='list-group-item'>".$row['product_code']." - ".$row['description']."</li>";
                        ?>
                    </ul>
                </div>
                <div class="col-6">
                    <h6>Last 5 Bins</h6>
                    <ul class="list-group">
                        <?php 
                        $b = mysqli_query($conn, "SELECT * FROM wms_storage_bins ORDER BY lgpla DESC LIMIT 5");
                        while($row = mysqli_fetch_assoc($b)) echo "<li class='list-group-item'>[".$row['lgtyp']."] ".$row['lgpla']."</li>";
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>