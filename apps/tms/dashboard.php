<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['tms_status']) || $_SESSION['tms_status'] != 'login') {
    header("Location: index.php"); exit();
}

// ── KPI ──────────────────────────────────────────────────────
$total_orders  = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_orders")['c'] ?? 0;
$active_ship   = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_shipments WHERE status IN ('planned','in_transit','arrived')")['c'] ?? 0;
$avail_fleet   = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_vehicles WHERE status='available'")['c'] ?? 0;
$total_drivers = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_drivers")['c'] ?? 0;

// ── ON-TIME DELIVERY % ───────────────────────────────────────
$delivered     = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_delivery_notes WHERE status='delivered'")['c'] ?? 0;
$total_dn      = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_delivery_notes")['c'] ?? 0;
$ontime_pct    = $total_dn > 0 ? round(($delivered / $total_dn) * 100) : 0;

// ── EXCEPTION: SHORTAGE ──────────────────────────────────────
$shortage = safeGetOne($pdo,
    "SELECT COUNT(DISTINCT l.dn_id) as c
     FROM tms_lpns l
     JOIN tms_items i ON i.lpn_id = l.id
     JOIN tms_delivery_notes dn ON dn.id = l.dn_id
     WHERE dn.status = 'delivered'
     AND (
         (i.qty_received < i.qty_ordered)
         OR (i.remarks LIKE '%DAMAGED%' AND i.remarks NOT LIKE '%RESOLVED%')
     )")['c'] ?? 0;

// ── CHART: 7 hari shipment ───────────────────────────────────
$chart_labels = []; $chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $cnt = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_shipments WHERE DATE(created_at)=?", [$d])['c'] ?? 0;
    $chart_labels[] = date('d/m', strtotime($d));
    $chart_data[]   = (int)$cnt;
}

