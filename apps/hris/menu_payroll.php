<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest   = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$msg = ''; $msg_type = '';

$sel_month = (int)($_GET['month'] ?? date('n'));
$sel_year  = (int)($_GET['year']  ?? date('Y'));
$period    = sprintf('%04d-%02d', $sel_year, $sel_month);
$nama_bulan= ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// GENERATE PAYSLIP SEMUA KARYAWAN
if(isset($_POST['generate_all']) && !$is_guest) {
    if(!verifyCSRFToken()) die("Security Error.");
    $karyawan = safeGetAll($pdo, "SELECT * FROM ess_users WHERE basic_salary > 0");
    $generated = 0;
    foreach($karyawan as $k) {
        $nik = $k['employee_id'];
        // Cek sudah ada?
        $existing = safeGetOne($pdo, "SELECT id FROM ess_payslips WHERE employee_id=? AND period_month=? AND period_year=?", [$nik, $sel_month, $sel_year]);
        if($existing) continue;

        $hadir = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_attendance WHERE employee_id=? AND date_log LIKE ?", [$nik, $period.'%'])['c'] ?? 0;
        $ot    = safeGetOne($pdo, "SELECT COALESCE(SUM(duration_hours),0) as total FROM ess_overtime WHERE employee_id=? AND status='Approved' AND DATE_FORMAT(overtime_date,'%Y-%m')=?", [$nik, $period])['total'] ?? 0;

        $gaji   = (float)$k['basic_salary'];
        $trans  = min(500000, $gaji * 0.03);
        $meal   = $hadir * 25000;
        $ot_rate= $gaji > 0 ? ($gaji / 173) * 1.5 : 0;
        $ot_pay = (float)$ot * $ot_rate;
        $bpjs   = $gaji * 0.01;
        $pph    = $gaji > 5000000 ? ($gaji - 5000000) * 0.05 : 0;
        $net    = $gaji + $trans + $meal + $ot_pay - $bpjs - $pph;

        safeQuery($pdo, "INSERT INTO ess_payslips (employee_id,period_month,period_year,basic_salary,transport_allowance,meal_allowance,overtime_pay,deduction_bpjs,deduction_tax,net_salary,attendance_days,overtime_hours,generated_by,generated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [$nik,$sel_month,$sel_year,$gaji,$trans,$meal,$ot_pay,$bpjs,$pph,$net,$hadir,$ot,$nama_admin]);

        // Notif ke karyawan
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
            [$nik,'payslip',"Slip gaji periode {$nama_bulan[$sel_month]} $sel_year sudah tersedia."]);
        $generated++;
    }
    $msg = "Berhasil generate $generated slip gaji untuk periode {$nama_bulan[$sel_month]} $sel_year.";
    $msg_type = 'success';
}

// Ambil data payslip periode yang dipilih
$slips = safeGetAll($pdo,
    "SELECT p.*, u.fullname, u.division, u.role FROM ess_payslips p
     JOIN ess_users u ON p.employee_id = u.employee_id
     WHERE p.period_month=? AND p.period_year=?
     ORDER BY u.fullname ASC",
    [$sel_month, $sel_year]);

// Total payroll
$total_net = array_sum(array_column($slips, 'net_salary'));
$total_bruto = array_sum(array_map(fn($s) => $s['basic_salary'] + $s['transport_allowance'] + $s['meal_allowance'] + $s['overtime_pay'], $slips));

// Karyawan belum ada slip
$total_emp = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_users WHERE basic_salary > 0")['c'] ?? 0;
$already   = count($slips);

function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

