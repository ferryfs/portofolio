<?php
include '../../koneksi.php';
$so = $_GET['so'];

// Header
$q_head = mysqli_query($conn, "SELECT * FROM wms_so_header WHERE so_number='$so'");
$d_head = mysqli_fetch_assoc($q_head);

// Items
$q_items = mysqli_query($conn, "
    SELECT i.*, p.product_code, p.description, p.base_uom 
    FROM wms_so_items i
    JOIN wms_products p ON i.product_uuid = p.product_uuid
    WHERE i.so_number = '$so'
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Surat Jalan - <?= $so ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px; }
        .info { width: 100%; margin-bottom: 20px; }
        .info td { padding: 5px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid black; padding: 8px; text-align: left; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .sign { text-align: center; width: 150px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>SURAT JALAN (DELIVERY NOTE)</h2>
        <h3>PT. MAJU MUNDUR LOGISTICS</h3>
    </div>

    <table class="info">
        <tr>
            <td width="150"><strong>No. Dokumen</strong></td>
            <td>: <?= $so ?></td>
            <td width="150"><strong>Tanggal Kirim</strong></td>
            <td>: <?= $d_head['delivery_date'] ?></td>
        </tr>
        <tr>
            <td><strong>Kepada Yth.</strong></td>
            <td>: <?= $d_head['customer_name'] ?></td>
            <td><strong>Plat Truk</strong></td>
            <td>: ______________</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Deskripsi</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:center;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1;
            while($row = mysqli_fetch_assoc($q_items)): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['product_code'] ?></td>
                <td><?= $row['description'] ?></td>
                <td style="text-align:center; font-weight:bold;"><?= $row['qty_ordered'] ?></td>
                <td style="text-align:center;"><?= $row['base_uom'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="sign">
            <p>Penerima,</p>
            <br><br><br>
            (________________)
        </div>
        <div class="sign">
            <p>Supir,</p>
            <br><br><br>
            (________________)
        </div>
        <div class="sign">
            <p>Kepala Gudang,</p>
            <br><br><br>
            (________________)
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.history.back()">Kembali</button>
    </div>

</body>
</html>