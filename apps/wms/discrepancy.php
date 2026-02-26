<?php
// apps/wms/discrepancy.php
// V1.2: INBOUND DISCREPANCY RESOLUTION (LOSS & SURPLUS SUPPORT)
// Features: Strictly mapped to `id` column. Perfect tracking for overage/shortage.

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once 'koneksi.php';

$user_id = $_SESSION['wms_fullname'] ?? 'Supervisor';
$alert_msg = '';
$alert_type = '';

// --- 🛠️ LOGIC EKSEKUSI (WRITE-OFF LOSS / APPROVE SURPLUS) ---
if (isset($_POST['action']) && $_POST['action'] == 'resolve') {
    try {
        $pdo->beginTransaction();
        $id = sanitizeInt($_POST['id']); // Disesuaikan dengan JSON lu
        
        // 1. Ambil data selisihnya
        $info = safeGetOne($pdo, "SELECT gi.*, gh.po_number 
                                  FROM wms_gr_items gi 
                                  JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number 
                                  WHERE gi.id = ?", [$id]);
        
        if (!$info) throw new Exception("Record tidak ditemukan atau sudah diselesaikan.");

        $actual_total = (float)$info['qty_actual_good'] + (float)$info['qty_actual_damaged'];
        $reported = (float)$info['qty_reported'];
        $diff = $reported - $actual_total; 
        
        if ($diff == 0) throw new Exception("Tidak ada selisih, dokumen sudah Balance.");

        // 2. KOREKSI DOKUMEN GR (Ubah Reported jadi sesuai temuan fisik operator)
        safeQuery($pdo, "UPDATE wms_gr_items 
                         SET qty_reported = ?, discrepancy_status = 'BALANCED' 
                         WHERE id = ?", [$actual_total, $id]);

        // 3. KOREKSI PO (Biar PO sinkron sama fisik)
        // Jika diff POSITIF (Loss): PO harus dikurangi received_qty-nya biar OPEN lagi
        // Jika diff NEGATIF (Surplus): PO harus ditambah received_qty-nya (karena kelebihan barang)
        safeQuery($pdo, "UPDATE wms_po_items 
                         SET received_qty = received_qty - ? 
                         WHERE po_item_id = ?", [$diff, $info['po_item_id']]);
        
        // Buka kembali PO kalau ternyata jadinya belum lunas (Loss)
        if ($diff > 0) {
            safeQuery($pdo, "UPDATE wms_po_header SET status = 'OPEN' WHERE po_number = ?", [$info['po_number']]);
        }

        // 4. LOG AUDIT IT
        $tipe_resolusi = ($diff > 0) ? 'WRITE_OFF_LOSS' : 'APPROVE_SURPLUS';
        $desc = "Supervisor resolved discrepancy for GR {$info['gr_number']}. SKU: {$info['product_uuid']}. Tipe: $tipe_resolusi. Diff: ".abs($diff).". GR dikoreksi menjadi $actual_total.";
        safeQuery($pdo, "INSERT INTO wms_system_logs (user_id, module, action_type, description, ip_address, log_date) 
                         VALUES (?, 'DISCREPANCY', ?, ?, ?, NOW())", [$user_id, $tipe_resolusi, $desc, $_SERVER['REMOTE_ADDR']]);

        // 5. NOTIFIKASI
        $notif = "Discrepancy PO {$info['po_number']} diselesaikan ($tipe_resolusi) oleh $user_id.";
        safeQuery($pdo, "INSERT INTO wms_inbound_notif (po_number, message, severity, created_at) VALUES (?, ?, 'WARNING', NOW())", [$info['po_number'], $notif]);

        $pdo->commit();
        $alert_type = 'success';
        $alert_msg = "Resolusi berhasil! Tagihan GR telah dikoreksi menjadi $actual_total unit.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $alert_type = 'error';
        $alert_msg = $e->getMessage();
    }
}

// --- 📊 FETCH DATA MISMATCH ---
$sql = "SELECT gi.*, gh.po_number, gh.gr_date, gh.received_by, p.product_code, p.description, p.base_uom 
        FROM wms_gr_items gi 
        JOIN wms_gr_header gh ON gi.gr_number = gh.gr_number 
        JOIN wms_products p ON gi.product_uuid = p.product_uuid 
        WHERE gi.discrepancy_status = 'MISMATCH' 
        ORDER BY gh.gr_date DESC";
$mismatches = safeGetAll($pdo, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discrepancy Center | WMS Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --primary: #4f46e5; --danger: #ef4444; --success: #10b981; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: #0f172a; padding-bottom: 50px; }
        .navbar-custom { background: #0f172a; padding: 15px 0; border-bottom: 3px solid var(--danger); }
        .navbar-brand { font-weight: 800; letter-spacing: 0.5px; }
        .mismatch-card { border: 1px solid #e2e8f0; border-radius: 20px; background: var(--card); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 25px; transition: 0.3s; }
        .metric-box { padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; background: #f8fafc; }
        .metric-danger { background: #fef2f2; border-color: #fca5a5; }
        .metric-success { background: #ecfdf5; border-color: #6ee7b7; }
        .font-mono { font-family: 'Consolas', monospace; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-5">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <i class="bi bi-shield-exclamation text-danger fs-4"></i> 
            <span>Resolution <span style="font-weight: 300;">Center</span></span>
        </a>
        <a href="inbound.php" class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold"><i class="bi bi-arrow-left me-2"></i>Back to Inbound</a>
    </div>
</nav>

<div class="container">
    <div class="mb-5">
        <h2 class="fw-bold m-0 text-dark">Inbound Discrepancies</h2>
        <p class="text-muted">Investigate and resolve physical count variances from the Receiving area.</p>
    </div>

    <?php if(empty($mismatches)): ?>
        <div class="text-center py-5" style="margin-top: 100px;">
            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 120px; height: 120px;">
                <i class="bi bi-check2-all" style="font-size: 4rem;"></i>
            </div>
            <h3 class="fw-bold text-dark">All Clear!</h3>
            <p class="text-muted">No operational mismatches detected between Admin and Operators.</p>
            <a href="inbound.php" class="btn btn-primary rounded-pill px-5 py-2 mt-3 fw-bold shadow-sm">Return to Dashboard</a>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <?php foreach($mismatches as $row): 
                $pk_val = $row['id']; // SEKARANG MURNI PAKE ID
                $reported = (float)$row['qty_reported'];
                $actual = (float)$row['qty_actual_good'] + (float)$row['qty_actual_damaged'];
                
                $diff = $reported - $actual;
                $is_loss = ($diff > 0);
                $abs_diff = abs($diff);
                
                $box_class = $is_loss ? 'metric-danger' : 'metric-success';
                $text_class = $is_loss ? 'text-danger' : 'text-success';
                $label_text = $is_loss ? 'VARIANCE (LOSS)' : 'VARIANCE (SURPLUS)';
                $btn_class = $is_loss ? 'btn-danger' : 'btn-success';
                $btn_text = $is_loss ? 'WRITE-OFF LOSS' : 'APPROVE SURPLUS';
                $icon = $is_loss ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle';
            ?>
            <div class="mismatch-card">
                <div class="row g-0">
                    <div class="col-md-8 p-4 border-end">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <span class="badge <?= $is_loss ? 'bg-danger' : 'bg-success' ?> rounded-pill mb-2"><i class="bi <?= $icon ?> me-1"></i> <?= $is_loss ? 'SHORTAGE' : 'OVERAGE' ?></span>
                                <h4 class="fw-bold m-0 text-primary"><?= $row['product_code'] ?></h4>
                                <small class="text-muted"><?= $row['description'] ?></small>
                            </div>
                            <div class="text-end font-mono small">
                                <div class="text-muted">GR Ref: <span class="fw-bold text-dark"><?= $row['gr_number'] ?></span></div>
                                <div class="text-muted">PO Ref: <span class="fw-bold text-dark"><?= $row['po_number'] ?></span></div>
                                <div class="text-muted mt-1"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($row['gr_date'])) ?></div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="metric-box">
                                    <div class="small text-muted fw-bold mb-1">ADMIN REPORTED</div>
                                    <div class="fs-4 fw-bold text-dark"><?= $reported ?> <small class="fs-6 fw-normal text-muted"><?= $row['base_uom'] ?></small></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="metric-box">
                                    <div class="small text-muted fw-bold mb-1">OPERATOR ACTUAL</div>
                                    <div class="fs-4 fw-bold text-primary"><?= $actual ?> <small class="fs-6 fw-normal text-muted"><?= $row['base_uom'] ?></small></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="metric-box <?= $box_class ?> shadow-sm">
                                    <div class="small <?= $text_class ?> fw-bold mb-1"><?= $label_text ?></div>
                                    <div class="fs-4 fw-bold <?= $text_class ?>"><?= $is_loss ? '-' : '+' ?><?= $abs_diff ?> <small class="fs-6 fw-normal opacity-75"><?= $row['base_uom'] ?></small></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 p-4 bg-light d-flex flex-column justify-content-center text-center">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-gear-fill me-2 text-muted"></i>Supervisor Action</h6>
                        
                        <form method="POST" id="form-<?= $pk_val ?>">
                            <input type="hidden" name="action" value="resolve">
                            <input type="hidden" name="id" value="<?= $pk_val ?>">
                            
                            <button type="button" class="btn <?= $btn_class ?> w-100 rounded-pill fw-bold mb-3 py-2 shadow-sm" onclick="confirmAction('<?= $pk_val ?>', '<?= $row['product_code'] ?>', <?= $abs_diff ?>, <?= $is_loss ? 'true' : 'false' ?>)">
                                <i class="bi bi-check-circle me-2"></i><?= $btn_text ?>
                            </button>
                        </form>

                        <?php if($is_loss): ?>
                        <button class="btn btn-outline-dark w-100 rounded-pill fw-bold" onclick="alert('Instruct Operator to find the items at GR-ZONE, then re-process the Task via Scanner.')">
                            <i class="bi bi-search me-2"></i>MARK AS FOUND
                        </button>
                        <?php endif; ?>
                        
                        <div class="mt-3 small text-muted lh-sm text-start bg-white p-2 rounded border" style="font-size: 0.7rem;">
                            <i class="bi bi-info-circle text-primary me-1"></i> <b>Action</b> will permanently adjust the GR document to match physical reality (<b><?= $actual ?></b>).
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    <?php if($alert_type == 'success'): ?>
        Swal.fire('Resolution Applied!', '<?= $alert_msg ?>', 'success');
    <?php elseif($alert_type == 'error'): ?>
        Swal.fire('Error', '<?= $alert_msg ?>', 'error');
    <?php endif; ?>

    function confirmAction(id, sku, qty, isLoss) {
        let title = isLoss ? 'Confirm Write-Off?' : 'Approve Surplus?';
        let actionWord = isLoss ? 'write-off' : 'approve a surplus of';
        let color = isLoss ? '#ef4444' : '#10b981';
        let btnText = isLoss ? 'Yes, Write-off Loss!' : 'Yes, Approve Surplus!';

        Swal.fire({
            title: title,
            html: `Are you sure you want to ${actionWord} <b>${qty}</b> units of <b>${sku}</b>?<br><br><span class='text-danger small'>This will alter the official GR document permanently.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#64748b',
            confirmButtonText: btnText
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-' + id).submit();
            }
        });
    }
</script>
</body>
</html>