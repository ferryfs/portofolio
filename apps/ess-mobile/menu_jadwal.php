<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik  = $_SESSION['ess_user'];
$nama = $_SESSION['ess_name'];

// Bulan & tahun yang ditampilkan
$sel_month = (int)($_GET['m'] ?? date('n'));
$sel_year  = (int)($_GET['y'] ?? date('Y'));

// Navigasi prev/next
$prev = mktime(0,0,0, $sel_month-1, 1, $sel_year);
$next = mktime(0,0,0, $sel_month+1, 1, $sel_year);
$prev_m = date('n', $prev); $prev_y = date('Y', $prev);
$next_m = date('n', $next); $next_y = date('Y', $next);

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];
$nama_hari  = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

// Ambil semua hari libur nasional bulan ini
$holidays = safeGetAll($pdo,
    "SELECT holiday_date, description FROM ess_holidays
     WHERE DATE_FORMAT(holiday_date,'%Y-%m') = ?",
    [sprintf('%04d-%02d', $sel_year, $sel_month)]);
$holiday_map = [];
foreach ($holidays as $h) $holiday_map[$h['holiday_date']] = $h['description'];

// Ambil semua absensi user bulan ini
$absensi = safeGetAll($pdo,
    "SELECT date_log, type, check_in_time, check_out_time
     FROM ess_attendance WHERE employee_id=? AND DATE_FORMAT(date_log,'%Y-%m')=?",
    [$nik, sprintf('%04d-%02d', $sel_year, $sel_month)]);
$absen_map = [];
foreach ($absensi as $a) $absen_map[$a['date_log']] = $a;

// Ambil cuti approved bulan ini
$cuti_list = safeGetAll($pdo,
    "SELECT start_date, end_date, leave_type FROM ess_leaves
     WHERE employee_id=? AND status='Approved'
     AND (DATE_FORMAT(start_date,'%Y-%m')=? OR DATE_FORMAT(end_date,'%Y-%m')=?)",
    [$nik, sprintf('%04d-%02d',$sel_year,$sel_month), sprintf('%04d-%02d',$sel_year,$sel_month)]);
$cuti_dates = [];
foreach ($cuti_list as $c) {
    $s = new DateTime($c['start_date']);
    $e = new DateTime($c['end_date']); $e->modify('+1 day');
    for ($d = clone $s; $d < $e; $d->modify('+1 day'))
        $cuti_dates[$d->format('Y-m-d')] = $c['leave_type'];
}

// Statistik bulan ini
$hadir  = count($absen_map);
$lembur = safeGetOne($pdo,
    "SELECT COUNT(*) as c FROM ess_overtime WHERE employee_id=? AND status='Approved' AND DATE_FORMAT(overtime_date,'%Y-%m')=?",
    [$nik, sprintf('%04d-%02d',$sel_year,$sel_month)])['c'] ?? 0;
$cuti_count = count($cuti_dates);

