<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

$msg = ''; $msg_type = '';

// RATE CONFIG (per kg per vendor type)
$RATE_INTERNAL = 1500;  // Rp/kg untuk armada internal
$RATE_3PL      = 2500;  // Rp/kg untuk 3PL
$MIN_CHARGE    = 50000; // Minimum charge per shipment

// UPDATE BIAYA MANUAL
if(isset($_POST['update_cost'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    $ship_id = sanitizeInt($_POST['shipment_id']);
    $cost    = sanitizeInt($_POST['actual_cost']);
    safeQuery($pdo, "UPDATE tms_shipments SET total_cost=?,status='completed' WHERE id=?", [$cost,$ship_id]);
    $ship = safeGetOne($pdo, "SELECT vehicle_id FROM tms_shipments WHERE id=?", [$ship_id]);
    if($ship) safeQuery($pdo, "UPDATE tms_vehicles SET status='available' WHERE id=?", [$ship['vehicle_id']]);
    $msg = "Biaya diupdate, shipment selesai."; $msg_type = 'success';
}

// AUTO CALCULATE
if(isset($_POST['auto_calc'])) {
    if (!verifyCSRFToken()) die("Invalid Token");
    $ship_id = sanitizeInt($_POST['shipment_id']);
    $weight  = (float)$_POST['weight'];
    $v_type  = sanitizeInput($_POST['vendor_type']);
    $rate    = ($v_type === 'internal') ? $RATE_INTERNAL : $RATE_3PL;
    $cost    = max($MIN_CHARGE, $weight * $rate);
    safeQuery($pdo, "UPDATE tms_shipments SET total_cost=? WHERE id=?", [round($cost), $ship_id]);
    $msg = "Biaya dikalkulasi: Rp " . number_format($cost,0,',','.') . " (Berat: {$weight}kg × Rp{$rate}/kg)"; $msg_type = 'info';
}

$sql = "SELECT shp.id, shp.shipment_no, shp.total_cost, shp.status,
        COALESCE(v.plate_number,'Unknown') as plate_number,
        COALESCE(ven.name,'Internal') as vendor_name,
        COALESCE(ven.type,'internal') as vendor_type,
        COALESCE(o.order_no,'-') as order_ref,
        COALESCE(o.total_weight,0) as total_weight,
        d.name as driver_name
        FROM tms_shipments shp
        LEFT JOIN tms_vehicles v ON shp.vehicle_id=v.id
        LEFT JOIN tms_vendors ven ON v.vendor_id=ven.id
        LEFT JOIN tms_drivers d ON shp.driver_id=d.id
        LEFT JOIN tms_shipment_stops ss ON ss.shipment_id=shp.id AND ss.stop_type='delivery'
        LEFT JOIN tms_orders o ON ss.order_id=o.id
        ORDER BY shp.id DESC";
$data = safeGetAll($pdo, $sql);

$total_cost     = array_sum(array_column($data, 'total_cost'));
$total_pending  = count(array_filter($data, fn($r) => $r['total_cost'] == 0));
$total_settled  = count(array_filter($data, fn($r) => $r['status'] == 'completed'));

$page_title  = 'Billing & Cost';
$active_page = 'billing';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <div><h3><i class="fa fa-file-invoice-dollar text-warning me-2"></i>Billing & Cost</h3><p>Kalkulasi biaya pengiriman per shipment</p></div>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?=$msg_type?> border-0 rounded-3 py-2 mb-3 small fw-bold">
        <i class="fa fa-info-circle me-2"></i><?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- RATE INFO -->
    <div class="data-card mb-4 p-3" style="border-color:#e0e7ff;">
        <div class="d-flex gap-4 align-items-center flex-wrap">
            <div><span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--muted);">Tarif Internal Fleet</span>
                <div style="font-size:1.1rem; font-weight:800; color:#4f46e5;">Rp <?= number_format($RATE_INTERNAL,0,',','.') ?>/kg</div></div>
            <div style="width:1px; height:40px; background:var(--border);"></div>
            <div><span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--muted);">Tarif 3PL</span>
                <div style="font-size:1.1rem; font-weight:800; color:#f59e0b;">Rp <?= number_format($RATE_3PL,0,',','.') ?>/kg</div></div>
            <div style="width:1px; height:40px; background:var(--border);"></div>
            <div><span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--muted);">Min. Charge</span>
                <div style="font-size:1.1rem; font-weight:800; color:#059669;">Rp <?= number_format($MIN_CHARGE,0,',','.') ?></div></div>
            <div style="margin-left:auto;">
                <div style="font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--muted);">Total Biaya Settled</div>
                <div style="font-size:1.3rem; font-weight:800; color:#0f172a;">Rp <?= number_format($total_cost,0,',','.') ?></div>
            </div>
        </div>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="kpi-card">
            <div><div class="kpi-num"><?= count($data) ?></div><div class="kpi-label">Total Shipment</div></div>
            <div class="kpi-icon" style="background:#eef2ff;color:#4f46e5;"><i class="fa fa-truck"></i></div>
        </div></div>
        <div class="col-md-4"><div class="kpi-card" style="<?=$total_pending>0?'border-color:#f59e0b;':''?>">
            <div><div class="kpi-num text-warning"><?=$total_pending?></div><div class="kpi-label">Belum Ada Biaya</div></div>
            <div class="kpi-icon" style="background:#fffbeb;color:#d97706;"><i class="fa fa-hourglass-half"></i></div>
        </div></div>
        <div class="col-md-4"><div class="kpi-card">
            <div><div class="kpi-num text-success"><?=$total_settled?></div><div class="kpi-label">Completed</div></div>
            <div class="kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fa fa-circle-check"></i></div>
        </div></div>
    </div>

    <div class="data-card">
        <table class="table" id="tableBilling">
            <thead><tr><th>Shipment No</th><th>Order Ref</th><th>Armada</th><th>Driver</th><th>Berat (kg)</th><th>Status</th><th>Biaya</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($data as $r):
                $sc = ['planned'=>'bs-muted','in_transit'=>'bs-warning','arrived'=>'bs-info','completed'=>'bs-success','failed'=>'bs-danger'][$r['status']] ?? 'bs-muted';
                $estimated = $r['total_weight'] > 0 ? max($MIN_CHARGE, $r['total_weight'] * ($r['vendor_type']==='internal'?$RATE_INTERNAL:$RATE_3PL)) : 0;
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($r['shipment_no']) ?></td>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($r['order_ref']) ?></td>
                <td>
                    <div style="font-size:0.82rem;"><?= htmlspecialchars($r['plate_number']) ?></div>
                    <span class="badge-soft <?= $r['vendor_type']==='internal'?'bs-muted':'bs-warning' ?>" style="font-size:0.65rem;"><?= $r['vendor_type']==='internal'?'Internal':'3PL' ?></span>
                </td>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($r['driver_name'] ?? '—') ?></td>
                <td><?= number_format($r['total_weight'],0) ?></td>
                <td><span class="badge-soft <?= $sc ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <?php if($r['total_cost'] > 0): ?>
                    <strong class="text-success">Rp <?= number_format($r['total_cost'],0,',','.') ?></strong>
                    <?php elseif($estimated > 0): ?>
                    <span class="text-muted" style="font-size:0.78rem;">~Rp <?= number_format($estimated,0,',','.') ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($r['status'] !== 'completed'): ?>
                    <button class="btn btn-sm btn-accent fw-bold"
                        onclick="openBilling('<?=$r['id']?>','<?=$r['shipment_no']?>',<?=$r['total_weight']?>,'<?=$r['vendor_type']?>',<?=$estimated?>)">
                        <i class="fa fa-pen me-1"></i>Set Biaya
                    </button>
                    <?php else: ?>
                    <span class="badge-soft bs-success"><i class="fa fa-check me-1"></i>Settled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL BILLING -->
