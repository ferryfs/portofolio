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

// LOGIC POST GOODS ISSUE (FINALISASI PENGIRIMAN)
if(isset($_POST['post_gi'])) {
    $so_number = $_POST['so_number'];
    
    // 1. Update Status SO jadi COMPLETED
    mysqli_query($conn, "UPDATE wms_so_header SET status='COMPLETED' WHERE so_number='$so_number'");
    
    // 2. HAPUS STOK DARI GI-ZONE (BARANG KELUAR FISIK)
    // Kita cari barang-barang yang ada di SO ini
    $q_items = mysqli_query($conn, "SELECT product_uuid, qty_ordered FROM wms_so_items WHERE so_number='$so_number'");
    
    while($item = mysqli_fetch_assoc($q_items)) {
        $prod = $item['product_uuid'];
        $qty  = $item['qty_ordered'];
        
        // Hapus stok di GI-ZONE (FIFO: Hapus sembarang batch di zona itu)
        // Note: Ini simplifikasi. Aslinya harus match HU/Batch.
        $q_stok = mysqli_query($conn, "SELECT * FROM wms_quants WHERE lgpla='GI-ZONE' AND product_uuid='$prod' LIMIT 1");
        $d_stok = mysqli_fetch_assoc($q_stok);
        
        if($d_stok) {
            $sisa = $d_stok['qty'] - $qty;
            if($sisa <= 0) {
                mysqli_query($conn, "DELETE FROM wms_quants WHERE quant_id='{$d_stok['quant_id']}'");
            } else {
                mysqli_query($conn, "UPDATE wms_quants SET qty='$sisa' WHERE quant_id='{$d_stok['quant_id']}'");
            }
        }
    }
    
    $msg = "✅ <b>PGI Success!</b> SO $so_number telah closed. Stok resmi berkurang dari pembukuan.";
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
                        <th class="text-center">Picking Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Tampilkan SO yang masih OPEN (Belum di PGI)
                    $q_so = mysqli_query($conn, "SELECT * FROM wms_so_header WHERE status != 'COMPLETED' ORDER BY so_number ASC");
                    
                    if(mysqli_num_rows($q_so) > 0) {
                        while($row = mysqli_fetch_assoc($q_so)):
                            $so = $row['so_number'];
                            
                            // Hitung Progress Picking
                            // Total Item di SO
                            $q1 = mysqli_query($conn, "SELECT SUM(qty_ordered) as total FROM wms_so_items WHERE so_number='$so'");
                            $d1 = mysqli_fetch_assoc($q1);
                            $total_order = $d1['total'] ?? 0;

                            // Total Item yang sudah di-confirm Task-nya (Sudah Picking)
                            // Kita cek Task Picking yang CONFIRMED untuk SO ini
                            $q2 = mysqli_query($conn, "
                                SELECT SUM(qty) as picked 
                                FROM wms_warehouse_tasks 
                                WHERE process_type='PICKING' AND status='CONFIRMED' AND dest_bin LIKE '%$so%'
                            ");
                            // Note: Logic dest_bin LIKE SO ini butuh penyesuaian di outbound.php nanti kalau mau presisi.
                            // Untuk sekarang kita pake asumsi simple: Cek stok di GI-ZONE yang relevan.
                            
                            // ALTERNATIF LOGIC PICKING STATUS:
                            // Cek apakah ada Task Picking yang masih OPEN untuk SO ini?
                            $q3 = mysqli_query($conn, "SELECT COUNT(*) as pending FROM wms_warehouse_tasks WHERE process_type='PICKING' AND status='OPEN' AND dest_bin LIKE '%$so%'");
                            // (Perlu update outbound.php sedikit biar kolom dest_bin atau source nyimpen SO Number, tapi skip dulu buat simplifikasi)
                            
                            // SIMPLIFIKASI STATUS:
                            // Kita anggap "Ready to Ship" kalau user klik tombol.
                    ?>
                    <tr>
                        <td class="fw-bold text-primary"><?= $row['so_number'] ?></td>
                        <td><?= $row['customer_name'] ?></td>
                        <td><?= $row['delivery_date'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">In Progress</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="print_sj.php?so=<?= $row['so_number'] ?>" target="_blank" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-printer"></i> Print SJ
                                </a>
                                
                                <form method="POST" onsubmit="return confirm('Yakin Post GI? Stok akan berkurang permanen.');">
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