<?php
// apps/wms/stock_master.php
// V9: INVENTORY DASHBOARD (Premium UI + Accurate Stats)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

// STATISTIK REAL-TIME
// Menggunakan COALESCE agar tidak error null
$stats = safeGetOne($pdo, "
    SELECT 
        COUNT(DISTINCT product_uuid) as total_sku,
        COALESCE(SUM(qty), 0) as total_items,
        COALESCE(SUM(CASE WHEN stock_type='B6' THEN qty ELSE 0 END), 0) as total_blocked
    FROM wms_quants
");

// STOCK OVERVIEW (Group by Product & Status)
$stocks = safeGetAll($pdo, "
    SELECT 
        p.product_code, p.description, p.base_uom,
        SUM(CASE WHEN q.stock_type = 'F1' THEN q.qty ELSE 0 END) as stock_f1,
        SUM(CASE WHEN q.stock_type = 'Q4' THEN q.qty ELSE 0 END) as stock_q4,
        SUM(CASE WHEN q.stock_type = 'B6' THEN q.qty ELSE 0 END) as stock_b6,
        SUM(q.qty) as total_stock
    FROM wms_products p
    LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid
    GROUP BY p.product_uuid 
    ORDER BY p.product_code ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, sans-serif; padding-bottom: 50px; }
        
        /* Stats Card */
        .card-stat { border: none; border-radius: 12px; padding: 20px; color: white; position: relative; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-stat h2 { font-weight: 800; margin: 0; font-size: 2.5rem; }
        .card-stat h6 { font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; opacity: 0.8; }
        .card-stat .icon { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 4rem; opacity: 0.2; }
        
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .bg-green { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-red { background: linear-gradient(135deg, #ef4444, #dc2626); }

        /* Menu Card */
        .menu-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        /* Table */
        .table-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .table thead th { background: #f1f5f9; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 1400px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark">Inventory Master</h3>
            <p class="text-muted m-0">Real-time stock monitoring & control</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Dashboard</a>
    </div>

    <div class="menu-card">
        <span class="text-muted small fw-bold text-uppercase me-2">Quick Actions:</span>
        <a href="internal.php" class="btn btn-outline-primary fw-bold btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Internal Transfer / Status Change
        </a>
        <a href="physical_inventory.php" class="btn btn-outline-dark fw-bold btn-sm">
            <i class="bi bi-clipboard-check me-1"></i> Stock Opname (PI)
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-stat bg-blue">
                <h6>Total SKU</h6>
                <h2><?= number_format($stats['total_sku']) ?></h2>
                <i class="bi bi-tags-fill icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stat bg-green">
                <h6>Total Items (Pcs)</h6>
                <h2><?= number_format($stats['total_items']) ?></h2>
                <i class="bi bi-box-seam-fill icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stat bg-red">
                <h6>Blocked (B6)</h6>
                <h2><?= number_format($stats['total_blocked']) ?></h2>
                <i class="bi bi-slash-circle-fill icon"></i>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0 text-primary"><i class="bi bi-table me-2"></i> Stock Overview (MB52)</h6>
            <button class="btn btn-sm btn-success" onclick="alert('Export feature coming soon!')">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Description</th>
                        <th class="text-center">UoM</th>
                        <th class="text-end text-success">Unrestricted (F1)</th>
                        <th class="text-end text-warning">Quality (Q4)</th>
                        <th class="text-end text-danger">Blocked (B6)</th>
                        <th class="text-end fw-bold">Total Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($stocks)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No stock data available.</td></tr>
                    <?php endif; ?>

                    <?php foreach($stocks as $row): ?>
                    <tr>
                        <td class="fw-bold"><?= $row['product_code'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td class="text-center text-muted"><?= $row['base_uom'] ?></td>
                        <td class="text-end fw-bold text-success"><?= number_format($row['stock_f1']) ?></td>
                        <td class="text-end fw-bold text-warning"><?= number_format($row['stock_q4']) ?></td>
                        <td class="text-end fw-bold text-danger"><?= number_format($row['stock_b6']) ?></td>
                        <td class="text-end fw-bold fs-6"><?= number_format($row['total_stock']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>