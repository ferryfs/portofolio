<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

$tenant_id = $_SESSION['tms_tenant_id'] ?? 1;
$msg = ''; $msg_type = '';

if (isset($_POST['action_type'])) {
    if (!verifyCSRFToken()) die("Security Alert: Invalid Token.");
    $action = sanitizeInput($_POST['action_type']);

    // CREATE ORDER
    if ($action == 'create_order') {
        $order_no  = sanitizeInput($_POST['order_no']);
        $type      = sanitizeInput($_POST['order_type']);
        $origin    = sanitizeInt($_POST['origin_id']);
        $dest      = sanitizeInt($_POST['destination_id']);
        $sla_date  = sanitizeInput($_POST['req_delivery_date']);
        $weight    = (float)($_POST['total_weight'] ?? 0);
        $nav_status= 'pending';

        safeQuery($pdo,
            "INSERT INTO tms_orders (tenant_id,order_no,order_type,origin_id,destination_id,req_delivery_date,status,nav_sync_status,total_weight)
             VALUES (?,?,?,?,?,?,'new',?,?)",
            [$tenant_id,$order_no,$type,$origin,$dest,$sla_date,$nav_status,$weight]);
        $msg = "Order $order_no berhasil dibuat."; $msg_type = 'success';
    }

    // DISPATCH → buat shipment
    elseif ($action == 'dispatch_order') {
        $order_id   = sanitizeInt($_POST['order_id']);
        $vehicle_id = sanitizeInt($_POST['vehicle_id']);
        $driver_id  = sanitizeInt($_POST['driver_id']);
        $veh = safeGetOne($pdo, "SELECT plate_number FROM tms_vehicles WHERE id=?", [$vehicle_id]);
        $shipment_no = "SHP-" . date('ymd') . rand(100,999);

        try {
            $pdo->beginTransaction();
            safeQuery($pdo, "INSERT INTO tms_shipments (shipment_no,vehicle_id,driver_id,status) VALUES (?,?,?,'planned')", [$shipment_no,$vehicle_id,$driver_id]);
            $ship_id = $pdo->lastInsertId();
            safeQuery($pdo, "UPDATE tms_orders SET status='planned' WHERE id=?", [$order_id]);
            safeQuery($pdo, "INSERT INTO tms_shipment_stops (shipment_id,order_id,stop_type,sequence_no,status) VALUES (?,?,'pickup',1,'pending')", [$ship_id,$order_id]);
            safeQuery($pdo, "INSERT INTO tms_shipment_stops (shipment_id,order_id,stop_type,sequence_no,status) VALUES (?,?,'delivery',2,'pending')", [$ship_id,$order_id]);
            safeQuery($pdo, "UPDATE tms_vehicles SET status='busy' WHERE id=?", [$vehicle_id]);
            $pdo->commit();
            $msg = "Dispatch berhasil ke {$veh['plate_number']}. Shipment: $shipment_no"; $msg_type = 'success';
        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "Gagal dispatch: " . $e->getMessage(); $msg_type = 'danger';
        }
    }

    // UPDATE STATUS SHIPMENT
    elseif ($action == 'update_status') {
        $ship_id = sanitizeInt($_POST['shipment_id']);
        $status  = sanitizeInput($_POST['new_status']);
        $allowed = ['planned','in_transit','arrived','completed','failed','cancelled'];
        if (in_array($status, $allowed)) {
            safeQuery($pdo, "UPDATE tms_shipments SET status=? WHERE id=?", [$status, $ship_id]);
            // Jika completed/failed, bebaskan kendaraan
            if (in_array($status, ['completed','failed','cancelled'])) {
                $ship = safeGetOne($pdo, "SELECT vehicle_id FROM tms_shipments WHERE id=?", [$ship_id]);
                if ($ship) safeQuery($pdo, "UPDATE tms_vehicles SET status='available' WHERE id=?", [$ship['vehicle_id']]);
            }
            $msg = "Status shipment diperbarui ke: $status"; $msg_type = 'success';
        }
    }
}

$locations = safeGetAll($pdo, "SELECT * FROM tms_locations WHERE tenant_id=?", [$tenant_id]);
$drivers   = safeGetAll($pdo, "SELECT id, name FROM tms_drivers");
$vehicles  = safeGetAll($pdo, "SELECT v.id, v.plate_number, v.vehicle_type, v.capacity_weight, vn.name as vendor_name FROM tms_vehicles v LEFT JOIN tms_vendors vn ON v.vendor_id=vn.id WHERE v.status='available'");

