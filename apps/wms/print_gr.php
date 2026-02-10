<?php
// apps/wms/print_gr.php
session_name("WMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';

$gr_num = isset($_GET['gr']) ? $_GET['gr'] : '';
if(!$gr_num) die("GR Number Missing");

// Ambil Header
$header = safeGetOne($pdo, "SELECT g.*, p.vendor_name FROM wms_gr_header g JOIN wms_po_header p ON g.po_number = p.po_number WHERE g.gr_number = ?", [$gr_num]);

// Ambil Items
$items = safeGetAll($pdo, "SELECT i.*, p.product_code, p.description, p.base_uom 
                           FROM wms_gr_items i 
                           JOIN wms_products p ON i.product_uuid = p.product_uuid 
                           WHERE i.gr_number = ?", [$gr_num]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print GR <?= $gr_num ?></title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { padding: 5px; font-size: 13px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .data-table th { background-color: #eee; }
        .footer { margin-top: 50px; width: 100%; display: flex; justify-content: space-between; }
        .sig-box { width: 200px; text-align: center; border-top: 1px solid #000; padding-top: 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2 style="margin:0">GOODS RECEIPT NOTE (GRN)</h2>
        <p style="margin:5px 0">PT. LOGITRACK INDONESIA WAREHOUSE</p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="15%"><strong>GR Number:</strong></td>
            <td width="35%"><?= $header['gr_number'] ?></td>
            <td width="15%"><strong>PO Number:</strong></td>
            <td width="35%"><?= $header['po_number'] ?></td>
        </tr>
        <tr>
            <td><strong>Received Date:</strong></td>
            <td><?= $header['gr_date'] ?></td>
            <td><strong>Vendor:</strong></td>
            <td><?= $header['vendor_name'] ?></td>
        </tr>
        <tr>
            <td><strong>Receiver:</strong></td>
            <td><?= $header['received_by'] ?></td>
            <td><strong>Vendor DO:</strong></td>
            <td><?= $header['vendor_do'] ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">SKU Code</th>
                <th>Description</th>
                <th width="15%">Batch No</th>
                <th width="10%" style="text-align:right">Good Qty</th>
                <th width="10%" style="text-align:right">Bad Qty</th>
                <th width="10%" style="text-align:center">UoM</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($items as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['product_code'] ?></td>
                <td><?= $row['description'] ?></td>
                <td><?= $row['batch_no'] ?></td>
                <td style="text-align:right"><?= number_format($row['qty_good']) ?></td>
                <td style="text-align:right"><?= number_format($row['qty_damaged']) ?></td>
                <td style="text-align:center"><?= $row['base_uom'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <div>
            <br><br><br>
            <div class="sig-box">Received By (Admin)</div>
        </div>
        <div>
            <br><br><br>
            <div class="sig-box">Delivered By (Driver)</div>
        </div>
    </div>

    <button class="no-print" onclick="window.print()" style="padding:10px 20px; font-weight:bold; margin-top:20px; cursor:pointer;">PRINT DOCUMENT</button>

</body>
</html>