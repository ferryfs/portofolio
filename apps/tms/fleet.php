<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

$msg = ''; $msg_type = '';

if (isset($_POST['add_fleet'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    $plate    = sanitizeInput($_POST['plate_number']);
    $type     = sanitizeInput($_POST['vehicle_type']);
    $vendor   = sanitizeInt($_POST['vendor_id']);
    $capacity = (float)($_POST['capacity_weight'] ?? 0);
    $stnk     = sanitizeInput($_POST['stnk_expired'] ?? '');
    $kir      = sanitizeInput($_POST['kir_expired'] ?? '');

    $cek = safeGetOne($pdo, "SELECT id FROM tms_vehicles WHERE plate_number=?", [$plate]);
    if ($cek) {
        header("Location: fleet.php?msg=duplicate&plate=".urlencode($plate));
    } else {
        safeQuery($pdo,
            "INSERT INTO tms_vehicles (vendor_id,plate_number,vehicle_type,capacity_weight,stnk_expired,kir_expired,status) VALUES (?,?,?,?,?,?,'available')",
            [$vendor,$plate,$type,$capacity,$stnk?:null,$kir?:null]);
        header("Location: fleet.php?msg=added&plate=".urlencode($plate));
    }
    exit();
}

if (isset($_POST['update_status_vehicle'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    $vid    = sanitizeInt($_POST['vehicle_id']);
    $status = sanitizeInput($_POST['vehicle_status']);
    if(in_array($status, ['available','busy','maintenance'])) {
        safeQuery($pdo, "UPDATE tms_vehicles SET status=? WHERE id=?", [$status,$vid]);
    }
    header("Location: fleet.php?msg=status_updated");
    exit();
    }

$fleets  = safeGetAll($pdo, "SELECT v.*, ven.name as vendor_name, ven.type as v_type FROM tms_vehicles v LEFT JOIN tms_vendors ven ON v.vendor_id=ven.id ORDER BY v.status, v.id");
$vendors = safeGetAll($pdo, "SELECT * FROM tms_vendors");

// KPI
$avail = count(array_filter($fleets, fn($f) => $f['status']=='available'));
$busy  = count(array_filter($fleets, fn($f) => $f['status']=='busy'));
$maint = count(array_filter($fleets, fn($f) => $f['status']=='maintenance'));

$page_title  = 'Fleet Management';
$active_page = 'fleet';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <div><h3><i class="fa fa-truck text-warning me-2"></i>Fleet Management</h3><p>Manage Vehicles & Vendor Assets</p></div>
        <button class="btn btn-accent shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFleet"><i class="fa fa-plus me-2"></i>Add Vehicle</button>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?=$msg_type?> border-0 rounded-3 py-2 mb-3 small fw-bold">
        <i class="fa fa-<?=$msg_type=='success'?'check-circle':'exclamation-circle'?> me-2"></i><?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="kpi-card">
            <div><div class="kpi-num text-success"><?=$avail?></div><div class="kpi-label">Available</div></div>
            <div class="kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fa fa-truck"></i></div>
        </div></div>
        <div class="col-md-4"><div class="kpi-card">
            <div><div class="kpi-num text-warning"><?=$busy?></div><div class="kpi-label">Busy / On Trip</div></div>
            <div class="kpi-icon" style="background:#fffbeb;color:#d97706;"><i class="fa fa-truck-fast"></i></div>
        </div></div>
        <div class="col-md-4"><div class="kpi-card">
            <div><div class="kpi-num text-danger"><?=$maint?></div><div class="kpi-label">Maintenance</div></div>
            <div class="kpi-icon" style="background:#fef2f2;color:#ef4444;"><i class="fa fa-wrench"></i></div>
        </div></div>
    </div>

    <div class="data-card">
        <table class="table" id="tableFleet">
            <thead><tr><th>Plate</th><th>Type</th><th>Vendor</th><th>Capacity</th><th>STNK</th><th>KIR</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($fleets as $row):
                $sc = ['available'=>'bs-success','busy'=>'bs-warning','maintenance'=>'bs-danger'][$row['status']] ?? 'bs-muted';
                $stnk_exp = $row['stnk_expired'] ? date('d M Y', strtotime($row['stnk_expired'])) : '—';
                $kir_exp  = $row['kir_expired']  ? date('d M Y', strtotime($row['kir_expired']))  : '—';
                $stnk_warn = $row['stnk_expired'] && strtotime($row['stnk_expired']) < strtotime('+30 days');
                $kir_warn  = $row['kir_expired']  && strtotime($row['kir_expired'])  < strtotime('+30 days');
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($row['plate_number']) ?></td>
                <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                <td>
                    <?= htmlspecialchars($row['vendor_name'] ?? 'Unknown') ?>
                    <span class="badge-soft <?= ($row['v_type']??'')==='internal'?'bs-muted':'bs-warning' ?>" style="font-size:0.65rem;"><?= ($row['v_type']??'')==='internal'?'Internal':'3PL' ?></span>
                </td>
                <td><?= number_format($row['capacity_weight']??0,0) ?> kg</td>
                <td style="font-size:0.78rem;" class="<?= $stnk_warn?'text-danger fw-bold':'' ?>"><?= $stnk_exp ?><?= $stnk_warn?' ⚠️':'' ?></td>
                <td style="font-size:0.78rem;" class="<?= $kir_warn?'text-danger fw-bold':'' ?>"><?= $kir_exp ?><?= $kir_warn?' ⚠️':'' ?></td>
                <td><span class="badge-soft <?= $sc ?>"><?= ucfirst($row['status']) ?></span></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <?= csrfTokenField() ?>
                        <input type="hidden" name="vehicle_id" value="<?= $row['id'] ?>">
                        <select name="vehicle_status" class="form-select form-select-sm d-inline" style="width:130px;" onchange="this.form.submit()">
                            <?php foreach(['available','busy','maintenance'] as $s): ?>
                            <option value="<?=$s?>" <?= $row['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="update_status_vehicle" value="1">
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalFleet" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Armada Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label">Nomor Polisi *</label><input type="text" name="plate_number" class="form-control" placeholder="B 1234 XYZ" required></div>
                    <div class="col-6"><label class="form-label">Tipe Kendaraan</label>
                        <select name="vehicle_type" class="form-select"><option>Blindvan</option><option>CDE Box</option><option>CDD Box</option><option>Wingbox</option><option>Tronton</option></select></div>
                    <div class="col-6"><label class="form-label">Kapasitas (kg)</label><input type="number" name="capacity_weight" class="form-control" placeholder="1000"></div>
                    <div class="col-6"><label class="form-label">Vendor / Pemilik</label>
                        <select name="vendor_id" class="form-select"><?php foreach($vendors as $v): ?><option value="<?=$v['id']?>"><?=$v['name']?></option><?php endforeach; ?></select></div>
                    <div class="col-6"><label class="form-label">STNK Exp.</label><input type="date" name="stnk_expired" class="form-control"></div>
                    <div class="col-6"><label class="form-label">KIR Exp.</label><input type="date" name="kir_expired" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" name="add_fleet" class="btn btn-accent w-100 fw-bold">Simpan Armada</button></div>
        </form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>$(document).ready(function(){ $('#tableFleet').DataTable(); });</script>
</body></html>