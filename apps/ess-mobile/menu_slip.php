<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik  = $_SESSION['ess_user'];
$nama = $_SESSION['ess_name'];
$role = $_SESSION['ess_role'];
$div  = $_SESSION['ess_div'] ?? '-';

// Ambil data user lengkap
$user = safeGetOne($pdo, "SELECT * FROM ess_users WHERE employee_id=?", [$nik]);
$gaji_pokok = (float)($user['basic_salary'] ?? 0);

// Filter periode yang dipilih
$sel_month = (int)($_GET['month'] ?? date('n'));
$sel_year  = (int)($_GET['year']  ?? date('Y'));
$period_str = sprintf('%04d-%02d', $sel_year, $sel_month);

// Cek apakah ada payslip di DB
$slip = safeGetOne($pdo, "SELECT * FROM ess_payslips WHERE employee_id=? AND period_month=? AND period_year=?",
    [$nik, $sel_month, $sel_year]);

// Kalau belum ada slip resmi, hitung estimasi dari data kehadiran
if (!$slip) {
    // Hitung hari kerja bulan ini
    $hadir = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_attendance WHERE employee_id=? AND date_log LIKE ?",
        [$nik, $period_str . '%']);
    $hadir_days = (int)($hadir['c'] ?? 0);

    // Hitung overtime hours approved
    $ot = safeGetOne($pdo, "SELECT COALESCE(SUM(duration_hours),0) as total FROM ess_overtime WHERE employee_id=? AND status='Approved' AND DATE_FORMAT(overtime_date,'%Y-%m')=?",
        [$nik, $period_str]);
    $ot_hours = (float)($ot['total'] ?? 0);

    // Estimasi komponen (rule sederhana)
    $transport   = $gaji_pokok > 0 ? min(500000, $gaji_pokok * 0.03) : 0;
    $meal        = $hadir_days * 25000;
    $ot_rate     = $gaji_pokok > 0 ? ($gaji_pokok / 173) * 1.5 : 0;
    $ot_pay      = $ot_hours * $ot_rate;
    $bpjs        = $gaji_pokok * 0.01;
    $pph21       = $gaji_pokok > 5000000 ? ($gaji_pokok - 5000000) * 0.05 : 0;
    $net         = $gaji_pokok + $transport + $meal + $ot_pay - $bpjs - $pph21;
    $is_estimate = true;
} else {
    $hadir_days  = $slip['attendance_days'];
    $ot_hours    = $slip['overtime_hours'];
    $transport   = $slip['transport_allowance'];
    $meal        = $slip['meal_allowance'];
    $ot_pay      = $slip['overtime_pay'];
    $bpjs        = $slip['deduction_bpjs'];
    $pph21       = $slip['deduction_tax'];
    $net         = $slip['net_salary'];
    $is_estimate = false;
}

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Slip Gaji</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:420px; margin:0 auto; min-height:100vh; background:#f8fafc; }
        .page-header {
            background:linear-gradient(135deg,#059669,#047857);
            padding:20px 20px 40px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .body-area { padding:0 16px 32px; }

        .period-nav { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
        .period-nav select { flex:1; border-radius:10px; border:1px solid #e5e7eb; padding:8px 12px; font-size:0.85rem; font-family:'DM Sans',sans-serif; }
        .period-nav button { background:#059669; color:#fff; border:none; border-radius:10px; padding:8px 14px; font-size:0.85rem; font-weight:600; }

        .slip-card {
            background:#fff; border-radius:20px;
            box-shadow:0 4px 20px rgba(5,150,105,0.08);
            overflow:hidden; margin-bottom:16px;
        }
        .slip-header {
            background:linear-gradient(135deg,#059669,#047857);
            padding:20px; color:#fff;
        }
        .slip-header .company { font-size:0.7rem; opacity:0.8; text-transform:uppercase; letter-spacing:0.5px; }
        .slip-header .period-label { font-size:1.1rem; font-weight:700; margin:4px 0; }
        .slip-header .emp-info { font-size:0.78rem; opacity:0.85; }
        .estimate-badge { background:rgba(255,255,255,0.2); font-size:0.65rem; padding:3px 8px; border-radius:6px; margin-top:6px; display:inline-block; }

        .slip-section { padding:16px; border-bottom:1px solid #f3f4f6; }
        .slip-section-title { font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; color:#94a3b8; margin-bottom:10px; }
        .slip-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .slip-row .label { font-size:0.82rem; color:#6b7280; }
        .slip-row .value { font-size:0.82rem; font-weight:600; color:#0f172a; font-family:'DM Mono',monospace; }
        .slip-row .value.plus { color:#10b981; }
        .slip-row .value.minus { color:#ef4444; }

        .net-box {
            padding:20px; background:linear-gradient(135deg,#f0fdf4,#dcfce7);
            display:flex; justify-content:space-between; align-items:center;
        }
        .net-label { font-size:0.8rem; font-weight:700; color:#065f46; text-transform:uppercase; letter-spacing:0.4px; }
        .net-amount { font-size:1.4rem; font-weight:800; color:#059669; font-family:'DM Mono',monospace; }

        .info-box { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:12px; font-size:0.75rem; color:#92400e; margin-bottom:16px; }

        .hist-list-btn { display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:12px 16px; text-decoration:none; color:#0f172a; margin-bottom:10px; transition:0.2s; }
        .hist-list-btn:hover { border-color:#059669; color:#059669; }
    </style>
</head>
<body>
<div class="shell">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 mb-2">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Keuangan</div>
                <h5 class="fw-bold mb-0">Slip Gaji</h5>
            </div>
        </div>
    </div>

    <div class="body-area">
        <!-- Period selector -->
        <form method="GET" class="period-nav mt-3 mb-4">
            <select name="month">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>><?= $nama_bulan[$m] ?></option>
                <?php endfor; ?>
            </select>
            <select name="year">
                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                <option value="<?=$y?>" <?=$y==$sel_year?'selected':''?>><?=$y?></option>
                <?php endfor; ?>
            </select>
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>

        <?php if($gaji_pokok == 0 && !$slip): ?>
        <div class="info-box">
            <i class="fa fa-info-circle me-2"></i>
            Gaji pokok belum diset oleh HR. Slip akan tersedia setelah HR mengisi data salary Anda.
        </div>
        <?php elseif($is_estimate): ?>
        <div class="info-box">
            <i class="fa fa-calculator me-2"></i>
            <strong>Estimasi Otomatis</strong> — Slip resmi belum diterbitkan HR untuk periode ini. Angka di bawah adalah kalkulasi sementara.
        </div>
        <?php endif; ?>

        <!-- SLIP CARD -->
        <div class="slip-card">
            <div class="slip-header">
                <div class="company">PT. Ferry Fernando &bull; Payroll System</div>
                <div class="period-label">Periode: <?= $nama_bulan[$sel_month] ?> <?= $sel_year ?></div>
                <div class="emp-info">
                    <?= htmlspecialchars($nama) ?> &bull; <?= htmlspecialchars($nik) ?><br>
                    <?= htmlspecialchars($div) ?> &bull; <?= htmlspecialchars($role) ?>
                </div>
                <?php if($is_estimate): ?>
                <span class="estimate-badge"><i class="fa fa-calculator me-1"></i> Estimasi Otomatis</span>
                <?php else: ?>
                <span class="estimate-badge"><i class="fa fa-check-circle me-1"></i> Slip Resmi HR</span>
                <?php endif; ?>
            </div>

            <!-- Kehadiran -->
            <div class="slip-section">
                <div class="slip-section-title">Kehadiran</div>
                <div class="slip-row"><span class="label">Hari Hadir</span><span class="value"><?= $hadir_days ?> hari</span></div>
                <div class="slip-row"><span class="label">Overtime</span><span class="value"><?= number_format((float)$ot_hours, 1) ?> jam</span></div>
            </div>

            <!-- Pendapatan -->
            <div class="slip-section">
                <div class="slip-section-title">Pendapatan (+)</div>
                <div class="slip-row"><span class="label">Gaji Pokok</span><span class="value plus"><?= rp($gaji_pokok) ?></span></div>
                <div class="slip-row"><span class="label">Tunjangan Transport</span><span class="value plus"><?= rp($transport) ?></span></div>
                <div class="slip-row"><span class="label">Tunjangan Makan</span><span class="value plus"><?= rp($meal) ?></span></div>
                <?php if($ot_pay > 0): ?>
                <div class="slip-row"><span class="label">Upah Lembur</span><span class="value plus"><?= rp($ot_pay) ?></span></div>
                <?php endif; ?>
            </div>

            <!-- Potongan -->
            <div class="slip-section">
                <div class="slip-section-title">Potongan (−)</div>
                <div class="slip-row"><span class="label">BPJS Kesehatan (1%)</span><span class="value minus">− <?= rp($bpjs) ?></span></div>
                <?php if($pph21 > 0): ?>
                <div class="slip-row"><span class="label">PPh 21</span><span class="value minus">− <?= rp($pph21) ?></span></div>
                <?php endif; ?>
            </div>

            <!-- TAKE HOME PAY -->
            <div class="net-box">
                <div class="net-label">Take Home Pay</div>
                <div class="net-amount"><?= rp($net) ?></div>
            </div>
        </div>

        <!-- Riwayat slip sebelumnya -->
        <?php
        $slips_hist = safeGetAll($pdo, "SELECT period_month, period_year, net_salary FROM ess_payslips WHERE employee_id=? ORDER BY period_year DESC, period_month DESC LIMIT 6", [$nik]);
        if(!empty($slips_hist)):
        ?>
        <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; color:#94a3b8; margin:16px 0 10px;">Slip Sebelumnya</div>
        <?php foreach($slips_hist as $sh): ?>
        <a href="?month=<?=$sh['period_month']?>&year=<?=$sh['period_year']?>" class="hist-list-btn">
            <div>
                <div class="fw-bold" style="font-size:0.9rem;"><?= $nama_bulan[$sh['period_month']] ?> <?= $sh['period_year'] ?></div>
                <div style="font-size:0.75rem; color:#64748b;">Slip Resmi</div>
            </div>
            <div class="text-end">
                <div style="font-size:0.85rem; font-weight:700; color:#059669; font-family:'DM Mono',monospace;"><?= rp($sh['net_salary']) ?></div>
                <i class="fa fa-chevron-right text-muted" style="font-size:0.7rem;"></i>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
