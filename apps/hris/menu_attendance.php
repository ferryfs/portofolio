<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$msg = ''; $msg_type = '';

function hitungHariKerja($start, $end, $pdo) {
    $s = new DateTime($start); $e = new DateTime($end); $e->modify('+1 day');
    $period = new DatePeriod($s, DateInterval::createFromDateString('1 day'), $e);
    $holidays = $pdo->query("SELECT holiday_date FROM ess_holidays")->fetchAll(PDO::FETCH_COLUMN);
    $days = 0;
    foreach ($period as $dt) { if ($dt->format('N') < 6 && !in_array($dt->format('Y-m-d'), $holidays)) $days++; }
    return $days;
}

// APPROVAL CUTI
if(isset($_POST['process_cuti']) && !$is_guest) {
    if(!verifyCSRFToken()) die("Security Error.");
    $id_cuti = sanitizeInt($_POST['id_cuti']);
    $action  = sanitizeInput($_POST['action']);
    $cuti    = safeGetOne($pdo, "SELECT * FROM ess_leaves WHERE id=?", [$id_cuti]);
    if($cuti) {
        if($action == 'Approved' && $cuti['leave_type'] == 'Cuti Tahunan') {
            $dur = hitungHariKerja($cuti['start_date'], $cuti['end_date'], $pdo);
            $uq  = safeGetOne($pdo, "SELECT annual_leave_quota FROM ess_users WHERE employee_id=?", [$cuti['employee_id']]);
            if((int)$uq['annual_leave_quota'] >= $dur) {
                safeQuery($pdo, "UPDATE ess_users SET annual_leave_quota=annual_leave_quota-? WHERE employee_id=?", [$dur, $cuti['employee_id']]);
                safeQuery($pdo, "UPDATE ess_leaves SET status=?,approved_by=? WHERE id=?", [$action, $nama_admin, $id_cuti]);
                safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
                    [$cuti['employee_id'],'leave_approved',"Cuti Anda ($dur hari) disetujui oleh HR."]);
                $msg = "Cuti disetujui, kuota dipotong $dur hari."; $msg_type = 'success';
            } else { $msg = "Sisa cuti karyawan tidak cukup!"; $msg_type = 'danger'; }
        } else {
            safeQuery($pdo, "UPDATE ess_leaves SET status=?,approved_by=? WHERE id=?", [$action, $nama_admin, $id_cuti]);
            $notif_type = $action=='Approved' ? 'leave_approved' : 'leave_rejected';
            safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
                [$cuti['employee_id'], $notif_type, $action=='Approved'?"Cuti/Izin Anda disetujui oleh HR.":"Cuti/Izin Anda ditolak oleh HR."]);
            $msg = "Pengajuan $action."; $msg_type = $action=='Approved'?'success':'warning';
        }
    }
}

// APPROVAL LEMBUR
if(isset($_POST['process_lembur']) && !$is_guest) {
    if(!verifyCSRFToken()) die("Security Error.");
    $id_ot  = sanitizeInt($_POST['id_lembur']);
    $action = sanitizeInput($_POST['action']);
    $ot     = safeGetOne($pdo, "SELECT * FROM ess_overtime WHERE id=?", [$id_ot]);
    if($ot) {
        safeQuery($pdo, "UPDATE ess_overtime SET status=?,approved_by=?,approved_at=NOW() WHERE id=?", [$action, $nama_admin, $id_ot]);
        $notif_type = $action=='Approved' ? 'lembur_approved' : 'lembur_rejected';
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
            [$ot['employee_id'], $notif_type, $action=='Approved'?"Lembur Anda disetujui oleh HR.":"Lembur Anda ditolak oleh HR."]);
        $msg = "Lembur $action."; $msg_type = $action=='Approved'?'success':'warning';
    }
}

$active_tab  = sanitizeInput($_GET['tab'] ?? 'absensi');
$filter_date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

