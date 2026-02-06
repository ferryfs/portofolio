<?php
// apps/wms/stock_master.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) exit("Akses Ditolak.");
require_once __DIR__ . '/../../config/database.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Inventory Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .card-stat { border: none; border-radius: 10px; transition: 0.3s; }
        .card-stat:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-boxes"></i> Inventory Management</h3>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex gap-3">
            <a href="internal.php" class="btn btn-outline-primary fw-bold"><i class="bi bi-arrows-move"></i> Internal Transfer / Ad-Hoc</a>
            <a href="physical_inventory.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-clipboard-check"></i> Stock Opname (PI)</a>
            <div class="vr"></div> 
            <span class="align-self-center text-muted small">Menu Operasional Gudang</span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <?php
        $sql = "SELECT 
                COUNT(DISTINCT product_uuid) as total_sku,
                SUM(qty) as total_items,
                SUM(CASE WHEN stock_type='B6' THEN qty ELSE 0 END) as total_blocked
                FROM wms_quants";
        $d_sum = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="col-md-4">
            <div class="card card-stat bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-white-50 mb-0">Total SKU</h6><h2 class="fw-bold mb-0"><?= number_format($d_sum['total_sku'] ?? 0) ?></h2></div>
                    <i class="bi bi-upc-scan fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-white-50 mb-0">Total Stock (Pcs)</h6><h2 class="fw-bold mb-0"><?= number_format($d_sum['total_items'] ?? 0) ?></h2></div>
                    <i class="bi bi-box-seam fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-danger text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-white-50 mb-0">Blocked (B6)</h6><h2 class="fw-bold mb-0"><?= number_format($d_sum['total_blocked'] ?? 0) ?></h2></div>
                    <i class="bi bi-x-octagon fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0"><i class="bi bi-table"></i> Stock Overview (MB52)</h6>
            <a href="export_stock.php" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Code</th><th>Description</th><th class="text-center">UoM</th>
                            <th class="text-end bg-success bg-opacity-25">Unrestricted (F1)</th>
                            <th class="text-end bg-warning bg-opacity-25">Quality (Q4)</th>
                            <th class="text-end bg-danger bg-opacity-25">Blocked (B6)</th>
                            <th class="text-end fw-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Query Pivot Stok per Status
                        $sql = "SELECT 
                                    p.product_code, p.description, p.base_uom,
                                    SUM(CASE WHEN q.stock_type = 'F1' THEN q.qty ELSE 0 END) as stock_f1,
                                    SUM(CASE WHEN q.stock_type = 'Q4' THEN q.qty ELSE 0 END) as stock_q4,
                                    SUM(CASE WHEN q.stock_type = 'B6' THEN q.qty ELSE 0 END) as stock_b6,
                                    SUM(q.qty) as total_stock
                                FROM wms_products p
                                LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid
                                GROUP BY p.product_uuid ORDER BY p.product_code ASC";
                        
                        $stmt = $pdo->query($sql);
                        
                        if($stmt->rowCount() > 0) {
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td class="fw-bold"><?= $row['product_code'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td class="text-center"><?= $row['base_uom'] ?></td>
                            <td class="text-end fw-bold text-success"><?= number_format($row['stock_f1']) ?></td>
                            <td class="text-end fw-bold text-warning text-dark"><?= number_format($row['stock_q4']) ?></td>
                            <td class="text-end fw-bold text-danger"><?= number_format($row['stock_b6']) ?></td>
                            <td class="text-end fw-bold fs-6"><?= number_format($row['total_stock']) ?></td>
                        </tr>
                        <?php endwhile; 
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4'>No Stock Data Found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>