// ── RECENT ACTIVITY (REAL) ───────────────────────────────────
$activity = safeGetAll($pdo,
    "(SELECT 'shipment' as type, shipment_no as ref, status, created_at as ts FROM tms_shipments ORDER BY id DESC LIMIT 4)
     UNION
     (SELECT 'pod' as type, dn_number as ref, status, tgl_kirim as ts FROM tms_delivery_notes ORDER BY id DESC LIMIT 4)
     ORDER BY ts DESC LIMIT 8");

// ── LOKASI MAP ───────────────────────────────────────────────
$locations = safeGetAll($pdo, "SELECT * FROM tms_locations WHERE tenant_id=?", [$_SESSION['tms_tenant_id'] ?? 1]);

// ── SHIPMENT AKTIF (untuk map line) ─────────────────────────
$active_shipments = safeGetAll($pdo,
    "SELECT s.shipment_no, s.status, d.name as driver_name, v.plate_number,
            l1.latitude as orig_lat, l1.longitude as orig_lng, l1.name as orig_name,
            l2.latitude as dest_lat, l2.longitude as dest_lng, l2.name as dest_name
     FROM tms_shipments s
     LEFT JOIN tms_drivers d ON s.driver_id=d.id
     LEFT JOIN tms_vehicles v ON s.vehicle_id=v.id
     LEFT JOIN tms_shipment_stops ss1 ON ss1.shipment_id=s.id AND ss1.stop_type='pickup'
     LEFT JOIN tms_orders o ON ss1.order_id=o.id
     LEFT JOIN tms_locations l1 ON o.origin_id=l1.id
     LEFT JOIN tms_locations l2 ON o.destination_id=l2.id
     WHERE s.status IN ('planned','in_transit','arrived')
     LIMIT 10");

$page_title  = 'Dashboard';
$active_page = 'dashboard';
ob_start();
include '_head.php';
$head_html = ob_get_clean();
ob_start();
include '_guide_content.php';
$guide_css = ob_get_clean();
// Inject guide CSS sebelum </head>
echo str_replace('</head>', $guide_css . '</head>', $head_html);
?>
<body>
<?php include '_sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h3><i class="fa fa-map-location-dot text-warning me-2"></i>Command Center</h3>
            <p>Selamat datang, <?= htmlspecialchars($_SESSION['tms_fullname']) ?> &mdash; <?= date('l, d F Y') ?></p>
        </div>
        <a href="orders.php" class="btn btn-accent shadow-sm px-4">
            <i class="fa fa-plus me-2"></i>New Order
        </a>
    </div>

    <!-- KPI ROW -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-card">
                <div><div class="kpi-num"><?= $total_orders ?></div><div class="kpi-label">Total Orders</div></div>
                <div class="kpi-icon" style="background:#eef2ff; color:#4f46e5;"><i class="fa fa-file-invoice"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="<?= $active_ship > 0 ? 'border-color:#f59e0b;' : '' ?>">
                <div><div class="kpi-num text-warning"><?= $active_ship ?></div><div class="kpi-label">Active Shipments</div><div class="kpi-sub">Planned + In Transit</div></div>
                <div class="kpi-icon" style="background:#fffbeb; color:#d97706;"><i class="fa fa-truck-fast"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <div><div class="kpi-num text-success"><?= $ontime_pct ?>%</div><div class="kpi-label">On-Time Delivery</div><div class="kpi-sub"><?= $delivered ?> dari <?= $total_dn ?> DN</div></div>
                <div class="kpi-icon" style="background:#d1fae5; color:#059669;"><i class="fa fa-circle-check"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="<?= $shortage > 0 ? 'border-color:#ef4444;' : '' ?>">
                <div>
                    <div class="kpi-num <?= $shortage > 0 ? 'text-danger' : 'text-success' ?>"><?= $shortage ?></div>
                    <div class="kpi-label">Exceptions</div>
                    <div class="kpi-sub"><?= $shortage > 0 ? 'Item shortage/damage' : 'Semua normal' ?></div>
                </div>
                <div class="kpi-icon" style="background:#fef2f2; color:#ef4444;"><i class="fa fa-triangle-exclamation"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- MAP -->
        <div class="col-lg-8">
            <div class="data-card">
                <div class="data-card-header">
                    <h6><i class="fa fa-map me-2 text-warning"></i>Live Monitoring Map</h6>
                    <span style="font-size:0.75rem; color:var(--muted);"><?= count($locations) ?> lokasi terdaftar</span>
                </div>
                <div id="map" style="height:380px; border-radius: 0 0 14px 14px;"></div>
            </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="col-lg-4">
            <div class="data-card mb-3">
                <div class="data-card-header">
                    <h6><i class="fa fa-rss me-2 text-warning"></i>Recent Activity</h6>
                </div>
                <div class="p-2">
                <?php if(empty($activity)): ?>
                <div class="empty-state" style="padding:20px;"><i class="fa fa-inbox"></i>Belum ada aktivitas.</div>
                <?php else: foreach($activity as $a):
                    $is_ship = $a['type'] === 'shipment';
                    $icon    = $is_ship ? 'fa-truck' : 'fa-file-signature';
                    $color   = $is_ship ? '#f59e0b' : '#4f46e5';
                    $bg      = $is_ship ? '#fffbeb' : '#eef2ff';
                    $status_map = [
                        'planned'=>'Planned','in_transit'=>'In Transit','arrived'=>'Arrived',
                        'completed'=>'Completed','delivered'=>'Delivered','draft'=>'Draft',
                        'failed'=>'Failed','cancelled'=>'Cancelled'
                    ];
                    $status_label = $status_map[$a['status']] ?? $a['status'];
                ?>
                <div class="d-flex gap-2 align-items-center p-2 rounded" style="margin-bottom:2px;">
                    <div style="width:34px; height:34px; border-radius:9px; background:<?=$bg?>; color:<?=$color?>; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.85rem;">
                        <i class="fa <?= $icon ?>"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:0.8rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($a['ref']) ?></div>
                        <div style="font-size:0.7rem; color:var(--muted);"><?= $status_label ?> &bull; <?= date('d M H:i', strtotime($a['ts'])) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- FLEET AVAILABILITY -->
            <div class="data-card">
                <div class="data-card-header"><h6><i class="fa fa-truck me-2 text-warning"></i>Fleet Status</h6></div>
                <div class="p-3">
                    <?php
                    $fleet_all = safeGetAll($pdo, "SELECT plate_number, vehicle_type, status FROM tms_vehicles ORDER BY status, id");
                    foreach($fleet_all as $f):
                        $sc = ['available'=>'bs-success','busy'=>'bs-warning','maintenance'=>'bs-danger'][$f['status']] ?? 'bs-muted';
                        $dc = ['available'=>'#10b981','busy'=>'#f59e0b','maintenance'=>'#ef4444'][$f['status']] ?? '#94a3b8';
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 py-1">
                        <div style="font-size:0.82rem;">
                            <span class="status-dot" style="background:<?=$dc?>;"></span>
                            <strong><?= htmlspecialchars($f['plate_number']) ?></strong>
                            <span style="color:var(--muted); font-size:0.72rem;"> — <?= $f['vehicle_type'] ?></span>
                        </div>
                        <span class="badge-soft <?= $sc ?>"><?= ucfirst($f['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- CHART SHIPMENT -->
        <div class="col-lg-8">
            <div class="data-card">
                <div class="data-card-header"><h6><i class="fa fa-chart-bar me-2 text-warning"></i>Shipment 7 Hari Terakhir</h6></div>
                <div class="p-3"><canvas id="shipChart" height="120"></canvas></div>
            </div>
        </div>

        <!-- QUICK STATS -->
        <div class="col-lg-4">
            <div class="data-card h-100">
                <div class="data-card-header"><h6><i class="fa fa-gauge me-2 text-warning"></i>Quick Stats</h6></div>
                <div class="p-3 d-flex flex-column gap-2">
                    <?php
                    $stats = [
                        ['Fleet Tersedia', $avail_fleet, '#059669'],
                        ['Total Driver', $total_drivers, '#4f46e5'],
                        ['DN Pending POD', safeGetOne($pdo,"SELECT COUNT(*) as c FROM tms_delivery_notes WHERE status='draft'")['c']??0, '#f59e0b'],
                        ['Shipment Selesai', safeGetOne($pdo,"SELECT COUNT(*) as c FROM tms_shipments WHERE status='completed'")['c']??0, '#06b6d4'],
                    ];
                    foreach($stats as $s):
                    ?>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#374151;"><?= $s[0] ?></span>
                        <strong style="font-size:1rem; color:<?= $s[2] ?>;"><?= $s[1] ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ USER GUIDE MODAL (full — sama dengan help.php) ═══ -->
<style>
#guideModal .modal-dialog { max-width: 860px; }
#guideModal .modal-content { border-radius: 20px; max-height: 90vh; display: flex; flex-direction: column; }
#guideModal .modal-header { background: linear-gradient(135deg,#0f172a,#1e293b); color:#fff; border-radius: 20px 20px 0 0; padding: 18px 24px; flex-shrink:0; }
#guideModal .modal-body { overflow-y: auto; flex:1; padding: 20px 24px; }
#guideModal .modal-footer { border-radius: 0 0 20px 20px; flex-shrink:0; }
#guideModal .guide-hero { display:none; } /* hero tidak perlu di modal */
</style>

<div class="modal fade" id="guideModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="fa fa-book-open me-2 text-warning"></i>User Guide — LogiTrack TMS</h5>
                    <div style="font-size:0.72rem; opacity:0.7; margin-top:2px;">Panduan lengkap semua fitur, menu, dan tombol</div>
                </div>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="guide-tabs d-flex gap-2 mb-4 flex-wrap" style="position:sticky; top:0; background:#fff; padding:8px 0; z-index:10; border-bottom:1px solid #f1f5f9; margin-bottom:16px !important;">
                    <button class="nav-link active" onclick="showTabM('intro',this)">🏠 Overview</button>
                    <button class="nav-link" onclick="showTabM('dashboard',this)">📊 Dashboard</button>
                    <button class="nav-link" onclick="showTabM('orders',this)">📦 Orders</button>
                    <button class="nav-link" onclick="showTabM('outbound',this)">✍️ POD</button>
                    <button class="nav-link" onclick="showTabM('exception',this)">⚠️ Exception</button>
                    <button class="nav-link" onclick="showTabM('fleet',this)">🚛 Fleet</button>
                    <button class="nav-link" onclick="showTabM('drivers',this)">👤 Drivers</button>
                    <button class="nav-link" onclick="showTabM('billing',this)">💰 Billing</button>
                    <button class="nav-link" onclick="showTabM('status',this)">📋 Status Flow</button>
                </div>
                <!-- Inject guide tabs dengan prefix 'm-' supaya tidak konflik dengan help.php -->
                <div id="modal-guide-body">
                    <?php
                    // Re-render guide tabs dengan ID berbeda untuk modal
                    ob_start();
                    include '_guide_tabs.php';
                    $guide_html = ob_get_clean();
                    // Rename id gs-* ke gs-m-* untuk modal agar tidak konflik
                    echo str_replace(
                        ['id="gs-', "showTab('", "id='gs-"],
                        ['id="gs-m-', "showTabM('", "id='gs-m-"],
                        $guide_html
                    );
                    ?>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <div class="me-auto" style="font-size:0.75rem; color:#94a3b8;"><i class="fa fa-info-circle me-1"></i>Demo Mode — Data fiktif untuk keperluan portofolio</div>
                <a href="help.php" class="btn btn-outline-secondary btn-sm rounded-3">Buka Full Guide</a>
                <button type="button" class="btn btn-accent px-4 fw-bold" data-bs-dismiss="modal">Mulai!</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// MAP
var map = L.map('map').setView([-6.234978, 106.992850], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

var locations = <?= json_encode($locations) ?>;
var icons = {
    warehouse: L.divIcon({ html: '<div style="background:#f59e0b;color:#000;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 2px 8px rgba(0,0,0,0.3);">🏭</div>', className: '', iconSize: [32,32], iconAnchor: [16,16] }),
    store: L.divIcon({ html: '<div style="background:#4f46e5;color:#fff;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 2px 8px rgba(0,0,0,0.3);">🏪</div>', className: '', iconSize: [32,32], iconAnchor: [16,16] }),
};
locations.forEach(function(loc) {
    if(loc.latitude && loc.longitude) {
        var icon = icons[loc.type] || icons.store;
        L.marker([loc.latitude, loc.longitude], {icon: icon}).addTo(map)
            .bindPopup(`<b>${loc.name}</b><br><small style="color:#64748b;">${loc.address}</small><br><span class="badge bg-secondary" style="font-size:0.65rem;">${loc.type}</span>`);
    }
});

// Draw active shipment routes
var shipments = <?= json_encode($active_shipments) ?>;
shipments.forEach(function(s) {
    if(s.orig_lat && s.dest_lat) {
        var line = L.polyline([[s.orig_lat, s.orig_lng],[s.dest_lat, s.dest_lng]], {
            color: s.status === 'in_transit' ? '#f59e0b' : '#94a3b8',
            weight: 2, dashArray: '6,4', opacity: 0.7
        }).addTo(map);
        line.bindPopup(`<b>${s.shipment_no}</b><br>${s.orig_name} → ${s.dest_name}<br>Driver: ${s.driver_name||'-'} | ${s.plate_number||'-'}`);
    }
});

// CHART
new Chart(document.getElementById('shipChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Shipment', data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(245,158,11,0.2)', borderColor: '#f59e0b',
            borderWidth: 2, borderRadius: 8, borderSkipped: false
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
});

// GUIDE MODAL
function closeGuide() { bootstrap.Modal.getInstance(document.getElementById('guideModal')).hide(); }
function showTabM(tab, btn) {
    document.querySelectorAll('#modal-guide-body .guide-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('#guideModal .guide-tabs .nav-link').forEach(b => b.classList.remove('active'));
    var el = document.getElementById('gs-m-' + tab);
    if(el) el.classList.add('active');
    btn.classList.add('active');
}
// Muncul setiap login (tanpa sessionStorage check)
new bootstrap.Modal(document.getElementById('guideModal')).show();
</script>
</body></html>