<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik = $_SESSION['ess_user'];

// Mark all as read
safeQuery($pdo, "UPDATE ess_notifications SET is_read=1 WHERE employee_id=?", [$nik]);

// Ambil notifikasi
$notifs = safeGetAll($pdo, "SELECT * FROM ess_notifications WHERE employee_id=? ORDER BY id DESC LIMIT 50", [$nik]);

$icon_map = [
    'checkin'          => ['fa-fingerprint', '#6366f1', '#eef2ff'],
    'checkout'         => ['fa-sign-out-alt', '#10b981', '#d1fae5'],
    'leave_approved'   => ['fa-check-circle', '#10b981', '#d1fae5'],
    'leave_rejected'   => ['fa-times-circle', '#ef4444', '#fee2e2'],
    'lembur'           => ['fa-clock', '#db2777', '#fce7f3'],
    'lembur_approved'  => ['fa-check-double', '#10b981', '#d1fae5'],
    'lembur_rejected'  => ['fa-times-circle', '#ef4444', '#fee2e2'],
    'payslip'          => ['fa-file-invoice-dollar', '#059669', '#dcfce7'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Notifikasi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:420px; margin:0 auto; min-height:100vh; background:#fff; }
        .page-header {
            background:linear-gradient(135deg,#4f46e5,#7c3aed);
            padding:20px 20px 40px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }
        .body-area { background:#f8fafc; padding:0 0 32px; }

        .notif-item {
            display:flex; gap:12px; align-items:flex-start;
            padding:14px 16px; background:#fff;
            border-bottom:1px solid #f1f5f9;
            transition:background 0.15s;
        }
        .notif-item:first-child { border-top:1px solid #f1f5f9; }
        .notif-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.1rem; }
        .notif-content { flex:1; }
        .notif-msg { font-size:0.85rem; color:#0f172a; font-weight:500; line-height:1.4; }
        .notif-time { font-size:0.72rem; color:#94a3b8; margin-top:3px; }

        .date-divider { padding:10px 16px 4px; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; color:#94a3b8; background:#f8fafc; }
    </style>
</head>
<body>
<div class="shell">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Aktivitas Anda</div>
                <h5 class="fw-bold mb-0">Notifikasi</h5>
            </div>
        </div>
    </div>

    <div class="body-area">
        <?php if(empty($notifs)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa fa-bell-slash fa-3x mb-3 d-block opacity-25"></i>
            <div style="font-size:0.9rem;">Belum ada notifikasi.</div>
        </div>
        <?php else:
            $last_date = '';
            foreach($notifs as $n):
                $notif_date = date('Y-m-d', strtotime($n['created_at']));
                $today_date = date('Y-m-d');
                $yesterday  = date('Y-m-d', strtotime('-1 day'));

                if ($notif_date !== $last_date) {
                    $last_date = $notif_date;
                    if ($notif_date == $today_date) $label = 'Hari Ini';
                    elseif ($notif_date == $yesterday) $label = 'Kemarin';
                    else $label = date('d M Y', strtotime($notif_date));
                    echo '<div class="date-divider">' . $label . '</div>';
                }

                $type  = $n['type'] ?? 'checkin';
                $icons = $icon_map[$type] ?? ['fa-bell','#6366f1','#eef2ff'];
                $time  = date('H:i', strtotime($n['created_at']));
        ?>
        <div class="notif-item">
            <div class="notif-icon" style="background:<?= $icons[2] ?>; color:<?= $icons[1] ?>;">
                <i class="fa <?= $icons[0] ?>"></i>
            </div>
            <div class="notif-content">
                <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                <div class="notif-time"><i class="fa fa-clock me-1"></i><?= $time ?> WIB</div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
</body>
</html>