// Data absensi
$absensi = safeGetAll($pdo, "SELECT a.*, u.division FROM ess_attendance a LEFT JOIN ess_users u ON a.employee_id=u.employee_id WHERE a.date_log=? ORDER BY a.check_in_time DESC", [$filter_date]);

// Data cuti pending
$cuti_pending = safeGetAll($pdo, "SELECT * FROM ess_leaves WHERE status='Pending' ORDER BY id DESC");
$cuti_all     = safeGetAll($pdo, "SELECT * FROM ess_leaves ORDER BY id DESC LIMIT 30");

// Data lembur pending
$ot_pending = safeGetAll($pdo, "SELECT * FROM ess_overtime WHERE status='Pending' ORDER BY id DESC");
$ot_all     = safeGetAll($pdo, "SELECT * FROM ess_overtime ORDER BY id DESC LIMIT 30");

$total_pending = count($cuti_pending) + count($ot_pending);

$page_title  = 'Kehadiran & Cuti';
$active_menu = 'attendance';
include '_head.php';
?>
<style>
.tab-nav { display:flex; gap:4px; background:#f1f5f9; padding:4px; border-radius:12px; }
.tab-btn { flex:1; padding:8px 12px; border-radius:9px; border:none; background:transparent; font-size:0.8rem; font-weight:600; color:#64748b; cursor:pointer; transition:0.15s; display:flex; align-items:center; justify-content:center; gap:6px; }
.tab-btn.active { background:#fff; color:#0f172a; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
.tab-badge { background:#ef4444; color:#fff; font-size:0.6rem; padding:2px 5px; border-radius:5px; }
</style>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div>
            <h5>Kehadiran & Cuti</h5>
            <div class="text-muted" style="font-size:0.75rem;">Monitor absensi, kelola pengajuan cuti & lembur</div>
        </div>
        <?php if($total_pending > 0): ?>
        <span class="badge-soft badge-warning"><i class="fa fa-clock me-1"></i><?= $total_pending ?> menunggu approval</span>
        <?php endif; ?>
    </div>
    <div class="page-body">

        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
            <i class="fa fa-check-circle me-2"></i><?= $msg ?>
        </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tab-nav mb-4" style="max-width:500px;">
            <button class="tab-btn <?= $active_tab=='absensi'?'active':'' ?>" onclick="switchTab('absensi')">
                <i class="fa fa-fingerprint"></i> Absensi
            </button>
            <button class="tab-btn <?= $active_tab=='cuti'?'active':'' ?>" onclick="switchTab('cuti')">
                <i class="fa fa-calendar-minus"></i> Cuti/Izin
                <?php if(count($cuti_pending)>0): ?><span class="tab-badge"><?= count($cuti_pending) ?></span><?php endif; ?>
            </button>
            <button class="tab-btn <?= $active_tab=='lembur'?'active':'' ?>" onclick="switchTab('lembur')">
                <i class="fa fa-clock"></i> Lembur
                <?php if(count($ot_pending)>0): ?><span class="tab-badge"><?= count($ot_pending) ?></span><?php endif; ?>
            </button>
        </div>

        <!-- TAB: ABSENSI -->
        <div id="tab-absensi" class="tab-content" style="display:<?= $active_tab=='absensi'?'block':'none' ?>">
            <div class="data-card">
                <div class="data-card-header">
                    <h6><i class="fa fa-fingerprint me-2 text-primary"></i>Log Kehadiran — <?= date('d F Y', strtotime($filter_date)) ?></h6>
                    <form method="GET" class="d-flex gap-2" onsubmit="this.querySelector('[name=tab]').value='absensi'">
                        <input type="hidden" name="tab" value="absensi">
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= $filter_date ?>" style="width:150px;">
                        <button type="submit" class="btn-primary-custom" style="padding:6px 12px; font-size:0.78rem;">Filter</button>
                    </form>
                </div>
                <table class="table">
                    <thead><tr><th>NIK</th><th>Nama</th><th>Divisi</th><th>Masuk</th><th>Pulang</th><th>Tipe</th><th>Status</th><th>Laporan Kerja</th></tr></thead>
                    <tbody>
                    <?php if(empty($absensi)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><i class="fa fa-calendar-times me-2"></i>Tidak ada data absensi.</td></tr>
                    <?php else: foreach($absensi as $row):
                        $masuk  = date('H:i', strtotime($row['check_in_time']));
                        $pulang = $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '—';
                        $telat  = strtotime($row['check_in_time']) > strtotime($row['date_log'].' 08:30:00');
                    ?>
                    <tr>
                        <td><code style="font-size:0.72rem; background:#f1f5f9; padding:2px 5px; border-radius:4px;"><?= $row['employee_id'] ?></code></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['division'] ?? '-') ?></td>
                        <td><?= $masuk ?> <?php if($telat): ?><span class="badge-soft badge-warning" style="font-size:0.6rem;">Telat</span><?php endif; ?></td>
                        <td><?= $pulang ?></td>
                        <td><span class="badge-soft <?= $row['type']=='WFO'?'badge-primary':'badge-success' ?>"><?= $row['type'] ?></span></td>
                        <td><?= $row['check_out_time'] ? '<span class="badge-soft badge-muted">Selesai</span>' : '<span class="badge-soft badge-warning">Aktif</span>' ?></td>
                        <td class="text-muted" style="font-size:0.75rem; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= $row['tasks'] ? htmlspecialchars(substr($row['tasks'],0,40)).'...' : '—' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: CUTI -->
        <div id="tab-cuti" class="tab-content" style="display:<?= $active_tab=='cuti'?'block':'none' ?>">
            <?php if(!empty($cuti_pending) && !$is_guest): ?>
            <div class="data-card mb-4">
                <div class="data-card-header" style="background:#fffbeb; border-color:#fde68a;">
                    <h6 style="color:#92400e;"><i class="fa fa-clock me-2"></i>Menunggu Approval (<?= count($cuti_pending) ?>)</h6>
                </div>
                <table class="table">
                    <thead><tr><th>Karyawan</th><th>Jenis</th><th>Periode</th><th>Alasan</th><th>Durasi</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach($cuti_pending as $row):
                        $dur = hitungHariKerja($row['start_date'], $row['end_date'], $pdo);
                    ?>
                    <tr>
                        <td><div class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></div><div style="font-size:0.72rem; color:#94a3b8;"><?= htmlspecialchars($row['division']) ?></div></td>
                        <td><span class="badge-soft badge-primary"><?= htmlspecialchars($row['leave_type']) ?></span></td>
                        <td style="font-size:0.8rem;"><?= date('d M',strtotime($row['start_date'])) ?> – <?= date('d M Y',strtotime($row['end_date'])) ?></td>
                        <td class="text-muted" style="font-size:0.8rem;">"<?= htmlspecialchars(substr($row['reason'],0,40)) ?>"</td>
                        <td><?= $row['leave_type']=='Cuti Tahunan' ? "$dur hari kerja" : '—' ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <?= csrfTokenField() ?><input type="hidden" name="id_cuti" value="<?= $row['id'] ?>"><input type="hidden" name="action" value="Rejected">
                                <button type="submit" name="process_cuti" class="btn btn-sm btn-light border text-danger fw-bold">Tolak</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <?= csrfTokenField() ?><input type="hidden" name="id_cuti" value="<?= $row['id'] ?>"><input type="hidden" name="action" value="Approved">
                                <button type="submit" name="process_cuti" class="btn btn-sm btn-success text-white fw-bold">Setujui</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <div class="data-card">
                <div class="data-card-header"><h6><i class="fa fa-list me-2"></i>Semua Pengajuan Cuti (30 Terbaru)</h6></div>
                <table class="table">
                    <thead><tr><th>Karyawan</th><th>Jenis</th><th>Periode</th><th>Status</th><th>Diproses Oleh</th><th>Tgl Pengajuan</th></tr></thead>
                    <tbody>
                    <?php if(empty($cuti_all)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data.</td></tr>
                    <?php else: foreach($cuti_all as $row):
                        $sc = ['Pending'=>'badge-warning','Approved'=>'badge-success','Rejected'=>'badge-danger'][$row['status']] ?? 'badge-muted';
                    ?>
                    <tr>
                        <td><div class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></div><div style="font-size:0.72rem; color:#94a3b8;"><?= $row['employee_id'] ?></div></td>
                        <td><?= htmlspecialchars($row['leave_type']) ?></td>
                        <td style="font-size:0.8rem;"><?= date('d M Y',strtotime($row['start_date'])) ?> – <?= date('d M Y',strtotime($row['end_date'])) ?></td>
                        <td><span class="badge-soft <?= $sc ?>"><?= $row['status'] ?></span></td>
                        <td><?= $row['approved_by'] ? htmlspecialchars($row['approved_by']) : '—' ?></td>
                        <td style="font-size:0.78rem; color:#94a3b8;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: LEMBUR -->
        <div id="tab-lembur" class="tab-content" style="display:<?= $active_tab=='lembur'?'block':'none' ?>">
            <?php if(!empty($ot_pending) && !$is_guest): ?>
            <div class="data-card mb-4">
                <div class="data-card-header" style="background:#fce7f3; border-color:#fbcfe8;">
                    <h6 style="color:#9d174d;"><i class="fa fa-clock me-2"></i>Menunggu Approval (<?= count($ot_pending) ?>)</h6>
                </div>
                <table class="table">
                    <thead><tr><th>Karyawan</th><th>Tanggal</th><th>Jam</th><th>Durasi</th><th>Alasan</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach($ot_pending as $row): ?>
                    <tr>
                        <td><div class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></div><div style="font-size:0.72rem; color:#94a3b8;"><?= $row['division'] ?></div></td>
                        <td><?= date('d M Y', strtotime($row['overtime_date'])) ?></td>
                        <td style="font-size:0.8rem;"><?= substr($row['start_time'],0,5) ?> – <?= substr($row['end_time'],0,5) ?></td>
                        <td><span class="badge-soft badge-info"><?= number_format((float)$row['duration_hours'],1) ?> jam</span></td>
                        <td class="text-muted" style="font-size:0.8rem;">"<?= htmlspecialchars(substr($row['reason'],0,40)) ?>"</td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <?= csrfTokenField() ?><input type="hidden" name="id_lembur" value="<?= $row['id'] ?>"><input type="hidden" name="action" value="Rejected">
                                <button type="submit" name="process_lembur" class="btn btn-sm btn-light border text-danger fw-bold">Tolak</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <?= csrfTokenField() ?><input type="hidden" name="id_lembur" value="<?= $row['id'] ?>"><input type="hidden" name="action" value="Approved">
                                <button type="submit" name="process_lembur" class="btn btn-sm btn-success text-white fw-bold">Setujui</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <div class="data-card">
                <div class="data-card-header"><h6><i class="fa fa-list me-2"></i>Semua Pengajuan Lembur (30 Terbaru)</h6></div>
                <table class="table">
                    <thead><tr><th>Karyawan</th><th>Tanggal</th><th>Durasi</th><th>Status</th><th>Diproses Oleh</th></tr></thead>
                    <tbody>
                    <?php if(empty($ot_all)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                    <?php else: foreach($ot_all as $row):
                        $sc = ['Pending'=>'badge-warning','Approved'=>'badge-success','Rejected'=>'badge-danger'][$row['status']] ?? 'badge-muted';
                    ?>
                    <tr>
                        <td><div class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></div><div style="font-size:0.72rem; color:#94a3b8;"><?= $row['employee_id'] ?></div></td>
                        <td><?= date('d M Y', strtotime($row['overtime_date'])) ?></td>
                        <td><?= number_format((float)$row['duration_hours'],1) ?> jam</td>
                        <td><span class="badge-soft <?= $sc ?>"><?= $row['status'] ?></span></td>
                        <td><?= $row['approved_by'] ? htmlspecialchars($row['approved_by']) : '—' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    event.target.closest('.tab-btn').classList.add('active');
}
</script>
</body></html>
