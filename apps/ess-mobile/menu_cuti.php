<?php
session_name("ESS_PORTAL_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik  = $_SESSION['ess_user'];
$nama = $_SESSION['ess_name'];
$div  = $_SESSION['ess_div'] ?? "General";

if (isset($_POST['submit_cuti'])) {
    if (!verifyCSRFToken()) die("Security Alert: Invalid Token.");

    $tipe   = sanitizeInput($_POST['tipe']);
    $mulai  = sanitizeInput($_POST['mulai']);
    $akhir  = sanitizeInput($_POST['akhir']);
    $alasan = sanitizeInput($_POST['alasan']);

    if ($mulai > $akhir) {
        echo "<script>alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir!'); window.location='menu_cuti.php';</script>";
        exit();
    }

    $sql = "INSERT INTO ess_leaves (employee_id, fullname, division, leave_type, start_date, end_date, reason, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

    if (safeQuery($pdo, $sql, [$nik, $nama, $div, $tipe, $mulai, $akhir, $alasan])) {
        echo "<script>alert('Pengajuan Berhasil! Menunggu Approval Atasan.'); window.location='menu_cuti.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data. Silakan coba lagi.');</script>";
    }
}

// Ambil sisa cuti & riwayat terbaru
$user_data = safeGetOne($pdo, "SELECT annual_leave_quota FROM ess_users WHERE employee_id=?", [$nik]);
$sisa_cuti = $user_data['annual_leave_quota'] ?? 0;
$riwayat   = safeGetAll($pdo, "SELECT * FROM ess_leaves WHERE employee_id=? ORDER BY id DESC LIMIT 5", [$nik]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Form Pengajuan Cuti</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:430px; margin:0 auto; min-height:100vh; background:#f8fafc; }
        .page-header {
            background:linear-gradient(135deg,#f59e0b,#d97706);
            padding:20px 20px 40px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .body-area { padding:0 16px 32px; }

        .sisa-chip {
            display:inline-flex; align-items:center; gap:6px;
            background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3);
            color:#fff; font-size:0.78rem; font-weight:600; padding:4px 12px; border-radius:20px;
            margin-top:8px;
        }
        .form-card { background:#fff; border-radius:16px; border:1px solid #f3f4f6; padding:16px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
        .form-label { font-size:0.78rem; font-weight:700; color:#374151; }
        .form-control, .form-select { border-radius:10px; font-size:0.875rem; border-color:#e5e7eb; font-family:'DM Sans',sans-serif; }
        .form-control:focus, .form-select:focus { border-color:#f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,0.1); }
        .btn-submit { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border:none; border-radius:12px; padding:13px; font-weight:700; width:100%; font-size:0.9rem; }

        .section-head { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.7px; color:#94a3b8; margin:20px 0 10px; }

        .hist-item { background:#fff; border:1px solid #f3f4f6; border-radius:12px; padding:12px 12px 12px 16px; margin-bottom:8px; position:relative; overflow:hidden; }
        .hist-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; }
        .hist-item.Pending::before  { background:#f59e0b; }
        .hist-item.Approved::before { background:#10b981; }
        .hist-item.Rejected::before { background:#ef4444; }
        .badge-s { font-size:0.65rem; font-weight:700; padding:3px 8px; border-radius:6px; }
        .bg-a { background:#d1fae5; color:#065f46; }
        .bg-r { background:#fee2e2; color:#991b1b; }
        .bg-p { background:#fef3c7; color:#92400e; }
    </style>
</head>
<body>
<div class="shell">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 mb-1">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem;opacity:0.8;">Menu Karyawan</div>
                <h5 class="fw-bold mb-0">Pengajuan Cuti / Izin</h5>
            </div>
        </div>
        <span class="sisa-chip"><i class="fa fa-calendar-check"></i> Sisa Cuti: <strong><?= $sisa_cuti ?> hari</strong></span>
    </div>

    <div class="body-area">
        <div class="section-head mt-3">Form Pengajuan Baru</div>
        <div class="form-card">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="mb-3">
                    <label class="form-label">Jenis Pengajuan</label>
                    <select name="tipe" class="form-select" required>
                        <option value="Cuti Tahunan">Cuti Tahunan</option>
                        <option value="Sakit">Sakit (Dengan Surat Dokter)</option>
                        <option value="Izin Khusus">Izin Khusus</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Mulai Tanggal</label>
                        <input type="date" name="mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="akhir" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan / Keterangan</label>
                    <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Urusan keluarga / Check up dokter" required></textarea>
                </div>
                <button type="submit" name="submit_cuti" class="btn-submit">
                    <i class="fa fa-paper-plane me-2"></i> Kirim Pengajuan
                </button>
            </form>
        </div>

        <!-- RIWAYAT 5 TERBARU -->
        <?php if(!empty($riwayat)): ?>
        <div class="section-head">Pengajuan Terbaru</div>
        <?php foreach($riwayat as $r):
            $status = $r['status'];
            $badge  = ['Approved'=>'bg-a','Rejected'=>'bg-r','Pending'=>'bg-p'][$status] ?? '';
            $label  = ['Approved'=>'Disetujui','Rejected'=>'Ditolak','Pending'=>'Menunggu'][$status] ?? $status;
            $start  = date('d M Y', strtotime($r['start_date']));
            $end    = date('d M Y', strtotime($r['end_date']));
            $range  = ($start == $end) ? $start : "$start – $end";
        ?>
        <div class="hist-item <?= $status ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:0.85rem; font-weight:700;"><?= htmlspecialchars($r['leave_type']) ?></div>
                    <div style="font-size:0.75rem; color:#64748b; margin-top:2px;"><i class="fa fa-calendar me-1"></i><?= $range ?></div>
                </div>
                <span class="badge-s <?= $badge ?>"><?= $label ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <a href="menu_history.php?tab=cuti" style="font-size:0.78rem; color:#f59e0b; font-weight:600; text-decoration:none; display:block; text-align:center; padding:8px;">
            Lihat semua riwayat <i class="fa fa-arrow-right ms-1"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
