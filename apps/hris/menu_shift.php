<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest   = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$msg = ''; $msg_type = '';

// TAMBAH SHIFT
if(isset($_POST['add_shift']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $name  = sanitizeInput($_POST['shift_name']);
    $start = sanitizeInput($_POST['start_time']);
    $end   = sanitizeInput($_POST['end_time']);
    $tol   = sanitizeInt($_POST['late_tolerance_minutes'] ?? 15);
    safeQuery($pdo, "INSERT INTO ess_shifts (shift_name,start_time,end_time,late_tolerance_minutes) VALUES (?,?,?,?)", [$name,$start,$end,$tol]);
    $msg = "Shift '$name' berhasil ditambahkan."; $msg_type = 'success';
}

// ASSIGN SHIFT KE KARYAWAN
if(isset($_POST['assign_shift']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $nik      = sanitizeInput($_POST['employee_id']);
    $shift_id = sanitizeInt($_POST['shift_id']);
    $eff_date = sanitizeInput($_POST['effective_date']);

    safeQuery($pdo, "INSERT INTO ess_employee_shifts (employee_id,shift_id,effective_date,assigned_by) VALUES (?,?,?,?)",
        [$nik, $shift_id, $eff_date, $nama_admin]);

    // Lifecycle event
    $shift = safeGetOne($pdo, "SELECT shift_name FROM ess_shifts WHERE id=?", [$shift_id]);
    safeQuery($pdo, "INSERT INTO ess_lifecycle (employee_id,event_type,event_date,notes,created_by) VALUES (?,?,?,?,?)",
        [$nik, 'Transferred', $eff_date, "Shift diubah ke: {$shift['shift_name']}", $nama_admin]);

    $msg = "Shift berhasil di-assign."; $msg_type = 'success';
}

// DELETE SHIFT
if(isset($_POST['delete_shift']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $sid = sanitizeInt($_POST['shift_id_del']);
    $used = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_employee_shifts WHERE shift_id=?", [$sid])['c'] ?? 0;
    if($used > 0) { $msg = "Shift masih digunakan oleh $used karyawan, tidak bisa dihapus."; $msg_type = 'danger'; }
    else { safeQuery($pdo, "DELETE FROM ess_shifts WHERE id=? AND id > 4", [$sid]); $msg = "Shift dihapus."; $msg_type = 'warning'; }
}

$shifts    = safeGetAll($pdo, "SELECT s.*, COUNT(es.id) as total_assigned FROM ess_shifts s LEFT JOIN ess_employee_shifts es ON s.id=es.shift_id GROUP BY s.id ORDER BY s.id");
$employees = safeGetAll($pdo, "SELECT u.employee_id, u.fullname, u.division, s.shift_name, es.effective_date FROM ess_users u LEFT JOIN ess_employee_shifts es ON u.employee_id=es.employee_id AND es.id=(SELECT MAX(id) FROM ess_employee_shifts WHERE employee_id=u.employee_id) LEFT JOIN ess_shifts s ON es.shift_id=s.id ORDER BY u.fullname");
$all_emp   = safeGetAll($pdo, "SELECT employee_id, fullname FROM ess_users ORDER BY fullname");

$page_title  = 'Shift Management';
$active_menu = 'shift';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div><h5>Shift Management</h5><div class="text-muted" style="font-size:0.75rem;">Kelola shift kerja dan assignment karyawan</div></div>
        <?php if(!$is_guest): ?>
        <div class="d-flex gap-2">
            <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalAddShift"><i class="fa fa-plus me-1"></i> Shift Baru</button>
            <button class="btn-primary-custom" style="background:#059669;" data-bs-toggle="modal" data-bs-target="#modalAssign"><i class="fa fa-user-tag me-1"></i> Assign Shift</button>
        </div>
        <?php endif; ?>
    </div>
    <div class="page-body">

        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
            <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- DAFTAR SHIFT -->
            <div class="col-md-5">
                <div class="data-card">
                    <div class="data-card-header"><h6><i class="fa fa-clock me-2 text-primary"></i>Definisi Shift (<?= count($shifts) ?>)</h6></div>
                    <div class="p-3 d-flex flex-column gap-2">
                    <?php foreach($shifts as $s): ?>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="d-flex align-items-center justify-content-center rounded-3" style="width:44px;height:44px;background:#eef2ff;color:#4f46e5;font-size:1.1rem;flex-shrink:0;">
                            <i class="fa fa-<?= $s['shift_name']=='Regular (08:00-17:00)'?'sun':'moon' ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:0.875rem;"><?= htmlspecialchars($s['shift_name']) ?></div>
                            <div style="font-size:0.75rem; color:#64748b;">
                                <?= substr($s['start_time'],0,5) ?> – <?= substr($s['end_time'],0,5) ?>
                                &bull; Toleransi <?= $s['late_tolerance_minutes'] ?> menit
                            </div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:0.72rem; color:#94a3b8;"><?= $s['total_assigned'] ?> karyawan</div>
                            <?php if(!$is_guest && $s['id'] > 4): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus shift ini?')">
                                <?= csrfTokenField() ?><input type="hidden" name="shift_id_del" value="<?= $s['id'] ?>">
                                <button type="submit" name="delete_shift" class="btn btn-sm btn-light border mt-1"><i class="fa fa-trash text-danger" style="font-size:0.7rem;"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ASSIGNMENT PER KARYAWAN -->
            <div class="col-md-7">
                <div class="data-card">
                    <div class="data-card-header"><h6><i class="fa fa-users me-2 text-primary"></i>Shift Karyawan</h6></div>
                    <table class="table">
                        <thead><tr><th>Karyawan</th><th>Divisi</th><th>Shift Aktif</th><th>Sejak</th></tr></thead>
                        <tbody>
                        <?php foreach($employees as $e): ?>
                        <tr>
                            <td>
                                <div class="fw-bold" style="font-size:0.875rem;"><?= htmlspecialchars($e['fullname']) ?></div>
                                <div style="font-size:0.7rem;color:#94a3b8;"><?= htmlspecialchars($e['employee_id']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($e['division']) ?></td>
                            <td>
                                <?php if($e['shift_name']): ?>
                                <span class="badge-soft badge-primary"><?= htmlspecialchars($e['shift_name']) ?></span>
                                <?php else: ?>
                                <span class="badge-soft badge-muted">Regular (default)</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.78rem; color:#94a3b8;">
                                <?= $e['effective_date'] ? date('d M Y', strtotime($e['effective_date'])) : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(!$is_guest): ?>
<!-- MODAL TAMBAH SHIFT -->
<div class="modal fade" id="modalAddShift" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">Tambah Shift Baru</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><<?= csrfTokenField() ?>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Nama Shift</label><input type="text" name="shift_name" class="form-control" placeholder="Misal: Sore (13:00-21:00)" required></div>
                    <div class="col-6"><label class="form-label">Jam Mulai</label><input type="time" name="start_time" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">Jam Selesai</label><input type="time" name="end_time" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Toleransi Keterlambatan (menit)</label><input type="number" name="late_tolerance_minutes" class="form-control" value="15" min="0" max="60"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_shift" class="btn-primary-custom">Simpan</button></div>
        </form>
    </div></div>
</div>

<!-- MODAL ASSIGN SHIFT -->
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">Assign Shift ke Karyawan</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Karyawan</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">— Pilih Karyawan —</option>
                            <?php foreach($all_emp as $e): ?><option value="<?= $e['employee_id'] ?>"><?= htmlspecialchars($e['fullname']) ?> (<?= $e['employee_id'] ?>)</option><?php endforeach; ?>
                        </select></div>
                    <div class="col-12"><label class="form-label">Shift</label>
                        <select name="shift_id" class="form-select" required>
                            <?php foreach($shifts as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['shift_name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-12"><label class="form-label">Berlaku Mulai</label><input type="date" name="effective_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="assign_shift" class="btn-primary-custom">Assign</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
