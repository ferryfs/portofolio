<?php
session_start();
// Cek Login
if (!isset($_SESSION['tms_status']) || $_SESSION['tms_status'] != 'login') {
    header("Location: index.php");
    exit();
}

include '../../koneksi.php';

// 1. AMBIL DATA STATISTIK
$total_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tms_orders"));
$total_fleet  = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tms_vehicles WHERE status='available'"));
$total_driver = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tms_drivers"));
$active_ship  = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tms_shipments WHERE status='in_transit'"));

// 2. AMBIL LOKASI BUAT PETA
$q_loc = mysqli_query($conn, "SELECT * FROM tms_locations");
$locations = [];
while($row = mysqli_fetch_assoc($q_loc)) {
    $locations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | LogiTrack TMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
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
        
        /* CARD & MAP */
        .stat-card { background: white; border: none; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: 100%; display: flex; align-items: center; justify-content: space-between; }
        .icon-box { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        #map { height: 400px; width: 100%; border-radius: 15px; z-index: 1; border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fa fa-map-location-dot text-warning me-2"></i>LogiTrack
        </div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link active"><i class="fa fa-gauge-high"></i> Dashboard</a>
            <a href="orders.php" class="nav-link"><i class="fa fa-truck-ramp-box"></i> Orders (SO/DO)</a>
            <a href="outbound.php" class="nav-link"><i class="fa fa-boxes-packing"></i> Outbound (POD)</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Data Master</div>
            <a href="fleet.php" class="nav-link"><i class="fa fa-truck"></i> Fleet Management</a>
            <a href="drivers.php" class="nav-link"><i class="fa fa-users-gear"></i> Drivers</a>
            <div class="mt-4 px-3 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem;">Settings</div>
            <a href="billing.php" class="nav-link"><i class="fa fa-file-invoice-dollar"></i> Billing & Cost</a>
            <a href="help.php" class="nav-link"><i class="fa fa-circle-question"></i> User Guide</a>
            <a href="logout.php" class="nav-link text-danger"><i class="fa fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Command Center</h4>
                <p class="text-muted small mb-0">Welcome back, <?php echo $_SESSION['tms_fullname']; ?>!</p>
            </div>
            <a href="orders.php" class="btn btn-warning btn-sm fw-bold shadow-sm"><i class="fa fa-plus me-1"></i> New Order</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted small fw-bold mb-1">TOTAL ORDERS</h6><h2 class="mb-0 fw-bold"><?php echo $total_orders; ?></h2></div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="fa fa-file-invoice"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted small fw-bold mb-1">ACTIVE SHIPMENTS</h6><h2 class="mb-0 fw-bold"><?php echo $active_ship; ?></h2></div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="fa fa-truck-fast"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted small fw-bold mb-1">AVAILABLE FLEET</h6><h2 class="mb-0 fw-bold"><?php echo $total_fleet; ?></h2></div>
                    <div class="icon-box bg-success bg-opacity-10 text-success"><i class="fa fa-truck"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div><h6 class="text-muted small fw-bold mb-1">TOTAL DRIVERS</h6><h2 class="mb-0 fw-bold"><?php echo $total_driver; ?></h2></div>
                    <div class="icon-box bg-info bg-opacity-10 text-info"><i class="fa fa-id-card"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <h6 class="fw-bold mb-3"><i class="fa fa-map text-secondary me-2"></i>Live Monitoring</h6>
                    <div id="map"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <h6 class="fw-bold mb-3">Recent Activity</h6>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex gap-3 align-items-center border-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px; height:35px;"><i class="fa fa-plus small"></i></div>
                            <div><div class="small fw-bold">System Ready</div><div class="text-muted" style="font-size:0.7rem;">Monitoring Started</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([-6.234978, 106.992850], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        var locations = <?php echo json_encode($locations); ?>;
        locations.forEach(function(loc) {
            if(loc.latitude && loc.longitude) {
                var popupContent = `<b>${loc.name}</b><br><small>${loc.address}</small>`;
                L.marker([loc.latitude, loc.longitude]).addTo(map).bindPopup(popupContent);
            }
        });
    </script>
</body>
</html>