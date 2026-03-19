<?php
session_name('ESS_PORTAL_SESSION');
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$role = $_SESSION['ess_role'] ?? '';
$uid  = $_SESSION['ess_user'] ?? '';
$nama_approver = $_SESSION['ess_name'] ?? '';

if (($role !== 'Manager' && $role !== 'Supervisor') || empty($uid)) { ?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Akses Dibatasi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head><body style="background:#f1f5f9;font-family:'DM Sans',sans-serif;" class="d-flex align-items-center justify-content-center vh-100">
<div class="text-center px-4">
    <div class="fs-1 mb-3">🔒</div>
    <h4 class="fw-bold">Akses Dibatasi</h4>
    <p class="text-muted">Menu ini hanya untuk <strong>Manager & Supervisor</strong>.</p>
    <a href="index.php" class="btn btn-dark rounded-pill px-5 fw-bold">Kembali</a>
</div></body></html>
<?php exit(); }

function hitungHariKerja($start, $end, $pdo) {
    $s = new DateTime($start); $e = new DateTime($end); $e->modify('+1 day');
    $period = new DatePeriod($s, DateInterval::createFromDateString('1 day'), $e);
    $holidays = $pdo->query("SELECT holiday_date FROM ess_holidays")->fetchAll(PDO::FETCH_COLUMN);
    $days = 0;
    foreach ($period as $dt) { $d = $dt->format('Y-m-d'); if ($dt->format('N') < 6 && !in_array($d, $holidays)) $days++; }
    return $days;
}

// PROCESS APPROVAL CUTI
if (isset($_POST['process_cuti'])) {
    if (!verifyCSRFToken()) die("Security Error.");
    $id_cuti  = sanitizeInt($_POST['id_cuti']);
    $action   = sanitizeInput($_POST['action']);
    if (!in_array($action, ['Approved', 'Rejected'])) { header("Location: menu_approval.php"); exit(); }
    $cuti = safeGetOne($pdo, "SELECT * FROM ess_leaves WHERE id=?", [$id_cuti]);
    if ($cuti) {
        if ($action == 'Approved' && $cuti['leave_type'] == 'Cuti Tahunan') {
            $durasi = hitungHariKerja($cuti['start_date'], $cuti['end_date'], $pdo);
            $user_q = safeGetOne($pdo, "SELECT annual_leave_quota FROM ess_users WHERE employee_id=?", [$cuti['employee_id']]);
            if ((int)$user_q['annual_leave_quota'] >= $durasi) {
                safeQuery($pdo, "UPDATE ess_users SET annual_leave_quota=annual_leave_quota-? WHERE employee_id=?", [$durasi, $cuti['employee_id']]);
                safeQuery($pdo, "UPDATE ess_leaves SET status=?,approved_by=? WHERE id=?", [$action, $nama_approver, $id_cuti]);
                safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
                    [$cuti['employee_id'], 'leave_approved', "Pengajuan cuti Anda ($durasi hari) telah disetujui oleh $nama_approver."]);
            } else {
                echo "<script>alert('GAGAL: Sisa cuti karyawan tidak cukup!'); window.location='menu_approval.php';</script>"; exit();
            }
        } else {
            safeQuery($pdo, "UPDATE ess_leaves SET status=?,approved_by=? WHERE id=?", [$action, $nama_approver, $id_cuti]);
            $notif_type = $action == 'Approved' ? 'leave_approved' : 'leave_rejected';
            $notif_msg  = $action == 'Approved'
                ? "Pengajuan cuti/izin Anda telah disetujui oleh $nama_approver."
                : "Pengajuan cuti/izin Anda ditolak oleh $nama_approver.";
            safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
                [$cuti['employee_id'], $notif_type, $notif_msg]);
        }
        header("Location: menu_approval.php?tab=cuti&msg=$action"); exit();
    }
}

