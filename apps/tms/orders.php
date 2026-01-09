<?php
session_start();
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }
include '../../koneksi.php';

// ============================================================
// 1. LOGIC UTAMA
// ============================================================
if (isset($_POST['action_type'])) {
    $tenant_id = $_SESSION['tms_tenant_id'];
    $action    = $_POST['action_type'];
    
    // INPUT ORDER
    if ($action == 'create_order') {
        $order_no   = mysqli_real_escape_string($conn, $_POST['order_no']);
        $type       = $_POST['order_type'];
        $origin     = $_POST['origin_id'];
        $dest       = $_POST['destination_id'];
        $sla_date   = $_POST['req_delivery_date'];
        $nav_status = ($tenant_id == 1) ? 'synced' : 'pending'; 

        $q = "INSERT INTO tms_orders (tenant_id, order_no, order_type, origin_id, destination_id, req_delivery_date, status, nav_sync_status)
              VALUES ('$tenant_id', '$order_no', '$type', '$origin', '$dest', '$sla_date', 'new', '$nav_status')";
        
        if(mysqli_query($conn, $q)) {
            echo "<script>alert('Order Berhasil!'); window.location='orders.php';</script>";
        }
    }

    // DISPATCH
    elseif ($action == 'dispatch_order') {
        $order_id   = $_POST['order_id'];
        $vehicle_id = $_POST['vehicle_id'];
        $driver_id  = $_POST['driver_id'];

        $q_cek_armada = mysqli_fetch_assoc(mysqli_query($conn, "SELECT v.plate_number, ven.type as vendor_type FROM tms_vehicles v JOIN tms_vendors ven ON v.vendor_id = ven.id WHERE v.id = '$vehicle_id'"));
        
        $shipment_no = "SHP-" . date('ymd') . rand(100,999);
        mysqli_query($conn, "INSERT INTO tms_shipments (shipment_no, vehicle_id, driver_id, status) VALUES ('$shipment_no', '$vehicle_id', '$driver_id', 'planned')");
        $shipment_id = mysqli_insert_id($conn);

        mysqli_query($conn, "UPDATE tms_orders SET status='planned' WHERE id='$order_id'");
        mysqli_query($conn, "INSERT INTO tms_shipment_stops (shipment_id, order_id, stop_type, sequence_no) VALUES ('$shipment_id', '$order_id', 'pickup', 1)");
        mysqli_query($conn, "INSERT INTO tms_shipment_stops (shipment_id, order_id, stop_type, sequence_no) VALUES ('$shipment_id', '$order_id', 'delivery', 2)");

        echo "<script>alert('Dispatch Berhasil ke ".$q_cek_armada['plate_number']."'); window.location='orders.php';</script>";
    }
}

