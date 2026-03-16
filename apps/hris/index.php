<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$nama_admin  = $_SESSION['hris_name'];
$today       = date('Y-m-d');
$bulan_ini   = date('Y-m');

// ── LIVE KPI ──────────────────────────────────────────────────
$total_emp   = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_users")['c'] ?? 0;

$hadir_hari_ini = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_attendance WHERE date_log=?", [$today])['c'] ?? 0;
$pct_hadir   = $total_emp > 0 ? round(($hadir_hari_ini / $total_emp) * 100) : 0;

$cuti_pending   = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_leaves WHERE status='Pending'")['c'] ?? 0;
$lembur_pending = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_overtime WHERE status='Pending'")['c'] ?? 0;
$total_pending  = $cuti_pending + $lembur_pending;

$total_payroll  = safeGetOne($pdo, "SELECT COALESCE(SUM(basic_salary),0) as total FROM ess_users")['total'] ?? 0;

// ── STATISTIK KEHADIRAN 7 HARI TERAKHIR ──────────────────────
$chart_labels = []; $chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $cnt = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_attendance WHERE date_log=?", [$d])['c'] ?? 0;
    $chart_labels[] = date('d/m', strtotime($d));
    $chart_data[]   = (int)$cnt;
}

// ── ACTIVITY FEED (cuti + lembur terbaru) ────────────────────
$activity = safeGetAll($pdo,
    "(SELECT 'cuti' as jenis, fullname, leave_type as keterangan, created_at, status FROM ess_leaves ORDER BY id DESC LIMIT 5)
     UNION
     (SELECT 'lembur' as jenis, fullname, reason as keterangan, created_at, status FROM ess_overtime ORDER BY id DESC LIMIT 5)
     ORDER BY created_at DESC LIMIT 8");

// ── SIAPA YANG BELUM ABSEN HARI INI ──────────────────────────
$belum_absen = safeGetAll($pdo,
    "SELECT fullname, employee_id, division FROM ess_users
     WHERE employee_id NOT IN (SELECT employee_id FROM ess_attendance WHERE date_log=?)
     LIMIT 5", [$today]);

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>

<div class="main-content">
    <!-- TOPBAR -->
    <div class="page-topbar">
        <div>
            <h5>Dashboard Overview</h5>
            <div class="text-muted" style="font-size:0.75rem;">Selamat datang, <?= htmlspecialchars($nama_admin) ?> &mdash; <?= date('l, d F Y') ?></div>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="fa fa-clock me-1 text-primary"></i><?= date('H:i') ?> WIB</span>
        </div>
    </div>

    <div class="page-body">

        <!-- KPI ROW -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#eef2ff; color:#4f46e5;"><i class="fa fa-users"></i></div>
                    <div>
                        <div class="kpi-num"><?= $total_emp ?></div>
                        <div class="kpi-label">Total Karyawan</div>
                        <div class="kpi-sub">Aktif terdaftar</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#d1fae5; color:#059669;"><i class="fa fa-fingerprint"></i></div>
                    <div>
                        <div class="kpi-num text-success"><?= $pct_hadir ?>%</div>
                        <div class="kpi-label">Kehadiran Hari Ini</div>
                        <div class="kpi-sub"><?= $hadir_hari_ini ?> dari <?= $total_emp ?> karyawan</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <a href="menu_attendance.php?tab=approval" class="kpi-card text-decoration-none" style="<?= $total_pending > 0 ? 'border-color:#f59e0b;' : '' ?>">
                    <div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="fa fa-clock"></i></div>
                    <div>
                        <div class="kpi-num text-warning"><?= $total_pending ?></div>
                        <div class="kpi-label">Menunggu Approval</div>
                        <div class="kpi-sub"><?= $cuti_pending ?> cuti &bull; <?= $lembur_pending ?> lembur</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="menu_payroll.php" class="kpi-card text-decoration-none">
                    <div class="kpi-icon" style="background:#cffafe; color:#0891b2;"><i class="fa fa-money-bill-wave"></i></div>
                    <div>
                        <div class="kpi-num" style="font-size:1.2rem; color:#0891b2;">Rp <?= number_format($total_payroll/1000000, 1) ?>M</div>
                        <div class="kpi-label">Estimasi Payroll</div>
                        <div class="kpi-sub">Total gaji pokok/bulan</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- CHART KEHADIRAN -->
            <div class="col-md-7">
                <div class="data-card">
                    <div class="data-card-header">
                        <h6><i class="fa fa-chart-bar me-2 text-primary"></i>Tren Kehadiran 7 Hari Terakhir</h6>
                    </div>
                    <div class="p-3">
                        <canvas id="attendanceChart" height="160"></canvas>
                    </div>
                </div>
            </div>

            <!-- BELUM ABSEN -->
            <div class="col-md-5">
                <div class="data-card h-100">
                    <div class="data-card-header">
                        <h6><i class="fa fa-exclamation-circle me-2 text-warning"></i>Belum Absen Hari Ini</h6>
                        <span class="badge-soft badge-warning"><?= count($belum_absen) ?> orang</span>
                    </div>
                    <div class="p-2">
                        <?php if(empty($belum_absen)): ?>
                        <div class="empty-state"><i class="fa fa-check-circle" style="color:#10b981; opacity:1;"></i><div style="font-size:0.82rem;">Semua sudah absen!</div></div>
                        <?php else: foreach($belum_absen as $b): ?>
                        <div class="d-flex align-items-center gap-2 p-2 rounded hover-bg" style="transition:0.15s;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($b['fullname']) ?>&background=f1f5f9&color=475569&size=32" class="avatar-sm">
                            <div>
                                <div style="font-size:0.82rem; font-weight:600;"><?= htmlspecialchars($b['fullname']) ?></div>
                                <div style="font-size:0.72rem; color:#94a3b8;"><?= htmlspecialchars($b['division']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY FEED -->
            <div class="col-12">
                <div class="data-card">
                    <div class="data-card-header">
                        <h6><i class="fa fa-rss me-2 text-primary"></i>Aktivitas Terbaru</h6>
                        <a href="menu_attendance.php" class="btn-primary-custom btn-sm" style="font-size:0.75rem; padding:5px 12px;">Lihat Semua</a>
                    </div>
                    <table class="table">
                        <thead><tr><th>Karyawan</th><th>Jenis</th><th>Keterangan</th><th>Status</th><th>Waktu</th></tr></thead>
                        <tbody>
                        <?php if(empty($activity)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
                        <?php else: foreach($activity as $a):
                            $is_cuti = $a['jenis'] == 'cuti';
                            $status_class = ['Pending'=>'badge-warning','Approved'=>'badge-success','Rejected'=>'badge-danger'][$a['status']] ?? 'badge-muted';
                        ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($a['fullname']) ?></td>
                            <td><span class="badge-soft <?= $is_cuti ? 'badge-primary' : 'badge-info' ?>"><?= $is_cuti ? 'Cuti/Izin' : 'Lembur' ?></span></td>
                            <td class="text-muted" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars(substr($a['keterangan'],0,40)) ?></td>
                            <td><span class="badge-soft <?= $status_class ?>"><?= $a['status'] ?></span></td>
                            <td style="font-size:0.78rem; color:#94a3b8;"><?= date('d M H:i', strtotime($a['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('attendanceChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Hadir',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(79,70,229,0.15)',
            borderColor: '#4f46e5',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});
</script>
</body></html>
