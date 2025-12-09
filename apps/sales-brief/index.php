<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// 1. CEK LOGIN
if(!isset($_SESSION['sb_user'])) {
    $_SESSION['sb_user'] = 'dev'; $_SESSION['sb_name'] = 'Developer Mode'; $_SESSION['sb_div'] = 'Trade Marketing';
}

// 2. LOGIC HAPUS DATA
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM sb_customers WHERE sb_id='$id'");
    mysqli_query($conn, "DELETE FROM sb_targets WHERE sb_id='$id'");
    mysqli_query($conn, "DELETE FROM sales_briefs WHERE id='$id'");
    header("Location: index.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TRADE MARKETING | Dashboard</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      
      /* Styling Menu Aktif */
      .nav-pills .nav-link.active, .nav-pills .show > .nav-link {
          background-color: #007bff !important;
          color: #fff !important;
          box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      }
      
      /* Table Header Clean */
      .table-custom thead th { 
          background-color: #343a40; 
          color: white; 
          border: none; 
          font-size: 0.85rem; 
          text-transform: uppercase;
          vertical-align: middle;
      }
      .table-custom tbody td { vertical-align: middle; font-size: 0.9rem; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link font-weight-bold text-dark">
            <i class="fas fa-user-circle text-success mr-1"></i> <?php echo $_SESSION['sb_name']; ?> 
            <span class="text-muted small ml-1">(<?php echo $_SESSION['sb_div']; ?>)</span>
        </a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link text-danger font-weight-bold" href="auth.php?logout=true">
                <i class="fas fa-sign-out-alt"></i> LOGOUT
            </a>
        </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link">
      <i class="fas fa-cube pl-3 pr-2 text-warning"></i>
      <span class="brand-text font-weight-bold">SALES BRIEF APP</span>
    </a>

    <div class="sidebar">
      <nav class="mt-3">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <li class="nav-header font-weight-bold text-muted">MAIN MENU</li>
          
          <li class="nav-item menu-open">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-store"></i>
              <p>Trade Marketing <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php" class="nav-link active">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Draft</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Informasi Promo</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-check-circle"></i>
              <p>Approval <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Draft Sales Brief</p>
                    </a>
                </li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-desktop"></i>
              <p>Monitoring <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Data Promo</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Claim & Redeem</p>
                    </a>
                </li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>Report</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold text-dark">Dashboard Overview</h1>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <div class="card card-success card-outline shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h3 class="card-title font-weight-bold text-secondary"><i class="fas fa-list mr-2"></i> List Sales Brief</h3>
                <div class="card-tools">
                    <a href="create_sb.php" class="btn btn-primary btn-sm font-weight-bold px-3 shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Create New
                    </a>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th class="text-center" width="10%">Action</th>
                                <th>SB Number</th>
                                <th>Promo Name</th>
                                <th>Period</th>
                                <th>Mechanism</th>
                                <th>Budget</th>
                                <th>Created By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM sales_briefs ORDER BY id DESC");
                            
                            if(mysqli_num_rows($query) == 0) {
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                                        <p class="font-weight-bold">Belum ada data Sales Brief.</p>
                                        <a href="create_sb.php" class="btn btn-sm btn-outline-primary mt-2">Buat Sekarang</a>
                                    </td>
                                </tr>
                            <?php 
                            } else {
                                while($row = mysqli_fetch_assoc($query)) {
                                    $start = date('d M Y', strtotime($row['start_date']));
                                    $end   = date('d M Y', strtotime($row['end_date']));
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="view_sb.php?id=<?php echo $row['id']; ?>" class="btn btn-default btn-xs border" title="View Detail"><i class="fas fa-eye text-info"></i></a>
                                            <a href="index.php?delete=<?php echo $row['id']; ?>" class="btn btn-default btn-xs border" onclick="return confirm('Yakin hapus data ini?')" title="Hapus"><i class="fas fa-trash text-danger"></i></a>
                                        </div>
                                    </td>
                                    <td class="text-primary font-weight-bold"><?php echo $row['sb_number']; ?></td>
                                    <td class="font-weight-bold text-dark"><?php echo $row['promo_name']; ?></td>
                                    <td class="small text-muted">
                                        <?php echo $start; ?> <span class="text-xs text-secondary">s/d</span> <?php echo $end; ?>
                                    </td>
                                    <td><span class="badge badge-light border text-dark px-2"><?php echo $row['promo_mechanism']; ?></span></td>
                                    <td class="font-weight-bold text-success">Rp <?php echo number_format($row['budget_allocation'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['created_by']; ?></td>
                                    <td><span class="badge badge-warning px-2">Draft</span></td>
                                </tr>
                            <?php 
                                } 
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

      </div>
    </section>
  </div>

  <footer class="main-footer text-sm text-center">
    <strong>Copyright &copy; 2025 <a href="#">Sales Brief System</a>.</strong> All rights reserved.
  </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>