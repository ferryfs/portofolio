<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest   = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$id         = sanitizeInt($_GET['id'] ?? 0);

$emp = safeGetOne($pdo,
    "SELECT u.*, s.shift_name, s.start_time, s.end_time,
            mgr.fullname as manager_name
     FROM ess_users u
     LEFT JOIN ess_employee_shifts es ON u.employee_id=es.employee_id
         AND es.id=(SELECT MAX(id) FROM ess_employee_shifts WHERE employee_id=u.employee_id)
     LEFT JOIN ess_shifts s ON es.shift_id=s.id
     LEFT JOIN ess_users mgr ON u.manager_id=mgr.id
     WHERE u.id=?", [$id]);

if(!$emp) { header("Location: menu_employee.php"); exit(); }

$nik = $emp['employee_id'];

// Data pendukung
$lifecycle   = safeGetAll($pdo, "SELECT * FROM ess_lifecycle WHERE employee_id=? ORDER BY event_date DESC, id DESC", [$nik]);
$absen_stats = safeGetOne($pdo,
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN TIME(check_in_time)>CONCAT(IFNULL((SELECT ADDTIME(s2.start_time, SEC_TO_TIME((SELECT late_tolerance_minutes*60 FROM ess_shifts s3 WHERE s3.id=es2.shift_id)) ) FROM ess_employee_shifts es2 LEFT JOIN ess_shifts s2 ON es2.shift_id=s2.id WHERE es2.employee_id=a.employee_id ORDER BY es2.id DESC LIMIT 1),'08:30:00'),'') THEN 1 ELSE 0 END) as telat
     FROM ess_attendance a WHERE employee_id=? AND DATE_FORMAT(date_log,'%Y-%m')=?",
    [$nik, date('Y-m')]);
$cuti_pending   = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_leaves WHERE employee_id=? AND status='Pending'", [$nik])['c'] ?? 0;
$lembur_bulan   = safeGetOne($pdo, "SELECT COALESCE(SUM(duration_hours),0) as total FROM ess_overtime WHERE employee_id=? AND status='Approved' AND DATE_FORMAT(overtime_date,'%Y-%m')=?", [$nik, date('Y-m')])['total'] ?? 0;
$slip_terakhir  = safeGetOne($pdo, "SELECT * FROM ess_payslips WHERE employee_id=? ORDER BY period_year DESC, period_month DESC LIMIT 1", [$nik]);

// Hitung masa kerja
$join    = new DateTime($emp['join_date']);
$now     = new DateTime();
$diff    = $join->diff($now);
$masa_kerja = '';
if($diff->y > 0) $masa_kerja .= $diff->y . ' tahun ';
if($diff->m > 0) $masa_kerja .= $diff->m . ' bulan';
if(!$masa_kerja) $masa_kerja = $diff->d . ' hari';

