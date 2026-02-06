<?php
// apps/wms/print_sj.php (PDO FULL)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak.");
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$so = sanitizeInput($_GET['so']);

// 1. Ambil Header
$stmt_h = $pdo->prepare("SELECT * FROM wms_so_header WHERE so_number = ?");
$stmt_h->execute([$so]);
$d_head = $stmt_h->fetch(PDO::FETCH_ASSOC);

if(!$d_head) die("Data SO tidak ditemukan.");

// 2. Ambil Items
$stmt_i = $pdo->prepare("
    SELECT i.*, p.product_code, p.description, p.base_uom 
    FROM wms_so_items i
    JOIN wms_products p ON i.product_uuid = p.product_uuid
    WHERE i.so_number = ?
");
$stmt_i->execute([$so]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Surat Jalan - <?= $so ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 14px; }
        .header { text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px; }
        .info { width: 100%; margin-bottom: 20px; }
        .info td { padding: 5px; vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items th, table.items td { border: 1px solid black; padding: 8px; text-align: left; }
        .footer { display: flex; justify-content: space-between; text-align: center; }
        .sign { width: 200px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2 style="margin:0;">SURAT JALAN (DELIVERY NOTE)</h2>
        <h4 style="margin:5px 0;">PT. LOGISTIK MAJU MUNDUR</h4>
    </div>

    <table class="info">
        <tr>
            <td width="150"><strong>No. Dokumen</strong></td>
            <td>: <?= htmlspecialchars($so) ?></td>
            <td width="150"><strong>Tanggal Kirim</strong></td>
            <td>: <?= htmlspecialchars($d_head['delivery_date']) ?></td>
        </tr>
        <tr>
            <td><strong>Kepada Yth.</strong></td>
            <td>: <?= htmlspecialchars($d_head['customer_name']) ?></td>
            <td><strong>Plat Truk</strong></td>
            <td>: __________________</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr style="background: #eee;">
                <th width="5%" style="text-align:center;">No</th>
                <th width="20%">Kode Barang</th>
                <th>Deskripsi Barang</th>
                <th width="10%" style="text-align:center;">Qty</th>
                <th width="10%" style="text-align:center;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; while($row = $stmt_i->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['product_code']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td style="text-align:center; font-weight:bold;"><?= (float)$row['qty_ordered'] ?></td>
                <td style="text-align:center;"><?= htmlspecialchars($row['base_uom']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="sign">
            <p>Penerima,</p>
            <br><br><br>
            (____________________)
        </div>
        <div class="sign">
            <p>Supir / Ekspedisi,</p>
            <br><br><br>
            (____________________)
        </div>
        <div class="sign">
            <p>Hormat Kami,</p>
            <br><br><br>
            (____________________)
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">&laquo; Kembali</button>
    </div>

</body>
</html>