$orders = safeGetAll($pdo,
    "SELECT o.*, l1.name as origin, l2.name as dest,
     s.shipment_no, s.status as ship_status, s.id as ship_id,
     d.name as driver_name, v.plate_number
     FROM tms_orders o
     LEFT JOIN tms_locations l1 ON o.origin_id=l1.id
     LEFT JOIN tms_locations l2 ON o.destination_id=l2.id
     LEFT JOIN tms_shipment_stops ss ON ss.order_id=o.id AND ss.stop_type='pickup'
     LEFT JOIN tms_shipments s ON ss.shipment_id=s.id
     LEFT JOIN tms_drivers d ON s.driver_id=d.id
     LEFT JOIN tms_vehicles v ON s.vehicle_id=v.id
     WHERE o.tenant_id=? ORDER BY o.id DESC", [$tenant_id]);

$page_title  = 'Orders';
$active_page = 'orders';
include '_head.php';
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h3><i class="fa fa-truck-ramp-box text-warning me-2"></i>TMS Operation Panel</h3>
            <p>Tenant: <?= htmlspecialchars($_SESSION['tms_tenant'] ?? 'TACO Group') ?></p>
        </div>
        <button class="btn btn-accent shadow-sm" data-bs-toggle="modal" data-bs-target="#modalOrder">
            <i class="fa fa-plus me-2"></i>Create Order
        </button>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
        <i class="fa fa-<?= $msg_type=='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="data-card">
        <table class="table" id="tableTMS">
            <thead><tr><th>Order No</th><th>Type</th><th>Rute</th><th>SLA Date</th><th>Berat</th><th>NAV</th><th>Status Order</th><th>Shipment</th><th>Action</th></tr></thead>
            <tbody>
            <?php if(empty($orders)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada order.</td></tr>
            <?php else: foreach($orders as $r):
                $status_map = ['new'=>['bs-muted','New'],'planned'=>['bs-primary','Planned'],'in_transit'=>['bs-warning','In Transit'],'arrived'=>['bs-info','Arrived'],'completed'=>['bs-success','Completed'],'failed'=>['bs-danger','Failed']];
                $ss = $status_map[$r['ship_status'] ?? 'new'] ?? ['bs-muted', $r['ship_status']];
                $os = $status_map[$r['status']] ?? ['bs-muted', $r['status']];
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($r['order_no']) ?></td>
                <td><span class="badge-soft <?= $r['order_type']=='sales'?'bs-primary':'bs-orange' ?>"><?= strtoupper($r['order_type']) ?></span></td>
                <td style="font-size:0.8rem;">
                    <div><i class="fa fa-circle-dot me-1 text-success" style="font-size:0.55rem;"></i><?= htmlspecialchars($r['origin']) ?></div>
                    <div><i class="fa fa-location-dot me-1 text-danger" style="font-size:0.55rem;"></i><?= htmlspecialchars($r['dest']) ?></div>
                </td>
                <td style="font-size:0.82rem;"><?= date('d M Y', strtotime($r['req_delivery_date'])) ?></td>
                <td style="font-size:0.82rem;"><?= number_format($r['total_weight'],0) ?> kg</td>
                <td><span class="badge-soft <?= $r['nav_sync_status']=='synced'?'bs-success':'bs-muted' ?>"><?= $r['nav_sync_status']=='synced'?'SYNCED':'PENDING' ?></span></td>
                <td><span class="badge-soft <?= $os[0] ?>"><?= $os[1] ?></span></td>
                <td style="font-size:0.78rem;">
                    <?php if($r['shipment_no']): ?>
                    <div class="fw-bold"><?= htmlspecialchars($r['shipment_no']) ?></div>
                    <div style="color:var(--muted);"><?= htmlspecialchars($r['driver_name']??'-') ?> | <?= htmlspecialchars($r['plate_number']??'-') ?></div>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td>
                    <?php if($r['status'] == 'new'): ?>
                    <button class="btn btn-sm btn-accent fw-bold" onclick="openDispatch('<?=$r['id']?>','<?=htmlspecialchars($r['order_no'])?>')">
                        <i class="fa fa-truck me-1"></i>Dispatch
                    </button>
                    <?php elseif($r['ship_id'] && !in_array($r['ship_status'],['completed','failed','cancelled'])): ?>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openStatusModal('<?=$r['ship_id']?>','<?=$r['shipment_no']?>','<?=$r['ship_status']?>')">
                        <i class="fa fa-arrows-rotate me-1"></i>Update Status
                    </button>
                    <?php else: ?>
                    <span class="badge-soft bs-muted">Selesai</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CREATE ORDER -->
<div class="modal fade" id="modalOrder" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Buat Order Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?><input type="hidden" name="action_type" value="create_order">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">No. SO / DO</label><input type="text" name="order_no" class="form-control" value="SO-<?= time() ?>" required></div>
                <div class="mb-3"><label class="form-label">Tipe Transaksi</label>
                    <select name="order_type" class="form-select"><option value="sales">Sales</option><option value="transfer">Stock Transfer</option></select></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label">Asal</label>
                        <select name="origin_id" class="form-select"><?php foreach($locations as $l): ?><option value="<?=$l['id']?>"><?=$l['name']?></option><?php endforeach; ?></select></div>
                    <div class="col-6"><label class="form-label">Tujuan</label>
                        <select name="destination_id" class="form-select"><?php foreach($locations as $l): ?><option value="<?=$l['id']?>"><?=$l['name']?></option><?php endforeach; ?></select></div>
                </div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">SLA Date</label><input type="date" name="req_delivery_date" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">Berat (kg)</label><input type="number" name="total_weight" class="form-control" placeholder="500"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-accent w-100 fw-bold">Create Order</button></div>
        </form>
    </div></div>
</div>

<!-- MODAL DISPATCH -->
<div class="modal fade" id="modalDispatch" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Dispatch: <span id="dispOrderNo"></span></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?><input type="hidden" name="action_type" value="dispatch_order"><input type="hidden" name="order_id" id="dispOrderId">
            <div class="modal-body">
                <?php if(empty($vehicles)): ?>
                <div class="alert alert-warning border-0 rounded-3 py-2 small">Tidak ada armada tersedia saat ini.</div>
                <?php endif; ?>
                <div class="mb-3"><label class="form-label">Pilih Kendaraan</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">— Pilih Armada —</option>
                        <?php foreach($vehicles as $v): ?>
                        <option value="<?=$v['id']?>"><?= htmlspecialchars($v['plate_number']) ?> (<?= $v['vehicle_type'] ?> — <?= number_format($v['capacity_weight'],0) ?>kg)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Pilih Driver</label>
                    <select name="driver_id" class="form-select" required>
                        <option value="">— Pilih Supir —</option>
                        <?php foreach($drivers as $d): ?><option value="<?=$d['id']?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-accent w-100 fw-bold">Assign & Dispatch</button></div>
        </form>
    </div></div>
</div>

<!-- MODAL UPDATE STATUS -->
<div class="modal fade" id="modalStatus" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">Update Status Shipment</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST"><?= csrfTokenField() ?><input type="hidden" name="action_type" value="update_status"><input type="hidden" name="shipment_id" id="statusShipId">
            <div class="modal-body">
                <div class="mb-2 text-muted small fw-bold" id="statusShipNo"></div>
                <label class="form-label">Status Baru</label>
                <select name="new_status" class="form-select" id="statusSelect">
                    <option value="planned">Planned</option>
                    <option value="in_transit">In Transit</option>
                    <option value="arrived">Arrived</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-accent w-100 fw-bold">Update</button></div>
        </form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() { $('#tableTMS').DataTable({ order: [[0,'desc']] }); });
function openDispatch(id, no) {
    document.getElementById('dispOrderId').value = id;
    document.getElementById('dispOrderNo').innerText = no;
    new bootstrap.Modal(document.getElementById('modalDispatch')).show();
}
function openStatusModal(shipId, shipNo, currentStatus) {
    document.getElementById('statusShipId').value = shipId;
    document.getElementById('statusShipNo').innerText = 'Shipment: ' + shipNo;
    document.getElementById('statusSelect').value = currentStatus;
    new bootstrap.Modal(document.getElementById('modalStatus')).show();
}
</script>
</body></html>
