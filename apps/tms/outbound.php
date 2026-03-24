<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

$msg = ''; $msg_type = '';

// SIMULATE PULL NAV DATA
if (isset($_POST['act']) && $_POST['act'] == 'simulate_nav') {
    if(!verifyCSRFToken()) die("Invalid Token");
    $tenant_id = $_SESSION['tms_tenant_id'] ?? 1;
    $so_no = "SO-NAV-" . rand(1000,9999);
    safeQuery($pdo, "INSERT INTO tms_sales_orders (tenant_id,so_number,customer_name,so_date,status) VALUES (?,?,'Mitra10 Cibubur (MT)',NOW(),'open')", [$tenant_id,$so_no]);
    $so_id = $pdo->lastInsertId();
    $dn_no = "DN-" . rand(10000,99999);
    safeQuery($pdo, "INSERT INTO tms_delivery_notes (so_id,dn_number,tgl_kirim,origin_id,dest_id,status) VALUES (?,?,NOW(),1,2,'draft')", [$so_id,$dn_no]);
    $dn_id = $pdo->lastInsertId();
    for ($i=1; $i<=2; $i++) {
        $lpn_no = "LPN-".$dn_no."-0".$i;
        safeQuery($pdo, "INSERT INTO tms_lpns (dn_id,lpn_number,total_items,status_check) VALUES (?,?,10,'pending')", [$dn_id,$lpn_no]);
        $lpn_id = $pdo->lastInsertId();
        safeQuery($pdo, "INSERT INTO tms_items (lpn_id,item_code,item_name,qty_ordered,qty_received) VALUES (?,'HPL-001','HPL White Gloss',5,0)", [$lpn_id]);
        safeQuery($pdo, "INSERT INTO tms_items (lpn_id,item_code,item_name,qty_ordered,qty_received) VALUES (?,'EDG-002','Edging PVC 22mm',5,0)", [$lpn_id]);
    }
    header("Location: outbound.php?msg=nav_pulled&dn=".urlencode($dn_no));
    exit();
}

// DISPATCH (Sender sign)
if (isset($_POST['act']) && $_POST['act'] == 'dispatch_confirm') {
    if(!verifyCSRFToken()) die("Invalid Token");
    $dn_id = sanitizeInt($_POST['dn_id']);
    $sign  = $_POST['signature_data'];
    safeQuery($pdo, "UPDATE tms_delivery_notes SET sign_sender=?,sign_sender_name='Admin Gudang',status='in_transit' WHERE id=?", [$sign,$dn_id]);
    header("Location: outbound.php?msg=released");
    exit();
}

// RECEIVE (Receiver sign + qty + exception check)
if (isset($_POST['act']) && $_POST['act'] == 'receive_confirm') {
    if(!verifyCSRFToken()) die("Invalid Token");
    $dn_id = sanitizeInt($_POST['dn_id']);
    $sign  = $_POST['signature_data'];
    $has_shortage = false; $has_damage = false;

    try {
        $pdo->beginTransaction();

        if(isset($_POST['qty_received'])) {
            foreach($_POST['qty_received'] as $item_id => $qty) {
                $item_id = (int)$item_id;
                $qty     = (int)$qty;
                $item    = safeGetOne($pdo, "SELECT qty_ordered FROM tms_items WHERE id=?", [$item_id]);
                if($item && $qty < $item['qty_ordered']) $has_shortage = true;
                safeQuery($pdo, "UPDATE tms_items SET qty_received=? WHERE id=?", [$qty,$item_id]);
            }
        }

        if(isset($_POST['qty_damaged'])) {
            foreach($_POST['qty_damaged'] as $item_id => $qty) {
                $item_id = (int)$item_id;
                $qty     = (int)$qty;
                if($qty > 0) {
                    $has_damage = true;
                    safeQuery($pdo, "UPDATE tms_items SET remarks=CONCAT(IFNULL(remarks,''),' [DAMAGED: ?]') WHERE id=?", [$qty, $item_id]);
                }
            }
        }

        safeQuery($pdo, "UPDATE tms_delivery_notes SET sign_receiver=?,sign_receiver_name='Store Manager',status='delivered' WHERE id=?", [$sign,$dn_id]);
        $pdo->commit();

        if($has_shortage || $has_damage) {
            header("Location: outbound.php?msg=received_exception");
        } else {
            header("Location: outbound.php?msg=received_ok");
        }
        exit();
    } catch(Exception $e) {
        $pdo->rollBack();
        header("Location: outbound.php?msg=receive_failed");
        exit();
    }
}

