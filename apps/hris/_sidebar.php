<?php
// Shared sidebar partial — include di semua halaman HRIS
// Usage: $active_menu = 'dashboard'; include '_sidebar.php';
$active = $active_menu ?? '';

// Hitung badge
$pending_cuti    = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_leaves WHERE status='Pending'")['c'] ?? 0;
$pending_lembur  = safeGetOne($pdo, "SELECT COUNT(*) as c FROM ess_overtime WHERE status='Pending'")['c'] ?? 0;
$pending_total   = $pending_cuti + $pending_lembur;
$hris_user       = $_SESSION['hris_name'] ?? 'Admin';
$hris_username   = $_SESSION['hris_user'] ?? '';
$is_guest        = ($hris_username === 'guest');
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <i class="fa fa-layer-group"></i>
            <span>HRIS</span>
        </div>
        <div class="brand-sub">Human Resource System</div>
    </div>

    <div class="sidebar-user">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($hris_user) ?>&background=4f46e5&color=fff&size=64&bold=true" class="user-avatar">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($hris_user) ?></div>
            <div class="user-role"><?= $is_guest ? 'Guest / Viewer' : 'HR Administrator' ?></div>
        </div>
    </div>

    <div class="sidebar-section">MAIN</div>
    <a href="index.php" class="nav-link <?= $active=='dashboard'?'active':'' ?>">
        <i class="fa fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <div class="sidebar-section">EMPLOYEE</div>
    <a href="menu_employee.php" class="nav-link <?= $active=='employee'?'active':'' ?>">
        <i class="fa fa-users"></i><span>Data Karyawan</span>
    </a>
    <a href="menu_attendance.php" class="nav-link <?= $active=='attendance'?'active':'' ?>">
        <i class="fa fa-fingerprint"></i>
        <span>Kehadiran & Cuti</span>
        <?php if($pending_total > 0): ?>
        <span class="nav-badge"><?= $pending_total ?></span>
        <?php endif; ?>
    </a>
    <a href="menu_payroll.php" class="nav-link <?= $active=='payroll'?'active':'' ?>">
        <i class="fa fa-money-bill-wave"></i><span>Payroll</span>
    </a>

    <div class="sidebar-section">SYSTEM</div>
    <a href="auth.php?logout=true" class="nav-link nav-link-danger">
        <i class="fa fa-sign-out-alt"></i><span>Logout</span>
    </a>
</div>
