<?php
session_start();
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }
include '../../koneksi.php';

// ============================================================
// 1. LOGIC GENERATE DUMMY DATA (SIMULASI NAV)
// ============================================================
if (isset($_POST['act']) && $_POST['act'] == 'simulate_nav') {
    $tenant_id = $_SESSION['tms_tenant_id'];
    
    // Bikin SO Header
    $so_no = "SO-NAV-" . rand(1000,9999);
    mysqli_query($conn, "INSERT INTO tms_sales_orders (tenant_id, so_number, customer_name, so_date, status) VALUES ('$tenant_id', '$so_no', 'Mitra10 Cibubur (MT)', NOW(), 'open')");
    $so_id = mysqli_insert_id($conn);

    // Bikin DN (Surat Jalan)
    $dn_no = "DN-" . rand(10000,99999);
    mysqli_query($conn, "INSERT INTO tms_delivery_notes (so_id, dn_number, tgl_kirim, origin_id, dest_id, status) VALUES ('$so_id', '$dn_no', NOW(), 1, 2, 'draft')");
    $dn_id = mysqli_insert_id($conn);

    // Bikin LPN & Items
    for ($i=1; $i<=2; $i++) {
        $lpn_no = "LPN-" . $dn_no . "-0" . $i;
        mysqli_query($conn, "INSERT INTO tms_lpns (dn_id, lpn_number, total_items, status_check) VALUES ('$dn_id', '$lpn_no', 10, 'pending')");
        $lpn_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO tms_items (lpn_id, item_code, item_name, qty_ordered, qty_received) VALUES ('$lpn_id', 'HPL-001', 'HPL White Gloss', 5, 0)");
        mysqli_query($conn, "INSERT INTO tms_items (lpn_id, item_code, item_name, qty_ordered, qty_received) VALUES ('$lpn_id', 'EDG-002', 'Edging PVC 22mm', 5, 0)");
    }
    echo "<script>window.location='outbound.php';</script>";
}

// ============================================================
// 2. LOGIC DISPATCH (TTD TACO)
// ============================================================
if (isset($_POST['act']) && $_POST['act'] == 'dispatch_confirm') {
    $dn_id = $_POST['dn_id'];
    $sign  = $_POST['signature_data'];
    mysqli_query($conn, "UPDATE tms_delivery_notes SET sign_sender = '$sign', sign_sender_name = 'Admin Gudang', status = 'in_transit' WHERE id = '$dn_id'");
    echo "<script>window.location='outbound.php';</script>";
}

// ============================================================
// 3. LOGIC RECEIVING (TTD MT / STORE)
// ============================================================
if (isset($_POST['act']) && $_POST['act'] == 'receive_confirm') {
    $dn_id = $_POST['dn_id'];
    $sign  = $_POST['signature_data'];
    // Update Qty
    if(isset($_POST['qty_received'])){
        foreach ($_POST['qty_received'] as $item_id => $qty) {
            mysqli_query($conn, "UPDATE tms_items SET qty_received = '$qty' WHERE id = '$item_id'");
        }
    }
    mysqli_query($conn, "UPDATE tms_delivery_notes SET sign_receiver = '$sign', sign_receiver_name = 'Store Manager', status = 'delivered' WHERE id = '$dn_id'");
    echo "<script>window.location='outbound.php';</script>";
}

