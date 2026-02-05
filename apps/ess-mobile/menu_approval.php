<?php
// apps/ess-mobile/menu_approval.php (FINAL - SECURE & ALERT ONLY)

session_name('ESS_PORTAL_SESSION');
session_start();

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

// 1. SECURITY CHECK (MODIFIKASI SESUAI REQUEST BOS)
$role = $_SESSION['ess_role'] ?? '';
$uid  = $_SESSION['ess_user'] ?? '';

// Jika BUKAN Manager/SPV, Tampilkan Alert visual, STOP proses, JANGAN redirect.
if (($role !== 'Manager' && $role !== 'Supervisor') || empty($uid)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Dibatasi</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center vh-100">
        <div class="text-center px-4">
            <div class="alert alert-warning shadow-sm p-5 rounded-4 border-0">
                <div class="display-1 mb-3">🚫</div>
                <h2 class="fw-bold text-dark">Akses Dibatasi</h2>
                <p class="text-muted mb-4 fs-5">
                    Halo <b><?= htmlspecialchars($_SESSION['ess_name'] ?? 'User'); ?></b>,<br>
                    Menu Approval hanya khusus untuk <b>Manager & Supervisor</b>.
                </p>
                <a href="index.php" class="btn btn-dark rounded-pill px-5 py-2 fw-bold">Kembali ke Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit(); // 🔥 PENTING: Stop script di sini biar data di bawah gak bocor
}

// --- FUNGSI HITUNG HARI KERJA ---
function hitungHariKerja($start, $end, $pdo) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $endDate->modify('+1 day');
    
    $period = new DatePeriod($startDate, DateInterval::createFromDateString('1 day'), $endDate);
    $days = 0;
    
    $stmt = $pdo->query("SELECT holiday_date FROM ess_holidays");
    $holidays = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($period as $dt) {
        $curr = $dt->format('Y-m-d');
        $day = $dt->format('N');
        if ($day < 6 && !in_array($curr, $holidays)) $days++;
    }
    return $days;
}

// 2. LOGIC PROCESS (POST ONLY)
if (isset($_POST['process_approval'])) {
    if (!verifyCSRFToken()) die("Security Error: Invalid Token.");

    $id_cuti  = sanitizeInt($_POST['id_cuti']);
    $action   = sanitizeInput($_POST['action']);
    $approver = $_SESSION['ess_name'];

    $cuti = safeGetOne($pdo, "SELECT * FROM ess_leaves WHERE id = ?", [$id_cuti]);

    if (!$cuti) {
        echo "<script>alert('Data tidak ditemukan!'); window.location='menu_approval.php';</script>";
        exit();
    }

    if ($action == 'Approved' && $cuti['leave_type'] == 'Cuti Tahunan') {
        $durasi = hitungHariKerja($cuti['start_date'], $cuti['end_date'], $pdo);
        $user = safeGetOne($pdo, "SELECT annual_leave_quota FROM ess_users WHERE employee_id = ?", [$cuti['employee_id']]);
        
        if ($user['annual_leave_quota'] >= $durasi) {
            safeQuery($pdo, "UPDATE ess_users SET annual_leave_quota = annual_leave_quota - ? WHERE employee_id = ?", [$durasi, $cuti['employee_id']]);
            safeQuery($pdo, "UPDATE ess_leaves SET status = ?, approved_by = ? WHERE id = ?", [$action, $approver, $id_cuti]);
            echo "<script>alert('Disetujui! Kuota dipotong $durasi hari.'); window.location='menu_approval.php';</script>";
        } else {
            echo "<script>alert('GAGAL: Sisa cuti tidak cukup!'); window.location='menu_approval.php';</script>";
        }
    } else {
        safeQuery($pdo, "UPDATE ess_leaves SET status = ?, approved_by = ? WHERE id = ?", [$action, $approver, $id_cuti]);
        echo "<script>alert('Berhasil diproses ($action).'); window.location='menu_approval.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Approval Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>
    <div class="container py-4" style="max-width: 450px;">
        
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="text-dark me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h5 class="fw-bold mb-0">Approval (<?php echo htmlspecialchars($role); ?>)</h5>
        </div>

        <?php
        $stmt = $pdo->query("SELECT * FROM ess_leaves WHERE status='Pending' ORDER BY id DESC");
        $count = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            $est_hari = hitungHariKerja($row['start_date'], $row['end_date'], $pdo);
        ?>
        
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($row['fullname']); ?></h6>
                    <span class="badge bg-warning text-dark">Pending</span>
                </div>
                <small class="text-muted d-block mb-1"><?php echo htmlspecialchars($row['division']); ?> | <?php echo htmlspecialchars($row['leave_type']); ?></small>
                
                <div class="alert alert-light border small py-2 mb-2">
                    <i class="fa fa-quote-left text-muted me-1"></i> <?php echo htmlspecialchars($row['reason']); ?>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <small class="fw-bold text-primary"><i class="fa fa-calendar"></i> <?php echo $row['start_date']; ?> - <?php echo $row['end_date']; ?></small>
                    <?php if($row['leave_type'] == 'Cuti Tahunan'): ?>
                        <span class="badge bg-info text-dark">Potong: <?php echo $est_hari; ?> Hari</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">No Potong</span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger btn-sm w-50" onclick="confirmAct('Rejected', <?php echo $row['id']; ?>, '<?php echo $row['fullname']; ?>')">Tolak</button>
                    <button class="btn btn-success btn-sm w-50" onclick="confirmAct('Approved', <?php echo $row['id']; ?>, '<?php echo $row['fullname']; ?>')">Setujui</button>
                </div>
            </div>
        </div>
        
        <?php } 
        if ($count == 0) echo "<div class='text-center text-muted mt-5'><i class='fa fa-check-circle fa-3x mb-3 text-secondary'></i><p>Semua beres! Tidak ada pengajuan.</p></div>";
        ?>

    </div>

    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title fw-bold">Konfirmasi</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <?php echo csrfTokenField(); ?>
                    <input type="hidden" name="id_cuti" id="val_id">
                    <input type="hidden" name="action" id="val_act">
                    <input type="hidden" name="process_approval" value="1">
                    <div class="modal-body text-center"><p id="msg_body" class="mb-0"></p></div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="submit" id="btn_submit" class="btn btn-primary btn-sm px-4">Ya, Proses</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmAct(act, id, name) {
            document.getElementById('val_id').value = id;
            document.getElementById('val_act').value = act;
            
            const btn = document.getElementById('btn_submit');
            const msg = document.getElementById('msg_body');
            
            if(act === 'Approved') {
                msg.innerHTML = `Setujui pengajuan <b>${name}</b>?`;
                btn.className = 'btn btn-success btn-sm px-4';
                btn.innerText = 'Setujui';
            } else {
                msg.innerHTML = `Tolak pengajuan <b>${name}</b>?`;
                btn.className = 'btn btn-danger btn-sm px-4';
                btn.innerText = 'Tolak';
            }
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }
    </script>
</body>
</html>