<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

// Session timeout — 8 jam (bukan 60 detik)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 28800)) {
    session_unset(); session_destroy();
    header("Location: landing.php?msg=timeout"); exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

$nama_user = $_SESSION['ess_name'];
$role_user = $_SESSION['ess_role'];
$nik_user  = $_SESSION['ess_user'];
$div_user  = $_SESSION['ess_div'] ?? '-';

$today      = date('Y-m-d');
$bulan_ini  = date('Y-m');
$nama_hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w')];
$tgl_indo   = date('d') . ' ' . ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][date('n')-1] . ' ' . date('Y');

// Sisa cuti
$d_user     = safeGetOne($pdo, "SELECT annual_leave_quota FROM ess_users WHERE employee_id=?", [$nik_user]);
$sisa_cuti  = $d_user['annual_leave_quota'] ?? 0;

// Status absen hari ini
$absen      = safeGetOne($pdo, "SELECT * FROM ess_attendance WHERE employee_id=? AND date_log=?", [$nik_user, $today]);
$status_absen = 'BELUM';
if ($absen) $status_absen = ($absen['check_out_time'] == NULL) ? 'SUDAH_MASUK' : 'SELESAI';

// Cek hari libur
$is_holiday     = (date('N') >= 6);
$holiday_reason = $is_holiday ? "Hari Libur (Weekend)" : "";
$libur = safeGetOne($pdo, "SELECT description FROM ess_holidays WHERE holiday_date=?", [$today]);
if ($libur) { $is_holiday = true; $holiday_reason = $libur['description']; }

// Statistik bulan ini — REAL dari DB
$stats = safeGetOne($pdo,
    "SELECT 
        COUNT(*) as total_hadir,
        SUM(CASE WHEN TIME(check_in_time) > '08:30:00' THEN 1 ELSE 0 END) as telat,
        SUM(CASE WHEN check_out_time IS NOT NULL THEN 1 ELSE 0 END) as selesai
     FROM ess_attendance 
     WHERE employee_id=? AND date_log LIKE ?",
    [$nik_user, $bulan_ini . '%']
);
$hadir_count = $stats['total_hadir'] ?? 0;
$telat_count = $stats['telat'] ?? 0;

// Notifikasi unread
$notif_count = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_notifications WHERE employee_id=? AND is_read=0", [$nik_user]);
$notif_unread = $notif_count['c'] ?? 0;

// Cuti pending milik user ini
$cuti_pending = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_leaves WHERE employee_id=? AND status='Pending'", [$nik_user]);
$my_pending   = $cuti_pending['c'] ?? 0;

// Lembur pending
$lembur_pending = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_overtime WHERE employee_id=? AND status='Pending'", [$nik_user]);
$my_lembur_pending = $lembur_pending['c'] ?? 0;