// DATA VIEW
$locations = mysqli_query($conn, "SELECT * FROM tms_locations WHERE tenant_id = '{$_SESSION['tms_tenant_id']}'");
$drivers = mysqli_query($conn, "SELECT * FROM tms_drivers d JOIN tms_users u ON d.user_id = u.id");
$vehicles = mysqli_query($conn, "SELECT v.*, ven.name as vendor_name FROM tms_vehicles v JOIN tms_vendors ven ON v.vendor_id = ven.id WHERE status='available'");
$orders = mysqli_query($conn, "SELECT o.*, l1.name as origin, l2.name as dest FROM tms_orders o JOIN tms_locations l1 ON o.origin_id = l1.id JOIN tms_locations l2 ON o.destination_id = l2.id ORDER BY o.id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>TMS Operation | LogiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0f172a; --accent: #f59e0b; --bg-body: #f1f5f9; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); overflow-x: hidden; }
        
        /* SIDEBAR FIXED */
        .sidebar { width: 250px; height: 100vh; position: fixed; top: 0; left: 0; background: var(--sidebar-bg); color: #94a3b8; z-index: 1000; transition: 0.3s; }
        .sidebar-brand { padding: 20px; font-size: 1.5rem; font-weight: 800; color: white; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: #94a3b8; padding: 12px 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-right: 3px solid var(--accent); }
        .nav-link i { width: 20px; text-align: center; }

        .main-content { margin-left: 250px; padding: 20px; }
        
        .badge-sales { background-color: #dbeafe; color: #1e40af; border: 1px solid #1e40af; }
        .badge-transfer { background-color: #fef3c7; color: #92400e; border: 1px solid #92400e; }
        .badge-nav-synced { background-color: #dcfce7; color: #166534; font-size: 0.65rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fa fa-map-location-dot text-warning me-2"></i>LogiTrack
        </div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link"><i class="fa fa-gauge-high"></i> Dashboard</a>
            <a href="orders.php" class="nav-link active"><i class="fa fa-truck-ramp-box"></i> Orders (SO/DO)</a>
            <a href="outbound.php" class="nav-link"><i class="fa fa-boxes-packing"></i> Outbound (POD)</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Data Master</div>
            <a href="fleet.php" class="nav-link"><i class="fa fa-truck"></i> Fleet Management</a>
            <a href="drivers.php" class="nav-link"><i class="fa fa-users-gear"></i> Drivers</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Settings</div>
            <a href="billing.php" class="nav-link"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between mb-4">
            <div>
                <h3 class="fw-bold"><i class="fa fa-cubes text-primary"></i> TMS Operation Panel</h3>
                <p class="text-muted mb-0">Tenant: <strong>TACO Group (Demo)</strong> | User: <?=$_SESSION['tms_fullname']?></p>
            </div>
            <div>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalOrder"><i class="fa fa-plus me-1"></i> Create Order (SO/Transfer)</button>
            </div>
        </div>

        <div class="card shadow border-0">
            <div class="card-body">
                <table class="table table-hover align-middle" id="tableTMS">
                    <thead class="table-light">
                        <tr>
                            <th>Order No</th>
                            <th>Type / Flow</th>
                            <th>Rute (Origin -> Dest)</th>
                            <th>SLA Date</th>
                            <th>NAV Status</th> <th>Status Fisik</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $r): ?>
                        <tr>
                            <td class="fw-bold"><?=$r['order_no']?></td>
                            <td>
                                <?php if($r['order_type'] == 'sales'): ?>
                                    <span class="badge badge-sales">SALES (MT)</span>
                                <?php else: ?>
                                    <span class="badge badge-transfer">RKM TRANSFER</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">From:</small> <b><?=$r['origin']?></b><br>
                                <small class="text-muted">To:</small> <b><?=$r['dest']?></b>
                            </td>
                            <td><?=date('d M Y', strtotime($r['req_delivery_date']))?></td>
                            <td>
                                <?php if($r['nav_sync_status'] == 'synced'): ?>
                                    <span class="badge badge-nav-synced"><i class="fa fa-check"></i> SYNCED NAV</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">PENDING</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-info text-dark text-uppercase"><?=$r['status']?></span></td>
                            <td>
                                <?php if($r['status'] == 'new'): ?>
                                    <button class="btn btn-sm btn-warning fw-bold" onclick="openDispatch('<?=$r['id']?>', '<?=$r['order_no']?>')"><i class="fa fa-truck"></i> Dispatch</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="fa fa-eye"></i> Track</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalOrder">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Order (Simulasi NAV)</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action_type" value="create_order">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>No. SO / DO (Dari NAV)</label>
                            <input type="text" name="order_no" class="form-control" value="SO-<?=time()?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Tipe Transaksi</label>
                            <select name="order_type" class="form-select">
                                <option value="sales">Sales (Gudang PGD -> Toko MT)</option>
                                <option value="transfer">Stock Transfer (RKM A -> RKM B)</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label>Asal (Origin)</label>
                                <select name="origin_id" class="form-select">
                                    <?php foreach($locations as $l): ?>
                                    <option value="<?=$l['id']?>"><?=$l['name']?> (<?=$l['type']?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label>Tujuan (Dest)</label>
                                <select name="destination_id" class="form-select">
                                    <?php $locations->data_seek(0); foreach($locations as $l): ?>
                                    <option value="<?=$l['id']?>"><?=$l['name']?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>SLA Delivery Date</label>
                            <input type="date" name="req_delivery_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Create Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDispatch">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispatch Order: <span id="dispOrderNo"></span></h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action_type" value="dispatch_order">
                    <input type="hidden" name="order_id" id="dispOrderId">
                    <div class="modal-body">
                        <div class="alert alert-info small">Pilih armada yang tersedia. Bisa Internal atau 3PL (JNE/Vendor).</div>
                        <div class="mb-3">
                            <label>Pilih Kendaraan</label>
                            <select name="vehicle_id" class="form-select" required>
                                <?php foreach($vehicles as $v): ?>
                                <option value="<?=$v['id']?>"><?=$v['plate_number']?> - <?=$v['vehicle_type']?> (<?=$v['vendor_name']?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Pilih Driver</label>
                            <select name="driver_id" class="form-select" required>
                                <?php foreach($drivers as $d): ?>
                                <option value="<?=$d['id']?>"><?=$d['fullname']?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning fw-bold w-100">Assign & Dispatch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() { $('#tableTMS').DataTable(); });
        function openDispatch(id, no) {
            document.getElementById('dispOrderId').value = id;
            document.getElementById('dispOrderNo').innerText = no;
            new bootstrap.Modal(document.getElementById('modalDispatch')).show();
        }
    </script>
</body>
</html>