// Hitung hari pertama & jumlah hari dalam bulan
$first_dow  = (int)date('w', mktime(0,0,0,$sel_month,1,$sel_year)); // 0=Sun
$days_total = (int)date('t', mktime(0,0,0,$sel_month,1,$sel_year));
$today      = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Jadwal</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand:#4f46e5; --success:#10b981; --warning:#f59e0b;
            --danger:#ef4444; --purple:#7c3aed;
        }
        * { box-sizing:border-box; }
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:430px; margin:0 auto; min-height:100vh; background:#fff; display:flex; flex-direction:column; }

        /* HEADER */
        .page-header {
            background:linear-gradient(135deg,#4f46e5,#7c3aed);
            padding:20px 20px 44px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }

        /* BODY */
        .body-area { padding:0 16px 32px; background:#f8fafc; flex-grow:1; }

        /* KPI */
        .kpi-strip { display:flex; gap:8px; margin-bottom:16px; }
        .kpi-box { flex:1; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:10px; text-align:center; }
        .kpi-num { font-size:1.2rem; font-weight:800; line-height:1; }
        .kpi-lbl { font-size:0.62rem; color:#64748b; text-transform:uppercase; font-weight:700; margin-top:3px; }

        /* CALENDAR CARD */
        .cal-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; margin-bottom:16px; }
        .cal-nav {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 16px; border-bottom:1px solid #f1f5f9;
        }
        .cal-nav .month-label { font-size:1rem; font-weight:800; }
        .cal-nav a { color:#4f46e5; text-decoration:none; width:32px; height:32px; border-radius:8px; background:#eef2ff; display:flex; align-items:center; justify-content:center; font-size:0.85rem; }
        .cal-nav a:hover { background:#e0e7ff; }

        /* Day headers */
        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); }
        .cal-day-head { text-align:center; font-size:0.62rem; font-weight:700; text-transform:uppercase; color:#94a3b8; padding:8px 0; }
        .cal-day-head:first-child { color:#ef4444; }
        .cal-day-head:last-child  { color:#6366f1; }

        /* Day cells */
        .cal-cell {
            aspect-ratio:1; display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            cursor:pointer; position:relative;
            border-radius:10px; margin:2px; transition:0.15s;
            font-size:0.82rem; font-weight:600;
        }
        .cal-cell:hover { background:#f8fafc; }
        .cal-cell.empty { cursor:default; }
        .cal-cell.today { background:var(--brand) !important; color:#fff !important; }
        .cal-cell.today .dot-row span { background:rgba(255,255,255,0.6) !important; }
        .cal-cell.weekend { color:#ef4444; }
        .cal-cell.holiday { color:#ef4444; background:#fff5f5; }
        .cal-cell.absen   { background:#f0fdf4; color:#065f46; }
        .cal-cell.cuti    { background:#fef3c7; color:#92400e; }
        .cal-cell.lembur  { background:#eef2ff; color:#3730a3; }

        /* Dot indicators */
        .dot-row { display:flex; gap:2px; justify-content:center; margin-top:2px; height:5px; }
        .dot-row span { width:4px; height:4px; border-radius:50%; }
        .d-green  { background:#10b981; }
        .d-yellow { background:#f59e0b; }
        .d-blue   { background:#4f46e5; }
        .d-red    { background:#ef4444; }

        /* LEGEND */
        .legend { display:flex; flex-wrap:wrap; gap:8px; padding:12px 16px; border-top:1px solid #f1f5f9; }
        .leg-item { display:flex; align-items:center; gap:5px; font-size:0.68rem; color:#64748b; }
        .leg-dot  { width:10px; height:10px; border-radius:3px; flex-shrink:0; }

        /* EVENT LIST */
        .event-card { background:#fff; border-radius:12px; border:1px solid #f1f5f9; padding:12px 14px; margin-bottom:8px; display:flex; align-items:center; gap:10px; }
        .event-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:0.9rem; flex-shrink:0; }
        .event-date { font-size:0.72rem; color:#94a3b8; }
        .event-name { font-size:0.85rem; font-weight:600; }
        .section-head { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.7px; color:#94a3b8; margin:16px 0 8px; }

        /* TOOLTIP popup */
        .day-popup {
            display:none; position:fixed; z-index:999;
            background:#0f172a; color:#fff;
            padding:8px 12px; border-radius:10px;
            font-size:0.75rem; font-weight:600;
            max-width:200px; text-align:center;
            pointer-events:none;
        }
    </style>
</head>
<body>
<div class="shell">

    <!-- HEADER -->
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Kalender Kerja</div>
                <h5 class="fw-bold mb-0">Jadwal Saya</h5>
            </div>
        </div>
    </div>

    <div class="body-area">
        <!-- KPI -->
        <div class="kpi-strip mt-3">
            <div class="kpi-box">
                <div class="kpi-num text-success"><?= $hadir ?></div>
                <div class="kpi-lbl">Hadir</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-num text-warning"><?= $cuti_count ?></div>
                <div class="kpi-lbl">Cuti</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-num" style="color:#7c3aed;"><?= $lembur ?></div>
                <div class="kpi-lbl">Lembur</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-num text-danger"><?= count($holiday_map) ?></div>
                <div class="kpi-lbl">Libur</div>
            </div>
        </div>

        <!-- CALENDAR -->
        <div class="cal-card">
            <div class="cal-nav">
                <a href="?m=<?= $prev_m ?>&y=<?= $prev_y ?>"><i class="fa fa-chevron-left"></i></a>
                <div class="month-label"><?= $nama_bulan[$sel_month] ?> <?= $sel_year ?></div>
                <a href="?m=<?= $next_m ?>&y=<?= $next_y ?>"><i class="fa fa-chevron-right"></i></a>
            </div>

            <div class="cal-grid" style="padding:8px;">
                <?php // Day headers
                foreach($nama_hari as $h): ?>
                <div class="cal-day-head"><?= $h ?></div>
                <?php endforeach; ?>

                <?php // Empty cells sebelum hari pertama
                for($i=0; $i<$first_dow; $i++): ?>
                <div class="cal-cell empty"></div>
                <?php endfor;

                // Isi hari
                for($d=1; $d<=$days_total; $d++):
                    $date_str = sprintf('%04d-%02d-%02d', $sel_year, $sel_month, $d);
                    $dow      = (int)date('w', mktime(0,0,0,$sel_month,$d,$sel_year));
                    $is_today = ($date_str == $today);
                    $is_wknd  = ($dow==0 || $dow==6);
                    $is_holi  = isset($holiday_map[$date_str]);
                    $has_absen= isset($absen_map[$date_str]);
                    $has_cuti = isset($cuti_dates[$date_str]);

                    // Tentukan class sel
                    $cell_class = '';
                    if    ($is_today)  $cell_class = 'today';
                    elseif($has_cuti)  $cell_class = 'cuti';
                    elseif($has_absen) $cell_class = 'absen';
                    elseif($is_holi)   $cell_class = 'holiday';
                    elseif($is_wknd)   $cell_class = 'weekend';

                    // Tooltip data
                    $tooltip = '';
                    if($is_holi)   $tooltip = $holiday_map[$date_str];
                    elseif($has_cuti)  $tooltip = $cuti_dates[$date_str];
                    elseif($has_absen) {
                        $a = $absen_map[$date_str];
                        $tooltip = $a['type'] . ' | Masuk ' . date('H:i', strtotime($a['check_in_time']));
                        if($a['check_out_time']) $tooltip .= ' | Pulang ' . date('H:i', strtotime($a['check_out_time']));
                    }
                ?>
                <div class="cal-cell <?= $cell_class ?>"
                     onclick="showTooltip(event, '<?= $d ?> <?= $nama_bulan[$sel_month] ?>', '<?= addslashes($tooltip) ?>')">
                    <?= $d ?>
                    <div class="dot-row">
                        <?php if($has_absen && !$is_today): ?><span class="d-green"></span><?php endif; ?>
                        <?php if($has_cuti):  ?><span class="d-yellow"></span><?php endif; ?>
                        <?php if($is_holi):   ?><span class="d-red"></span><?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- LEGEND -->
            <div class="legend">
                <div class="leg-item"><div class="leg-dot" style="background:#4f46e5;"></div> Hari Ini</div>
                <div class="leg-item"><div class="leg-dot" style="background:#d1fae5; border:1px solid #10b981;"></div> Hadir</div>
                <div class="leg-item"><div class="leg-dot" style="background:#fef3c7; border:1px solid #f59e0b;"></div> Cuti</div>
                <div class="leg-item"><div class="leg-dot" style="background:#fff5f5; border:1px solid #ef4444;"></div> Libur Nasional</div>
                <div class="leg-item"><div class="leg-dot" style="background:#f1f5f9;"></div> Akhir Pekan</div>
            </div>
        </div>

        <!-- HARI LIBUR NASIONAL BULAN INI -->
        <?php if(!empty($holiday_map)): ?>
        <div class="section-head"><i class="fa fa-star me-1 text-danger"></i> Hari Libur Nasional</div>
        <?php foreach($holiday_map as $tgl => $desc): ?>
        <div class="event-card">
            <div class="event-icon" style="background:#fef2f2; color:#ef4444;"><i class="fa fa-flag"></i></div>
            <div>
                <div class="event-name"><?= htmlspecialchars($desc) ?></div>
                <div class="event-date"><i class="fa fa-calendar me-1"></i><?= date('d F Y', strtotime($tgl)) ?></div>
            </div>
        </div>
        <?php endforeach; endif; ?>

        <!-- RIWAYAT KEHADIRAN BULAN INI -->
        <?php if(!empty($absen_map)):
            arsort($absen_map);
        ?>
        <div class="section-head"><i class="fa fa-fingerprint me-1 text-success"></i> Kehadiran Bulan Ini</div>
        <?php foreach($absen_map as $tgl => $a):
            $masuk  = date('H:i', strtotime($a['check_in_time']));
            $pulang = $a['check_out_time'] ? date('H:i', strtotime($a['check_out_time'])) : '—';
            $telat  = strtotime($a['check_in_time']) > strtotime($tgl.' 08:30:00');
            $is_wfh = $a['type'] == 'WFH';
        ?>
        <div class="event-card">
            <div class="event-icon" style="background:<?= $is_wfh ? '#f0fdf4' : '#eef2ff' ?>; color:<?= $is_wfh ? '#059669' : '#4f46e5' ?>;">
                <i class="fa <?= $is_wfh ? 'fa-house-laptop' : 'fa-building' ?>"></i>
            </div>
            <div class="flex-grow-1">
                <div class="event-name">
                    <?= date('d M', strtotime($tgl)) ?> &mdash; <?= $a['type'] ?>
                    <?php if($telat): ?><span style="font-size:0.65rem; background:#fee2e2; color:#991b1b; padding:1px 6px; border-radius:4px; margin-left:4px;">Telat</span><?php endif; ?>
                </div>
                <div class="event-date"><i class="fa fa-sign-in-alt me-1 text-success"></i><?= $masuk ?> &nbsp; <i class="fa fa-sign-out-alt me-1 text-danger"></i><?= $pulang ?></div>
            </div>
        </div>
        <?php endforeach; endif; ?>

        <!-- CUTI BULAN INI -->
        <?php if(!empty($cuti_list)): ?>
        <div class="section-head"><i class="fa fa-calendar-minus me-1 text-warning"></i> Cuti Bulan Ini</div>
        <?php foreach($cuti_list as $c): ?>
        <div class="event-card">
            <div class="event-icon" style="background:#fef3c7; color:#d97706;"><i class="fa fa-umbrella-beach"></i></div>
            <div>
                <div class="event-name"><?= htmlspecialchars($c['leave_type']) ?></div>
                <div class="event-date">
                    <?= date('d M', strtotime($c['start_date'])) ?>
                    <?php if($c['start_date'] != $c['end_date']): ?> &ndash; <?= date('d M Y', strtotime($c['end_date'])) ?><?php else: echo date(' Y', strtotime($c['start_date'])); endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>

        <?php if(empty($absen_map) && empty($holiday_map) && empty($cuti_list)): ?>
        <div style="text-align:center; padding:32px; color:#94a3b8; font-size:0.85rem;">
            <i class="fa fa-calendar fa-2x mb-3 d-block opacity-25"></i>
            Belum ada aktivitas di bulan ini.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- TOOLTIP POPUP -->
<div class="day-popup" id="dayPopup">
    <div id="popupDate" style="font-size:0.7rem; opacity:0.7; margin-bottom:2px;"></div>
    <div id="popupInfo"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let popupTimer;
function showTooltip(e, date, info) {
    if(!info) return;
    const popup = document.getElementById('dayPopup');
    document.getElementById('popupDate').textContent = date;
    document.getElementById('popupInfo').textContent = info;
    popup.style.display = 'block';
    popup.style.left = Math.min(e.clientX - 60, window.innerWidth - 220) + 'px';
    popup.style.top  = (e.clientY - 70) + 'px';
    clearTimeout(popupTimer);
    popupTimer = setTimeout(() => popup.style.display = 'none', 2500);
}
document.addEventListener('click', () => {
    clearTimeout(popupTimer);
    document.getElementById('dayPopup').style.display = 'none';
});
</script>
</body>
</html>
