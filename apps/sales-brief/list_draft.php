<?php
session_name("SB_APP_SESSION");
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
    // Hapus child tables dulu (Foreign Key constraint)
    mysqli_query($conn, "DELETE FROM sb_customers WHERE sb_id='$id'");
    mysqli_query($conn, "DELETE FROM sb_targets WHERE sb_id='$id'");
    // Baru hapus parent
    mysqli_query($conn, "DELETE FROM sales_briefs WHERE id='$id'");
    
    // Redirect balik biar bersih URL-nya
    header("Location: list_draft.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Draft List | Sales Brief</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      /* Table Header Clean */
      .table-custom thead th { background-color: #343a40; color: white; border: none; font-size: 0.85rem; text-transform: uppercase; vertical-align: middle; }
      .table-custom tbody td { vertical-align: middle; font-size: 0.9rem; }
      /* Callout Info */
      .callout-custom { border-left: 4px solid #007bff; background: #fff; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link text-danger font-weight-bold" href="auth.php?logout=true"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </li>
    </ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold text-dark">Draft Sales Brief</h1>
            <p class="text-muted small mb-0">Kelola proposal yang masih dalam status Draft.</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
              <li class="breadcrumb-item active">Draft List</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <div class="callout-custom rounded">
            <h6 class="font-weight-bold text-primary"><i class="fas fa-info-circle mr-1"></i> Quick Info</h6>
            <p class="small mb-0 text-muted">Proposal yang berstatus <b>Draft</b> belum bisa dilihat oleh Approver. Pastikan data sudah lengkap sebelum mengajukan Approval.</p>
        </div>

        <div class="card card-outline card-primary shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-layer-group mr-2"></i> Repository Data</h3>
                <div class="card-tools">
                    <a href="create_sb.php" class="btn btn-primary btn-sm font-weight-bold px-3 shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Create New Proposal
                    </a>
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
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM sales_briefs ORDER BY id DESC");
                            
                            if(mysqli_num_rows($query) == 0) {
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                                        <p class="font-weight-bold">Belum ada data Draft.</p>
                                        <a href="create_sb.php" class="btn btn-sm btn-outline-primary mt-2">Buat Sekarang</a>
                                    </td>
                                </tr>
                            <?php 
                            } else {
                                while($row = mysqli_fetch_assoc($query)) {
                                    $start = date('d M Y', strtotime($row['start_date']));
                                    $end   = date('d M Y', strtotime($row['end_date']));
                                    
                                    // Warna Badge Status
                                    $badge_class = 'secondary';
                                    if($row['status'] == 'Draft') $badge_class = 'warning';
                                    if($row['status'] == 'Approved') $badge_class = 'success';
                                    if($row['status'] == 'Reopened') $badge_class = 'danger';
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="view_sb.php?id=<?php echo $row['id']; ?>" class="btn btn-default btn-xs border" title="View Detail"><i class="fas fa-eye text-info"></i></a>
                                            
                                            <?php if($row['status'] == 'Draft' || $row['status'] == 'Reopened') { ?>
                                            <a href="list_draft.php?delete=<?php echo $row['id']; ?>" class="btn btn-default btn-xs border" onclick="return confirm('Yakin hapus data ini?')" title="Hapus"><i class="fas fa-trash text-danger"></i></a>
                                            <?php } else { ?>
                                                <button class="btn btn-default btn-xs border" disabled><i class="fas fa-lock text-muted"></i></button>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    <td class="text-primary font-weight-bold"><?php echo $row['sb_number']; ?></td>
                                    <td class="font-weight-bold text-dark"><?php echo $row['promo_name']; ?></td>
                                    <td class="small text-muted">
                                        <?php echo $start; ?> <span class="text-xs text-secondary">s/d</span> <?php echo $end; ?>
                                    </td>
                                    <td><span class="badge badge-light border text-dark px-2"><?php echo $row['promo_mechanism']; ?></span></td>
                                    <td class="font-weight-bold text-success">Rp <?php echo number_format($row['budget_allocation'], 0, ',', '.'); ?></td>
                                    <td><span class="badge badge-<?php echo $badge_class; ?> px-2"><?php echo $row['status']; ?></span></td>
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