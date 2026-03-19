<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest   = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$msg = ''; $msg_type = '';

// UPDATE POLICY
if(isset($_POST['update_policy']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $id          = sanitizeInt($_POST['policy_id']);
    $quota       = sanitizeInt($_POST['annual_quota']);
    $min_service = sanitizeInt($_POST['min_service_months']);
    $carry       = isset($_POST['carry_forward']) ? 1 : 0;
    $max_carry   = sanitizeInt($_POST['max_carry_forward']);
    $req_doc     = isset($_POST['requires_document']) ? 1 : 0;
    safeQuery($pdo, "UPDATE ess_leave_policy SET annual_quota=?,min_service_months=?,carry_forward=?,max_carry_forward=?,requires_document=? WHERE id=?",
        [$quota,$min_service,$carry,$max_carry,$req_doc,$id]);
    $msg = "Policy cuti diperbarui."; $msg_type = 'success';
}

// RESET KUOTA TAHUNAN
if(isset($_POST['reset_quota']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $year       = (int)date('Y');
    $karyawan   = safeGetAll($pdo, "SELECT employee_id, annual_leave_quota FROM ess_users WHERE employee_status IN ('Active','Probation')");
    $policy     = safeGetOne($pdo, "SELECT annual_quota, carry_forward, max_carry_forward FROM ess_leave_policy WHERE leave_type='Cuti Tahunan'");
    $base_quota = $policy['annual_quota'] ?? 12;
    $can_carry  = $policy['carry_forward'] ?? 0;
    $max_carry  = $policy['max_carry_forward'] ?? 0;
    $reset_count = 0;

    foreach($karyawan as $k) {
        // Cek sudah di-reset tahun ini?
        $done = safeGetOne($pdo, "SELECT id FROM ess_leave_reset_log WHERE employee_id=? AND reset_year=?", [$k['employee_id'], $year]);
        if($done) continue;

        $sisa      = (int)$k['annual_leave_quota'];
        $carry_days= $can_carry ? min($sisa, $max_carry) : 0;
        $new_quota = $base_quota + $carry_days;

        safeQuery($pdo, "UPDATE ess_users SET annual_leave_quota=? WHERE employee_id=?", [$new_quota, $k['employee_id']]);
        safeQuery($pdo, "INSERT INTO ess_leave_reset_log (employee_id,reset_year,old_quota,new_quota,carry_forward_days,reset_by) VALUES (?,?,?,?,?,?)",
            [$k['employee_id'], $year, $sisa, $new_quota, $carry_days, $nama_admin]);

        // Notif ke karyawan
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
            [$k['employee_id'], 'leave_reset', "Kuota cuti tahunan Anda telah direset. Kuota baru: $new_quota hari."]);
        $reset_count++;
    }
    $msg = "$reset_count karyawan berhasil di-reset kuota cutunya untuk tahun $year."; $msg_type = 'success';
}

$policies   = safeGetAll($pdo, "SELECT * FROM ess_leave_policy ORDER BY id");
$reset_logs = safeGetAll($pdo, "SELECT r.*, u.fullname FROM ess_leave_reset_log r JOIN ess_users u ON r.employee_id=u.employee_id WHERE r.reset_year=? ORDER BY r.reset_at DESC LIMIT 20", [(int)date('Y')]);
$already_reset = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_leave_reset_log WHERE reset_year=?", [(int)date('Y')])['c'] ?? 0;

$page_title  = 'Leave Policy';
$active_menu = 'leavepolicy';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div><h5>Leave Policy & Kuota Cuti</h5><div class="text-muted" style="font-size:0.75rem;">Aturan cuti dan reset kuota tahunan</div></div>
        <?php if(!$is_guest): ?>
        <form method="POST" onsubmit="return confirm('Reset kuota cuti tahunan semua karyawan aktif?\n\nProses ini tidak bisa di-undo.')">
            <?= csrfTokenField() ?>
            <button type="submit" name="reset_quota" class="btn-primary-custom" style="background:#dc2626;">
                <i class="fa fa-refresh me-1"></i> Reset Kuota Tahunan <?= date('Y') ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
    <div class="page-body">

        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
            <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <?php if($already_reset > 0): ?>
        <div class="alert border-0 rounded-3 py-2 mb-3 small" style="background:#d1fae5; color:#065f46;">
            <i class="fa fa-check-circle me-2"></i> Kuota tahun <?= date('Y') ?> sudah di-reset untuk <?= $already_reset ?> karyawan.
        </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- POLICY TABLE -->
            <div class="col-md-7">
                <div class="data-card">
                    <div class="data-card-header"><h6><i class="fa fa-clipboard-list me-2 text-primary"></i>Kebijakan Per Jenis Cuti</h6></div>
                    <table class="table">
                        <thead><tr><th>Jenis Cuti</th><th>Kuota/Tahun</th><th>Min. Masa Kerja</th><th>Carry Forward</th><th>Dokumen</th><?php if(!$is_guest): ?><th>Aksi</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach($policies as $p): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($p['leave_type']) ?></td>
                            <td><?= $p['annual_quota'] > 0 ? $p['annual_quota'].' hari' : '<span class="text-muted">Tidak terbatas</span>' ?></td>
                            <td><?= $p['min_service_months'] > 0 ? $p['min_service_months'].' bulan' : '—' ?></td>
                            <td>
                                <?php if($p['carry_forward']): ?>
                                <span class="badge-soft badge-success">Maks. <?= $p['max_carry_forward'] ?> hari</span>
                                <?php else: ?>
                                <span class="badge-soft badge-muted">Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $p['requires_document'] ? '<span class="badge-soft badge-warning">Wajib</span>' : '<span class="text-muted">—</span>' ?></td>
                            <?php if(!$is_guest): ?>
                            <td>
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editPolicy<?= $p['id'] ?>"><i class="fa fa-pen text-primary" style="font-size:0.75rem;"></i></button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RESET LOG -->
            <div class="col-md-5">
                <div class="data-card">
                    <div class="data-card-header"><h6><i class="fa fa-history me-2 text-primary"></i>Log Reset Kuota <?= date('Y') ?></h6></div>
                    <?php if(empty($reset_logs)): ?>
                    <div class="empty-state"><i class="fa fa-history"></i>Belum ada reset tahun ini.</div>
                    <?php else: ?>
                    <table class="table">
                        <thead><tr><th>Karyawan</th><th>Lama</th><th>Baru</th><th>Carry</th></tr></thead>
                        <tbody>
                        <?php foreach($reset_logs as $r): ?>
                        <tr>
                            <td style="font-size:0.82rem;" class="fw-bold"><?= htmlspecialchars($r['fullname']) ?></td>
                            <td style="font-size:0.78rem;"><?= $r['old_quota'] ?> hari</td>
                            <td style="font-size:0.78rem;"><strong class="text-success"><?= $r['new_quota'] ?> hari</strong></td>
                            <td style="font-size:0.78rem;"><?= $r['carry_forward_days'] > 0 ? '+'.$r['carry_forward_days'] : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT POLICY -->
<?php if(!$is_guest): foreach($policies as $p): ?>
<div class="modal fade" id="editPolicy<?= $p['id'] ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">Edit: <?= htmlspecialchars($p['leave_type']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?>
            <input type="hidden" name="policy_id" value="<?= $p['id'] ?>">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label">Kuota Per Tahun (hari)</label><input type="number" name="annual_quota" class="form-control" value="<?= $p['annual_quota'] ?>" min="0"></div>
                    <div class="col-6"><label class="form-label">Min. Masa Kerja (bulan)</label><input type="number" name="min_service_months" class="form-control" value="<?= $p['min_service_months'] ?>" min="0"></div>
                    <div class="col-12">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="carry_forward" id="cf<?= $p['id'] ?>" <?= $p['carry_forward']?'checked':'' ?>>
                            <label class="form-check-label" for="cf<?= $p['id'] ?>">Carry Forward (sisa cuti bisa dibawa ke tahun depan)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_document" id="rd<?= $p['id'] ?>" <?= $p['requires_document']?'checked':'' ?>>
                            <label class="form-check-label" for="rd<?= $p['id'] ?>">Wajib Lampirkan Dokumen</label>
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label">Maks. Carry Forward (hari)</label><input type="number" name="max_carry_forward" class="form-control" value="<?= $p['max_carry_forward'] ?>" min="0"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="update_policy" class="btn-primary-custom">Simpan</button></div>
        </form>
    </div></div>
</div>
<?php endforeach; endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