$page_title  = 'Payroll';
$active_menu = 'payroll';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div>
            <h5>Manajemen Payroll</h5>
            <div class="text-muted" style="font-size:0.75rem;">Generate & pantau slip gaji karyawan</div>
        </div>
    </div>
    <div class="page-body">

        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
            <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <!-- PERIOD SELECTOR + GENERATE -->
        <div class="data-card mb-4">
            <div class="p-4">
                <div class="d-flex align-items-end gap-3 flex-wrap">
                    <form method="GET" class="d-flex gap-2 align-items-end">
                        <div>
                            <label class="form-label">Bulan</label>
                            <select name="month" class="form-select" style="min-width:130px;">
                                <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>><?= $nama_bulan[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tahun</label>
                            <select name="year" class="form-select" style="min-width:90px;">
                                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                                <option value="<?=$y?>" <?=$y==$sel_year?'selected':''?>><?=$y?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary-custom">Tampilkan</button>
                    </form>
                    <?php if(!$is_guest): ?>
                    <form method="POST" onsubmit="return confirm('Generate slip gaji untuk semua karyawan periode ini?')">
                        <?= csrfTokenField() ?>
                        <input type="hidden" name="month_gen" value="<?= $sel_month ?>">
                        <input type="hidden" name="year_gen" value="<?= $sel_year ?>">
                        <button type="submit" name="generate_all" class="btn-primary-custom" style="background:#059669;">
                            <i class="fa fa-magic me-1"></i> Generate Semua Slip
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if(!$is_guest && $already < $total_emp): ?>
                <div class="alert border-0 rounded-3 mt-3 py-2 small" style="background:#fffbeb; color:#92400e;">
                    <i class="fa fa-info-circle me-2"></i>
                    <?= $total_emp - $already ?> karyawan bergaji belum memiliki slip untuk periode ini.
                    Klik "Generate Semua Slip" untuk membuat secara otomatis.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KPI -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card"><div class="kpi-icon" style="background:#eef2ff;color:#4f46e5;"><i class="fa fa-file-invoice-dollar"></i></div>
                    <div><div class="kpi-num"><?= $already ?></div><div class="kpi-label">Slip Dibuat</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card"><div class="kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fa fa-wallet"></i></div>
                    <div><div class="kpi-num" style="font-size:1.2rem; color:#059669;"><?= rp($total_net) ?></div><div class="kpi-label">Total Take Home Pay</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fa fa-chart-pie"></i></div>
                    <div><div class="kpi-num" style="font-size:1.2rem; color:#d97706;"><?= rp($total_bruto) ?></div><div class="kpi-label">Total Bruto</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card"><div class="kpi-icon" style="background:#cffafe;color:#0891b2;"><i class="fa fa-users"></i></div>
                    <div><div class="kpi-num"><?= $total_emp ?></div><div class="kpi-label">Karyawan Bergaji</div></div></div>
            </div>
        </div>

        <!-- TABLE SLIP -->
        <div class="data-card">
            <div class="data-card-header">
                <h6><i class="fa fa-list me-2 text-primary"></i>Slip Gaji — <?= $nama_bulan[$sel_month] ?> <?= $sel_year ?></h6>
            </div>
            <table class="table">
                <thead><tr><th>Karyawan</th><th>Divisi</th><th>Gaji Pokok</th><th>Tunjangan</th><th>Lembur</th><th>Potongan</th><th>Take Home Pay</th><th>Hadir</th></tr></thead>
                <tbody>
                <?php if(empty($slips)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">
                    <i class="fa fa-file-invoice-dollar fa-2x mb-2 d-block opacity-25"></i>
                    Belum ada slip gaji untuk periode ini.<?= !$is_guest ? ' Klik "Generate Semua Slip" untuk membuat.' : '' ?>
                </td></tr>
                <?php else: foreach($slips as $s):
                    $tunjangan = $s['transport_allowance'] + $s['meal_allowance'];
                    $potongan  = $s['deduction_bpjs'] + $s['deduction_tax'];
                ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($s['fullname']) ?></div>
                        <div style="font-size:0.72rem; color:#94a3b8;"><?= $s['employee_id'] ?></div>
                    </td>
                    <td><?= htmlspecialchars($s['division']) ?></td>
                    <td style="font-size:0.82rem;"><?= rp($s['basic_salary']) ?></td>
                    <td style="font-size:0.82rem; color:#059669;">+<?= rp($tunjangan) ?></td>
                    <td style="font-size:0.82rem; color:#0891b2;">+<?= rp($s['overtime_pay']) ?></td>
                    <td style="font-size:0.82rem; color:#ef4444;">-<?= rp($potongan) ?></td>
                    <td><strong style="color:#059669;"><?= rp($s['net_salary']) ?></strong></td>
                    <td><?= $s['attendance_days'] ?> hari</td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <?php if(!empty($slips)): ?>
                <tfoot style="background:#f8fafc; font-weight:700;">
                    <tr>
                        <td colspan="6" class="text-end fw-bold" style="font-size:0.82rem;">TOTAL TAKE HOME PAY:</td>
                        <td style="color:#059669; font-size:0.9rem;"><?= rp($total_net) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
