<?php
include '../../koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Warehouse Task Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .badge-putaway { background-color: #0d6efd; }
        .badge-picking { background-color: #198754; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h3><i class="bi bi-list-check"></i> Warehouse Task Monitor (WT)</h3>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>WT Number</th>
                        <th>Process Type</th>
                        <th>Product</th>
                        <th>Source Bin (VLPLA)</th>
                        <th>Dest. Bin (NLPLA)</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Kita perlu query manual karena di file inbound/outbound sebelumnya
                    // kita belum insert ke tabel 'wms_warehouse_tasks'.
                    // TAPI, untuk simulasi, kita tarik dari tabel wms_quants (Stok saat ini) 
                    // sebagai representasi 'Confirmed WT' (Putaway).
                    // *Idealnya, pas Inbound/Outbound, kita insert juga ke tabel wms_warehouse_tasks*
                    
                    // Cek tabel WT
                    $q = mysqli_query($conn, "
                        SELECT t.*, p.product_code 
                        FROM wms_warehouse_tasks t
                        JOIN wms_products p ON t.product_uuid = p.product_uuid
                        ORDER BY t.tanum DESC
                    ");

                    if(mysqli_num_rows($q) > 0) {
                        while($row = mysqli_fetch_assoc($q)):
                    ?>
                    <tr>
                        <td><?= str_pad($row['tanum'], 10, "0", STR_PAD_LEFT) ?></td>
                        <td>
                            <?php if($row['process_type'] == 'PUTAWAY'): ?>
                                <span class="badge badge-putaway">PUTAWAY</span>
                            <?php else: ?>
                                <span class="badge badge-picking">PICKING</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['product_code'] ?></td>
                        <td><?= $row['source_bin'] ? $row['source_bin'] : '-' ?></td>
                        <td><?= $row['dest_bin'] ? $row['dest_bin'] : '-' ?></td>
                        <td class="fw-bold"><?= number_format($row['qty'],0) ?></td>
                        <td><span class="badge bg-success">CONFIRMED</span></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                    <?php endwhile; 
                    } else {
                        echo "<tr><td colspan='8' class='text-center text-muted py-4'>Belum ada Warehouse Task tercatat. Lakukan Inbound/Outbound terlebih dahulu.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="alert alert-info mt-3 small">
        <i class="bi bi-info-circle"></i> 
        <strong>Konsep SAP EWM:</strong> Setiap pergerakan stok (Goods Receipt atau Goods Issue) akan menghasilkan 
        <strong>Warehouse Task (WT)</strong>. Tabel di atas merepresentasikan WO/WT yang sudah di-confirm.
    </div>
</div>

</body>
</html>