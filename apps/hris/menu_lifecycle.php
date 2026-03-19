<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$is_guest   = ($_SESSION['hris_user'] === 'guest');
$nama_admin = $_SESSION['hris_name'];
$msg = ''; $msg_type = '';

// ── TAMBAH EVENT ─────────────────────────────────────────────
if(isset($_POST['add_event']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $nik      = sanitizeInput($_POST['employee_id']);
    $ev_type  = sanitizeInput($_POST['event_type']);
    $ev_date  = sanitizeInput($_POST['event_date']);
    $new_pos  = sanitizeInput($_POST['new_position'] ?? '');
    $new_div  = sanitizeInput($_POST['new_division'] ?? '');
    $new_sal  = (float)$_POST['new_salary'] > 0 ? (float)$_POST['new_salary'] : null;
    $notes    = sanitizeInput($_POST['notes'] ?? '');

    // Ambil data lama
    $old = safeGetOne($pdo, "SELECT position, division, basic_salary, employee_status FROM ess_users WHERE employee_id=?", [$nik]);

    safeQuery($pdo,
        "INSERT INTO ess_lifecycle (employee_id,event_type,event_date,old_position,new_position,old_division,new_division,old_salary,new_salary,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
        [$nik,$ev_type,$ev_date,$old['position'],$new_pos?:null,$old['division'],$new_div?:null,$old['basic_salary'],$new_sal,$notes,$nama_admin]);

    // Update ess_users jika ada perubahan
    $updates = [];
    if($new_pos) { safeQuery($pdo, "UPDATE ess_users SET position=? WHERE employee_id=?", [$new_pos, $nik]); }
    if($new_div) { safeQuery($pdo, "UPDATE ess_users SET division=? WHERE employee_id=?", [$new_div, $nik]); }
    if($new_sal) { safeQuery($pdo, "UPDATE ess_users SET basic_salary=? WHERE employee_id=?", [$new_sal, $nik]); }

    // Update status otomatis berdasarkan event
    $status_map = ['Confirmed'=>'Active','Resigned'=>'Resigned','Terminated'=>'Terminated','Reactivated'=>'Active','Probation Start'=>'Probation'];
    if(isset($status_map[$ev_type])) {
        safeQuery($pdo, "UPDATE ess_users SET employee_status=? WHERE employee_id=?", [$status_map[$ev_type], $nik]);
    }

    // Kirim notifikasi ke karyawan
    $notif_msg = [
        'Confirmed'   => 'Selamat! Status Anda telah dikonfirmasi sebagai karyawan tetap.',
        'Promoted'    => 'Selamat atas promosi jabatan Anda!',
        'Transferred' => 'Anda telah dipindahkan ke divisi/posisi baru.',
        'Resigned'    => 'Proses pengunduran diri Anda telah dicatat.',
        'Contract Renewed' => 'Kontrak kerja Anda telah diperpanjang.',
    ];
    if(isset($notif_msg[$ev_type])) {
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
            [$nik, 'lifecycle', $notif_msg[$ev_type]]);
    }

    $msg = "Event '$ev_type' berhasil ditambahkan."; $msg_type = 'success';

    // Redirect kembali kalau dari detail page
    if(isset($_POST['redirect_to'])) {
        header("Location: " . sanitizeInput($_POST['redirect_to']));
        exit();
    }
}

// ── DELETE EVENT ─────────────────────────────────────────────
if(isset($_POST['delete_event']) && !$is_guest) {
    if(!verifyCSRFToken()) die('Invalid Token');
    $ev_id = sanitizeInt($_POST['event_id']);
    safeQuery($pdo, "DELETE FROM ess_lifecycle WHERE id=?", [$ev_id]);
    $msg = "Event dihapus."; $msg_type = 'warning';
}

// Filter
$filter_emp  = sanitizeInput($_GET['emp'] ?? '');
$filter_type = sanitizeInput($_GET['type'] ?? '');

$sql = "SELECT l.*, u.fullname, u.division FROM ess_lifecycle l JOIN ess_users u ON l.employee_id=u.employee_id WHERE 1=1";
$params = [];
if($filter_emp)  { $sql .= " AND (u.fullname LIKE ? OR l.employee_id LIKE ?)"; $params = array_merge($params, ["%$filter_emp%","%$filter_emp%"]); }
if($filter_type) { $sql .= " AND l.event_type=?"; $params[] = $filter_type; }
$sql .= " ORDER BY l.event_date DESC, l.id DESC LIMIT 100";
$events = safeGetAll($pdo, $sql, $params);

// Summary
$summary = safeGetAll($pdo, "SELECT event_type, COUNT(*) as c FROM ess_lifecycle GROUP BY event_type ORDER BY c DESC");

