<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik  = $_SESSION['ess_user'];
$nama = $_SESSION['ess_name'];
$active_tab = sanitizeInput($_GET['tab'] ?? 'absensi');

// Statistik ringkas untuk header
$stats_cuti = safeGetOne($pdo,
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) as approved,
     SUM(CASE WHEN status='Pending'  THEN 1 ELSE 0 END) as pending
     FROM ess_leaves WHERE employee_id=?", [$nik]);

$stats_lembur = safeGetOne($pdo,
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) as approved,
     COALESCE(SUM(CASE WHEN status='Approved' THEN duration_hours ELSE 0 END),0) as total_jam
     FROM ess_overtime WHERE employee_id=?", [$nik]);

$stats_absen = safeGetOne($pdo,
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN TIME(check_in_time) > '08:30:00' THEN 1 ELSE 0 END) as telat,
     SUM(CASE WHEN check_out_time IS NOT NULL THEN 1 ELSE 0 END) as selesai
     FROM ess_attendance WHERE employee_id=?", [$nik]);

// Data per tab
$list_absen  = safeGetAll($pdo, "SELECT * FROM ess_attendance WHERE employee_id=? ORDER BY date_log DESC LIMIT 30", [$nik]);
$list_cuti   = safeGetAll($pdo, "SELECT * FROM ess_leaves WHERE employee_id=? ORDER BY id DESC", [$nik]);
$list_lembur = safeGetAll($pdo, "SELECT * FROM ess_overtime WHERE employee_id=? ORDER BY id DESC", [$nik]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Riwayat Saya</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; color:#0f172a; }
        .shell { max-width:430px; margin:0 auto; min-height:100vh; background:#fff; display:flex; flex-direction:column; }

        /* Header */
        .page-header {
            background:linear-gradient(135deg,#4f46e5,#7c3aed);
            padding:20px 20px 44px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }

        /* KPI strip */
        .kpi-strip { display:flex; gap:8px; padding:0 16px 16px; background:#f8fafc; }
        .kpi-box { flex:1; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:10px; text-align:center; }
        .kpi-num { font-size:1.2rem; font-weight:800; line-height:1; }
        .kpi-lbl { font-size:0.62rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.4px; margin-top:3px; }

        /* Tab nav */
        .tab-nav { display:flex; background:#f1f5f9; padding:4px; border-radius:14px; margin:0 16px 16px; }
        .tab-btn { flex:1; padding:9px 6px; border-radius:11px; border:none; background:transparent; font-size:0.75rem; font-weight:600; color:#64748b; cursor:pointer; transition:0.15s; display:flex; align-items:center; justify-content:center; gap:5px; }
        .tab-btn.active { background:#fff; color:#4f46e5; box-shadow:0 1px 4px rgba(0,0,0,0.08); }

        /* Cards */
        .body-area { padding:0 16px 32px; background:#f8fafc; flex-grow:1; }

        .hist-card { background:#fff; border-radius:14px; border:1px solid #f1f5f9; padding:14px 14px 14px 18px; margin-bottom:10px; position:relative; overflow:hidden; }
        .hist-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px; }
        .hist-card.green::before  { background:#10b981; }
        .hist-card.yellow::before { background:#f59e0b; }
        .hist-card.red::before    { background:#ef4444; }
        .hist-card.blue::before   { background:#4f46e5; }
        .hist-card.purple::before { background:#7c3aed; }

        .card-title { font-size:0.88rem; font-weight:700; margin-bottom:2px; }
        .card-sub   { font-size:0.73rem; color:#64748b; }
        .card-meta  { font-size:0.73rem; color:#94a3b8; margin-top:6px; }

        .badge-s { font-size:0.65rem; font-weight:700; padding:3px 8px; border-radius:6px; }
        .bg-approved { background:#d1fae5; color:#065f46; }
        .bg-rejected { background:#fee2e2; color:#991b1b; }
        .bg-pending  { background:#fef3c7; color:#92400e; }
        .bg-wfo      { background:#eef2ff; color:#3730a3; }
        .bg-wfh      { background:#f0fdf4; color:#166534; }
        .bg-selesai  { background:#f1f5f9; color:#475569; }
        .bg-aktif    { background:#fef3c7; color:#92400e; }

        .empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
        .empty-state i { font-size:2.5rem; margin-bottom:10px; display:block; opacity:0.2; }
    </style>
</head>
<body>
<div class="shell">

    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Aktivitas Saya</div>
                <h5 class="fw-bold mb-0">Riwayat</h5>
            </div>
        </div>
    </div>

    <!-- KPI STRIP -->
    <div class="kpi-strip">
        <div class="kpi-box">
            <div class="kpi-num text-primary"><?= $stats_absen['total'] ?? 0 ?></div>
            <div class="kpi-lbl">Hari Hadir</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-num text-warning"><?= $stats_cuti['total'] ?? 0 ?></div>
            <div class="kpi-lbl">Pengajuan</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-num text-purple" style="color:#7c3aed;"><?= number_format((float)($stats_lembur['total_jam'] ?? 0), 1) ?></div>
            <div class="kpi-lbl">Jam Lembur</div>
        </div>
    </div>

    <!-- TAB NAV -->
    <div class="tab-nav">
        <button class="tab-btn <?= $active_tab=='absensi'?'active':'' ?>" onclick="switchTab('absensi')">
            <i class="fa fa-fingerprint"></i> Absensi
        </button>
        <button class="tab-btn <?= $active_tab=='cuti'?'active':'' ?>" onclick="switchTab('cuti')">
            <i class="fa fa-calendar-minus"></i> Cuti/Izin
        </button>
        <button class="tab-btn <?= $active_tab=='lembur'?'active':'' ?>" onclick="switchTab('lembur')">
            <i class="fa fa-clock"></i> Lembur
        </button>
    </div>

    <!-- ═══ TAB ABSENSI ═══ -->
    <div class="body-area" id="tab-absensi" style="display:<?= $active_tab=='absensi'?'block':'none' ?>">
        <?php if(empty($list_absen)): ?>
        <div class="empty-state"><i class="fa fa-fingerprint"></i>Belum ada data absensi.</div>
        <?php else: foreach($list_absen as $row):
            $masuk  = date('H:i', strtotime($row['check_in_time']));
            $pulang = $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : null;
            $telat  = strtotime($row['check_in_time']) > strtotime($row['date_log'].' 08:30:00');
            $tgl    = date('d M Y', strtotime($row['date_log']));
            $hari   = ['Sun'=>'Minggu','Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu'][date('D', strtotime($row['date_log']))];
        ?>
        <div class="hist-card <?= $pulang ? 'green' : 'yellow' ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-title"><?= $hari ?>, <?= $tgl ?></div>
                    <div class="card-sub">
                        <i class="fa fa-sign-in-alt me-1 text-success"></i><?= $masuk ?>
                        <?php if($pulang): ?>
                        &nbsp;<i class="fa fa-sign-out-alt me-1 text-danger"></i><?= $pulang ?>
                        <?php endif; ?>
                        <?php if($telat): ?> <span class="badge-s bg-rejected ms-1">Telat</span><?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                    <span class="badge-s <?= $row['type']=='WFO'?'bg-wfo':'bg-wfh' ?>"><?= $row['type'] ?></span>
                    <span class="badge-s <?= $pulang?'bg-selesai':'bg-aktif' ?>"><?= $pulang?'Selesai':'Aktif' ?></span>
                </div>
            </div>
            <?php if($row['tasks']): ?>
            <div class="card-meta fst-italic">"<?= htmlspecialchars(substr($row['tasks'], 0, 60)) ?><?= strlen($row['tasks'])>60?'...':'' ?>"</div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- ═══ TAB CUTI ═══ -->
    <div class="body-area" id="tab-cuti" style="display:<?= $active_tab=='cuti'?'block':'none' ?>">
        <?php if(empty($list_cuti)): ?>
        <div class="empty-state"><i class="fa fa-calendar-minus"></i>Belum ada pengajuan cuti.</div>
        <?php else: foreach($list_cuti as $row):
            $status = $row['status'];
            $css    = ['Approved'=>'green','Rejected'=>'red','Pending'=>'yellow'][$status] ?? 'blue';
            $badge  = ['Approved'=>'bg-approved','Rejected'=>'bg-rejected','Pending'=>'bg-pending'][$status] ?? 'bg-selesai';
            $label  = ['Approved'=>'Disetujui','Rejected'=>'Ditolak','Pending'=>'Menunggu'][$status] ?? $status;
            $start  = date('d M Y', strtotime($row['start_date']));
            $end    = date('d M Y', strtotime($row['end_date']));
            $range  = ($start == $end) ? $start : "$start – $end";
        ?>
        <div class="hist-card <?= $css ?>">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="card-title"><?= htmlspecialchars($row['leave_type']) ?></div>
                <span class="badge-s <?= $badge ?>"><?= $label ?></span>
            </div>
            <div class="card-sub"><i class="fa fa-calendar me-1"></i><?= $range ?></div>
            <div class="card-meta fst-italic">"<?= htmlspecialchars($row['reason']) ?>"</div>
            <?php if($row['approved_by']): ?>
            <div class="card-meta"><i class="fa fa-user-tie me-1"></i>Diproses: <strong><?= htmlspecialchars($row['approved_by']) ?></strong></div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- ═══ TAB LEMBUR ═══ -->
    <div class="body-area" id="tab-lembur" style="display:<?= $active_tab=='lembur'?'block':'none' ?>">
        <?php if(empty($list_lembur)): ?>
        <div class="empty-state"><i class="fa fa-clock"></i>Belum ada pengajuan lembur.</div>
        <?php else: foreach($list_lembur as $row):
            $status = $row['status'];
            $css    = ['Approved'=>'purple','Rejected'=>'red','Pending'=>'yellow'][$status] ?? 'blue';
            $badge  = ['Approved'=>'bg-approved','Rejected'=>'bg-rejected','Pending'=>'bg-pending'][$status] ?? 'bg-selesai';
            $label  = ['Approved'=>'Disetujui','Rejected'=>'Ditolak','Pending'=>'Menunggu'][$status] ?? $status;
        ?>
        <div class="hist-card <?= $css ?>">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="card-title"><?= date('d M Y', strtotime($row['overtime_date'])) ?></div>
                <span class="badge-s <?= $badge ?>"><?= $label ?></span>
            </div>
            <div class="card-sub">
                <i class="fa fa-clock me-1"></i><?= substr($row['start_time'],0,5) ?> – <?= substr($row['end_time'],0,5) ?>
                &bull; <strong><?= number_format((float)$row['duration_hours'],1) ?> jam</strong>
            </div>
            <div class="card-meta fst-italic">"<?= htmlspecialchars($row['reason']) ?>"</div>
            <?php if($row['approved_by']): ?>
            <div class="card-meta"><i class="fa fa-user-tie me-1"></i>Diproses: <strong><?= htmlspecialchars($row['approved_by']) ?></strong></div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(tab) {
    ['absensi','cuti','lembur'].forEach(t => {
        document.getElementById('tab-'+t).style.display = t===tab ? 'block' : 'none';
    });
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>