// Approval badge (Manager/SPV)
$approval_pending = 0;
if ($role_user == 'Manager' || $role_user == 'Supervisor') {
    $ap = safeGetOne($pdo, "SELECT (SELECT COUNT(*) FROM ess_leaves WHERE status='Pending') + (SELECT COUNT(*) FROM ess_overtime WHERE status='Pending') as c");
    $approval_pending = $ap['c'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESS Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand: #6366f1;
            --brand-dark: #4f46e5;
            --brand-light: #e0e7ff;
            --surface: #ffffff;
            --bg: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            background: var(--bg);
            display: flex; justify-content: center; align-items: flex-start;
            min-height: 100vh; font-family: 'DM Sans', sans-serif;
            margin: 0; color: var(--text);
        }
        .shell {
            width: 100%; max-width: 420px; min-height: 100vh;
            background: var(--surface);
            display: flex; flex-direction: column;
            position: relative;
        }

        /* ── HEADER ── */
        .app-header {
            background: linear-gradient(145deg, #4f46e5 0%, #7c3aed 100%);
            padding: 20px 20px 56px;
            position: relative; overflow: hidden;
        }
        .app-header::before {
            content: '';
            position: absolute; top: -40px; right: -40px;
            width: 180px; height: 180px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }
        .app-header::after {
            content: '';
            position: absolute; bottom: -20px; left: 20px;
            width: 100px; height: 100px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 2; }
        .greeting { color: rgba(255,255,255,0.7); font-size: 0.78rem; font-weight: 500; letter-spacing: 0.3px; }
        .user-name { color: #fff; font-size: 1.25rem; font-weight: 700; line-height: 1.2; margin: 2px 0 6px; }
        .role-chip {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
            color: #fff; font-size: 0.72rem; font-weight: 600;
            padding: 4px 10px; border-radius: 20px;
        }
        .notif-btn {
            width: 40px; height: 40px; border-radius: 12px;
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            color: #fff; cursor: pointer; position: relative; text-decoration: none;
            transition: background 0.2s;
        }
        .notif-btn:hover { background: rgba(255,255,255,0.25); }
        .notif-badge {
            position: absolute; top: -4px; right: -4px;
            background: var(--danger); color: #fff;
            font-size: 0.6rem; font-weight: 700;
            min-width: 16px; height: 16px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 3px; border: 2px solid #5b21b6;
        }
        .date-bar { color: rgba(255,255,255,0.8); font-size: 0.8rem; margin-top: 12px; position: relative; z-index: 2; }

        /* ── STATS CARD (floating) ── */
        .stats-card {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(79,70,229,0.12);
            margin: -36px 16px 0;
            padding: 16px 20px;
            position: relative; z-index: 10;
            display: flex; gap: 0;
        }
        .stat-col { flex: 1; text-align: center; }
        .stat-col + .stat-col { border-left: 1px solid var(--border); }
        .stat-num { font-size: 1.35rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.4px; font-weight: 600; margin-top: 3px; }

        /* ── CHECKIN AREA ── */
        .checkin-area { padding: 24px 20px 8px; text-align: center; }
        .clock-text { font-family: 'DM Mono', monospace; font-size: 2.2rem; font-weight: 500; color: var(--text); letter-spacing: -1px; }
        .date-text { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }
        .checkin-btn {
            width: 120px; height: 120px; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            margin: 20px auto 0; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
            border: none; outline: none;
        }
        .checkin-btn:active { transform: scale(0.93); }
        .btn-masuk {
            background: linear-gradient(145deg, var(--success), #059669);
            box-shadow: 0 12px 28px rgba(16,185,129,0.35);
            color: #fff;
        }
        .btn-pulang {
            background: linear-gradient(145deg, var(--danger), #dc2626);
            box-shadow: 0 12px 28px rgba(239,68,68,0.35);
            color: #fff;
        }
        .btn-done {
            background: #e2e8f0; color: var(--muted);
            cursor: default; box-shadow: none;
        }
        .btn-libur {
            background: #fef3c7; color: var(--warning);
            cursor: default; box-shadow: none;
        }
        .checkin-btn i { font-size: 1.8rem; margin-bottom: 4px; }
        .checkin-btn span { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
        .checkin-status { font-size: 0.8rem; font-weight: 600; margin-top: 12px; }

        /* ── MENU GRID ── */
        .section-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); padding: 0 20px; margin: 24px 0 12px; }
        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 0 20px; }
        .menu-item { text-align: center; text-decoration: none; }
        .menu-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin: 0 auto 6px;
            transition: transform 0.15s;
            position: relative;
        }
        .menu-item:active .menu-icon { transform: scale(0.9); }
        .menu-label { font-size: 0.68rem; font-weight: 600; color: var(--text); }
        .menu-badge {
            position: absolute; top: -4px; right: -4px;
            background: var(--danger); color: #fff;
            font-size: 0.55rem; font-weight: 700;
            min-width: 15px; height: 15px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; padding: 0 3px;
        }

        /* ── QUICK INFO ── */
        .quick-banner {
            margin: 20px 16px 0;
            background: linear-gradient(135deg, #eff6ff, #e0e7ff);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex; align-items: center; gap: 12px;
            border: 1px solid #c7d2fe;
        }
        .quick-banner i { color: var(--brand); font-size: 1.2rem; flex-shrink: 0; }
        .quick-banner small { font-size: 0.75rem; color: #3730a3; font-weight: 500; }

        /* ── APPROVAL CARD (atasan) ── */
        .approval-card {
            margin: 16px 16px 0;
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-radius: 14px; border: 1px solid #fde68a;
            padding: 14px 16px;
        }
        .approval-card a { text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .approval-card .badge-count {
            background: var(--warning); color: #fff;
            font-size: 0.8rem; font-weight: 800;
            min-width: 28px; height: 28px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            position: sticky; bottom: 0; background: var(--surface);
            border-top: 1px solid var(--border);
            display: flex; justify-content: space-around;
            padding: 10px 0 14px; z-index: 99; margin-top: auto;
        }
        .nav-item {
            text-align: center; color: var(--muted);
            text-decoration: none; transition: color 0.15s;
            display: flex; flex-direction: column; align-items: center; gap: 3px;
        }
        .nav-item.active { color: var(--brand); }
        .nav-item i { font-size: 1.1rem; }
        .nav-item span { font-size: 0.62rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

        /* ── MODAL ── */
        .modal-content { border-radius: 20px; border: none; overflow: hidden; }
        .modal-header { border: none; }
        .btn-type-checkin {
            border: 2px solid var(--border); border-radius: 14px;
            padding: 16px; display: flex; flex-direction: column;
            align-items: center; gap: 8px; text-decoration: none;
            color: var(--text); transition: all 0.2s; font-weight: 600;
        }
        .btn-type-checkin:hover { border-color: var(--brand); background: var(--brand-light); color: var(--brand-dark); }
        .btn-type-checkin i { font-size: 1.5rem; }
    </style>
</head>
<body>
<div class="shell">

    <!-- HEADER -->
    <div class="app-header">
        <div class="header-top">
            <div>
                <div class="greeting">Selamat <?= (date('H')<12)?'Pagi':((date('H')<15)?'Siang':((date('H')<18)?'Sore':'Malam')) ?>,</div>
                <div class="user-name"><?= htmlspecialchars($nama_user) ?></div>
                <span class="role-chip"><i class="fa fa-id-badge"></i> <?= htmlspecialchars($role_user) ?> &bull; <?= htmlspecialchars($div_user) ?></span>
            </div>
            <a href="menu_notif.php" class="notif-btn">
                <i class="fa fa-bell"></i>
                <?php if($notif_unread > 0): ?>
                <span class="notif-badge"><?= $notif_unread > 9 ? '9+' : $notif_unread ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="date-bar"><i class="fa fa-calendar-alt me-1"></i> <?= $nama_hari ?>, <?= $tgl_indo ?></div>
    </div>

    <!-- STATS CARD -->
    <div class="stats-card">
        <div class="stat-col">
            <div class="stat-num text-success"><?= $hadir_count ?></div>
            <div class="stat-label">Hadir</div>
        </div>
        <div class="stat-col">
            <div class="stat-num text-warning"><?= $telat_count ?></div>
            <div class="stat-label">Telat</div>
        </div>
        <div class="stat-col">
            <div class="stat-num text-primary"><?= $sisa_cuti ?></div>
            <div class="stat-label">Sisa Cuti</div>
        </div>
    </div>

    <!-- CHECKIN AREA -->
    <div class="checkin-area">
        <div class="clock-text" id="clockDisplay"><?= date('H:i') ?></div>
        <div class="date-text">WIB &mdash; <?= $nama_hari ?></div>

        <?php if ($is_holiday): ?>
            <div class="checkin-btn btn-libur" onclick="alert('<?= htmlspecialchars($holiday_reason) ?>')">
                <i class="fa fa-umbrella-beach"></i><span>Libur</span>
            </div>
            <div class="checkin-status text-warning"><?= htmlspecialchars($holiday_reason) ?></div>

        <?php elseif ($status_absen == 'BELUM'): ?>
            <button class="checkin-btn btn-masuk" onclick="openModal('modalCheckIn')">
                <i class="fa fa-fingerprint"></i><span>Absen Masuk</span>
            </button>
            <div class="checkin-status text-muted">Belum absen hari ini</div>

        <?php elseif ($status_absen == 'SUDAH_MASUK'): ?>
            <button class="checkin-btn btn-pulang" onclick="openModal('modalCheckOut')">
                <i class="fa fa-sign-out-alt"></i><span>Absen Pulang</span>
            </button>
            <div class="checkin-status text-success">
                <i class="fa fa-check-circle me-1"></i> Masuk <?= date('H:i', strtotime($absen['check_in_time'])) ?> &bull; <?= $absen['type'] ?>
            </div>

        <?php else: ?>
            <div class="checkin-btn btn-done">
                <i class="fa fa-check-double"></i><span>Selesai</span>
            </div>
            <div class="checkin-status text-success">
                <i class="fa fa-check-circle me-1"></i> <?= date('H:i', strtotime($absen['check_in_time'])) ?> &ndash; <?= date('H:i', strtotime($absen['check_out_time'])) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- QUICK INFO: cuti/lembur pending -->
    <?php if($my_pending > 0 || $my_lembur_pending > 0): ?>
    <div class="quick-banner">
        <i class="fa fa-clock"></i>
        <small>
            <?php if($my_pending > 0) echo "$my_pending pengajuan cuti "; ?>
            <?php if($my_lembur_pending > 0) echo "$my_lembur_pending pengajuan lembur "; ?>
            sedang menunggu approval atasan.
        </small>
    </div>
    <?php endif; ?>

    <!-- MENU -->
    <div class="section-title">Menu Karyawan</div>
    <div class="menu-grid">
        <a href="menu_cuti.php" class="menu-item">
            <div class="menu-icon" style="background:#fef3c7; color:#d97706;">
                <i class="fa fa-calendar-minus"></i>
                <?php if($my_pending > 0): ?><span class="menu-badge"><?= $my_pending ?></span><?php endif; ?>
            </div>
            <div class="menu-label">Cuti/Izin</div>
        </a>
        <a href="menu_lembur.php" class="menu-item">
            <div class="menu-icon" style="background:#fce7f3; color:#db2777;">
                <i class="fa fa-clock"></i>
                <?php if($my_lembur_pending > 0): ?><span class="menu-badge"><?= $my_lembur_pending ?></span><?php endif; ?>
            </div>
            <div class="menu-label">Lembur</div>
        </a>
        <a href="menu_slip.php" class="menu-item">
            <div class="menu-icon" style="background:#d1fae5; color:#059669;">
                <i class="fa fa-file-invoice-dollar"></i>
            </div>
            <div class="menu-label">Slip Gaji</div>
        </a>
        <a href="menu_history.php" class="menu-item">
            <div class="menu-icon" style="background:#e0e7ff; color:#4f46e5;">
                <i class="fa fa-history"></i>
            </div>
            <div class="menu-label">Riwayat</div>
        </a>
        <a href="menu_team.php" class="menu-item">
            <div class="menu-icon" style="background:#ede9fe; color:#7c3aed;">
                <i class="fa fa-users"></i>
            </div>
            <div class="menu-label">Tim Saya</div>
        </a>
        <a href="menu_setting.php" class="menu-item">
            <div class="menu-icon" style="background:#f1f5f9; color:#475569;">
                <i class="fa fa-cog"></i>
            </div>
            <div class="menu-label">Setting</div>
        </a>
        <a href="menu_notif.php" class="menu-item">
            <div class="menu-icon" style="background:#fef2f2; color:#ef4444; position:relative;">
                <i class="fa fa-bell"></i>
                <?php if($notif_unread > 0): ?><span class="menu-badge"><?= $notif_unread ?></span><?php endif; ?>
            </div>
            <div class="menu-label">Notifikasi</div>
        </a>
        <a href="auth.php?logout=true" class="menu-item">
            <div class="menu-icon" style="background:#fff1f2; color:#e11d48;">
                <i class="fa fa-sign-out-alt"></i>
            </div>
            <div class="menu-label">Keluar</div>
        </a>
    </div>

    <?php if($role_user == 'Manager' || $role_user == 'Supervisor'): ?>
    <div class="section-title">Menu Atasan</div>
    <div class="menu-grid">
        <a href="menu_approval.php" class="menu-item">
            <div class="menu-icon" style="background:#fffbeb; color:#d97706; position:relative;">
                <i class="fa fa-check-double"></i>
                <?php if($approval_pending > 0): ?><span class="menu-badge"><?= $approval_pending ?></span><?php endif; ?>
            </div>
            <div class="menu-label">Approval</div>
        </a>
    </div>
    <?php endif; ?>

    <div style="display:none;"><!-- end menu section -->
    </div>

    <!-- APPROVAL CARD (manager/SPV) -->
    <?php if(($role_user == 'Manager' || $role_user == 'Supervisor') && $approval_pending > 0): ?>
    <div class="approval-card">
        <a href="menu_approval.php">
            <div class="badge-count"><?= $approval_pending ?></div>
            <div class="flex-grow-1">
                <div style="font-weight:700; font-size:0.9rem; color:#92400e;">Ada Pengajuan Menunggu</div>
                <small style="color:#b45309;"><?= $approval_pending ?> pengajuan perlu persetujuan Anda</small>
            </div>
            <i class="fa fa-chevron-right" style="color:#d97706;"></i>
        </a>
    </div>
    <?php endif; ?>

    <div style="height: 80px;"></div>

    <!-- BOTTOM NAV -->
    <div class="bottom-nav">
        <div class="nav-item active">
            <i class="fa fa-home"></i><span>Beranda</span>
        </div>
        <a href="menu_history.php" class="nav-item">
            <i class="fa fa-calendar-alt"></i><span>Riwayat</span>
        </a>
        <a href="menu_team.php" class="nav-item">
            <i class="fa fa-users"></i><span>Tim</span>
        </a>
        <a href="menu_setting.php" class="nav-item">
            <i class="fa fa-user-circle"></i><span>Profil</span>
        </a>
    </div>
</div>

<!-- MODAL CHECK IN -->
<div class="modal fade" id="modalCheckIn" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-light px-4 pt-4 pb-3">
                <div>
                    <h6 class="fw-bold mb-0">Pilih Tipe Absensi</h6>
                    <small class="text-muted"><?= date('H:i') ?> WIB &mdash; <?= $tgl_indo ?></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <div class="d-grid gap-3">
                    <a href="attendance.php?type=WFO" class="btn-type-checkin">
                        <i class="fa fa-building" style="color:#4f46e5;"></i>
                        <span>WFO &mdash; Kerja di Kantor</span>
                    </a>
                    <a href="attendance.php?type=WFH" class="btn-type-checkin">
                        <i class="fa fa-house-laptop" style="color:#10b981;"></i>
                        <span>WFH &mdash; Kerja dari Rumah</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CHECK OUT -->
<div class="modal fade" id="modalCheckOut" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#ef4444,#dc2626); color:#fff; border:none;">
                <div>
                    <h6 class="fw-bold mb-0">Laporan Kerja Harian</h6>
                    <small style="opacity:0.8;">Ceritakan kegiatan hari ini sebelum pulang</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="attendance.php" method="POST">
                <?php echo csrfTokenField(); ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Aktivitas & Pencapaian Hari Ini</label>
                        <textarea name="tasks" class="form-control" rows="4"
                            placeholder="Contoh: Meeting sprint planning, review PR, koordinasi dengan tim desain..." required
                            style="border-radius:12px; resize:none;"></textarea>
                    </div>
                    <?php if($absen): ?>
                    <div class="d-flex gap-3 text-muted small">
                        <span><i class="fa fa-clock me-1 text-success"></i> Masuk: <?= date('H:i', strtotime($absen['check_in_time'])) ?></span>
                        <span><i class="fa fa-map-marker-alt me-1 text-primary"></i> <?= $absen['type'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="submit" name="checkout" class="btn w-100 fw-bold py-3"
                        style="background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; border-radius:14px; border:none;">
                        <i class="fa fa-sign-out-alt me-2"></i> Kirim & Pulang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openModal(id) { new bootstrap.Modal(document.getElementById(id)).show(); }

// Live clock
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const el = document.getElementById('clockDisplay');
    if (el) el.textContent = h + ':' + m;
}
setInterval(updateClock, 1000);
</script>
</body>
</html>