// RESOLVE EXCEPTION
if (isset($_POST['act']) && $_POST['act'] == 'resolve_exception') {
    if(!verifyCSRFToken()) die("Invalid Token");
    $dn_id      = sanitizeInt($_POST['dn_id']);
    $action     = sanitizeInput($_POST['resolve_action']);
    $notes      = sanitizeInput($_POST['resolve_notes'] ?? '');
    $allowed    = ['backorder','vendor_claim','writeoff','resolved'];

    if(in_array($action, $allowed)) {
        $action_label = [
            'backorder'     => 'BACKORDER — akan dikirim ulang di pengiriman berikutnya',
            'vendor_claim'  => 'KLAIM VENDOR — dilaporkan ke transporter/vendor',
            'writeoff'      => 'WRITE-OFF — dicatat sebagai kerugian',
            'resolved'      => 'RESOLVED — exception sudah ditangani',
        ][$action];

        // Simpan resolusi di field sign_receiver_name sebagai append (workaround tanpa tabel baru)
        // Atau update remarks di semua item terkait
        $dn = safeGetOne($pdo, "SELECT * FROM tms_delivery_notes WHERE id=?", [$dn_id]);
        if($dn) {
            $resolve_tag = ' [RESOLVED: ' . $action_label . ($notes ? ' — ' . $notes : '') . ']';
            $lpns = safeGetAll($pdo, "SELECT id FROM tms_lpns WHERE dn_id=?", [$dn_id]);
            foreach($lpns as $lpn) {
                safeQuery($pdo,
                    "UPDATE tms_items SET remarks = CONCAT(IFNULL(remarks,''), ?) WHERE lpn_id=? AND (qty_received < qty_ordered OR remarks LIKE '%DAMAGED%')",
                    [$resolve_tag, $lpn['id']]);
            }
            safeQuery($pdo, "UPDATE tms_delivery_notes SET status='resolved' WHERE id=?", [$dn_id]);
            header("Location: outbound.php?msg=resolved&dn=".urlencode($dn['dn_number']));
            exit();
        }
    }
}

// Flash message dari redirect
$msg = ''; $msg_type = '';
$flash_map = [
    'nav_pulled'        => ['success', 'Data NAV berhasil di-pull. DN: ' . htmlspecialchars($_GET['dn'] ?? '')],
    'released'          => ['success', 'Surat jalan berhasil dikeluarkan — status In Transit.'],
    'received_ok'       => ['success', 'Barang diterima lengkap. Status: Delivered.'],
    'received_exception'=> ['warning', '⚠️ Barang diterima dengan exception (shortage/damage). Lihat panel Exception Report di bawah.'],
    'receive_failed'    => ['danger',  'Gagal konfirmasi penerimaan. Coba lagi.'],
    'resolved'          => ['success', 'Exception DN ' . htmlspecialchars($_GET['dn'] ?? '') . ' berhasil di-resolve.'],
];
$flash = $_GET['msg'] ?? '';
if($flash && isset($flash_map[$flash])) {
    [$msg_type, $msg] = $flash_map[$flash];
}

