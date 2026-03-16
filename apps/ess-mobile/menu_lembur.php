<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik  = $_SESSION['ess_user'];
$nama = $_SESSION['ess_name'];
$div  = $_SESSION['ess_div'] ?? 'General';
$role = $_SESSION['ess_role'];
$msg  = ''; $msg_type = '';

// SUBMIT PENGAJUAN LEMBUR
if (isset($_POST['submit_lembur'])) {
    if (!verifyCSRFToken()) die("Security Alert: Invalid Token.");

    $tgl    = sanitizeInput($_POST['overtime_date']);
    $mulai  = sanitizeInput($_POST['start_time']);
    $selesai= sanitizeInput($_POST['end_time']);
    $alasan = sanitizeInput($_POST['reason']);

    if ($mulai >= $selesai) {
        $msg = "Jam selesai harus lebih dari jam mulai!"; $msg_type = "danger";
    } else {
        // Hitung durasi
        $dur = (strtotime($selesai) - strtotime($mulai)) / 3600;
        $sql = "INSERT INTO ess_overtime (employee_id,fullname,division,overtime_date,start_time,end_time,duration_hours,reason,status,created_at)
                VALUES (?,?,?,?,?,?,?,'Pending',NOW())";
        // Ganti tanda koma: reason dulu
        $sql = "INSERT INTO ess_overtime (employee_id,fullname,division,overtime_date,start_time,end_time,duration_hours,reason,status,created_at) VALUES (?,?,?,?,?,?,?,?,'Pending',NOW())";
        safeQuery($pdo, $sql, [$nik,$nama,$div,$tgl,$mulai,$selesai,round($dur,2),$alasan]);

        // Notif
        safeQuery($pdo, "INSERT INTO ess_notifications (employee_id,type,message,created_at) VALUES (?,?,?,NOW())",
            [$nik,'lembur','Pengajuan lembur '.date('d M',strtotime($tgl)).' ('.round($dur,1).' jam) berhasil dikirim, menunggu approval.']);
        $msg = "Pengajuan lembur berhasil dikirim!"; $msg_type = "success";
    }
}

// Riwayat lembur user
$list = safeGetAll($pdo, "SELECT * FROM ess_overtime WHERE employee_id=? ORDER BY id DESC LIMIT 20", [$nik]);