// PROCESS APPROVAL LEMBUR
if (isset($_POST['process_lembur'])) {
    if (!verifyCSRFToken()) die("Security Error.");
    $id_ot  = sanitizeInt($_POST['id_lembur']);
    $action = sanitizeInput($_POST['action']);
    if (!in_array($action, ['Approved', 'Rejected'])) { header("Location: menu_approval.php"); exit(); }
    $ot = safeGetOne($pdo, "SELECT * FROM ess_overtime WHERE id=?", [$id_ot]);
    if ($ot) {
        safeQuery($pdo, "UPDATE ess_overtime SET status=?,approved_by=?,approved_at=NOW() WHERE id=?", [$action, $nama_approver, $id_ot]);
        $notif_type = $action == 'Approved' ? 'lembur_approved' : 'lembur_rejected';
        $notif_msg  = $action == 'Approved'
            ? "Pengajuan lembur Anda (" . number_format((float)$ot['duration_hours'],1) . " jam) disetujui oleh $nama_approver."
            : "Pengajuan lembur Anda ditolak oleh $nama_approver.";
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
            [$ot['employee_id'], $notif_type, $notif_msg]);
        header("Location: menu_approval.php?tab=lembur&msg=$action"); exit();
    }
}

$active_tab = sanitizeInput($_GET['tab'] ?? 'cuti');
$msg_action = sanitizeInput($_GET['msg'] ?? '');

