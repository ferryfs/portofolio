<?php
// apps/wms/print_gr.php
// ENTERPRISE GR & PALLET LABEL GENERATOR

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { die("Unauthorized Access"); }

require_once __DIR__ . '/../../config/database.php';
require_once 'koneksi.php'; 

$gr_num = $_GET['gr_number'] ?? '';
if(!$gr_num) die("<h2 style='color:red; font-family:sans-serif;'>❌ Error: GR Number missing!</h2>");

// Ambil Data Header & Item
$header = safeGetOne($pdo, "SELECT h.*, p.vendor_name, p.expected_date FROM wms_gr_header h JOIN wms_po_header p ON h.po_number = p.po_number WHERE h.gr_number = ?", [$gr_num]);
if(!$header) die("Document not found!");

$items = safeGetAll($pdo, "SELECT gi.*, p.product_code, p.description, p.base_uom FROM wms_gr_items gi JOIN wms_products p ON gi.product_uuid = p.product_uuid WHERE gi.gr_number = ?", [$gr_num]);
$quants = safeGetAll($pdo, "SELECT * FROM wms_quants WHERE gr_ref = ?", [$gr_num]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print GR - <?= htmlspecialchars($gr_num) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background: #e2e8f0; }
        .page { background: white; width: 210mm; min-height: 297mm; padding: 20mm; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: 800; text-transform: uppercase; margin: 0; }
        .meta-data { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .meta-data td { padding: 8px; border: 1px solid #cbd5e1; font-size: 14px; }
        .meta-data .label { font-weight: bold; background: #f8fafc; width: 150px; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .item-table th { background: #0f172a; color: white; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; }
        .item-table td { padding: 10px; border-bottom: 1px solid #cbd5e1; font-size: 14px; }
        
        .lpn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; page-break-inside: avoid; }
        .lpn-card { border: 2px dashed #000; padding: 15px; border-radius: 8px; text-align: center; }
        .lpn-title { font-weight: 800; font-size: 18px; margin-bottom: 10px; }
        .lpn-sku { font-size: 14px; font-weight: bold; color: #334155; }
        
        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; width: auto; min-height: auto; padding: 0; margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #4f46e5; color: white; border: none; border-radius: 5px;">🖨️ PRINT DOCUMENT</button>
</div>

<div class="page">
    <div class="header">
        <div>
            <h1 class="title">GOODS RECEIPT</h1>
            <div style="font-family: 'JetBrains Mono'; font-size: 14px; color: #64748b;"><?= $gr_num ?></div>
        </div>
        <div style="text-align: right;">
            <svg id="barcode-gr"></svg>
            <script>JsBarcode("#barcode-gr", "<?= $gr_num ?>", { height: 40, displayValue: false, width: 1.5 });</script>
        </div>
    </div>

    <table class="meta-data">
        <tr>
            <td class="label">Vendor Name</td>
            <td style="font-weight: bold;"><?= $header['vendor_name'] ?></td>
            <td class="label">PO Number</td>
            <td style="font-family: 'JetBrains Mono'; font-weight: bold;"><?= $header['po_number'] ?></td>
        </tr>
        <tr>
            <td class="label">Vendor DO / SJ</td>
            <td style="font-family: 'JetBrains Mono';"><?= $header['vendor_do'] ?></td>
            <td class="label">Receipt Date</td>
            <td><?= date('d F Y H:i', strtotime($header['gr_date'])) ?></td>
        </tr>
        <tr>
            <td class="label">Received By</td>
            <td><?= $header['received_by'] ?></td>
            <td class="label">Status</td>
            <td><strong><?= $header['status'] ?></strong></td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Description</th>
                <th style="text-align: center;">Good Qty</th>
                <th style="text-align: center;">Damaged</th>
                <th style="text-align: center;">Batch</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $it): ?>
            <tr>
                <td style="font-family: 'JetBrains Mono'; font-weight: bold;"><?= $it['product_code'] ?></td>
                <td><?= $it['description'] ?></td>
                <td style="text-align: center; font-weight: bold;"><?= (float)$it['qty_good'] ?> <?= $it['base_uom'] ?></td>
                <td style="text-align: center; color: red;"><?= (float)$it['qty_damaged'] ?> <?= $it['base_uom'] ?></td>
                <td style="text-align: center; font-family: 'JetBrains Mono'; font-size: 12px;"><?= $it['batch_no'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; border-top: 2px solid #000; padding-top: 20px;">
        <h3 style="margin: 0 0 15px 0;">HANDLING UNIT (LPN) LABELS</h3>
        <p style="font-size: 12px; color: #64748b; margin-top: -10px;">Please cut and attach these labels to the physical pallets/boxes before putaway.</p>
        
        <div class="lpn-grid">
            <?php foreach($quants as $index => $q): ?>
            <div class="lpn-card">
                <div class="lpn-title">ROUTING HU LABEL</div>
                <svg id="barcode-hu-<?= $index ?>"></svg>
                <script>JsBarcode("#barcode-hu-<?= $index ?>", "<?= $q['hu_id'] ?>", { height: 50, displayValue: true, fontSize: 16, font: "monospace", textMargin: 5 });</script>
                <div class="lpn-sku" style="margin-top: 10px;">
                    QTY: <span style="font-size: 20px;"><?= (float)$q['qty'] ?></span> | TYPE: <?= $q['stock_type'] ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>