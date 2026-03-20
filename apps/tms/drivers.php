<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

$msg = ''; $msg_type = '';

if (isset($_POST['add_driver'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    $name  = sanitizeInput($_POST['fullname']);
    $phone = sanitizeInput($_POST['phone']);
    $sim   = sanitizeInput($_POST['license_type']);
    $nik   = sanitizeInput($_POST['license_number'] ?? '');
    $username = strtolower(str_replace(' ','', $name)) . rand(10,99);
    $hash  = hashPassword('driver123');
    try {
        $pdo->beginTransaction();
        safeQuery($pdo, "INSERT INTO tms_users (tenant_id,fullname,username,password,role,status) VALUES (1,?,?,?,'driver','active')", [$name,$username,$hash]);
        $uid = $pdo->lastInsertId();
        safeQuery($pdo, "INSERT INTO tms_drivers (user_id,vendor_id,name,phone,license_type,license_number) VALUES (?,1,?,?,?,?)", [$uid,$name,$phone,$sim,$nik]);
        $pdo->commit();
        $msg = "Driver $name ditambahkan. Login: $username / driver123"; $msg_type = 'success';
    } catch(Exception $e) { $pdo->rollBack(); $msg = "Gagal tambah driver."; $msg_type = 'danger'; }
}

$drivers = safeGetAll($pdo,
    "SELECT d.*, u.username, u.status as user_status,
     COALESCE(s.shipment_no,'—') as active_shipment,
     COALESCE(s.status,'—') as ship_status
     FROM tms_drivers d
     LEFT JOIN tms_users u ON d.user_id=u.id
     LEFT JOIN tms_shipments s ON s.driver_id=d.id AND s.status NOT IN ('completed','failed','cancelled')
     ORDER BY d.id DESC");

$page_title  = 'Driver Management';
$active_page = 'drivers';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <div><h3><i class="fa fa-users-gear text-warning me-2"></i>Driver Management</h3><p>Manage Personnel & Licenses</p></div>
        <button class="btn btn-accent shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDriver"><i class="fa fa-plus me-2"></i>Add Driver</button>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?=$msg_type?> border-0 rounded-3 py-2 mb-3 small fw-bold">
        <i class="fa fa-<?=$msg_type=='success'?'check-circle':'exclamation-circle'?> me-2"></i><?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="data-card">
        <table class="table" id="tableDriver">
            <thead><tr><th>Nama</th><th>No. HP</th><th>SIM</th><th>No. SIM</th><th>Login</th><th>Status Akun</th><th>Shipment Aktif</th></tr></thead>
            <tbody>
            <?php foreach($drivers as $row): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="https://ui-avatars.com/api/?name=<?=urlencode($row['name'])?>&background=f59e0b&color=000&size=32&bold=true" class="rounded-circle" width="32">
                        <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><span class="badge-soft bs-primary"><?= htmlspecialchars($row['license_type'] ?? '—') ?></span></td>
                <td style="font-size:0.8rem;"><?= htmlspecialchars($row['license_number'] ?? '—') ?></td>
                <td style="font-size:0.78rem;"><code><?= htmlspecialchars($row['username'] ?? '—') ?></code></td>
                <td><span class="badge-soft bs-success"><?= ucfirst($row['user_status'] ?? 'active') ?></span></td>
                <td style="font-size:0.78rem;">
                    <?php if($row['active_shipment'] !== '—'): ?>
                    <span class="badge-soft bs-warning"><?= htmlspecialchars($row['active_shipment']) ?></span>
                    <?php else: ?><span class="text-muted">Tidak ada</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalDriver" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Registrasi Driver Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Nama Lengkap *</label><input type="text" name="fullname" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">No. HP (WA) *</label><input type="text" name="phone" class="form-control" placeholder="0812..." required></div>
                    <div class="col-6"><label class="form-label">Jenis SIM</label>
                        <select name="license_type" class="form-select"><option value="B1 Polos">B1 Polos</option><option value="B1 Umum">B1 Umum</option><option value="B2 Umum">B2 Umum</option></select></div>
                    <div class="col-12"><label class="form-label">No. SIM</label><input type="text" name="license_number" class="form-control" placeholder="SIM-001-XXX"></div>
                </div>
                <div class="alert border-0 rounded-3 mt-3 py-2 small" style="background:#eff6ff; color:#1d4ed8;">
                    <i class="fa fa-info-circle me-2"></i>Password login driver otomatis: <strong>driver123</strong>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" name="add_driver" class="btn btn-accent w-100 fw-bold">Simpan Driver</button></div>
        </form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>$(document).ready(function(){ $('#tableDriver').DataTable(); });</script>
</body></html>
