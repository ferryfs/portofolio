<?php
// Deteksi Nama File
$page = basename($_SERVER['PHP_SELF']);

// 0. DASHBOARD (Active logic)
$is_dash_active = ($page == 'index.php');

// 1. TRADE MARKETING
// Child: Draft (Sekarang file-nya list_draft.php)
$is_draft_active = ($page == 'list_draft.php' || $page == 'create_sb.php' || ($page == 'view_sb.php' && (!isset($_GET['source']) || $_GET['source'] == '')));
// Child: Info Promo
$is_info_active  = ($page == 'informasi_promo.php' || $page == 'edit_reopen.php');
// Parent State
$is_trade_open   = ($is_draft_active || $is_info_active) ? 'menu-open' : '';
$is_trade_active = ($is_draft_active || $is_info_active) ? 'active' : '';

// 2. APPROVAL
$is_appr_sb_active = ($page == 'approval.php' || ($page == 'view_sb.php' && isset($_GET['source']) && $_GET['source'] == 'approval'));
$is_appr_open      = ($is_appr_sb_active) ? 'menu-open' : '';
$is_appr_active    = ($is_appr_sb_active) ? 'active' : '';

// 3. MONITORING
$is_mon_promo_active = ($page == 'monitoring.php' || ($page == 'view_sb.php' && isset($_GET['source']) && $_GET['source'] == 'monitoring'));
$is_mon_open         = ($is_mon_promo_active) ? 'menu-open' : '';
$is_mon_active       = ($is_mon_promo_active) ? 'active' : '';

// 4. REPORT
$is_rep_summary_active = ($page == 'report_summary.php' || $page == 'report_detail.php');
$is_rep_open           = ($is_rep_summary_active) ? 'menu-open' : '';
$is_rep_active         = ($is_rep_summary_active) ? 'active' : '';
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link">
      <i class="fas fa-cube pl-3 pr-2 text-warning"></i>
      <span class="brand-text font-weight-bold">SALES BRIEF APP</span>
    </a>

    <div class="sidebar">
      <nav class="mt-3">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          
          <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $is_dash_active ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
            </a>
          </li>

          <li class="nav-header">OPERATIONAL</li>
          
          <li class="nav-item <?php echo $is_trade_open; ?>">
            <a href="#" class="nav-link <?php echo $is_trade_active; ?>">
              <i class="nav-icon fas fa-store"></i><p>Trade Marketing <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="list_draft.php" class="nav-link <?php echo $is_draft_active ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i><p>Draft</p>
                </a>
              </li>
              <li class="nav-item"><a href="informasi_promo.php" class="nav-link <?php echo $is_info_active ? 'active' : ''; ?>"><i class="far fa-circle nav-icon"></i><p>Informasi Promo</p></a></li>
            </ul>
          </li>

          <li class="nav-item <?php echo $is_appr_open; ?>">
            <a href="#" class="nav-link <?php echo $is_appr_active; ?>">
              <i class="nav-icon fas fa-check-circle"></i><p>Approval <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="approval.php" class="nav-link <?php echo $is_appr_sb_active ? 'active' : ''; ?>"><i class="far fa-circle nav-icon"></i><p>Draft Sales Brief</p></a></li>
            </ul>
          </li>

          <li class="nav-item <?php echo $is_mon_open; ?>">
            <a href="#" class="nav-link <?php echo $is_mon_active; ?>">
              <i class="nav-icon fas fa-desktop"></i><p>Monitoring <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="monitoring.php" class="nav-link <?php echo $is_mon_promo_active ? 'active' : ''; ?>"><i class="far fa-circle nav-icon"></i><p>Data Promo</p></a></li>
            </ul>
          </li>

          <li class="nav-item <?php echo $is_rep_open; ?>">
            <a href="#" class="nav-link <?php echo $is_rep_active; ?>">
              <i class="nav-icon fas fa-chart-bar"></i><p>Report <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="report_summary.php" class="nav-link <?php echo $is_rep_summary_active ? 'active' : ''; ?>"><i class="far fa-file-alt nav-icon"></i><p>Summary Promo</p></a></li>
            </ul>
          </li>

        </ul>
      </nav>
    </div>
</aside>