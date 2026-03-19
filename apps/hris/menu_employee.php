<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest   = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$msg = ''; $msg_type = '';

// ── TAMBAH KARYAWAN ──────────────────────────────────────────
if(isset($_POST['add_employee']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $nama   = sanitizeInput($_POST['fullname']);
    $email  = sanitizeInput($_POST['email']);
    $nik    = sanitizeInput($_POST['employee_id']);
    $div    = sanitizeInput($_POST['division']);
    $dept   = sanitizeInput($_POST['department'] ?? '');
    $role   = sanitizeInput($_POST['role']);
    $pos    = sanitizeInput($_POST['position'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $birth  = sanitizeInput($_POST['birth_date'] ?? '');
    $edu    = sanitizeInput($_POST['education'] ?? '');
    $gaji   = sanitizeInt(str_replace('.', '', $_POST['basic_salary'] ?? '0'));
    $join   = sanitizeInput($_POST['join_date']);
    $tipe   = sanitizeInput($_POST['tipe_kontrak'] ?? 'PKWTT');
    $status = sanitizeInput($_POST['employee_status'] ?? 'Active');
    $k_start= sanitizeInput($_POST['kontrak_start'] ?? '');
    $k_end  = sanitizeInput($_POST['kontrak_end'] ?? '');
    $prob   = sanitizeInput($_POST['probation_end'] ?? '');
    $mgr    = sanitizeInt($_POST['manager_id'] ?? 0);
    $pass   = hashPassword($_POST['password']);

    $cek = safeGetOne($pdo, "SELECT id FROM ess_users WHERE employee_id=?", [$nik]);
    if($cek) { $msg = "NIK $nik sudah terdaftar!"; $msg_type = 'danger'; }
    else {
        safeQuery($pdo,
            "INSERT INTO ess_users (fullname,gender,birth_date,education,email,division,department,position,employee_id,password,role,tipe_kontrak,employee_status,basic_salary,annual_leave_quota,join_date,kontrak_start,kontrak_end,probation_end,manager_id,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,12,?,?,?,?,?,NOW())",
            [$nama,$gender,$birth?:null,$edu,$email,$div,$dept,$pos,$nik,$pass,$role,$tipe,$status,$gaji,$join,$k_start?:null,$k_end?:null,$prob?:null,$mgr?:null]);

        // Auto-create lifecycle event: Hired
        safeQuery($pdo,
            "INSERT INTO ess_lifecycle (employee_id,event_type,event_date,new_position,new_division,new_salary,notes,created_by) VALUES (?,?,?,?,?,?,?,?)",
            [$nik,'Hired',$join,$pos,$div,$gaji,'Karyawan baru ditambahkan oleh HR',$nama_admin]);

        // Auto-assign shift Regular
        safeQuery($pdo,
            "INSERT INTO ess_employee_shifts (employee_id,shift_id,effective_date,assigned_by) VALUES (?,1,?,?)",
            [$nik,$join,$nama_admin]);

        logSecurityEvent("HRIS Add Emp: $nik");
        $msg = "Karyawan $nama berhasil ditambahkan."; $msg_type = 'success';
    }
}

// ── UPDATE KARYAWAN ──────────────────────────────────────────
if(isset($_POST['update_employee']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $id       = sanitizeInt($_POST['id_user']);
    $old      = safeGetOne($pdo, "SELECT * FROM ess_users WHERE id=?", [$id]);

    $role     = sanitizeInput($_POST['role']);
    $div      = sanitizeInput($_POST['division']);
    $dept     = sanitizeInput($_POST['department'] ?? '');
    $pos      = sanitizeInput($_POST['position'] ?? '');
    $gaji     = sanitizeInt(str_replace('.', '', $_POST['basic_salary'] ?? '0'));
    $quota    = sanitizeInt($_POST['annual_leave_quota']);
    $tipe     = sanitizeInput($_POST['tipe_kontrak'] ?? 'PKWTT');
    $status   = sanitizeInput($_POST['employee_status'] ?? 'Active');
    $k_end    = sanitizeInput($_POST['kontrak_end'] ?? '');
    $prob     = sanitizeInput($_POST['probation_end'] ?? '');
    $mgr      = sanitizeInt($_POST['manager_id'] ?? 0);
    $npwp     = sanitizeInput($_POST['npwp'] ?? '');
    $bpjs_kes = sanitizeInput($_POST['no_bpjs_kes'] ?? '');
    $bpjs_tk  = sanitizeInput($_POST['no_bpjs_tk'] ?? '');

    safeQuery($pdo,
        "UPDATE ess_users SET role=?,division=?,department=?,position=?,basic_salary=?,annual_leave_quota=?,tipe_kontrak=?,employee_status=?,kontrak_end=?,probation_end=?,manager_id=?,npwp=?,no_bpjs_kes=?,no_bpjs_tk=? WHERE id=?",
        [$role,$div,$dept,$pos,$gaji,$quota,$tipe,$status,$k_end?:null,$prob?:null,$mgr?:null,$npwp,$bpjs_kes,$bpjs_tk,$id]);

    // Auto-create lifecycle jika ada perubahan signifikan
    $events = [];
    if($old['role'] != $role || $old['division'] != $div) {
        safeQuery($pdo,
            "INSERT INTO ess_lifecycle (employee_id,event_type,event_date,old_position,new_position,old_division,new_division,old_salary,new_salary,notes,created_by) VALUES (?,?,NOW(),?,?,?,?,?,?,?,?)",
            [$old['employee_id'],'Promoted',$old['position'],$pos,$old['division'],$div,$old['basic_salary'],$gaji,'Update data oleh HR',$nama_admin]);
    }
    if($old['employee_status'] != $status) {
        $event_map = ['Resigned'=>'Resigned','Terminated'=>'Terminated','Active'=>'Reactivated','Probation'=>'Probation Start','Inactive'=>'Terminated'];
        $ev = $event_map[$status] ?? 'Confirmed';
        safeQuery($pdo,
            "INSERT INTO ess_lifecycle (employee_id,event_type,event_date,notes,created_by) VALUES (?,?,NOW(),?,?)",
            [$old['employee_id'],$ev,"Status berubah dari {$old['employee_status']} ke $status",$nama_admin]);
    }

    logSecurityEvent("HRIS Update Emp: $id");
    $msg = "Data karyawan diperbarui."; $msg_type = 'success';
}

// ── DELETE KARYAWAN ──────────────────────────────────────────
if(isset($_POST['delete_employee']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $id  = sanitizeInt($_POST['emp_id']);
    $emp = safeGetOne($pdo, "SELECT employee_id, fullname FROM ess_users WHERE id=?", [$id]);
    safeQuery($pdo, "DELETE FROM ess_users WHERE id=?", [$id]);
    logSecurityEvent("HRIS Delete Emp: $id");
    $msg = "Karyawan {$emp['fullname']} dihapus."; $msg_type = 'warning';
}

// ── FILTER & DATA ────────────────────────────────────────────
$search      = sanitizeInput($_GET['q'] ?? '');
$filter_div  = sanitizeInput($_GET['div'] ?? '');
$filter_stat = sanitizeInput($_GET['status'] ?? '');
$filter_tipe = sanitizeInput($_GET['tipe'] ?? '');

$sql    = "SELECT u.*, s.shift_name FROM ess_users u
           LEFT JOIN ess_employee_shifts es ON u.employee_id=es.employee_id
               AND es.id = (SELECT MAX(id) FROM ess_employee_shifts WHERE employee_id=u.employee_id)
           LEFT JOIN ess_shifts s ON es.shift_id=s.id
           WHERE 1=1";
$params = [];
if($search)      { $sql .= " AND (u.fullname LIKE ? OR u.employee_id LIKE ? OR u.email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if($filter_div)  { $sql .= " AND u.division=?"; $params[] = $filter_div; }
if($filter_stat) { $sql .= " AND u.employee_status=?"; $params[] = $filter_stat; }
if($filter_tipe) { $sql .= " AND u.tipe_kontrak=?"; $params[] = $filter_tipe; }
$sql .= " ORDER BY u.fullname ASC";

$employees = safeGetAll($pdo, $sql, $params);
$divisions = safeGetAll($pdo, "SELECT DISTINCT division FROM ess_users ORDER BY division ASC");
$managers  = safeGetAll($pdo, "SELECT id, fullname, employee_id FROM ess_users WHERE role IN ('Manager','Supervisor') ORDER BY fullname");
$shifts    = safeGetAll($pdo, "SELECT * FROM ess_shifts WHERE is_active=1");

// Summary KPI
$kpi = safeGetOne($pdo,
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN employee_status='Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN employee_status='Probation' THEN 1 ELSE 0 END) as probation,
        SUM(CASE WHEN tipe_kontrak='PKWT' AND kontrak_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring
     FROM ess_users");

$page_title  = 'Data Karyawan';
$active_menu = 'employee';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div>
            <h5>Data Karyawan</h5>
            <div class="text-muted" style="font-size:0.75rem;"><?= count($employees) ?> karyawan ditampilkan</div>
        </div>
        <?php if(!$is_guest): ?>
        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fa fa-plus"></i> Tambah Karyawan
        </button>
        <?php endif; ?>
    </div>
    <div class="page-body">

        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
            <i class="fa fa-<?= $msg_type=='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <?php if($is_guest): ?>
        <div class="alert border-0 rounded-3 mb-3 py-2 small" style="background:#fef3c7; color:#92400e;">
            <i class="fa fa-eye me-2"></i> Mode Guest — hanya bisa melihat data.
        </div>
        <?php endif; ?>

        <!-- KPI STRIP -->
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="kpi-card">
                <div class="kpi-icon" style="background:#eef2ff;color:#4f46e5;"><i class="fa fa-users"></i></div>
                <div><div class="kpi-num"><?= $kpi['total'] ?></div><div class="kpi-label">Total Karyawan</div></div>
            </div></div>
            <div class="col-md-3"><div class="kpi-card">
                <div class="kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fa fa-user-check"></i></div>
                <div><div class="kpi-num text-success"><?= $kpi['active'] ?></div><div class="kpi-label">Aktif</div></div>
            </div></div>
            <div class="col-md-3"><div class="kpi-card">
                <div class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fa fa-user-clock"></i></div>
                <div><div class="kpi-num text-warning"><?= $kpi['probation'] ?></div><div class="kpi-label">Probation</div></div>
            </div></div>
            <div class="col-md-3"><div class="kpi-card" style="<?= $kpi['expiring']>0?'border-color:#ef4444;':'' ?>">
                <div class="kpi-icon" style="background:#fef2f2;color:#ef4444;"><i class="fa fa-file-contract"></i></div>
                <div><div class="kpi-num text-danger"><?= $kpi['expiring'] ?></div><div class="kpi-label">Kontrak Hampir Habis</div><div class="kpi-sub">30 hari ke depan</div></div>
            </div></div>
        </div>

        <!-- FILTER -->
        <div class="data-card mb-3">
            <div class="p-3">
                <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                    <div><label class="form-label mb-1">Cari</label>
                        <input type="text" name="q" class="form-control" placeholder="Nama / NIK / Email" value="<?= htmlspecialchars($search) ?>" style="min-width:180px;"></div>
                    <div><label class="form-label mb-1">Divisi</label>
                        <select name="div" class="form-select">
                            <option value="">Semua</option>
                            <?php foreach($divisions as $d): ?>
                            <option value="<?= $d['division'] ?>" <?= $filter_div==$d['division']?'selected':'' ?>><?= htmlspecialchars($d['division']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div><label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <?php foreach(['Active','Probation','Inactive','Resigned','Terminated'] as $s): ?>
                            <option value="<?=$s?>" <?= $filter_stat==$s?'selected':'' ?>><?=$s?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div><label class="form-label mb-1">Tipe Kontrak</label>
                        <select name="tipe" class="form-select">
                            <option value="">Semua</option>
                            <?php foreach(['PKWTT','PKWT','Kontrak','Magang','Freelance'] as $t): ?>
                            <option value="<?=$t?>" <?= $filter_tipe==$t?'selected':'' ?>><?=$t?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <button type="submit" class="btn-primary-custom">Filter</button>
                    <?php if($search||$filter_div||$filter_stat||$filter_tipe): ?>
                    <a href="menu_employee.php" class="btn btn-light border rounded-3 py-2 px-3 fw-bold" style="font-size:0.82rem;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- TABLE -->
        <div class="data-card">
            <table class="table">
                <thead><tr><th>NIK</th><th>Nama Karyawan</th><th>Jabatan</th><th>Tipe Kontrak</th><th>Status</th><th>Shift</th><th>Gaji Pokok</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if(empty($employees)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                <?php else: foreach($employees as $row):
                    $stat_color = ['Active'=>'badge-success','Probation'=>'badge-warning','Inactive'=>'badge-muted','Resigned'=>'badge-danger','Terminated'=>'badge-danger'][$row['employee_status']] ?? 'badge-muted';
                    $tipe_color = ['PKWTT'=>'badge-success','PKWT'=>'badge-warning','Kontrak'=>'badge-info','Magang'=>'badge-muted','Freelance'=>'badge-muted'][$row['tipe_kontrak']] ?? 'badge-muted';
                    // Kontrak hampir habis?
                    $expiring = $row['kontrak_end'] && strtotime($row['kontrak_end']) <= strtotime('+30 days') && strtotime($row['kontrak_end']) >= strtotime('today');
                ?>
                <tr <?= $expiring ? 'style="background:#fff5f5;"' : '' ?>>
                    <td><code style="font-size:0.72rem; background:#f1f5f9; padding:2px 5px; border-radius:4px;"><?= htmlspecialchars($row['employee_id']) ?></code></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['fullname']) ?>&background=eef2ff&color=4f46e5&size=32&bold=true" class="avatar-sm">
                            <div>
                                <div class="fw-bold" style="font-size:0.875rem;">
                                    <a href="menu_employee_detail.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($row['fullname']) ?></a>
                                </div>
                                <div style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($row['division']) ?><?= $row['department'] ? ' › '.$row['department'] : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:0.82rem; font-weight:600;"><?= htmlspecialchars($row['position'] ?: $row['role']) ?></div>
                        <div style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($row['role']) ?></div>
                    </td>
                    <td>
                        <span class="badge-soft <?= $tipe_color ?>"><?= $row['tipe_kontrak'] ?></span>
                        <?php if($expiring): ?>
                        <div style="font-size:0.65rem; color:#ef4444; margin-top:2px;"><i class="fa fa-exclamation-triangle me-1"></i>Habis <?= date('d M', strtotime($row['kontrak_end'])) ?></div>
                        <?php elseif($row['kontrak_end']): ?>
                        <div style="font-size:0.65rem; color:#94a3b8; margin-top:2px;">s/d <?= date('d M Y', strtotime($row['kontrak_end'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-soft <?= $stat_color ?>"><?= $row['employee_status'] ?></span></td>
                    <td style="font-size:0.78rem;"><?= htmlspecialchars($row['shift_name'] ?? 'Regular') ?></td>
                    <td class="fw-bold text-success" style="font-size:0.82rem;">Rp <?= number_format($row['basic_salary'],0,',','.') ?></td>
                    <td>
                        <?php if(!$is_guest): ?>
                        <div class="d-flex gap-1">
                            <a href="menu_employee_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border" title="Detail"><i class="fa fa-eye text-primary"></i></a>
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit"><i class="fa fa-pen text-warning"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus karyawan <?= addslashes($row['fullname']) ?>?')">
                                <?= csrfTokenField() ?>
                                <input type="hidden" name="emp_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_employee" class="btn btn-sm btn-light border" title="Hapus"><i class="fa fa-trash text-danger"></i></button>
                            </form>
                        </div>
                        <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── MODAL TAMBAH ── -->
<?php if(!$is_guest): ?>
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fa fa-user-plus me-2 text-primary"></i>Tambah Karyawan Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfTokenField() ?>
                <div class="modal-body">
                    <!-- TAB NAVIGASI -->
                    <ul class="nav nav-tabs mb-3" id="addTabs">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-identitas" type="button">👤 Identitas</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-posisi" type="button">🏢 Posisi & Kontrak</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-akun" type="button">🔐 Akun & Gaji</button></li>
                    </ul>
                    <div class="tab-content">
                        <!-- IDENTITAS -->
                        <div class="tab-pane fade show active" id="tab-identitas">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Nama Lengkap *</label><input type="text" name="fullname" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label">Jenis Kelamin</label>
                                    <select name="gender" class="form-select"><option value="">— Pilih —</option><option>Laki-laki</option><option>Perempuan</option></select></div>
                                <div class="col-md-3"><label class="form-label">Tanggal Lahir</label><input type="date" name="birth_date" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label">Pendidikan Terakhir</label>
                                    <select name="education" class="form-select"><option value="">— Pilih —</option><option>SMA/SMK</option><option>D3</option><option>S1</option><option>S2</option><option>S3</option></select></div>
                            </div>
                        </div>
                        <!-- POSISI & KONTRAK -->
                        <div class="tab-pane fade" id="tab-posisi">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">NIK / Employee ID *</label><input type="text" name="employee_id" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Divisi</label><input type="text" name="division" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Departemen</label><input type="text" name="department" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Jabatan</label><input type="text" name="position" class="form-control" placeholder="Misal: Software Engineer"></div>
                                <div class="col-md-4"><label class="form-label">Role Sistem</label>
                                    <select name="role" class="form-select"><option value="Staff">Staff</option><option value="Supervisor">Supervisor</option><option value="Manager">Manager</option></select></div>
                                <div class="col-md-4"><label class="form-label">Atasan Langsung</label>
                                    <select name="manager_id" class="form-select"><option value="0">— Tidak Ada —</option>
                                        <?php foreach($managers as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['fullname']) ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-md-4"><label class="form-label">Tipe Kontrak</label>
                                    <select name="tipe_kontrak" class="form-select"><option value="PKWTT">PKWTT (Tetap)</option><option value="PKWT">PKWT</option><option value="Kontrak">Kontrak</option><option value="Magang">Magang</option><option value="Freelance">Freelance</option></select></div>
                                <div class="col-md-4"><label class="form-label">Status Awal</label>
                                    <select name="employee_status" class="form-select"><option value="Probation">Probation</option><option value="Active">Active</option></select></div>
                                <div class="col-md-4"><label class="form-label">Join Date *</label><input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                                <div class="col-md-4"><label class="form-label">Kontrak Mulai</label><input type="date" name="kontrak_start" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Kontrak Selesai</label><input type="date" name="kontrak_end" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Akhir Probation</label><input type="date" name="probation_end" class="form-control"></div>
                            </div>
                        </div>
                        <!-- AKUN & GAJI -->
                        <div class="tab-pane fade" id="tab-akun">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Password Awal *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                                <div class="col-md-6"><label class="form-label">Gaji Pokok (Rp)</label><input type="number" name="basic_salary" class="form-control" placeholder="0"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_employee" class="btn-primary-custom">Simpan Karyawan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL EDIT per karyawan ── -->
<?php foreach($employees as $row): ?>
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title fw-bold">Edit: <?= htmlspecialchars($row['fullname']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= csrfTokenField() ?>
                <input type="hidden" name="id_user" value="<?= $row['id'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Role</label>
                            <select name="role" class="form-select"><option value="Staff" <?= $row['role']=='Staff'?'selected':'' ?>>Staff</option><option value="Supervisor" <?= $row['role']=='Supervisor'?'selected':'' ?>>Supervisor</option><option value="Manager" <?= $row['role']=='Manager'?'selected':'' ?>>Manager</option></select></div>
                        <div class="col-md-4"><label class="form-label">Divisi</label><input type="text" name="division" class="form-control" value="<?= htmlspecialchars($row['division']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Departemen</label><input type="text" name="department" class="form-control" value="<?= htmlspecialchars($row['department'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Jabatan</label><input type="text" name="position" class="form-control" value="<?= htmlspecialchars($row['position'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Tipe Kontrak</label>
                            <select name="tipe_kontrak" class="form-select">
                                <?php foreach(['PKWTT','PKWT','Kontrak','Magang','Freelance'] as $t): ?>
                                <option value="<?=$t?>" <?= $row['tipe_kontrak']==$t?'selected':'' ?>><?=$t?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Status Karyawan</label>
                            <select name="employee_status" class="form-select">
                                <?php foreach(['Active','Probation','Inactive','Resigned','Terminated'] as $s): ?>
                                <option value="<?=$s?>" <?= $row['employee_status']==$s?'selected':'' ?>><?=$s?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Gaji Pokok (Rp)</label><input type="number" name="basic_salary" class="form-control" value="<?= $row['basic_salary'] ?>"></div>
                        <div class="col-md-4"><label class="form-label">Kuota Cuti</label><input type="number" name="annual_leave_quota" class="form-control" value="<?= $row['annual_leave_quota'] ?>" min="0" max="365"></div>
                        <div class="col-md-4"><label class="form-label">Kontrak Selesai</label><input type="date" name="kontrak_end" class="form-control" value="<?= $row['kontrak_end'] ?? '' ?>"></div>
                        <div class="col-md-4"><label class="form-label">Akhir Probation</label><input type="date" name="probation_end" class="form-control" value="<?= $row['probation_end'] ?? '' ?>"></div>
                        <div class="col-md-4"><label class="form-label">Atasan Langsung</label>
                            <select name="manager_id" class="form-select"><option value="0">— Tidak Ada —</option>
                                <?php foreach($managers as $m): ?><option value="<?= $m['id'] ?>" <?= $row['manager_id']==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['fullname']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">NPWP</label><input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($row['npwp'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">No. BPJS Kesehatan</label><input type="text" name="no_bpjs_kes" class="form-control" value="<?= htmlspecialchars($row['no_bpjs_kes'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">No. BPJS TK</label><input type="text" name="no_bpjs_tk" class="form-control" value="<?= htmlspecialchars($row['no_bpjs_tk'] ?? '') ?>"></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="update_employee" class="btn-primary-custom">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if($msg): ?>
<script>
Swal.fire({ icon: '<?= $msg_type ?>', title: '<?= $msg_type=="success"?"Berhasil":"Perhatian" ?>', text: '<?= addslashes($msg) ?>', confirmButtonColor: '#4f46e5', timer: 3000, showConfirmButton: false });
</script>
<?php endif; ?>
</body></html>