// AMBIL DATA DN
$data_dn = mysqli_query($conn, "SELECT dn.*, so.so_number, so.customer_name FROM tms_delivery_notes dn JOIN tms_sales_orders so ON dn.so_id = so.id ORDER BY dn.id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Outbound POD | LogiTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        
        /* CARD & SIGNATURE */
        .signature-pad { border: 2px dashed #94a3b8; border-radius: 12px; cursor: crosshair; width: 100%; height: 200px; background: #fff; }
        .step-badge { font-size: 0.75rem; padding: 6px 12px; border-radius: 50px; font-weight: 600; }
        .bg-draft { background: #e2e8f0; color: #475569; }
        .bg-transit { background: #fef3c7; color: #b45309; }
        .bg-done { background: #dcfce7; color: #15803d; }

        /* RESPONSIVE */
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"><i class="fa fa-map-location-dot text-warning me-2"></i>LogiTrack</div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link"><i class="fa fa-gauge-high"></i> Dashboard</a>
            <a href="orders.php" class="nav-link"><i class="fa fa-truck-ramp-box"></i> Orders (SO/DO)</a>
            <a href="outbound.php" class="nav-link active"><i class="fa fa-boxes-packing"></i> Outbound (POD)</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Data Master</div>
            <a href="fleet.php" class="nav-link"><i class="fa fa-truck"></i> Fleet Management</a>
            <a href=drivers.php" class="nav-link"><i class="fa fa-users-gear"></i> Drivers</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Settings</div>
            <a href="billing.php" class="nav-link"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0"><i class="fa fa-signature text-primary"></i> Digital POD</h3>
                <p class="text-muted small mb-0">Proof of Delivery & Signature</p>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="simulate_nav">
                <button type="submit" class="btn btn-dark btn-sm shadow-sm"><i class="fa fa-cloud-arrow-down me-2"></i> Pull NAV Data</button>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">No DN (Surat Jalan)</th>
                                <th>Tujuan</th>
                                <th>Status Flow</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($data_dn)): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?=$row['dn_number']?> <br> <small class="text-muted fw-normal"><?=$row['so_number']?></small></td>
                                <td><?=$row['customer_name']?></td>
                                <td>
                                    <?php if($row['status']=='draft'): ?>
                                        <span class="badge step-badge bg-draft">1. Warehouse</span>
                                    <?php elseif($row['status']=='in_transit'): ?>
                                        <span class="badge step-badge bg-transit">2. On The Way</span>
                                    <?php else: ?>
                                        <span class="badge step-badge bg-done">3. Delivered</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($row['status']=='draft'): ?>
                                        <button class="btn btn-sm btn-primary fw-bold" onclick="openDispatch(<?=$row['id']?>, '<?=$row['dn_number']?>')"><i class="fa fa-signature"></i> Approve</button>
                                    <?php elseif($row['status']=='in_transit'): ?>
                                        <button class="btn btn-sm btn-success fw-bold" onclick="openReceive(<?=$row['id']?>, '<?=$row['dn_number']?>')"><i class="fa fa-box-open"></i> Receive</button>
                                    <?php else: ?>
                                        <a href="print_dn.php?id=<?=$row['id']?>" target="_blank" class="btn btn-sm btn-outline-dark">
                                            <i class="fa fa-print"></i> Cetak SJ
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDispatch">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispatch Confirmation</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="small text-muted">Saya menyatakan barang untuk <b><span id="disp_dn"></span></b> lengkap.</p>
                    <canvas id="sigPadSender" class="signature-pad"></canvas>
                    <button class="btn btn-sm btn-light border mt-2 w-100" onclick="clearPad('sender')">Clear Signature</button>
                </div>
                <div class="modal-footer">
                    <form method="POST" onsubmit="return saveSig('sender')" class="w-100">
                        <input type="hidden" name="act" value="dispatch_confirm">
                        <input type="hidden" name="dn_id" id="disp_id">
                        <input type="hidden" name="signature_data" id="sigDataSender">
                        <button type="submit" class="btn btn-primary w-100">Confirm & Release</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReceive">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Penerimaan Barang</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" onsubmit="return saveSig('receiver')">
                    <input type="hidden" name="act" value="receive_confirm">
                    <input type="hidden" name="dn_id" id="recv_id">
                    <input type="hidden" name="signature_data" id="sigDataReceiver">
                    
                    <div class="modal-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm small mb-0">
                                <thead class="bg-light"><tr><th>Item</th><th>Qty SO</th><th>Qty Diterima</th></tr></thead>
                                <tbody>
                                    <tr><td>HPL White Gloss</td><td class="text-center">5</td><td><input type="number" name="qty_received[1]" class="form-control form-control-sm" value="5"></td></tr>
                                    <tr><td>Edging PVC</td><td class="text-center">5</td><td><input type="number" name="qty_received[2]" class="form-control form-control-sm" value="5"></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <label class="fw-bold small mb-2">Tanda Tangan Penerima (Store):</label>
                        <canvas id="sigPadReceiver" class="signature-pad" style="height: 150px;"></canvas>
                        <button type="button" class="btn btn-sm btn-light border mt-2 w-100" onclick="clearPad('receiver')">Clear Signature</button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success w-100">Selesai (Update NAV)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        var padSender, padReceiver;
        window.onload = function() {
            padSender = new SignaturePad(document.getElementById('sigPadSender'));
            padReceiver = new SignaturePad(document.getElementById('sigPadReceiver'));
        };
        function clearPad(who) { (who === 'sender') ? padSender.clear() : padReceiver.clear(); }
        function saveSig(who) {
            var pad = (who === 'sender') ? padSender : padReceiver;
            if(pad.isEmpty()) { alert('Tanda tangan wajib diisi!'); return false; }
            document.getElementById((who === 'sender') ? 'sigDataSender' : 'sigDataReceiver').value = pad.toDataURL();
            return true;
        }
        function openDispatch(id, no) {
            document.getElementById('disp_id').value = id;
            document.getElementById('disp_dn').innerText = no;
            new bootstrap.Modal(document.getElementById('modalDispatch')).show();
            setTimeout(() => { padSender.on(); }, 500); // Fix canvas render issue
        }
        function openReceive(id, no) {
            document.getElementById('recv_id').value = id;
            new bootstrap.Modal(document.getElementById('modalReceive')).show();
            setTimeout(() => { padReceiver.on(); }, 500);
        }
    </script>
</body>
</html>