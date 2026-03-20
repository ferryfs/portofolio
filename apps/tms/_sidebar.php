<?php
// Shared sidebar — include di semua halaman LogiTrack
// Usage: $active_page = 'dashboard'; include '_sidebar.php';
$active_page = $active_page ?? '';

// Badge pending (shipment yang belum selesai)
try {
    $pending_ship = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_shipments WHERE status NOT IN ('completed','cancelled')")['c'] ?? 0;
    $pending_pod  = safeGetOne($pdo, "SELECT COUNT(*) as c FROM tms_delivery_notes WHERE status='draft'")['c'] ?? 0;
    $shortage_cnt = safeGetOne($pdo,
        "SELECT COUNT(DISTINCT l.dn_id) as c
         FROM tms_lpns l
         JOIN tms_items i ON i.lpn_id = l.id
         JOIN tms_delivery_notes dn ON dn.id = l.dn_id
         WHERE dn.status != 'resolved'
         AND dn.status = 'delivered'
         AND (
             (i.qty_received < i.qty_ordered)
             OR (i.remarks LIKE '%DAMAGED%' AND i.remarks NOT LIKE '%RESOLVED%')
         )")['c'] ?? 0;
} catch(Exception $e) { $pending_ship = $pending_pod = $shortage_cnt = 0; }
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fa fa-map-location-dot text-warning me-2"></i>LogiTrack
    </div>
    <div class="sidebar-tenant">
        <div class="tenant-badge">
            <i class="fa fa-building me-1"></i>
            <?= htmlspecialchars($_SESSION['tms_tenant'] ?? 'TACO Group') ?>
        </div>
        <div class="tenant-user"><?= htmlspecialchars($_SESSION['tms_fullname'] ?? '') ?></div>
    </div>
    <nav class="nav flex-column mt-2">
        <a href="dashboard.php" class="nav-link <?= $active_page=='dashboard'?'active':'' ?>">
            <i class="fa fa-gauge-high"></i> Dashboard
        </a>

        <div class="nav-section">OPERATIONS</div>
        <a href="orders.php" class="nav-link <?= $active_page=='orders'?'active':'' ?>">
            <i class="fa fa-truck-ramp-box"></i> Orders (SO/DO)
            <?php if($pending_ship > 0): ?>
            <span class="nav-badge"><?= $pending_ship ?></span>
            <?php endif; ?>
        </a>
        <a href="outbound.php" class="nav-link <?= $active_page=='outbound'?'active':'' ?>">
            <i class="fa fa-boxes-packing"></i> Outbound (POD)
            <?php if($pending_pod > 0): ?>
            <span class="nav-badge"><?= $pending_pod ?></span>
            <?php endif; ?>
        </a>
        <?php if($shortage_cnt > 0): ?>
        <a href="outbound.php#exceptions" class="nav-link" style="color:#fca5a5;">
            <i class="fa fa-triangle-exclamation"></i> Exceptions
            <span class="nav-badge" style="background:#dc2626;"><?= $shortage_cnt ?></span>
        </a>
        <?php endif; ?>

        <div class="nav-section">MASTER DATA</div>
        <a href="fleet.php" class="nav-link <?= $active_page=='fleet'?'active':'' ?>">
            <i class="fa fa-truck"></i> Fleet Management
        </a>
        <a href="drivers.php" class="nav-link <?= $active_page=='drivers'?'active':'' ?>">
            <i class="fa fa-users-gear"></i> Drivers
        </a>

        <div class="nav-section">FINANCE</div>
        <a href="billing.php" class="nav-link <?= $active_page=='billing'?'active':'' ?>">
            <i class="fa fa-file-invoice-dollar"></i> Billing & Cost
        </a>

        <div class="nav-section">SYSTEM</div>
        <a href="help.php" class="nav-link <?= $active_page=='help'?'active':'' ?>">
            <i class="fa fa-circle-question"></i> User Guide
        </a>
        <a href="auth.php?logout=true" class="nav-link nav-danger">
            <i class="fa fa-power-off"></i> Logout
        </a>
    </nav>
</div>
