<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest = ($_SESSION['hris_user'] === 'guest');
$msg = ''; $msg_type = '';

// TAMBAH KARYAWAN
if(isset($_POST['add_employee']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $nama  = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $nik   = sanitizeInput($_POST['employee_id']);
    $div   = sanitizeInput($_POST['division']);
    $role  = sanitizeInput($_POST['role']);
    $gaji  = sanitizeInt(str_replace('.', '', $_POST['basic_salary'] ?? '0'));
    $join  = sanitizeInput($_POST['join_date']);
    $pass  = hashPassword($_POST['password']);

    $cek = safeGetOne($pdo, "SELECT id FROM ess_users WHERE employee_id=?", [$nik]);
    if($cek) { $msg = "NIK $nik sudah terdaftar!"; $msg_type = 'danger'; }
    else {
        safeQuery($pdo, "INSERT INTO ess_users (fullname,email,division,employee_id,password,role,basic_salary,annual_leave_quota,join_date,created_at) VALUES (?,?,?,?,?,?,?,12,?,NOW())",
            [$nama,$email,$div,$nik,$pass,$role,$gaji,$join]);
        logSecurityEvent("HRIS Add Emp: $nik");
        $msg = "Karyawan $nama berhasil ditambahkan."; $msg_type = 'success';
    }
}

// UPDATE KARYAWAN
if(isset($_POST['update_employee']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $id   = sanitizeInt($_POST['id_user']);
    $role = sanitizeInput($_POST['role']);
    $div  = sanitizeInput($_POST['division']);
    $gaji = sanitizeInt(str_replace('.', '', $_POST['basic_salary'] ?? '0'));
    $quota = sanitizeInt($_POST['annual_leave_quota']);
    safeQuery($pdo, "UPDATE ess_users SET role=?,division=?,basic_salary=?,annual_leave_quota=? WHERE id=?", [$role,$div,$gaji,$quota,$id]);
    logSecurityEvent("HRIS Update Emp: $id");
    $msg = "Data karyawan diperbarui."; $msg_type = 'success';
}

// DELETE KARYAWAN
if(isset($_POST['delete_employee']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $id = sanitizeInt($_POST['emp_id']);
    safeQuery($pdo, "DELETE FROM ess_users WHERE id=?", [$id]);
    logSecurityEvent("HRIS Delete Emp: $id");
    $msg = "Karyawan dihapus."; $msg_type = 'warning';
}

// Filter & search
$search = sanitizeInput($_GET['q'] ?? '');
$filter_div = sanitizeInput($_GET['div'] ?? '');
$sql = "SELECT * FROM ess_users WHERE 1=1";
$params = [];
if($search) { $sql .= " AND (fullname LIKE ? OR employee_id LIKE ? OR email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if($filter_div) { $sql .= " AND division=?"; $params[] = $filter_div; }
$sql .= " ORDER BY fullname ASC";
$employees = safeGetAll($pdo, $sql, $params);

$divisions = safeGetAll($pdo, "SELECT DISTINCT division FROM ess_users ORDER BY division ASC");

$page_title = 'Data Karyawan';
$active_menu = 'employee';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div>
            <h5>Data Karyawan</h5>
            <div class="text-muted" style="font-size:0.75rem;"><?= count($employees) ?> karyawan terdaftar</div>
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
            <i class="fa fa-<?= $msg_type=='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= $msg ?>
        </div>
        <?php endif; ?>

        <?php if($is_guest): ?>
        <div class="alert border-0 rounded-3 mb-3 py-2 small" style="background:#fef3c7; color:#92400e;">
            <i class="fa fa-eye me-2"></i> Mode Guest — hanya bisa melihat data. Tidak bisa tambah, edit, atau hapus karyawan.
        </div>
        <?php endif; ?>

        <!-- FILTER -->
        <div class="data-card mb-3">
            <div class="p-3">
                <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                    <div>
                        <label class="form-label mb-1">Cari Karyawan</label>
                        <input type="text" name="q" class="form-control" placeholder="Nama / NIK / Email..." value="<?= htmlspecialchars($search) ?>" style="min-width:200px;">
                    </div>
                    <div>
                        <label class="form-label mb-1">Filter Divisi</label>
                        <select name="div" class="form-select">
                            <option value="">Semua Divisi</option>
                            <?php foreach($divisions as $d): ?>
                            <option value="<?= $d['division'] ?>" <?= $filter_div==$d['division']?'selected':'' ?>><?= $d['division'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary-custom">Filter</button>
                    <?php if($search || $filter_div): ?>
                    <a href="menu_employee.php" class="btn btn-light border rounded-3 py-2 px-3 fw-bold" style="font-size:0.82rem;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- TABLE -->
        <div class="data-card">
            <table class="table">
                <thead>
                    <tr><th>NIK</th><th>Nama Karyawan</th><th>Divisi</th><th>Jabatan</th><th>Gaji Pokok</th><th>Sisa Cuti</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php if(empty($employees)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data karyawan.</td></tr>
                <?php else: foreach($employees as $row):
                    $last_login = $row['last_login'] ? date('d M', strtotime($row['last_login'])) : '-';
                    $is_active  = $row['last_login'] && strtotime($row['last_login']) > strtotime('-30 days');
                ?>
                <tr>
                    <td><code style="font-size:0.75rem; background:#f1f5f9; padding:2px 6px; border-radius:5px;"><?= $row['employee_id'] ?></code></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['fullname']) ?>&background=eef2ff&color=4f46e5&size=32&bold=true" class="avatar-sm">
                            <div>
                                <div class="fw-bold" style="font-size:0.875rem;"><?= htmlspecialchars($row['fullname']) ?></div>
                                <div style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($row['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['division']) ?></td>
                    <td><span class="badge-soft badge-<?= $row['role']=='Manager'?'danger':($row['role']=='Supervisor'?'warning':'muted') ?>"><?= $row['role'] ?></span></td>
                    <td class="fw-bold text-success">Rp <?= number_format($row['basic_salary'], 0, ',', '.') ?></td>
                    <td><?= $row['annual_leave_quota'] ?> hari</td>
                    <td><span class="badge-soft <?= $is_active ? 'badge-success' : 'badge-muted' ?>"><?= $is_active ? 'Aktif' : 'Tidak Aktif' ?></span></td>
                    <td>
                        <?php if(!$is_guest): ?>
                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit"><i class="fa fa-pen text-primary"></i></button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus karyawan <?= addslashes($row['fullname']) ?>?')">
                            <?= csrfTokenField() ?>
                            <input type="hidden" name="emp_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="delete_employee" class="btn btn-sm btn-light border" title="Hapus"><i class="fa fa-trash text-danger"></i></button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH -->
<?php if(!$is_guest): ?>
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title fw-bold"><i class="fa fa-user-plus me-2 text-primary"></i>Tambah Karyawan Baru</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= csrfTokenField() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama Lengkap *</label><input type="text" name="fullname" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">NIK / Employee ID *</label><input type="text" name="employee_id" class="form-control" required placeholder="contoh: 010101"></div>
                        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Password Awal *</label><input type="password" name="password" class="form-control" required minlength="6" placeholder="Min. 6 karakter"></div>
                        <div class="col-md-4"><label class="form-label">Divisi</label><input type="text" name="division" class="form-control" placeholder="IT, Finance, ..."></div>
                        <div class="col-md-4"><label class="form-label">Jabatan</label>
                            <select name="role" class="form-select">
                                <option value="Staff">Staff</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Manager">Manager</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Join Date</label><input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Gaji Pokok (Rp)</label><input type="number" name="basic_salary" class="form-control" placeholder="0"></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_employee" class="btn-primary-custom">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT per karyawan -->
<?php foreach($employees as $row): ?>
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title fw-bold">Edit: <?= htmlspecialchars($row['fullname']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= csrfTokenField() ?>
                <input type="hidden" name="id_user" value="<?= $row['id'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">Jabatan</label>
                            <select name="role" class="form-select">
                                <option value="Staff" <?= $row['role']=='Staff'?'selected':'' ?>>Staff</option>
                                <option value="Supervisor" <?= $row['role']=='Supervisor'?'selected':'' ?>>Supervisor</option>
                                <option value="Manager" <?= $row['role']=='Manager'?'selected':'' ?>>Manager</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Divisi</label><input type="text" name="division" class="form-control" value="<?= htmlspecialchars($row['division']) ?>"></div>
                        <div class="col-6"><label class="form-label">Gaji Pokok (Rp)</label><input type="number" name="basic_salary" class="form-control" value="<?= $row['basic_salary'] ?>"></div>
                        <div class="col-6"><label class="form-label">Sisa Cuti (hari)</label><input type="number" name="annual_leave_quota" class="form-control" value="<?= $row['annual_leave_quota'] ?>" min="0" max="30"></div>
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
