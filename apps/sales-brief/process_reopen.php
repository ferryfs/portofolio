<?php
// apps/sales-brief/print_memo.php (PDO)
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

$id = sanitizeInt($_GET['id']);
$d = safeGetOne($pdo, "SELECT * FROM sales_briefs WHERE id=?", [$id]);

function formatList($json) {
    $arr = json_decode($json, true);
    return (is_array($arr) && count($arr) > 0) ? implode(", ", $arr) : "-";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Memo_<?php echo $d['sb_number']; ?></title>
    <style>
        @page { size: A4; margin: 2cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.4; }
        .container { max-width: 210mm; margin: 0 auto; }
        .memo-header { border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
        .company-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .doc-title { font-size: 14pt; font-weight: bold; text-align: center; margin: 20px 0; text-decoration: underline; }
        .table-info { width: 100%; margin-bottom: 20px; }
        .table-info td { vertical-align: top; padding: 3px 0; }
        .label { font-weight: bold; width: 120px; }
        .table-data { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11pt; }
        .table-data th, .table-data td { border: 1px solid #000; padding: 5px 8px; }
        .table-data th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        .signature-box { margin-top: 50px; display: flex; justify-content: space-between; }
        .sign-col { width: 30%; text-align: center; }
        .sign-space { height: 70px; }
        .sign-name { font-weight: bold; text-decoration: underline; }
        .stamp { position: absolute; right: 20px; top: 150px; border: 3px solid #28a745; color: #28a745; padding: 10px 20px; font-size: 20pt; font-weight: bold; text-transform: uppercase; transform: rotate(-15deg); opacity: 0.8; border-radius: 10px; }
        .stamp-draft { border-color: #ffc107; color: #ffc107; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="position: fixed; top: 20px; right: 20px;">
        <a href="view_sb.php?id=<?php echo $id; ?>" style="background: #333; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-family: sans-serif;">&laquo; Back to Apps</a>
    </div>

    <div class="container">
        
        <div class="memo-header">
            <div class="company-name">PT. MAJU MUNDUR SEJAHTERA</div>
            <div style="font-size: 10pt;">Jl. Sudirman Kav. 50, Jakarta Selatan | Telp: (021) 555-1234</div>
        </div>

        <div class="doc-title">INTERNAL MEMORANDUM</div>

        <?php if($d['status'] == 'Approved') { ?>
            <div class="stamp">APPROVED</div>
        <?php } elseif($d['status'] == 'Draft') { ?>
            <div class="stamp stamp-draft">DRAFT</div>
        <?php } ?>

        <table class="table-info">
            <tr><td class="label">To</td><td>: Finance Dept, Supply Chain, All Sales Team</td></tr>
            <tr><td class="label">From</td><td>: Trade Marketing Dept</td></tr>
            <tr><td class="label">Date</td><td>: <?php echo date('d F Y', strtotime($d['created_at'])); ?></td></tr>
            <tr><td class="label">Ref No</td><td>: <?php echo $d['sb_number']; ?> / <?php echo $d['ref_number'] ?: '-'; ?></td></tr>
            <tr><td class="label">Subject</td><td>: <strong><?php echo $d['promo_name']; ?></strong></td></tr>
        </table>

        <hr style="border: 1px solid #000;">

        <div class="content">
            <p>Dengan hormat,</p>
            <p>Sehubungan dengan strategi peningkatan penjualan periode ini, kami mengajukan program promosi dengan detail sebagai berikut:</p>

            <strong>1. Mekanisme & Periode</strong>
            <table class="table-info" style="margin-left: 20px; width: 95%;">
                <tr><td width="150">Periode</td><td>: <?php echo date('d M Y', strtotime($d['start_date'])); ?> s/d <?php echo date('d M Y', strtotime($d['end_date'])); ?></td></tr>
                <tr><td>Mekanisme</td><td>: <?php echo $d['promo_mechanism']; ?> (<?php echo $d['promo_type'] == '2' ? 'Cashback' : 'Free Gift'; ?>)</td></tr>
                <tr><td>Item Produk</td><td>: <?php echo formatList($d['selected_items']); ?></td></tr>
            </table>

            <strong>2. Detail Target & Skema Tiering</strong>
            <p style="margin-top:5px; margin-bottom:5px;">Skema insentif yang berlaku adalah sebagai berikut:</p>
            <table class="table-data">
                <thead>
                    <tr><th>Tier</th><th>Min Motif</th><th>Min Qty (<?php echo $d['uom']; ?>)</th><th>Min Amount (IDR)</th><th>Discount (%)</th></tr>
                </thead>
                <tbody>
                    <?php
                    $stmt_tier = $pdo->prepare("SELECT * FROM sb_targets WHERE sb_id=? ORDER BY tier_level ASC");
                    $stmt_tier->execute([$id]);
                    while($t = $stmt_tier->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                            <td align='center'>Tier {$t['tier_level']}</td>
                            <td align='center'>{$t['min_motif']}</td>
                            <td align='center'>".number_format($t['min_qty'])."</td>
                            <td align='right'>".number_format($t['min_amount'])."</td>
                            <td align='center'>{$t['discount_pct']}%</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>

            <strong>3. Estimasi Budget</strong>
            <p>Total alokasi budget untuk program ini adalah sebesar <strong>Rp <?php echo number_format($d['budget_allocation'], 0, ',', '.'); ?></strong> (Belum termasuk pajak).</p>

            <strong>4. Syarat & Ketentuan</strong>
            <div style="margin-left: 20px; font-size: 11pt;">
                <?php echo $d['terms_conditions']; ?>
            </div>

            <p>Demikian memorandum ini kami sampaikan agar dapat dijalankan sebagaimana mestinya. Terima kasih.</p>
        </div>

        <div class="signature-box">
            <div class="sign-col">
                <div>Prepared By,</div><div class="sign-space"></div>
                <div class="sign-name"><?php echo $d['created_by']; ?></div><div class="sign-title">Trade Marketing</div>
            </div>
            <div class="sign-col">
                <div>Reviewed By,</div><div class="sign-space"></div>
                <div class="sign-name">Budi Santoso</div><div class="sign-title">Sales Manager</div>
            </div>
            <div class="sign-col">
                <div>Approved By,</div><div class="sign-space"></div>
                <div class="sign-name">Haryanto</div><div class="sign-title">Finance Director</div>
            </div>
        </div>

        <div style="font-size: 9pt; color: #888; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 5px;">
            <i>System Generated by Sales Brief App | Printed on: <?php echo date('d-m-Y H:i'); ?></i>
        </div>

    </div>
</body>
</html>