$cuti_list  = safeGetAll($pdo, "SELECT * FROM ess_leaves WHERE status='Pending' ORDER BY id DESC");
$ot_list    = safeGetAll($pdo, "SELECT * FROM ess_overtime WHERE status='Pending' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Approval</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:420px; margin:0 auto; min-height:100vh; background:#f8fafc; }
        .page-header {
            background:linear-gradient(135deg,#f59e0b,#d97706);
            padding:20px 20px 44px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .body-area { padding:0 16px 32px; }

        .tab-row { display:flex; gap:8px; margin-bottom:16px; }
        .tab-btn { flex:1; padding:10px; border-radius:12px; border:2px solid #e5e7eb; background:#fff; font-size:0.82rem; font-weight:600; color:#6b7280; cursor:pointer; text-decoration:none; text-align:center; transition:0.2s; }
        .tab-btn.active { border-color:#f59e0b; background:#fffbeb; color:#92400e; }
        .tab-btn .badge-tab { background:#ef4444; color:#fff; font-size:0.6rem; padding:2px 5px; border-radius:6px; margin-left:4px; }

        .req-card { background:#fff; border-radius:16px; border:1px solid #f3f4f6; padding:14px; margin-bottom:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
        .req-name { font-size:0.95rem; font-weight:700; }
        .req-meta { font-size:0.75rem; color:#6b7280; }
        .req-reason { background:#f8fafc; border-radius:8px; padding:8px 10px; font-size:0.78rem; color:#374151; font-style:italic; margin:8px 0; }
        .req-detail { font-size:0.78rem; color:#0f172a; font-weight:600; }
        .btn-approve { background:#10b981; color:#fff; border:none; border-radius:10px; padding:8px 16px; font-size:0.82rem; font-weight:700; flex:1; }
        .btn-reject  { background:#fff; color:#ef4444; border:2px solid #ef4444; border-radius:10px; padding:8px 16px; font-size:0.82rem; font-weight:700; flex:1; }
        .empty-state { text-align:center; padding:40px 20px; color:#94a3b8; font-size:0.85rem; }

        .toast-msg { position:fixed; top:16px; left:50%; transform:translateX(-50%); background:#0f172a; color:#fff; padding:10px 20px; border-radius:12px; font-size:0.82rem; font-weight:600; z-index:9999; animation: fadeout 0.5s 2.5s forwards; }
        @keyframes fadeout { to { opacity:0; visibility:hidden; } }
    </style>
</head>
<body>
<div class="shell">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 mb-1">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Menu Atasan</div>
                <h5 class="fw-bold mb-0">Approval &mdash; <?= htmlspecialchars($role) ?></h5>
            </div>
        </div>
    </div>

    <?php if($msg_action): ?>
    <div class="toast-msg">
        <i class="fa fa-<?= $msg_action=='Approved'?'check-circle text-success':'times-circle text-danger' ?> me-2"></i>
        <?= $msg_action == 'Approved' ? 'Pengajuan disetujui' : 'Pengajuan ditolak' ?>
    </div>
    <?php endif; ?>

    <div class="body-area">
        <div class="tab-row mt-2">
            <a href="?tab=cuti" class="tab-btn <?= $active_tab=='cuti'?'active':'' ?>">
                <i class="fa fa-calendar-minus me-1"></i> Cuti/Izin
                <?php if(count($cuti_list)>0): ?><span class="badge-tab"><?= count($cuti_list) ?></span><?php endif; ?>
            </a>
            <a href="?tab=lembur" class="tab-btn <?= $active_tab=='lembur'?'active':'' ?>">
                <i class="fa fa-clock me-1"></i> Lembur
                <?php if(count($ot_list)>0): ?><span class="badge-tab"><?= count($ot_list) ?></span><?php endif; ?>
            </a>
        </div>

        <?php if($active_tab == 'cuti'): ?>
            <?php if(empty($cuti_list)): ?>
            <div class="empty-state"><i class="fa fa-check-circle fa-3x mb-3 d-block" style="color:#d1d5db;"></i>Semua beres! Tidak ada pengajuan cuti.</div>
            <?php else: foreach($cuti_list as $row):
                $est_hari = hitungHariKerja($row['start_date'], $row['end_date'], $pdo);
            ?>
            <div class="req-card">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="req-name"><?= htmlspecialchars($row['fullname']) ?></div>
                    <span style="background:#fef3c7;color:#92400e;font-size:0.68rem;font-weight:700;padding:3px 8px;border-radius:6px;">Pending</span>
                </div>
                <div class="req-meta"><?= htmlspecialchars($row['division']) ?> &bull; <?= htmlspecialchars($row['leave_type']) ?></div>
                <div class="req-reason">"<?= htmlspecialchars($row['reason']) ?>"</div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="req-detail"><i class="fa fa-calendar me-1 text-primary"></i><?= date('d M',strtotime($row['start_date'])) ?> &ndash; <?= date('d M Y',strtotime($row['end_date'])) ?></div>
                    <?php if($row['leave_type']=='Cuti Tahunan'): ?>
                    <span style="background:#dbeafe;color:#1d4ed8;font-size:0.7rem;font-weight:700;padding:3px 8px;border-radius:6px;"><?= $est_hari ?> hari kerja</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" style="flex:1;">
                        <?= csrfTokenField() ?>
                        <input type="hidden" name="id_cuti" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="Rejected">
                        <button type="submit" name="process_cuti" class="btn-reject w-100">✕ Tolak</button>
                    </form>
                    <form method="POST" style="flex:1;">
                        <?= csrfTokenField() ?>
                        <input type="hidden" name="id_cuti" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="Approved">
                        <button type="submit" name="process_cuti" class="btn-approve w-100">✓ Setujui</button>
                    </form>
                </div>
            </div>
            <?php endforeach; endif;

        else: // TAB LEMBUR ?>
            <?php if(empty($ot_list)): ?>
            <div class="empty-state"><i class="fa fa-check-circle fa-3x mb-3 d-block" style="color:#d1d5db;"></i>Tidak ada pengajuan lembur.</div>
            <?php else: foreach($ot_list as $row): ?>
            <div class="req-card">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="req-name"><?= htmlspecialchars($row['fullname']) ?></div>
                    <span style="background:#fce7f3;color:#9d174d;font-size:0.68rem;font-weight:700;padding:3px 8px;border-radius:6px;"><?= number_format((float)$row['duration_hours'],1) ?> jam</span>
                </div>
                <div class="req-meta"><?= htmlspecialchars($row['division']) ?> &bull; <?= date('d M Y',strtotime($row['overtime_date'])) ?></div>
                <div class="req-meta mb-1"><i class="fa fa-clock me-1"></i><?= substr($row['start_time'],0,5) ?> &ndash; <?= substr($row['end_time'],0,5) ?></div>
                <div class="req-reason">"<?= htmlspecialchars($row['reason']) ?>"</div>
                <div class="d-flex gap-2">
                    <form method="POST" style="flex:1;">
                        <?= csrfTokenField() ?>
                        <input type="hidden" name="id_lembur" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="Rejected">
                        <button type="submit" name="process_lembur" class="btn-reject w-100">✕ Tolak</button>
                    </form>
                    <form method="POST" style="flex:1;">
                        <?= csrfTokenField() ?>
                        <input type="hidden" name="id_lembur" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="Approved">
                        <button type="submit" name="process_lembur" class="btn-approve w-100">✓ Setujui</button>
                    </form>
                </div>
            </div>
            <?php endforeach; endif;
        endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