$data_dn = safeGetAll($pdo,
    "SELECT dn.*, so.so_number, so.customer_name FROM tms_delivery_notes dn
     JOIN tms_sales_orders so ON dn.so_id=so.id ORDER BY dn.id DESC");

// Exceptions: DN dengan shortage/damage yang BELUM resolved
$exceptions = safeGetAll($pdo,
    "SELECT dn.id as dn_id, dn.dn_number, dn.status, so.customer_name,
     COUNT(DISTINCT CASE WHEN i.qty_received < i.qty_ordered THEN i.id END) as shortage_items,
     GROUP_CONCAT(DISTINCT CASE WHEN i.remarks LIKE '%DAMAGED%' AND i.remarks NOT LIKE '%RESOLVED%' THEN i.item_name END) as damaged_items,
     SUM(CASE WHEN i.qty_received < i.qty_ordered THEN i.qty_ordered - i.qty_received ELSE 0 END) as total_shortage_qty
     FROM tms_delivery_notes dn
     JOIN tms_sales_orders so ON dn.so_id=so.id
     JOIN tms_lpns l ON l.dn_id=dn.id
     JOIN tms_items i ON i.lpn_id=l.id
     WHERE dn.status != 'resolved'
     AND (
         (i.qty_received < i.qty_ordered)
         OR (i.remarks LIKE '%DAMAGED%' AND i.remarks NOT LIKE '%RESOLVED%')
     )
     GROUP BY dn.id HAVING shortage_items > 0 OR damaged_items IS NOT NULL");

$page_title  = 'Outbound POD';
$active_page = 'outbound';
include '_head.php';
?>
<style>
.signature-pad { border: 2px dashed #cbd5e1; border-radius: 12px; cursor: crosshair; width: 100%; height: 180px; background: #fff; }
.step-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; font-weight: 600; }
.exception-card { background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 12px 16px; margin-bottom: 8px; }
</style>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h3><i class="fa fa-signature text-warning me-2"></i>Digital POD</h3>
            <p>Proof of Delivery & Exception Management</p>
        </div>
        <form method="POST" style="display:inline;">
            <?= csrfTokenField() ?>
            <input type="hidden" name="act" value="simulate_nav">
            <button type="submit" class="btn btn-dark shadow-sm"><i class="fa fa-cloud-arrow-down me-2"></i>Pull NAV Data</button>
        </form>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg_type ?> border-0 rounded-3 py-2 mb-3 small fw-bold">
        <i class="fa fa-<?= $msg_type=='success'?'check-circle':($msg_type=='warning'?'triangle-exclamation':'exclamation-circle') ?> me-2"></i><?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- EXCEPTION PANEL -->
    <?php if(!empty($exceptions)): ?>
    <div class="data-card mb-4" id="exceptions">
        <div class="data-card-header" style="background:#fff5f5; border-color:#fecaca;">
            <h6 style="color:#991b1b;"><i class="fa fa-triangle-exclamation me-2"></i>Exception Report (<?= count($exceptions) ?> DN perlu ditangani)</h6>
            <span class="badge-soft bs-danger" style="font-size:0.72rem;">Klik "Resolve" untuk menangani</span>
        </div>
        <div class="p-3">
        <?php foreach($exceptions as $ex): ?>
        <div class="exception-card">
            <div class="d-flex justify-content-between align-items-start">
                <div style="flex:1;">
                    <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($ex['dn_number']) ?></div>
                    <div style="font-size:0.78rem; color:#64748b; margin-bottom:6px;"><?= htmlspecialchars($ex['customer_name']) ?></div>
                    <?php if($ex['shortage_items'] > 0): ?>
                    <span class="badge-soft bs-danger me-1"><i class="fa fa-box-open me-1"></i><?= $ex['shortage_items'] ?> item shortage (<?= $ex['total_shortage_qty'] ?> qty kurang)</span>
                    <?php endif; ?>
                    <?php if($ex['damaged_items']): ?>
                    <span class="badge-soft bs-warning"><i class="fa fa-box me-1"></i>Damaged: <?= htmlspecialchars($ex['damaged_items']) ?></span>
                    <?php endif; ?>
                </div>
                <button class="btn btn-sm fw-bold ms-3"
                    style="background:#dc2626; color:#fff; border-radius:8px; white-space:nowrap;"
                    onclick="openResolve(<?= $ex['dn_id'] ?>, '<?= htmlspecialchars($ex['dn_number']) ?>', <?= $ex['shortage_items'] ?>, '<?= htmlspecialchars($ex['damaged_items'] ?? '') ?>')">
                    <i class="fa fa-wrench me-1"></i>Resolve
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- DN TABLE -->
    <div class="data-card">
        <div class="data-card-header">
            <h6><i class="fa fa-list me-2"></i>Delivery Notes</h6>
        </div>
        <table class="table">
            <thead><tr><th>No DN / SO</th><th>Customer</th><th>Tgl Kirim</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach($data_dn as $row):
                $sc = ['draft'=>'bs-muted','in_transit'=>'bs-warning','delivered'=>'bs-success','resolved'=>'bs-primary'][$row['status']] ?? 'bs-muted';
                $sl = ['draft'=>'1. Warehouse','in_transit'=>'2. On The Way','delivered'=>'3. Delivered','resolved'=>'✅ Resolved'][$row['status']] ?? ucfirst($row['status']);
            ?>
            <tr>
                <td>
                    <div class="fw-bold text-primary"><?= htmlspecialchars($row['dn_number']) ?></div>
                    <div style="font-size:0.72rem; color:var(--muted);"><?= htmlspecialchars($row['so_number']) ?></div>
                </td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td style="font-size:0.82rem;"><?= date('d M Y', strtotime($row['tgl_kirim'])) ?></td>
                <td><span class="badge-soft <?= $sc ?>"><?= $sl ?></span></td>
                <td class="text-end">
                    <?php if($row['status']=='draft'): ?>
                    <button class="btn btn-sm btn-accent fw-bold" onclick="openDispatch(<?=$row['id']?>,'<?=$row['dn_number']?>')">
                        <i class="fa fa-signature me-1"></i>Approve & Release
                    </button>
                    <?php elseif($row['status']=='in_transit'): ?>
                    <button class="btn btn-sm btn-success text-white fw-bold" onclick="openReceive(<?=$row['id']?>,'<?=$row['dn_number']?>')">
                        <i class="fa fa-box-open me-1"></i>Receive
                    </button>
                    <?php elseif($row['status']=='delivered'): ?>
                    <a href="print_dn.php?id=<?=$row['id']?>" target="_blank" class="btn btn-sm btn-outline-dark">
                        <i class="fa fa-print me-1"></i>Cetak SJ
                    </a>
                    <?php elseif($row['status']=='resolved'): ?>
                    <div class="d-flex gap-1">
                        <a href="print_dn.php?id=<?=$row['id']?>" target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="fa fa-print me-1"></i>Cetak SJ
                        </a>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DISPATCH (sender sign) -->
<div class="modal fade" id="modalDispatch" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Release dari Gudang</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="text-muted small">DN: <strong><span id="disp_dn"></span></strong> — Pengirim menyatakan barang lengkap sesuai dokumen.</p>
            <label class="form-label">Tanda Tangan Pengirim (Admin Gudang)</label>
            <canvas id="sigPadSender" class="signature-pad"></canvas>
            <button class="btn btn-sm btn-light border mt-2 w-100" onclick="clearPad('sender')"><i class="fa fa-eraser me-1"></i>Clear</button>
        </div>
        <div class="modal-footer border-0">
            <form method="POST" onsubmit="return saveSig('sender')" class="w-100">
                <?= csrfTokenField() ?>
                <input type="hidden" name="act" value="dispatch_confirm">
                <input type="hidden" name="dn_id" id="disp_id">
                <input type="hidden" name="signature_data" id="sigDataSender">
                <button type="submit" class="btn btn-accent w-100 fw-bold">Confirm & Release</button>
            </form>
        </div>
    </div></div>
</div>

<!-- MODAL RECEIVE (receiver sign + qty + damage) -->
<div class="modal fade" id="modalReceive" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-bold">Konfirmasi Penerimaan Barang</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" onsubmit="return saveSig('receiver')">
            <?= csrfTokenField() ?>
            <input type="hidden" name="act" value="receive_confirm">
            <input type="hidden" name="dn_id" id="recv_id">
            <input type="hidden" name="signature_data" id="sigDataReceiver">
            <div class="modal-body">
                <div class="mb-3">
                    <div class="fw-bold small mb-2">Cek Item & Kuantitas:</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="receiveItemTable" style="font-size:0.82rem;">
                            <thead class="table-light"><tr><th>Item</th><th>Qty SO</th><th>Qty Diterima</th><th>Qty Rusak</th><th>Status</th></tr></thead>
                            <tbody id="receiveItemBody">
                                <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="exceptionWarning" class="alert alert-warning border-0 rounded-3 py-2 small d-none">
                        <i class="fa fa-triangle-exclamation me-2"></i><strong>Exception Terdeteksi!</strong> Ada item shortage atau rusak. Data akan dicatat sebagai exception.
                    </div>
                </div>
                <label class="form-label">Tanda Tangan Penerima (Store Manager)</label>
                <canvas id="sigPadReceiver" class="signature-pad" style="height:150px;"></canvas>
                <button type="button" class="btn btn-sm btn-light border mt-2 w-100" onclick="clearPad('receiver')"><i class="fa fa-eraser me-1"></i>Clear</button>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-success w-100 fw-bold text-white">Konfirmasi Penerimaan</button></div>
        </form>
    </div></div>
</div>

<!-- MODAL RESOLVE EXCEPTION -->
<div class="modal fade" id="modalResolve" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header" style="background:#7f1d1d; color:#fff; border:none;">
            <div>
                <h6 class="modal-title fw-bold mb-0"><i class="fa fa-wrench me-2"></i>Resolve Exception</h6>
                <div style="font-size:0.72rem; opacity:0.8;" id="resolveShipNo"></div>
            </div>
            <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST"><?= csrfTokenField() ?>
            <input type="hidden" name="act" value="resolve_exception">
            <input type="hidden" name="dn_id" id="resolveDnId">
            <div class="modal-body">
                <!-- Summary exception -->
                <div id="resolveExceptionSummary" class="p-3 rounded-3 mb-3" style="background:#fff5f5; border:1px solid #fecaca;"></div>

                <label class="form-label">Pilih Tindakan Penanganan *</label>
                <div class="d-flex flex-column gap-2 mb-3" id="resolveOptions">
                    <label class="d-flex align-items-start gap-3 p-3 rounded-3 cursor-pointer" style="border:2px solid #e2e8f0; cursor:pointer;" onclick="selectResolve(this,'backorder')">
                        <input type="radio" name="resolve_action" value="backorder" style="margin-top:3px; flex-shrink:0;">
                        <div>
                            <div class="fw-bold" style="font-size:0.875rem;">📦 Backorder — Kirim Ulang</div>
                            <div style="font-size:0.75rem; color:#64748b;">Item yang kurang akan dikirim di pengiriman berikutnya. Cocok untuk shortage karena item ketinggalan di gudang.</div>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-3 p-3 rounded-3 cursor-pointer" style="border:2px solid #e2e8f0; cursor:pointer;" onclick="selectResolve(this,'vendor_claim')">
                        <input type="radio" name="resolve_action" value="vendor_claim" style="margin-top:3px; flex-shrink:0;">
                        <div>
                            <div class="fw-bold" style="font-size:0.875rem;">📋 Klaim Vendor/Transporter</div>
                            <div style="font-size:0.75rem; color:#64748b;">Laporkan ke vendor pengiriman. Cocok untuk damage atau shortage yang terjadi dalam perjalanan.</div>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-3 p-3 rounded-3 cursor-pointer" style="border:2px solid #e2e8f0; cursor:pointer;" onclick="selectResolve(this,'writeoff')">
                        <input type="radio" name="resolve_action" value="writeoff" style="margin-top:3px; flex-shrink:0;">
                        <div>
                            <div class="fw-bold" style="font-size:0.875rem;">✏️ Write-Off / Catat Kerugian</div>
                            <div style="font-size:0.75rem; color:#64748b;">Catat sebagai kerugian perusahaan. Digunakan jika klaim tidak bisa dilakukan.</div>
                        </div>
                    </label>
                    <label class="d-flex align-items-start gap-3 p-3 rounded-3 cursor-pointer" style="border:2px solid #e2e8f0; cursor:pointer;" onclick="selectResolve(this,'resolved')">
                        <input type="radio" name="resolve_action" value="resolved" style="margin-top:3px; flex-shrink:0;">
                        <div>
                            <div class="fw-bold" style="font-size:0.875rem;">✅ Mark as Resolved</div>
                            <div style="font-size:0.75rem; color:#64748b;">Tandai sudah ditangani secara offline. Gunakan jika sudah ada tindak lanjut di luar sistem.</div>
                        </div>
                    </label>
                </div>
                <div>
                    <label class="form-label">Catatan Tambahan (opsional)</label>
                    <textarea name="resolve_notes" class="form-control" rows="2" placeholder="Contoh: Barang akan dikirim ulang tanggal 25 Maret..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn fw-bold px-4" style="background:#dc2626; color:#fff; border-radius:10px; border:none;">
                    <i class="fa fa-check me-2"></i>Konfirmasi Resolve
                </button>
            </div>
        </form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
var padSender, padReceiver;
window.onload = function() {
    padSender   = new SignaturePad(document.getElementById('sigPadSender'));
    padReceiver = new SignaturePad(document.getElementById('sigPadReceiver'));
};
function clearPad(who) { who==='sender' ? padSender.clear() : padReceiver.clear(); }
function saveSig(who) {
    var pad = who==='sender' ? padSender : padReceiver;
    if(pad.isEmpty()) { alert('Tanda tangan wajib diisi!'); return false; }
    document.getElementById(who==='sender' ? 'sigDataSender' : 'sigDataReceiver').value = pad.toDataURL();
    return true;
}
function openDispatch(id, no) {
    document.getElementById('disp_id').value = id;
    document.getElementById('disp_dn').innerText = no;
    new bootstrap.Modal(document.getElementById('modalDispatch')).show();
    setTimeout(() => padSender.on(), 400);
}
function openReceive(id, no) {
    document.getElementById('recv_id').value = id;
    document.getElementById('receiveItemBody').innerHTML = '<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading items...</td></tr>';
    fetch('get_dn_items.php?dn_id=' + id)
        .then(r => r.json())
        .then(items => {
            var html = '';
            items.forEach(function(item) {
                html += `<tr>
                    <td>${item.item_name} <small class="text-muted">(${item.item_code})</small></td>
                    <td class="text-center fw-bold">${item.qty_ordered}</td>
                    <td><input type="number" name="qty_received[${item.id}]" class="form-control form-control-sm qty-recv" value="${item.qty_ordered}" min="0" max="${item.qty_ordered}" onchange="checkException()" style="width:80px;"></td>
                    <td><input type="number" name="qty_damaged[${item.id}]" class="form-control form-control-sm qty-dmg" value="0" min="0" max="${item.qty_ordered}" onchange="checkException()" style="width:80px;"></td>
                    <td id="status_${item.id}"><span class="badge-soft bs-success">OK</span></td>
                </tr>`;
            });
            document.getElementById('receiveItemBody').innerHTML = html;
            checkException();
        })
        .catch(() => {
            document.getElementById('receiveItemBody').innerHTML = `
                <tr><td>HPL White Gloss</td><td class="text-center">5</td><td><input type="number" name="qty_received[1]" class="form-control form-control-sm qty-recv" value="5" min="0" max="5" onchange="checkException()"></td><td><input type="number" name="qty_damaged[1]" class="form-control form-control-sm qty-dmg" value="0" min="0" onchange="checkException()"></td><td><span class="badge-soft bs-success">OK</span></td></tr>
                <tr><td>Edging PVC 22mm</td><td class="text-center">5</td><td><input type="number" name="qty_received[2]" class="form-control form-control-sm qty-recv" value="5" min="0" max="5" onchange="checkException()"></td><td><input type="number" name="qty_damaged[2]" class="form-control form-control-sm qty-dmg" value="0" min="0" onchange="checkException()"></td><td><span class="badge-soft bs-success">OK</span></td></tr>`;
        });
    new bootstrap.Modal(document.getElementById('modalReceive')).show();
    setTimeout(() => padReceiver.on(), 400);
}
function checkException() {
    var hasEx = false;
    document.querySelectorAll('.qty-recv').forEach(function(inp) {
        if(parseInt(inp.value) < parseInt(inp.getAttribute('max'))) hasEx = true;
    });
    document.querySelectorAll('.qty-dmg').forEach(function(inp) {
        if(parseInt(inp.value) > 0) hasEx = true;
    });
    document.getElementById('exceptionWarning').classList.toggle('d-none', !hasEx);
}
function openResolve(dnId, dnNo, shortageCount, damagedItems) {
    document.getElementById('resolveDnId').value = dnId;
    document.getElementById('resolveShipNo').innerText = 'DN: ' + dnNo;
    // Build summary
    var summary = '';
    if(shortageCount > 0) summary += '<span class="badge-soft bs-danger me-2"><i class="fa fa-box-open me-1"></i>' + shortageCount + ' item shortage</span>';
    if(damagedItems) summary += '<span class="badge-soft bs-warning"><i class="fa fa-box me-1"></i>Damaged: ' + damagedItems + '</span>';
    document.getElementById('resolveExceptionSummary').innerHTML = summary || 'Exception terdeteksi.';
    // Reset radio
    document.querySelectorAll('#resolveOptions label').forEach(l => l.style.borderColor = '#e2e8f0');
    document.querySelectorAll('#resolveOptions input[type=radio]').forEach(r => r.checked = false);
    new bootstrap.Modal(document.getElementById('modalResolve')).show();
}
function selectResolve(label, val) {
    document.querySelectorAll('#resolveOptions label').forEach(l => l.style.borderColor = '#e2e8f0');
    label.style.borderColor = '#dc2626';
    label.querySelector('input').checked = true;
}
</script>
</body></html>