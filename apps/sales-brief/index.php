<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// 1. CEK LOGIN
if(!isset($_SESSION['sb_user'])) {
    header("Location: landing.php");
    exit();
}

// 2. LOGIC HAPUS DATA
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM sales_brief_data WHERE id='$id'");
    header("Location: index.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TRADE MARKETING | Dashboard</title>
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      .nav-link.active { background-color: #28a745 !important; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link font-weight-bold text-uppercase">
            <i class="fas fa-user"></i> &nbsp; <?php echo $_SESSION['sb_name']; ?> 
            <small class="text-muted">(<?php echo $_SESSION['sb_div']; ?>)</small>
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
      <span class="brand-text font-weight-light pl-3 font-weight-bold">SALES BRIEF APP</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          
          <li class="nav-header">MAIN MENU</li>
          <li class="nav-item menu-open">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-store"></i>
              <p>Trademarketing <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link active">
                  <i class="fas fa-file-alt nav-icon"></i>
                  <p>Draft</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="fas fa-info-circle nav-icon"></i>
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
                        <i class="fas fa-file-signature nav-icon"></i>
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
                        <i class="fas fa-database nav-icon"></i>
                        <p>Data Promo</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-hand-holding-usd nav-icon"></i>
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
            <h1 class="m-0">Sales Brief Draft</h1>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> List Table Promo</h3>
                <div class="card-tools">
                    <button class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3 text-right">
                    <a href="create_sb.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create New Sales Brief
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Action</th>
                                <th>SB Number</th>
                                <th>Promo Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Product</th>
                                <th>Creator</th>
                                <th>Division</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // QUERY DATA DARI DATABASE
                            $query = mysqli_query($conn, "SELECT * FROM sales_brief_data ORDER BY id DESC");
                            
                            // Kalau Kosong
                            if(mysqli_num_rows($query) == 0) {
                            ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i><br>
                                        Belum ada data Sales Brief. Silakan buat baru.
                                    </td>
                                </tr>
                            <?php 
                            } 
                            // Kalau Ada Data
                            else {
                                while($row = mysqli_fetch_assoc($query)) {
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <button class="btn btn-success btn-xs" title="Edit"><i class="fas fa-edit"></i></button>
                                        <a href="index.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                    <td class="text-primary font-weight-bold"><?php echo $row['sb_number']; ?></td>
                                    <td><?php echo $row['promo_name']; ?></td>
                                    <td><?php echo $row['start_date']; ?></td>
                                    <td><?php echo $row['end_date']; ?></td>
                                    <td><?php echo $row['product']; ?></td>
                                    <td><?php echo $row['creator_name']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $row['division']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $row['status']; ?></span></td>
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

  <footer class="main-footer">
    <strong>Copyright &copy; 2025 <a href="#">Sales Brief System</a>.</strong>
    All rights reserved.
  </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>