// Total jam lembur bulan ini (approved)
$total_jam = safeGetOne($pdo,
    "SELECT COALESCE(SUM(duration_hours),0) as total FROM ess_overtime WHERE employee_id=? AND status='Approved' AND DATE_FORMAT(overtime_date,'%Y-%m')=?",
    [$nik, date('Y-m')]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengajuan Lembur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:420px; margin:0 auto; min-height:100vh; background:#fff; display:flex; flex-direction:column; }
        .page-header {
            background: linear-gradient(135deg, #db2777, #9d174d);
            padding: 20px 20px 40px; color:#fff; position:relative;
        }
        .page-header::after {
            content:''; position:absolute; bottom:0; left:0; right:0; height:24px;
            background:#fff; border-radius:24px 24px 0 0;
        }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; transition:0.2s; }
        .back-btn:hover { background:rgba(255,255,255,0.3); color:#fff; }
        .body-area { padding: 0 16px 20px; flex-grow:1; }

        .kpi-row { display:flex; gap:10px; margin-bottom:20px; }
        .kpi-box { flex:1; background:#fff4f7; border:1px solid #fce7f3; border-radius:14px; padding:12px; text-align:center; }
        .kpi-num { font-size:1.4rem; font-weight:800; color:#db2777; }
        .kpi-label { font-size:0.65rem; color:#9d174d; text-transform:uppercase; font-weight:600; }

        .form-card { background:#fff; border:1px solid #f3f4f6; border-radius:16px; padding:16px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
        .form-label { font-size:0.78rem; font-weight:700; color:#374151; margin-bottom:6px; }
        .form-control, .form-select { border-radius:10px; font-size:0.875rem; border-color:#e5e7eb; padding:10px 12px; }
        .form-control:focus, .form-select:focus { border-color:#db2777; box-shadow:0 0 0 3px rgba(219,39,119,0.1); }
        .btn-submit { background:linear-gradient(135deg,#db2777,#9d174d); color:#fff; border:none; border-radius:12px; padding:13px; font-weight:700; width:100%; font-size:0.9rem; }

        .hist-item { background:#fff; border:1px solid #f3f4f6; border-radius:14px; padding:14px; margin-bottom:10px; position:relative; overflow:hidden; }
        .hist-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; }
        .hist-item.Pending::before { background:#f59e0b; }
        .hist-item.Approved::before { background:#10b981; }
        .hist-item.Rejected::before { background:#ef4444; }
        .badge-status { font-size:0.68rem; font-weight:700; padding:3px 8px; border-radius:6px; }
        .badge-Pending { background:#fef3c7; color:#92400e; }
        .badge-Approved { background:#d1fae5; color:#065f46; }
        .badge-Rejected { background:#fee2e2; color:#991b1b; }
        .section-head { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; color:#94a3b8; margin:20px 0 10px; }
    </style>
</head>
<body>
<div class="shell">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 mb-3">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Menu Karyawan</div>
                <h5 class="fw-bold mb-0">Pengajuan Lembur</h5>
            </div>
        </div>
    </div>

    <div class="body-area">
        <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 px-3 small fw-bold mt-2 mb-3">
            <i class="fa fa-<?= $msg_type=='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= $msg ?>
        </div>
        <?php endif; ?>

        <div class="kpi-row">
            <div class="kpi-box">
                <div class="kpi-num"><?= number_format((float)$total_jam['total'], 1) ?></div>
                <div class="kpi-label">Jam Lembur Bulan Ini</div>
            </div>
            <?php
            $count_pending = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_overtime WHERE employee_id=? AND status='Pending'", [$nik]);
            $count_approved = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_overtime WHERE employee_id=? AND status='Approved'", [$nik]);
            ?>
            <div class="kpi-box">
                <div class="kpi-num" style="color:#f59e0b;"><?= $count_pending['c'] ?? 0 ?></div>
                <div class="kpi-label">Menunggu</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-num" style="color:#10b981;"><?= $count_approved['c'] ?? 0 ?></div>
                <div class="kpi-label">Disetujui</div>
            </div>
        </div>

        <!-- FORM -->
        <div class="section-head">Ajukan Lembur Baru</div>
        <div class="form-card">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="mb-3">
                    <label class="form-label">Tanggal Lembur</label>
                    <input type="date" name="overtime_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="start_time" class="form-control" required value="17:00">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="end_time" class="form-control" required value="20:00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan & Rencana Kerja</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Finalisasi laporan Q4 untuk meeting besok pagi..." required></textarea>
                </div>
                <button type="submit" name="submit_lembur" class="btn-submit">
                    <i class="fa fa-paper-plane me-2"></i> Kirim Pengajuan
                </button>
            </form>
        </div>

        <!-- RIWAYAT -->
        <div class="section-head">Riwayat Pengajuan</div>
        <?php if(empty($list)): ?>
        <div class="text-center py-4 text-muted small">
            <i class="fa fa-clock fa-2x mb-2 d-block opacity-25"></i>
            Belum ada riwayat lembur.
        </div>
        <?php else: foreach($list as $r):
            $dur_display = number_format((float)$r['duration_hours'], 1) . ' jam';
        ?>
        <div class="hist-item <?= $r['status'] ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-bold" style="font-size:0.9rem;"><?= date('d M Y', strtotime($r['overtime_date'])) ?></div>
                    <div style="font-size:0.78rem; color:#6b7280; margin-top:2px;">
                        <i class="fa fa-clock me-1"></i><?= substr($r['start_time'],0,5) ?> &ndash; <?= substr($r['end_time'],0,5) ?>
                        &bull; <strong><?= $dur_display ?></strong>
                    </div>
                </div>
                <span class="badge-status badge-<?= $r['status'] ?>"><?= $r['status'] ?></span>
            </div>
            <div class="mt-2 text-muted" style="font-size:0.78rem; font-style:italic;">
                "<?= htmlspecialchars($r['reason']) ?>"
            </div>
            <?php if($r['approved_by']): ?>
            <div class="mt-2 pt-2 border-top" style="font-size:0.72rem; color:#94a3b8;">
                <i class="fa fa-user-tie me-1"></i> Diproses oleh: <strong><?= htmlspecialchars($r['approved_by']) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>

        <div style="height:20px;"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
