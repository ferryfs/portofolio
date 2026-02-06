<?php
// apps/wms/inbound.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../../config/database.php';

// Mockup Data (Bisa diganti query ke tabel wms_po_header kalau sudah ada)
$incoming_list = [
    ['po_number' => 'PO-2023-001', 'vendor' => 'PT. SUPPLIER JAYA', 'eta' => date('Y-m-d'), 'total_item' => 2, 'status' => 'OPEN'],
    ['po_number' => 'PO-2023-002', 'vendor' => 'CV. SUMBER MAKMUR', 'eta' => date('Y-m-d', strtotime('+1 day')), 'total_item' => 5, 'status' => 'OPEN']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inbound Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-box-arrow-in-right text-primary me-2"></i> Inbound Monitor</h3>
            <p class="text-muted small mb-0">Daftar Kedatangan Barang (PO)</p>
        </div>
        <div>
            <a href="receiving.php" class="btn btn-primary fw-bold shadow-sm"><i class="bi bi-plus-lg me-1"></i> Direct Receiving</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>PO Number</th><th>Vendor</th><th>Est. Arrival (ETA)</th><th class="text-center">Total SKU</th><th class="text-center">Status</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($incoming_list as $row): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= $row['po_number'] ?></td>
                            <td><?= $row['vendor'] ?></td>
                            <td><?= $row['eta'] ?></td>
                            <td class="text-center"><?= $row['total_item'] ?></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?= $row['status'] ?></span></td>
                            <td class="text-end">
                                <a href="receiving.php?po=<?= $row['po_number'] ?>" class="btn btn-sm btn-outline-primary fw-bold">Process GR <i class="bi bi-arrow-right ms-1"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>