$page_title  = 'Employee Lifecycle';
$active_menu = 'lifecycle';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-topbar">
        <div>
            <h5>Employee Lifecycle</h5>
            <div class="text-muted" style="font-size:0.75rem;">Riwayat perjalanan karir seluruh karyawan</div>
        </div>
    </div>
    <div class="page-body">

        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
            <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <!-- SUMMARY CHIPS -->
        <div class="d-flex gap-2 flex-wrap mb-3">
            <?php foreach($summary as $s):
                $ec = ['Hired'=>'badge-success','Confirmed'=>'badge-primary','Promoted'=>'badge-info','Resigned'=>'badge-danger','Terminated'=>'badge-danger'][$s['event_type']] ?? 'badge-muted';
            ?>
            <span class="badge-soft <?= $ec ?>" style="font-size:0.75rem; padding:5px 10px;">
                <?= $s['event_type'] ?> <strong>(<?= $s['c'] ?>)</strong>
            </span>
            <?php endforeach; ?>
        </div>

        <!-- FILTER -->
        <div class="data-card mb-3">
            <div class="p-3">
                <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                    <div><label class="form-label mb-1">Cari Karyawan</label>
                        <input type="text" name="emp" class="form-control" placeholder="Nama / NIK" value="<?= htmlspecialchars($filter_emp) ?>" style="min-width:180px;"></div>
                    <div><label class="form-label mb-1">Jenis Event</label>
                        <select name="type" class="form-select">
                            <option value="">Semua</option>
                            <?php foreach(['Hired','Probation Start','Probation End','Confirmed','Promoted','Transferred','Contract Renewed','Contract Expired','Resigned','Terminated','Reactivated'] as $t): ?>
                            <option value="<?=$t?>" <?= $filter_type==$t?'selected':'' ?>><?=$t?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <button type="submit" class="btn-primary-custom">Filter</button>
                    <?php if($filter_emp || $filter_type): ?><a href="menu_lifecycle.php" class="btn btn-light border rounded-3 py-2 px-3" style="font-size:0.82rem;">Reset</a><?php endif; ?>
                </form>
            </div>
        </div>

        <!-- TABLE -->
        <div class="data-card">
            <table class="table">
                <thead><tr><th>Karyawan</th><th>Event</th><th>Tanggal</th><th>Perubahan</th><th>Catatan</th><th>Oleh</th><?php if(!$is_guest): ?><th>Aksi</th><?php endif; ?></tr></thead>
                <tbody>
                <?php if(empty($events)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data lifecycle.</td></tr>
                <?php else: foreach($events as $ev):
                    $ec = ['Hired'=>'badge-success','Confirmed'=>'badge-primary','Promoted'=>'badge-info','Transferred'=>'badge-warning','Resigned'=>'badge-danger','Terminated'=>'badge-danger'][$ev['event_type']] ?? 'badge-muted';
                ?>
                <tr>
                    <td>
                        <a href="menu_employee_detail.php?id=<?= safeGetOne($pdo,'SELECT id FROM ess_users WHERE employee_id=?',[$ev['employee_id']])['id'] ?? '' ?>" class="text-decoration-none">
                            <div class="fw-bold" style="font-size:0.875rem;"><?= htmlspecialchars($ev['fullname']) ?></div>
                            <div style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($ev['employee_id']) ?></div>
                        </a>
                    </td>
                    <td><span class="badge-soft <?= $ec ?>"><?= $ev['event_type'] ?></span></td>
                    <td style="font-size:0.82rem;"><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                    <td style="font-size:0.78rem;">
                        <?php if($ev['old_position'] || $ev['new_position']): ?>
                        <div><i class="fa fa-briefcase me-1 text-muted"></i><?= htmlspecialchars($ev['old_position'] ?? '—') ?> → <strong><?= htmlspecialchars($ev['new_position'] ?? '—') ?></strong></div>
                        <?php endif; ?>
                        <?php if($ev['old_salary'] || $ev['new_salary']): ?>
                        <div><i class="fa fa-money-bill me-1 text-muted"></i>Rp <?= number_format($ev['old_salary'],0,',','.') ?> → <strong class="text-success">Rp <?= number_format($ev['new_salary'],0,',','.') ?></strong></div>
                        <?php endif; ?>
                        <?php if(!$ev['old_position'] && !$ev['new_position'] && !$ev['old_salary'] && !$ev['new_salary']): ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted" style="font-size:0.78rem; max-width:180px;"><?= $ev['notes'] ? htmlspecialchars(substr($ev['notes'],0,50)) : '—' ?></td>
                    <td style="font-size:0.78rem; color:#94a3b8;"><?= htmlspecialchars($ev['created_by'] ?? 'Sistem') ?></td>
                    <?php if(!$is_guest): ?>
                    <td>
                        <form method="POST" onsubmit="return confirm('Hapus event ini?')">
                            <?= csrfTokenField() ?>
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <button type="submit" name="delete_event" class="btn btn-sm btn-light border"><i class="fa fa-trash text-danger"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