$page_title  = 'Detail Karyawan';
$active_menu = 'employee';
include '_head.php';
?>
<style>
.profile-banner { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 28px; border-radius: 16px; color: #fff; display: flex; gap: 20px; align-items: center; margin-bottom: 24px; }
.profile-avatar { width: 72px; height: 72px; border-radius: 16px; border: 3px solid rgba(255,255,255,0.3); flex-shrink: 0; }
.profile-name { font-size: 1.2rem; font-weight: 800; }
.profile-sub { font-size: 0.8rem; opacity: 0.8; }
.profile-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.profile-chip { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }

.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.info-item { background: #f8fafc; border-radius: 10px; padding: 10px 14px; }
.info-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 3px; }
.info-value { font-size: 0.875rem; font-weight: 600; color: #0f172a; }

.timeline { position: relative; padding-left: 24px; }
.timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.tl-item { position: relative; margin-bottom: 20px; }
.tl-item::before { content: ''; position: absolute; left: -20px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #4f46e5; border: 2px solid #fff; box-shadow: 0 0 0 2px #e2e8f0; }
.tl-date { font-size: 0.68rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px; }
.tl-event { font-size: 0.82rem; font-weight: 700; color: #0f172a; }
.tl-detail { font-size: 0.75rem; color: #64748b; margin-top: 2px; }

.event-badge { font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; }
.ev-Hired      { background: #d1fae5; color: #065f46; }
.ev-Confirmed  { background: #dbeafe; color: #1e40af; }
.ev-Promoted   { background: #ede9fe; color: #5b21b6; }
.ev-Transferred{ background: #fef3c7; color: #92400e; }
.ev-Resigned, .ev-Terminated { background: #fee2e2; color: #991b1b; }
.ev-default    { background: #f1f5f9; color: #475569; }
</style>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div class="d-flex align-items-center gap-3">
            <a href="menu_employee.php" class="btn btn-light border rounded-3 py-1 px-3" style="font-size:0.82rem;"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
            <div>
                <h5>Detail Karyawan</h5>
                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($emp['fullname']) ?> &bull; <?= htmlspecialchars($nik) ?></div>
            </div>
        </div>
        <?php if(!$is_guest): ?>
        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#editDetailModal"><i class="fa fa-pen me-1"></i> Edit</button>
        <?php endif; ?>
    </div>
    <div class="page-body">

        <!-- PROFILE BANNER -->
        <div class="profile-banner">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($emp['fullname']) ?>&background=ffffff&color=4f46e5&size=128&bold=true" class="profile-avatar">
            <div class="flex-grow-1">
                <div class="profile-name"><?= htmlspecialchars($emp['fullname']) ?></div>
                <div class="profile-sub"><?= htmlspecialchars($emp['position'] ?: $emp['role']) ?> &bull; <?= htmlspecialchars($emp['division']) ?><?= $emp['department'] ? ' › '.$emp['department'] : '' ?></div>
                <div class="profile-chips">
                    <?php
                    $sc = ['Active'=>'#10b981','Probation'=>'#f59e0b','Inactive'=>'#94a3b8','Resigned'=>'#ef4444','Terminated'=>'#ef4444'][$emp['employee_status']] ?? '#94a3b8';
                    ?>
                    <span class="profile-chip" style="background:<?= $sc ?>33; border-color:<?= $sc ?>55;"><?= $emp['employee_status'] ?></span>
                    <span class="profile-chip"><?= $emp['tipe_kontrak'] ?></span>
                    <span class="profile-chip"><i class="fa fa-clock me-1"></i><?= htmlspecialchars($emp['shift_name'] ?? 'Regular') ?></span>
                    <span class="profile-chip"><i class="fa fa-calendar me-1"></i><?= $masa_kerja ?></span>
                </div>
            </div>
            <div class="text-end" style="flex-shrink:0;">
                <div style="font-size:1.4rem; font-weight:800;">Rp <?= number_format($emp['basic_salary']/1000000, 1) ?>M</div>
                <div style="font-size:0.72rem; opacity:0.7;">Gaji Pokok / Bulan</div>
            </div>
        </div>

        <div class="row g-3">
            <!-- KOLOM KIRI: Info + Lifecycle -->
            <div class="col-md-8">

                <!-- BIODATA -->
                <div class="data-card mb-3">
                    <div class="data-card-header"><h6><i class="fa fa-id-card me-2 text-primary"></i>Data Pribadi & Kepegawaian</h6></div>
                    <div class="p-3">
                        <div class="info-grid">
                            <div class="info-item"><div class="info-label">NIK / Employee ID</div><div class="info-value"><?= htmlspecialchars($nik) ?></div></div>
                            <div class="info-item"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($emp['email']) ?></div></div>
                            <div class="info-item"><div class="info-label">Jenis Kelamin</div><div class="info-value"><?= htmlspecialchars($emp['gender'] ?? '—') ?></div></div>
                            <div class="info-item"><div class="info-label">Tanggal Lahir</div><div class="info-value"><?= $emp['birth_date'] ? date('d M Y', strtotime($emp['birth_date'])) : '—' ?></div></div>
                            <div class="info-item"><div class="info-label">Pendidikan</div><div class="info-value"><?= htmlspecialchars($emp['education'] ?? '—') ?></div></div>
                            <div class="info-item"><div class="info-label">No. HP</div><div class="info-value"><?= htmlspecialchars($emp['phone_number'] ?? '—') ?></div></div>
                            <div class="info-item"><div class="info-label">Join Date</div><div class="info-value"><?= date('d M Y', strtotime($emp['join_date'])) ?></div></div>
                            <div class="info-item"><div class="info-label">Masa Kerja</div><div class="info-value"><?= $masa_kerja ?></div></div>
                            <div class="info-item"><div class="info-label">Atasan Langsung</div><div class="info-value"><?= htmlspecialchars($emp['manager_name'] ?? '—') ?></div></div>
                            <?php if($emp['tipe_kontrak'] != 'PKWTT'): ?>
                            <div class="info-item"><div class="info-label">Kontrak Selesai</div>
                                <div class="info-value <?= ($emp['kontrak_end'] && strtotime($emp['kontrak_end']) <= strtotime('+30 days')) ? 'text-danger' : '' ?>">
                                    <?= $emp['kontrak_end'] ? date('d M Y', strtotime($emp['kontrak_end'])) : '—' ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if($emp['probation_end']): ?>
                            <div class="info-item"><div class="info-label">Akhir Probation</div><div class="info-value"><?= date('d M Y', strtotime($emp['probation_end'])) ?></div></div>
                            <?php endif; ?>
                            <div class="info-item"><div class="info-label">NPWP</div><div class="info-value"><?= htmlspecialchars($emp['npwp'] ?? '—') ?></div></div>
                            <div class="info-item"><div class="info-label">BPJS Kesehatan</div><div class="info-value"><?= htmlspecialchars($emp['no_bpjs_kes'] ?? '—') ?></div></div>
                            <div class="info-item"><div class="info-label">BPJS TK</div><div class="info-value"><?= htmlspecialchars($emp['no_bpjs_tk'] ?? '—') ?></div></div>
                        </div>
                    </div>
                </div>

                <!-- LIFECYCLE TIMELINE -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h6><i class="fa fa-timeline me-2 text-primary"></i>Riwayat Perjalanan Karir</h6>
                        <?php if(!$is_guest): ?>
                        <button class="btn-primary-custom" style="font-size:0.75rem; padding:5px 12px;" data-bs-toggle="modal" data-bs-target="#modalAddEvent">+ Event</button>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <?php if(empty($lifecycle)): ?>
                        <div class="empty-state"><i class="fa fa-timeline"></i>Belum ada riwayat karir.</div>
                        <?php else: ?>
                        <div class="timeline">
                        <?php foreach($lifecycle as $ev):
                            $ev_class = str_replace([' '], '', $ev['event_type']);
                            $ec = ['Hired'=>'ev-Hired','Confirmed'=>'ev-Confirmed','Promoted'=>'ev-Promoted','Transferred'=>'ev-Transferred','Resigned'=>'ev-Resigned','Terminated'=>'ev-Terminated'][$ev['event_type']] ?? 'ev-default';
                        ?>
                        <div class="tl-item">
                            <div class="tl-date"><?= date('d M Y', strtotime($ev['event_date'])) ?> &bull; oleh <?= htmlspecialchars($ev['created_by'] ?? 'Sistem') ?></div>
                            <div class="tl-event">
                                <span class="event-badge <?= $ec ?>"><?= $ev['event_type'] ?></span>
                            </div>
                            <?php if($ev['old_position'] || $ev['new_position']): ?>
                            <div class="tl-detail"><i class="fa fa-briefcase me-1 text-muted"></i><?= htmlspecialchars($ev['old_position'] ?? '—') ?> → <strong><?= htmlspecialchars($ev['new_position'] ?? '—') ?></strong></div>
                            <?php endif; ?>
                            <?php if($ev['old_salary'] || $ev['new_salary']): ?>
                            <div class="tl-detail"><i class="fa fa-money-bill me-1 text-muted"></i>Rp <?= number_format($ev['old_salary'],0,',','.') ?> → <strong class="text-success">Rp <?= number_format($ev['new_salary'],0,',','.') ?></strong></div>
                            <?php endif; ?>
                            <?php if($ev['notes']): ?>
                            <div class="tl-detail fst-italic">"<?= htmlspecialchars($ev['notes']) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: KPI + Payslip -->
            <div class="col-md-4">
                <!-- KPI BULAN INI -->
                <div class="data-card mb-3">
                    <div class="data-card-header"><h6><i class="fa fa-chart-pie me-2 text-primary"></i>Bulan Ini</h6></div>
                    <div class="p-3 d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#f0fdf4;">
                            <span style="font-size:0.8rem; color:#065f46;"><i class="fa fa-fingerprint me-2"></i>Hari Hadir</span>
                            <strong style="color:#059669;"><?= $absen_stats['total'] ?? 0 ?> hari</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fef3c7;">
                            <span style="font-size:0.8rem; color:#92400e;"><i class="fa fa-clock me-2"></i>Hari Telat</span>
                            <strong style="color:#d97706;"><?= $absen_stats['telat'] ?? 0 ?> hari</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fce7f3;">
                            <span style="font-size:0.8rem; color:#9d174d;"><i class="fa fa-moon me-2"></i>Jam Lembur</span>
                            <strong style="color:#db2777;"><?= number_format((float)$lembur_bulan, 1) ?> jam</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fffbeb;">
                            <span style="font-size:0.8rem; color:#92400e;"><i class="fa fa-calendar-minus me-2"></i>Sisa Cuti</span>
                            <strong style="color:#d97706;"><?= $emp['annual_leave_quota'] ?> hari</strong>
                        </div>
                        <?php if($cuti_pending > 0): ?>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fef2f2;">
                            <span style="font-size:0.8rem; color:#991b1b;"><i class="fa fa-hourglass-half me-2"></i>Cuti Pending</span>
                            <strong style="color:#ef4444;"><?= $cuti_pending ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SLIP TERAKHIR -->
                <?php if($slip_terakhir): ?>
                <div class="data-card">
                    <div class="data-card-header"><h6><i class="fa fa-file-invoice-dollar me-2 text-success"></i>Slip Terakhir</h6></div>
                    <div class="p-3">
                        <?php
                        $bl = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        function rph($n) { return 'Rp '.number_format((float)$n,0,',','.'); }
                        ?>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:10px;">Periode: <?= $bl[$slip_terakhir['period_month']] ?> <?= $slip_terakhir['period_year'] ?></div>
                        <div class="d-flex flex-column gap-1" style="font-size:0.78rem;">
                            <div class="d-flex justify-content-between"><span class="text-muted">Gaji Pokok</span><span><?= rph($slip_terakhir['basic_salary']) ?></span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Tunjangan</span><span class="text-success">+<?= rph($slip_terakhir['transport_allowance']+$slip_terakhir['meal_allowance']) ?></span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Lembur</span><span class="text-success">+<?= rph($slip_terakhir['overtime_pay']) ?></span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Potongan</span><span class="text-danger">-<?= rph($slip_terakhir['deduction_bpjs']+$slip_terakhir['deduction_tax']) ?></span></div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fw-bold"><span>Take Home Pay</span><span class="text-success"><?= rph($slip_terakhir['net_salary']) ?></span></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ADD LIFECYCLE EVENT -->
<?php if(!$is_guest): ?>
<div class="modal fade" id="modalAddEvent" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title fw-bold">Tambah Event Karir</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="menu_lifecycle.php">
                <?= csrfTokenField() ?>
                <input type="hidden" name="employee_id" value="<?= htmlspecialchars($nik) ?>">
                <input type="hidden" name="redirect_to" value="menu_employee_detail.php?id=<?= $id ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Jenis Event</label>
                            <select name="event_type" class="form-select">
                                <?php foreach(['Hired','Probation Start','Probation End','Confirmed','Promoted','Transferred','Contract Renewed','Contract Expired','Resigned','Terminated','Reactivated'] as $ev): ?>
                                <option><?= $ev ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-6"><label class="form-label">Tanggal Event</label><input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-6"><label class="form-label">Jabatan Baru</label><input type="text" name="new_position" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Divisi Baru</label><input type="text" name="new_division" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Gaji Baru (Rp)</label><input type="number" name="new_salary" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_event" class="btn-primary-custom">Simpan Event</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
