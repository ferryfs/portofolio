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

// ── DISTRIBUSI TIPE KONTRAK ──────────────────────────────────
$kontrak_dist = safeGetAll($pdo,
    "SELECT tipe_kontrak, COUNT(*) as c FROM ess_users GROUP BY tipe_kontrak ORDER BY c DESC");

// ── DISTRIBUSI STATUS KARYAWAN ───────────────────────────────
$status_dist = safeGetAll($pdo,
    "SELECT employee_status, COUNT(*) as c FROM ess_users GROUP BY employee_status ORDER BY c DESC");

// ── KONTRAK HAMPIR HABIS ─────────────────────────────────────
$expiring = safeGetAll($pdo,
    "SELECT fullname, employee_id, division, tipe_kontrak, kontrak_end FROM ess_users
     WHERE tipe_kontrak='PKWT' AND kontrak_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
     ORDER BY kontrak_end ASC LIMIT 5");

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
            <button onclick="document.getElementById('guideModal').style.display='flex'" style="background:#eef2ff; border:1px solid #c7d2fe; color:#4f46e5; border-radius:10px; padding:7px 14px; font-size:0.82rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                <i class="fa fa-question-circle"></i> User Guide
            </button>
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
            <div class="col-md-5">
                <div class="data-card">
                    <div class="data-card-header">
                        <h6><i class="fa fa-chart-bar me-2 text-primary"></i>Kehadiran 7 Hari Terakhir</h6>
                    </div>
                    <div class="p-3">
                        <canvas id="attendanceChart" height="180"></canvas>
                    </div>
                </div>
            </div>

            <!-- DONUT CHARTS -->
            <div class="col-md-4">
                <div class="data-card h-100">
                    <div class="data-card-header"><h6><i class="fa fa-chart-pie me-2 text-primary"></i>Komposisi Karyawan</h6></div>
                    <div class="p-3">
                        <canvas id="kontrakChart" height="140"></canvas>
                        <div class="d-flex flex-wrap gap-2 mt-2" id="kontrakLegend"></div>
                    </div>
                </div>
            </div>

            <!-- KONTRAK HAMPIR HABIS + BELUM ABSEN -->
            <div class="col-md-3">
                <div class="data-card mb-3" style="<?= count($expiring)>0?'border-color:#ef4444;':'' ?>">
                    <div class="data-card-header" style="<?= count($expiring)>0?'background:#fff5f5;':'' ?>">
                        <h6 style="<?= count($expiring)>0?'color:#991b1b;':'' ?>"><i class="fa fa-file-contract me-2"></i>Kontrak Hampir Habis</h6>
                        <span class="badge-soft <?= count($expiring)>0?'badge-danger':'badge-muted' ?>"><?= count($expiring) ?></span>
                    </div>
                    <div class="p-2">
                    <?php if(empty($expiring)): ?>
                    <div class="empty-state" style="padding:20px;"><i class="fa fa-check-circle" style="color:#10b981;opacity:1;font-size:1.5rem;"></i><div style="font-size:0.78rem;">Tidak ada</div></div>
                    <?php else: foreach($expiring as $e):
                        $days_left = (int)ceil((strtotime($e['kontrak_end']) - time()) / 86400);
                    ?>
                    <div class="d-flex align-items-center gap-2 p-2 rounded" style="margin-bottom:4px; background:#fff5f5;">
                        <div style="flex:1;">
                            <div style="font-size:0.78rem; font-weight:700;"><?= htmlspecialchars($e['fullname']) ?></div>
                            <div style="font-size:0.68rem; color:#ef4444;"><i class="fa fa-calendar me-1"></i><?= date('d M Y', strtotime($e['kontrak_end'])) ?> (<?= $days_left ?> hari)</div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- BELUM ABSEN -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h6><i class="fa fa-exclamation-circle me-2 text-warning"></i>Belum Absen</h6>
                        <span class="badge-soft badge-warning"><?= count($belum_absen) ?></span>
                    </div>
                    <div class="p-2">
                        <?php if(empty($belum_absen)): ?>
                        <div class="empty-state" style="padding:20px;"><i class="fa fa-check-circle" style="color:#10b981;opacity:1;font-size:1.5rem;"></i><div style="font-size:0.78rem;">Semua sudah absen!</div></div>
                        <?php else: foreach($belum_absen as $b): ?>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="margin-bottom:2px;">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($b['fullname']) ?>&background=f1f5f9&color=475569&size=32" class="avatar-sm">
                            <div>
                                <div style="font-size:0.78rem; font-weight:600;"><?= htmlspecialchars($b['fullname']) ?></div>
                                <div style="font-size:0.68rem; color:#94a3b8;"><?= htmlspecialchars($b['division']) ?></div>
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

<!-- ═══ USER GUIDE MODAL ═══ -->
<style>
#guideModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:flex-end; justify-content:center; }
#guideModal.open { display:flex; }
.gm-sheet { background:#fff; border-radius:24px 24px 0 0; width:100%; max-width:900px; max-height:88vh; display:flex; flex-direction:column; }
.gm-handle { width:40px; height:4px; background:#e2e8f0; border-radius:2px; margin:12px auto 0; flex-shrink:0; }
.gm-header { display:flex; justify-content:space-between; align-items:center; padding:12px 24px 10px; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
.gm-header h6 { font-weight:800; font-size:1rem; color:#0f172a; margin:0; }
.gm-close { background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer; padding:4px; border-radius:6px; }
.gm-close:hover { background:#f1f5f9; color:#374151; }

.gm-tabs { display:flex; gap:4px; padding:8px 16px; overflow-x:auto; scrollbar-width:none; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
.gm-tabs::-webkit-scrollbar { display:none; }
.gtab { white-space:nowrap; font-size:0.75rem; font-weight:600; padding:6px 14px; border-radius:20px; border:1px solid #e2e8f0; background:#fff; color:#64748b; cursor:pointer; }
.gtab.active { background:#4f46e5; color:#fff; border-color:#4f46e5; }

.gm-body { overflow-y:auto; flex:1; }
.gs { display:none; padding:20px 24px 28px; }
.gs.active { display:block; }

.gs h5 { font-size:1rem; font-weight:800; color:#0f172a; margin-bottom:4px; }
.gs .sub { font-size:0.78rem; color:#64748b; margin-bottom:16px; }
.gs h6 { font-size:0.82rem; font-weight:700; color:#4f46e5; margin:18px 0 8px; text-transform:uppercase; letter-spacing:0.4px; }
.gs p, .gs li { font-size:0.82rem; color:#374151; line-height:1.65; }
.gs ul { padding-left:18px; margin:6px 0; }
.gs ul li { margin-bottom:4px; }

.step-item { display:flex; gap:12px; align-items:flex-start; padding:10px 12px; background:#f8fafc; border-radius:10px; margin-bottom:8px; }
.step-num { min-width:24px; height:24px; background:#4f46e5; color:#fff; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.68rem; font-weight:800; flex-shrink:0; }
.step-item strong { font-size:0.82rem; color:#0f172a; display:block; margin-bottom:2px; }
.step-item span { font-size:0.75rem; color:#64748b; line-height:1.5; }

.info-tbl { width:100%; border-collapse:collapse; font-size:0.78rem; margin:10px 0; }
.info-tbl th { background:#0f172a; color:#fff; padding:7px 10px; text-align:left; font-weight:700; font-size:0.72rem; }
.info-tbl td { padding:7px 10px; border-bottom:1px solid #f1f5f9; color:#374151; vertical-align:top; }
.info-tbl tr:last-child td { border-bottom:none; }
.info-tbl tr:nth-child(even) td { background:#f8fafc; }
.info-tbl td:first-child { font-weight:700; color:#0f172a; white-space:nowrap; }

.gm-note { padding:10px 14px; border-radius:10px; font-size:0.75rem; margin:12px 0; line-height:1.5; }
.gm-note.purple { background:#eef2ff; color:#3730a3; border-left:3px solid #4f46e5; }
.gm-note.green  { background:#f0fdf4; color:#065f46; border-left:3px solid #10b981; }
.gm-note.yellow { background:#fffbeb; color:#78350f; border-left:3px solid #f59e0b; }
.gm-note.red    { background:#fff5f5; color:#991b1b; border-left:3px solid #ef4444; }
</style>

<div id="guideModal" onclick="if(event.target===this) closeGuide()">
<div class="gm-sheet">
    <div class="gm-handle"></div>
    <div class="gm-header">
        <div>
            <h6>📖 User Guide — HRIS</h6>
            <div style="font-size:0.7rem; color:#94a3b8;">Panduan lengkap administrator HR</div>
        </div>
        <button class="gm-close" onclick="closeGuide()"><i class="fa fa-times"></i></button>
    </div>
    <div class="gm-tabs">
        <button class="gtab active" onclick="gTab('intro',this)">🏠 Intro</button>
        <button class="gtab" onclick="gTab('employee',this)">👤 Karyawan</button>
        <button class="gtab" onclick="gTab('lifecycle',this)">📈 Lifecycle</button>
        <button class="gtab" onclick="gTab('shift',this)">⏰ Shift</button>
        <button class="gtab" onclick="gTab('attendance',this)">📋 Kehadiran</button>
        <button class="gtab" onclick="gTab('leave',this)">🏖️ Cuti</button>
        <button class="gtab" onclick="gTab('payroll',this)">💰 Payroll</button>
        <button class="gtab" onclick="gTab('export',this)">📥 Export</button>
    </div>
    <div class="gm-body">

        <!-- INTRO -->
        <div class="gs active" id="gs-intro">
            <h5>Human Resource Information System</h5>
            <div class="sub">Pusat kendali seluruh proses HR — terintegrasi penuh dengan ESS Portal</div>
            <div class="gm-note purple"><strong>ℹ️ Tentang Aplikasi Ini</strong><br>Aplikasi ini merupakan <strong>simulasi demonstrasi</strong> yang dikembangkan sebagai cerminan dari sistem HRIS enterprise yang sesungguhnya. Seluruh fitur, logika bisnis, dan kalkulasi merepresentasikan implementasi nyata. Data bersifat fiktif untuk tujuan demonstrasi portofolio teknis.</div>
            <div class="gm-note green"><strong>✅ Akun Demo</strong><br>Admin: <code>adminhr</code> — akses penuh ke semua fitur<br>Guest: <code>guest</code> — hanya bisa melihat data, semua tombol aksi disembunyikan</div>
            <h6>Fitur Utama</h6>
            <table class="info-tbl">
                <thead><tr><th>Menu</th><th>Fungsi</th></tr></thead>
                <tbody>
                    <tr><td>Data Karyawan</td><td>Master data lengkap + tambah/edit/hapus karyawan</td></tr>
                    <tr><td>Employee Lifecycle</td><td>Riwayat karir: hired, promosi, mutasi, resign, PHK</td></tr>
                    <tr><td>Shift Management</td><td>Definisi shift kerja & assignment per karyawan</td></tr>
                    <tr><td>Kehadiran & Cuti</td><td>Monitor absensi + approval cuti & lembur</td></tr>
                    <tr><td>Leave Policy</td><td>Aturan cuti & reset kuota tahunan otomatis</td></tr>
                    <tr><td>Payroll</td><td>Generate slip gaji + export CSV</td></tr>
                </tbody>
            </table>
            <h6>Integrasi dengan ESS Portal</h6>
            <ul>
                <li>Tambah karyawan di HRIS → langsung bisa login di ESS</li>
                <li>Approve cuti/lembur di HRIS → status ESS karyawan langsung berubah</li>
                <li>Generate payslip → notifikasi otomatis ke ESS karyawan</li>
                <li>Reset kuota cuti → sisa cuti ESS langsung diperbarui</li>
            </ul>
        </div>

        <!-- KARYAWAN -->
        <div class="gs" id="gs-employee">
            <h5>👤 Data Karyawan</h5>
            <div class="sub">Master data lengkap — single source of truth untuk seluruh sistem</div>
            <h6>Tambah Karyawan Baru</h6>
            <div class="step-item"><span class="step-num">1</span><div><strong>Klik "+ Tambah Karyawan"</strong><span>Form terbagi 3 tab: Identitas / Posisi & Kontrak / Akun & Gaji</span></div></div>
            <div class="step-item"><span class="step-num">2</span><div><strong>Tab Identitas</strong><span>Nama, email, gender, tanggal lahir, pendidikan</span></div></div>
            <div class="step-item"><span class="step-num">3</span><div><strong>Tab Posisi & Kontrak</strong><span>NIK (untuk login ESS), divisi, jabatan, tipe kontrak (PKWTT/PKWT/dll), atasan langsung, tanggal kontrak</span></div></div>
            <div class="step-item"><span class="step-num">4</span><div><strong>Tab Akun & Gaji</strong><span>Password awal (min 6 karakter) dan gaji pokok</span></div></div>
            <div class="step-item"><span class="step-num">5</span><div><strong>Klik Simpan</strong><span>Sistem otomatis buat event lifecycle "Hired" + assign shift Regular</span></div></div>
            <h6>Filter & Pencarian</h6>
            <p>Kombinasikan filter Nama/NIK/Email, Divisi, Status Karyawan, dan Tipe Kontrak secara bebas.</p>
            <h6>Detail Karyawan</h6>
            <p>Klik nama karyawan di tabel untuk membuka halaman detail: profile banner, 15+ field biodata, timeline karir, KPI bulan ini, dan slip gaji terakhir.</p>
            <div class="gm-note yellow">⚠️ Karyawan PKWT dengan kontrak berakhir dalam 30 hari ditandai merah di tabel dan muncul di widget dashboard.</div>
        </div>

        <!-- LIFECYCLE -->
        <div class="gs" id="gs-lifecycle">
            <h5>📈 Employee Lifecycle</h5>
            <div class="sub">Audit trail perjalanan karir karyawan dari masuk hingga keluar</div>
            <h6>Jenis Event & Efek Otomatis</h6>
            <table class="info-tbl">
                <thead><tr><th>Event</th><th>Update Otomatis</th></tr></thead>
                <tbody>
                    <tr><td>Hired</td><td>Status → Active/Probation</td></tr>
                    <tr><td>Confirmed</td><td>Status → Active + notifikasi ESS</td></tr>
                    <tr><td>Promoted</td><td>Jabatan & gaji di-update + notifikasi ESS</td></tr>
                    <tr><td>Transferred</td><td>Divisi di-update + notifikasi ESS</td></tr>
                    <tr><td>Contract Renewed</td><td>Notifikasi ESS dikirim</td></tr>
                    <tr><td>Resigned / Terminated</td><td>Status → Resigned/Terminated</td></tr>
                    <tr><td>Reactivated</td><td>Status → Active</td></tr>
                </tbody>
            </table>
            <h6>Cara Tambah Event</h6>
            <p>Bisa dari menu Lifecycle atau dari halaman Detail Karyawan. Isi jenis event, tanggal, jabatan/divisi baru, gaji baru (opsional), dan catatan.</p>
            <div class="gm-note red">🔴 Event tidak bisa diedit setelah disimpan — hanya bisa dihapus. Ini menjaga integritas audit trail.</div>
        </div>

        <!-- SHIFT -->
        <div class="gs" id="gs-shift">
            <h5>⏰ Shift Management</h5>
            <div class="sub">Kelola shift kerja dan assign ke karyawan</div>
            <h6>Shift Default</h6>
            <table class="info-tbl">
                <thead><tr><th>Shift</th><th>Jam Kerja</th><th>Toleransi Telat</th></tr></thead>
                <tbody>
                    <tr><td>Regular</td><td>08:00 – 17:00</td><td>15 menit</td></tr>
                    <tr><td>Pagi</td><td>06:00 – 14:00</td><td>15 menit</td></tr>
                    <tr><td>Siang</td><td>14:00 – 22:00</td><td>15 menit</td></tr>
                    <tr><td>Malam</td><td>22:00 – 06:00</td><td>15 menit</td></tr>
                </tbody>
            </table>
            <h6>Tambah Shift & Assign</h6>
            <ul>
                <li><strong>Shift Baru</strong> — isi nama, jam mulai/selesai, toleransi keterlambatan</li>
                <li><strong>Assign Shift</strong> — pilih karyawan, shift, dan tanggal berlaku</li>
                <li>Perubahan shift otomatis tercatat sebagai event lifecycle</li>
            </ul>
            <div class="gm-note purple">ℹ️ Toleransi keterlambatan per shift digunakan untuk kalkulasi status "Telat" di rekap absensi.</div>
        </div>

        <!-- KEHADIRAN -->
        <div class="gs" id="gs-attendance">
            <h5>📋 Kehadiran & Approval</h5>
            <div class="sub">Monitor absensi + kelola pengajuan cuti dan lembur</div>
            <h6>3 Tab Tersedia</h6>
            <ul>
                <li><strong>Absensi</strong> — log kehadiran harian dengan filter tanggal, deteksi telat otomatis, dan kolom laporan kerja</li>
                <li><strong>Cuti/Izin</strong> — approval pengajuan + riwayat semua cuti</li>
                <li><strong>Lembur</strong> — approval pengajuan + riwayat semua overtime</li>
            </ul>
            <h6>Proses Approval Cuti</h6>
            <div class="step-item"><span class="step-num">1</span><div><strong>Buka Tab Cuti/Izin</strong><span>Pengajuan pending muncul di section atas dengan highlight kuning</span></div></div>
            <div class="step-item"><span class="step-num">2</span><div><strong>Review detail</strong><span>Nama, divisi, jenis cuti, periode, alasan, estimasi hari kerja yang akan dipotong</span></div></div>
            <div class="step-item"><span class="step-num">3</span><div><strong>Setujui atau Tolak</strong><span>Setujui → kuota cuti karyawan berkurang + notifikasi ESS. Tolak → kuota tetap + notifikasi ESS.</span></div></div>
            <div class="gm-note green">✅ Perhitungan hari kerja mengecualikan weekend dan hari libur nasional secara otomatis.</div>
        </div>

        <!-- CUTI -->
        <div class="gs" id="gs-leave">
            <h5>🏖️ Leave Policy & Kuota Cuti</h5>
            <div class="sub">Aturan cuti dan reset kuota tahunan seluruh karyawan</div>
            <h6>Policy Default</h6>
            <table class="info-tbl">
                <thead><tr><th>Jenis Cuti</th><th>Kuota</th><th>Carry Forward</th></tr></thead>
                <tbody>
                    <tr><td>Cuti Tahunan</td><td>12 hari/tahun</td><td>Ya, maks. 5 hari</td></tr>
                    <tr><td>Sakit</td><td>Tidak terbatas</td><td>Tidak</td></tr>
                    <tr><td>Izin Khusus</td><td>Tidak terbatas</td><td>Tidak</td></tr>
                    <tr><td>Cuti Melahirkan</td><td>90 hari</td><td>Tidak</td></tr>
                    <tr><td>Cuti Ayah</td><td>2 hari</td><td>Tidak</td></tr>
                    <tr><td>Cuti Besar</td><td>30 hari (min 6 thn kerja)</td><td>Tidak</td></tr>
                </tbody>
            </table>
            <h6>Reset Kuota Tahunan</h6>
            <div class="step-item"><span class="step-num">1</span><div><strong>Klik "Reset Kuota Tahunan [Tahun]"</strong><span>Sistem memproses semua karyawan Active dan Probation</span></div></div>
            <div class="step-item"><span class="step-num">2</span><div><strong>Kalkulasi otomatis</strong><span>Kuota baru = Kuota base + Carry forward (sisa cuti tahun lalu, maks sesuai policy)</span></div></div>
            <div class="step-item"><span class="step-num">3</span><div><strong>Notifikasi terkirim</strong><span>Setiap karyawan menerima notifikasi di ESS dengan kuota baru mereka</span></div></div>
            <div class="gm-note yellow">⚠️ Reset hanya bisa dilakukan sekali per tahun per karyawan. Log reset tersimpan dan ditampilkan di halaman ini.</div>
        </div>

        <!-- PAYROLL -->
        <div class="gs" id="gs-payroll">
            <h5>💰 Payroll</h5>
            <div class="sub">Generate slip gaji otomatis dengan kalkulasi lengkap</div>
            <h6>Cara Generate</h6>
            <div class="step-item"><span class="step-num">1</span><div><strong>Pilih periode bulan dan tahun</strong><span>Klik Tampilkan untuk melihat data periode tersebut</span></div></div>
            <div class="step-item"><span class="step-num">2</span><div><strong>Klik "Generate Semua Slip"</strong><span>Sistem menghitung slip untuk karyawan bergaji yang belum punya slip periode ini</span></div></div>
            <div class="step-item"><span class="step-num">3</span><div><strong>Notifikasi otomatis</strong><span>Setiap karyawan menerima notifikasi di ESS bahwa slip sudah tersedia</span></div></div>
            <h6>Komponen Kalkulasi</h6>
            <table class="info-tbl">
                <thead><tr><th>Komponen</th><th>Formula</th></tr></thead>
                <tbody>
                    <tr><td>Gaji Pokok</td><td>Sesuai data master karyawan</td></tr>
                    <tr><td>Tunjangan Transport</td><td>MIN(Rp 500.000, Gaji × 3%)</td></tr>
                    <tr><td>Tunjangan Makan</td><td>Rp 25.000 × Hari Hadir</td></tr>
                    <tr><td>Upah Lembur</td><td>(Gaji ÷ 173) × 1,5 × Jam Lembur</td></tr>
                    <tr><td>Potongan BPJS</td><td>Gaji × 1%</td></tr>
                    <tr><td>Potongan PPh 21</td><td>(Gaji − Rp 5jt) × 5% jika gaji > 5jt</td></tr>
                </tbody>
            </table>
        </div>

        <!-- EXPORT -->
        <div class="gs" id="gs-export">
            <h5>📥 Export Data</h5>
            <div class="sub">Unduh data ke CSV — kompatibel langsung dengan Microsoft Excel</div>
            <h6>Tiga Jenis Export</h6>
            <table class="info-tbl">
                <thead><tr><th>Export</th><th>Lokasi Tombol</th><th>Isi File</th></tr></thead>
                <tbody>
                    <tr><td>Data Karyawan</td><td>Halaman Payroll → "Export Karyawan"</td><td>Seluruh master data karyawan (NIK, nama, jabatan, gaji, BPJS, dll)</td></tr>
                    <tr><td>Rekap Absensi</td><td>Halaman Kehadiran → "Export CSV"</td><td>Log absensi bulan berjalan (jam masuk/pulang, telat, laporan kerja)</td></tr>
                    <tr><td>Rekap Payroll</td><td>Halaman Payroll → "Export CSV"</td><td>Slip gaji periode yang sedang ditampilkan (semua komponen + take home pay)</td></tr>
                </tbody>
            </table>
            <div class="gm-note green">✅ Semua file CSV menggunakan BOM UTF-8 sehingga karakter Indonesia (nama dengan huruf khusus) tampil benar di Microsoft Excel tanpa konversi tambahan.</div>
        </div>

    </div><!-- end gm-body -->
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function closeGuide() { document.getElementById('guideModal').style.display = 'none'; }
function gTab(tab, btn) {
    document.querySelectorAll('.gs').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.gtab').forEach(b => b.classList.remove('active'));
    document.getElementById('gs-' + tab).classList.add('active');
    btn.classList.add('active');
    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
}

// Bar chart kehadiran
new Chart(document.getElementById('attendanceChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{ label: 'Hadir', data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(79,70,229,0.15)', borderColor: '#4f46e5',
            borderWidth: 2, borderRadius: 8, borderSkipped: false }]
    },
    options: { responsive: true, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } } } }
});

// Donut chart komposisi kontrak
const kontrakData   = <?= json_encode(array_values(array_map(fn($k) => (int)$k['c'], $kontrak_dist))) ?>;
const kontrakLabels = <?= json_encode(array_values(array_map(fn($k) => $k['tipe_kontrak'] ?? 'Tidak diset', $kontrak_dist))) ?>;
const colors = ['#4f46e5','#10b981','#f59e0b','#06b6d4','#8b5cf6'];
new Chart(document.getElementById('kontrakChart'), {
    type: 'doughnut',
    data: { labels: kontrakLabels, datasets: [{ data: kontrakData, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
    options: { responsive: true, cutout: '65%', plugins: { legend: { display: false } } }
});
const legend = document.getElementById('kontrakLegend');
kontrakLabels.forEach((label, i) => {
    legend.innerHTML += `<div style="display:flex;align-items:center;gap:4px;font-size:0.72rem;color:#374151;">
        <span style="width:10px;height:10px;border-radius:3px;background:${colors[i]};flex-shrink:0;"></span>${label} (${kontrakData[i]})</div>`;
});
</script>
</body></html>
