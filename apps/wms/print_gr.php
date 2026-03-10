<?php
// apps/wms/print_gr.php
// ENTERPRISE GR & PALLET LABEL GENERATOR (Print & Page-Break Optimized)

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

// Ambil Data HU (Join dengan wms_products untuk memunculkan SKU di Label)
$quants = safeGetAll($pdo, "SELECT q.*, p.product_code, p.base_uom FROM wms_quants q JOIN wms_products p ON q.product_uuid = p.product_uuid WHERE q.gr_ref = ?", [$gr_num]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print GR - <?= htmlspecialchars($gr_num) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root { --main-dark: #0f172a; --border-color: #cbd5e1; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background: #e2e8f0; color: var(--main-dark); }
        
        .page { background: white; width: 210mm; min-height: 297mm; padding: 20mm; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 8px; }
        
        .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid var(--main-dark); padding-bottom: 15px; margin-bottom: 25px; }
        .title-wrapper h1 { font-size: 28px; font-weight: 800; margin: 0 0 5px 0; letter-spacing: -1px; }
        .doc-id { font-family: 'JetBrains Mono', monospace; font-size: 16px; color: #64748b; font-weight: 600; }
        
        .meta-data { width: 100%; border-collapse: collapse; margin-bottom: 35px; }
        .meta-data td { padding: 10px 12px; border: 1px solid var(--border-color); font-size: 13px; vertical-align: middle; }
        .meta-data .label { font-weight: 700; background: #f8fafc; width: 140px; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .meta-data .value { font-weight: 600; }
        .meta-data .mono { font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 14px;}
        
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .item-table th { background: var(--main-dark); color: white; padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .item-table td { padding: 12px; border-bottom: 1px solid var(--border-color); font-size: 13px; vertical-align: top; }
        
        .section-title { margin: 0 0 15px 0; font-size: 18px; font-weight: 800; border-top: 2px solid var(--main-dark); padding-top: 20px; text-transform: uppercase; }
        .section-desc { font-size: 12px; color: #64748b; margin-top: -10px; margin-bottom: 20px; }
        
        /* Layout Grid Label Pallet */
        .lpn-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        
        /* 🔥 FIX: Anti Kepotong pas Print */
        .lpn-card { border: 2px dashed var(--main-dark); padding: 15px; border-radius: 12px; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        
        .lpn-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 10px; }
        .lpn-title { font-weight: 800; font-size: 14px; color: #475569; }
        .lpn-type { font-weight: 800; font-size: 12px; padding: 3px 8px; border-radius: 4px; background: #e2e8f0; }
        
        .lpn-barcode-wrapper svg { width: 100%; max-width: 250px; height: auto; }
        
        .lpn-sku { font-family: 'JetBrains Mono', monospace; font-size: 15px; font-weight: 800; margin: 10px 0 5px 0; }
        .lpn-qty { font-size: 18px; font-weight: 800; border: 2px solid var(--main-dark); display: inline-block; padding: 5px 15px; border-radius: 8px; }

        .btn-print { padding: 12px 25px; font-size: 16px; font-weight: 700; cursor: pointer; background: #0f172a; color: white; border: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-print:hover { background: #1e293b; transform: translateY(-2px); }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; width: 100%; min-height: auto; padding: 0; margin: 0; border-radius: 0; }
            .no-print { display: none !important; }
            .lpn-card { border: 2px dashed #000; break-inside: avoid; page-break-inside: avoid; }
            .item-table th { background: #e2e8f0; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .lpn-type { background: #e2e8f0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 30px;">
    <button onclick="window.print()" class="btn-print">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
            <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
        </svg>
        PRINT DOCUMENT
    </button>
</div>

<div class="page">
    <div class="header">
        <div class="title-wrapper">
            <h1>GOODS RECEIPT</h1>
            <div class="doc-id"><?= $gr_num ?></div>
        </div>
        <div>
            <svg id="barcode-gr"></svg>
            <script>JsBarcode("#barcode-gr", "<?= $gr_num ?>", { height: 45, displayValue: false, width: 1.8, margin: 0 });</script>
        </div>
    </div>

    <table class="meta-data">
        <tr>
            <td class="label">Vendor Name</td>
            <td class="value"><?= $header['vendor_name'] ?></td>
            <td class="label">PO Number</td>
            <td class="value mono"><?= $header['po_number'] ?></td>
        </tr>
        <tr>
            <td class="label">Vendor DO / SJ</td>
            <td class="value mono"><?= $header['vendor_do'] ?></td>
            <td class="label">Receipt Date</td>
            <td class="value"><?= date('d F Y H:i', strtotime($header['gr_date'])) ?></td>
        </tr>
        <tr>
            <td class="label">Received By</td>
            <td class="value"><?= $header['received_by'] ?></td>
            <td class="label">Doc Status</td>
            <td class="value"><?= $header['status'] ?></td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th width="20%">Material SKU</th>
                <th width="40%">Description</th>
                <th width="15%" style="text-align: center;">Batch</th>
                <th width="12%" style="text-align: right;">Good Qty</th>
                <th width="13%" style="text-align: right;">Damaged</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $it): ?>
            <tr>
                <td style="font-family: 'JetBrains Mono'; font-weight: 700;"><?= $it['product_code'] ?></td>
                <td><div style="font-weight: 600;"><?= $it['description'] ?></div></td>
                <td style="text-align: center; font-family: 'JetBrains Mono'; font-size: 11px; color: #64748b;"><?= $it['batch_no'] ?></td>
                <td style="text-align: right; font-weight: 700;"><?= (float)$it['qty_good'] ?> <span style="font-weight: 400; font-size: 11px;"><?= $it['base_uom'] ?></span></td>
                <td style="text-align: right; font-weight: 700; color: #ef4444;"><?= (float)$it['qty_damaged'] ?> <span style="font-weight: 400; font-size: 11px; color: #000;"><?= $it['base_uom'] ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="page-break-before: auto;">
        <h3 class="section-title">HANDLING UNIT (LPN) LABELS</h3>
        <p class="section-desc">Cut and attach these labels to the physical pallets/boxes before operator execution.</p>
        
        <div class="lpn-grid">
            <?php foreach($quants as $index => $q): 
                $isDamage = $q['stock_type'] == 'B6';
                $borderColor = $isDamage ? '#ef4444' : 'var(--main-dark)';
                $typeBadge = $isDamage ? 'DAMAGED' : 'PUTAWAY';
            ?>
            <div class="lpn-card" style="border-color: <?= $borderColor ?>;">
                <div class="lpn-header">
                    <span class="lpn-title">ROUTING LABEL</span>
                    <span class="lpn-type" style="<?= $isDamage ? 'background:#fee2e2; color:#ef4444;' : '' ?>"><?= $typeBadge ?></span>
                </div>
                
                <div class="lpn-barcode-wrapper">
                    <svg id="barcode-hu-<?= $index ?>"></svg>
                    <script>
                        JsBarcode("#barcode-hu-<?= $index ?>", "<?= $q['hu_id'] ?>", { 
                            height: 60, 
                            displayValue: true, 
                            fontSize: 18, 
                            font: "monospace", 
                            textMargin: 8,
                            width: 2
                        });
                    </script>
                </div>
                
                <div class="lpn-sku"><?= $q['product_code'] ?></div>
                <div class="lpn-qty">QTY: <?= (float)$q['qty'] ?> <span style="font-size:12px; font-weight:600;"><?= $q['base_uom'] ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Fitur Opsional: Auto-Print setelah barcode selesai digambar (dihapus biar user bisa cek dulu)
    // window.onload = function() { window.print(); } 
</script>
</body>
</html>