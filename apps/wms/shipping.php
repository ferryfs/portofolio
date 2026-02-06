<?php
// apps/wms/shipping.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php'; 

// LOGIC POST GOODS ISSUE (FINALISASI PENGIRIMAN)
if(isset($_POST['post_gi'])) {
    if (!verifyCSRFToken()) die("Token Invalid");

    $so_number = sanitizeInput($_POST['so_number']);
    $user      = $_SESSION['wms_fullname'];
    
    try {
        $pdo->beginTransaction();

        // 1. Update Status SO jadi COMPLETED
        safeQuery($pdo, "UPDATE wms_so_header SET status='COMPLETED' WHERE so_number=?", [$so_number]);
        
        // 2. HAPUS STOK DARI GI-ZONE (FISIK KELUAR GUDANG)
        // Ambil item yang dipesan
        $stmt_items = $pdo->prepare("SELECT product_uuid, qty_ordered FROM wms_so_items WHERE so_number=?");
        $stmt_items->execute([$so_number]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($items as $item) {
            $prod = $item['product_uuid'];
            $qty  = $item['qty_ordered'];
            
            // FIFO Simplified: Ambil stok di GI-ZONE (hasil picking)
            // Kurangi sampai habis
            $stmt_stok = $pdo->prepare("SELECT * FROM wms_quants WHERE lgpla='GI-ZONE' AND product_uuid=? ORDER BY gr_date ASC");
            $stmt_stok->execute([$prod]);
            
            $sisa_butuh = $qty;

            while ($sisa_butuh > 0 && $d_stok = $stmt_stok->fetch(PDO::FETCH_ASSOC)) {
                $qty_di_bin = $d_stok['qty'];
                
                if ($qty_di_bin <= $sisa_butuh) {
                    // Stok di bin ini habis semua
                    safeQuery($pdo, "DELETE FROM wms_quants WHERE quant_id=?", [$d_stok['quant_id']]);
                    $sisa_butuh -= $qty_di_bin;
                } else {
                    // Stok di bin ini sisa
                    $sisa_stok = $qty_di_bin - $sisa_butuh;
                    safeQuery($pdo, "UPDATE wms_quants SET qty=? WHERE quant_id=?", [$sisa_stok, $d_stok['quant_id']]);
                    $sisa_butuh = 0;
                }
            }
        }
        
        $pdo->commit();
        catat_log($pdo, $user, 'PGI', 'OUTBOUND', "Post Goods Issue SO: $so_number");
        $msg = "✅ <b>PGI Success!</b> SO $so_number telah closed. Stok resmi berkurang.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error PGI: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Shipping & GI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="bi bi-truck"></i> Shipping & Goods Issue</h4>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white">
            <h6 class="mb-0">Outbound Delivery Monitor</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>SO Number</th>
                        <th>Customer</th>
                        <th>Delivery Date</th>
                        <th class="text-center">Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Tampilkan SO yang masih OPEN (Belum di PGI)
                    $stmt_so = $pdo->query("SELECT * FROM wms_so_header WHERE status != 'COMPLETED' ORDER BY so_number ASC");
                    
                    if($stmt_so->rowCount() > 0) {
                        while($row = $stmt_so->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td class="fw-bold text-primary"><?= $row['so_number'] ?></td>
                        <td><?= $row['customer_name'] ?></td>
                        <td><?= $row['delivery_date'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">Ready to Ship</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="print_sj.php?so=<?= $row['so_number'] ?>" target="_blank" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-printer"></i> Print SJ
                                </a>
                                
                                <form method="POST" onsubmit="return confirm('Yakin Post GI? Stok akan berkurang permanen.');">
                                    <?php echo csrfTokenField(); ?>
                                    <input type="hidden" name="so_number" value="<?= $row['so_number'] ?>">
                                    <button type="submit" name="post_gi" class="btn btn-sm btn-success ms-1">
                                        <i class="bi bi-box-arrow-right"></i> Post GI
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; 
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No Pending Shipments.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>