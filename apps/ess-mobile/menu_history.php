<?php
// apps/ess-mobile/menu_history.php (PRO UI + PDO)

session_name("ESS_PORTAL_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

// 1. CEK LOGIN
if (!isset($_SESSION['ess_user'])) {
    header("Location: landing.php");
    exit();
}

$nik = $_SESSION['ess_user'];

// 2. HITUNG STATISTIK (Untuk Header)
// Kita pakai satu query biar efisien
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM ess_leaves WHERE employee_id = ?";
$stats = safeGetOne($pdo, $sql_stats, [$nik]);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Riwayat Saya</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f3f4f6; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        .mobile-frame { width: 100%; max-width: 450px; min-height: 100vh; background: #fff; margin: 0 auto; display: flex; flex-direction: column; }
        
        /* Header */
        .history-header {
            background: #fff;
            padding: 20px;
            position: sticky; top: 0; z-index: 10;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Stats Cards */
        .stats-container {
            display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;
            padding: 0 20px 20px 20px;
            background: #fff;
            margin-bottom: 10px;
        }
        .stat-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 10px; text-align: center;
        }
        .stat-num { font-size: 1.2rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-label { font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-top: 4px; }

        /* History Card */
        .history-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .history-card:active { transform: scale(0.98); }
        
        /* Status Indicator Strip */
        .status-strip {
            position: absolute; left: 0; top: 0; bottom: 0; width: 6px;
        }
        .strip-Approved { background-color: #10b981; } /* Green */
        .strip-Rejected { background-color: #ef4444; } /* Red */
        .strip-Pending { background-color: #f59e0b; }  /* Orange */

        /* Badges */
        .badge-soft { padding: 5px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .bg-soft-success { background-color: #d1fae5; color: #065f46; }
        .bg-soft-danger { background-color: #fee2e2; color: #991b1b; }
        .bg-soft-warning { background-color: #fef3c7; color: #92400e; }

        .date-box {
            background: #f1f5f9; border-radius: 8px; padding: 6px 10px;
            font-size: 0.75rem; color: #475569; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
        }
    </style>
</head>
<body>

    <div class="mobile-frame">
        
        <div class="history-header d-flex align-items-center mb-0">
            <a href="index.php" class="btn btn-light rounded-circle shadow-sm" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;">
                <i class="fa fa-arrow-left"></i>
            </a>
            <h5 class="fw-bold mb-0 ms-3">Riwayat Pengajuan</h5>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-num text-primary"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-box">
                <div class="stat-num text-success"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Disetujui</div>
            </div>
            <div class="stat-box">
                <div class="stat-num text-warning"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Proses</div>
            </div>
        </div>

        <div class="container pb-5 bg-light flex-grow-1 pt-3">
            
            <?php
            // QUERY PDO
            $stmt = $pdo->prepare("SELECT * FROM ess_leaves WHERE employee_id = ? ORDER BY id DESC");
            $stmt->execute([$nik]);
            
            $count = 0;
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $count++;
                
                // Logic Status UI
                $status = $row['status']; // Approved, Rejected, Pending
                $status_label = $status;
                $css_status = 'bg-soft-warning';
                $icon = 'fa-hourglass-half';
                
                if ($status == 'Approved') {
                    $status_label = 'Disetujui';
                    $css_status = 'bg-soft-success';
                    $icon = 'fa-check-circle';
                } elseif ($status == 'Rejected') {
                    $status_label = 'Ditolak';
                    $css_status = 'bg-soft-danger';
                    $icon = 'fa-times-circle';
                }

                // Format Tanggal Cantik
                $start = date('d M Y', strtotime($row['start_date']));
                $end   = date('d M Y', strtotime($row['end_date']));
                $date_display = ($start == $end) ? $start : "$start - $end";
            ?>

            <div class="history-card">
                <div class="status-strip strip-<?php echo $status; ?>"></div>
                
                <div class="d-flex justify-content-between align-items-start mb-2 ps-2">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($row['leave_type']); ?></h6>
                        <div class="date-box">
                            <i class="fa fa-calendar-alt"></i> <?php echo $date_display; ?>
                        </div>
                    </div>
                    <span class="badge badge-soft <?php echo $css_status; ?>">
                        <i class="fa <?php echo $icon; ?> me-1"></i> <?php echo $status_label; ?>
                    </span>
                </div>

                <div class="ps-2 mt-3">
                    <p class="text-muted small mb-0 bg-light p-2 rounded fst-italic border border-light">
                        "<?php echo htmlspecialchars($row['reason']); ?>"
                    </p>
                    
                    <?php if(!empty($row['approved_by'])): ?>
                    <div class="d-flex align-items-center mt-3 pt-2 border-top border-light">
                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center text-secondary" style="width: 24px; height: 24px; font-size: 0.7rem;">
                            <i class="fa fa-user-tie"></i>
                        </div>
                        <small class="text-muted ms-2" style="font-size: 0.75rem;">
                            Diproses oleh: <b><?php echo htmlspecialchars($row['approved_by']); ?></b>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php } 
            
            // EMPTY STATE
            if ($count == 0) { ?>
                <div class="text-center py-5">
                    <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 80px; height: 80px;">
                        <i class="fa fa-history text-muted fa-2x opacity-50"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Belum Ada Riwayat</h6>
                    <p class="text-muted small">Anda belum pernah mengajukan cuti atau izin.</p>
                </div>
            <?php } ?>

        </div>
    </div>

</body>
</html>