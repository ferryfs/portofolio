<?php
// apps/wms/export_stock.php
// V8: HTML EXCEL EXPORT (Styled & Formatted)

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { exit("Akses Ditolak."); }

require_once __DIR__ . '/../../config/database.php';

// Nama File
$filename = "WMS_Stock_Overview_" . date('Ymd_His') . ".xls";

// Set Header agar browser download file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache"); 
header("Expires: 0");

// Query Pivot (Sama dengan stock_master.php)
$sql = "SELECT 
            p.product_code, p.description, p.base_uom,
            SUM(CASE WHEN q.stock_type = 'F1' THEN q.qty ELSE 0 END) as stock_f1,
            SUM(CASE WHEN q.stock_type = 'Q4' THEN q.qty ELSE 0 END) as stock_q4,
            SUM(CASE WHEN q.stock_type = 'B6' THEN q.qty ELSE 0 END) as stock_b6,
            SUM(q.qty) as total_stock
        FROM wms_products p
        LEFT JOIN wms_quants q ON p.product_uuid = q.product_uuid
        GROUP BY p.product_uuid 
        ORDER BY p.product_code ASC";

$stmt = $pdo->query($sql);

// BUKA TABLE HTML (Excel bisa baca HTML Table)
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000000; padding: 5px; }
        .head { font-weight: bold; color: white; text-align: center; }
        .bg-dark { background-color: #333333; }
        .bg-f1 { background-color: #d1e7dd; color: #0f5132; } /* Hijau Muda */
        .bg-q4 { background-color: #fff3cd; color: #664d03; } /* Kuning Muda */
        .bg-b6 { background-color: #f8d7da; color: #842029; } /* Merah Muda */
        .num { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }
        .txt { mso-number-format:"\@"; } /* Paksa Text */
    </style>
</head>
<body>
    <table>
        <thead>
            <tr style="height: 30px;">
                <th class="head bg-dark">Product Code</th>
                <th class="head bg-dark" style="width: 300px;">Description</th>
                <th class="head bg-dark">UoM</th>
                <th class="head" style="background-color: #198754;">Available (F1)</th>
                <th class="head" style="background-color: #ffc107; color:black;">Quality (Q4)</th>
                <th class="head" style="background-color: #dc3545;">Blocked (B6)</th>
                <th class="head bg-dark">Total Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php while($r = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td class="txt" style="font-weight:bold;"><?= $r['product_code'] ?></td>
                <td><?= htmlspecialchars($r['description']) ?></td>
                <td style="text-align:center;"><?= $r['base_uom'] ?></td>
                
                <td class="num <?= $r['stock_f1']>0 ? 'bg-f1' : '' ?>">
                    <?= $r['stock_f1'] ?>
                </td>
                
                <td class="num <?= $r['stock_q4']>0 ? 'bg-q4' : '' ?>">
                    <?= $r['stock_q4'] ?>
                </td>
                
                <td class="num <?= $r['stock_b6']>0 ? 'bg-b6' : '' ?>">
                    <?= $r['stock_b6'] ?>
                </td>
                
                <td class="num" style="font-weight:bold;">
                    <?= $r['total_stock'] ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>