<div class="modal fade" id="modalBilling" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">Set Biaya Pengiriman</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="text-muted small mb-3" id="billShipNo"></div>
            <!-- Auto calc -->
            <form method="POST" class="mb-3 p-3 rounded-3" style="background:#fffbeb; border:1px solid #fde68a;">
                <?= csrfTokenField() ?>
                <input type="hidden" name="auto_calc" value="1">
                <input type="hidden" name="shipment_id" id="autoShipId">
                <input type="hidden" name="vendor_type" id="autoVendorType">
                <div class="mb-2"><label class="form-label" style="font-size:0.75rem;">Berat (kg)</label>
                    <input type="number" name="weight" id="autoWeight" class="form-control form-control-sm"></div>
                <div class="mb-2 fw-bold" style="font-size:0.78rem;">Estimasi: <span id="autoEstimate" class="text-success"></span></div>
                <button type="submit" class="btn btn-sm w-100" style="background:#f59e0b; font-weight:700; border-radius:8px;">
                    <i class="fa fa-calculator me-1"></i>Auto Kalkulasi
                </button>
            </form>
            <!-- Manual -->
            <form method="POST">
                <?= csrfTokenField() ?>
                <input type="hidden" name="shipment_id" id="billShipId">
                <label class="form-label" style="font-size:0.75rem;">Biaya Manual (Rp)</label>
                <input type="number" name="actual_cost" id="billCost" class="form-control mb-3" placeholder="150000">
                <button type="submit" name="update_cost" class="btn btn-accent w-100 fw-bold">Simpan & Selesaikan</button>
            </form>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){ $('#tableBilling').DataTable({ order:[[0,'desc']] }); });
const RATE_INT = <?= $RATE_INTERNAL ?>, RATE_3PL = <?= $RATE_3PL ?>, MIN_CHG = <?= $MIN_CHARGE ?>;

document.addEventListener('DOMContentLoaded', function() {
    var weightEl = document.getElementById('autoWeight');
    if(weightEl) {
        weightEl.addEventListener('input', function() {
            updateEstimate(this.value, document.getElementById('autoVendorType').value);
        });
    }
});

function openBilling(shipId, shipNo, weight, vendorType, estimated) {
    document.getElementById('billShipId').value = shipId;
    document.getElementById('autoShipId').value = shipId;
    document.getElementById('autoVendorType').value = vendorType;
    document.getElementById('autoWeight').value = weight;
    document.getElementById('billShipNo').innerText = 'Shipment: ' + shipNo;
    document.getElementById('billCost').value = estimated > 0 ? Math.round(estimated) : '';
    updateEstimate(weight, vendorType);
    new bootstrap.Modal(document.getElementById('modalBilling')).show();
}
document.getElementById('autoWeight').addEventListener('input', function() {
    updateEstimate(this.value, document.getElementById('autoVendorType').value);
});
function updateEstimate(w, vt) {
    var rate = vt==='internal' ? RATE_INT : RATE_3PL;
    var est  = Math.max(MIN_CHG, parseFloat(w||0) * rate);
    document.getElementById('autoEstimate').innerText = 'Rp ' + est.toLocaleString('id-ID');
}
</script>
</body></html>
