<?php
session_name("SB_APP_SESSION");
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// Cek Login
if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Informasi Promo (Reopen)</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      /* Styling Menu Aktif */
      .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: #007bff !important; color: #fff !important; }
      /* Table Header Clean */
      .table-custom thead th { background-color: #343a40; color: white; border: none; font-size: 0.85rem; vertical-align: middle; }
      .table-custom tbody td { vertical-align: middle; font-size: 0.9rem; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link text-danger font-weight-bold" href="auth.php?logout=true">LOGOUT</a></li>
    </ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold text-dark">Informasi Promo (Reopened)</h1>
            <p class="text-muted small">Data proposal yang dibuka kembali untuk revisi terbatas.</p>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <div class="card card-warning card-outline shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th class="text-center" width="15%">Action</th>
                                <th>SB Number</th>
                                <th>Promo Name</th>
                                <th>Original Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Cuma ambil yang statusnya Reopened
                            $query = mysqli_query($conn, "SELECT * FROM sales_briefs WHERE status = 'Reopened' ORDER BY id DESC");
                            
                            if(mysqli_num_rows($query) == 0) {
                                echo '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x mb-3 opacity-50"></i><p>Tidak ada data yang sedang di-reopen.</p></td></tr>';
                            } else {
                                while($row = mysqli_fetch_assoc($query)) {
                                    $period = date('d M', strtotime($row['start_date'])) . ' - ' . date('d M Y', strtotime($row['end_date']));
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <a href="edit_reopen.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm font-weight-bold shadow-sm">
                                            <i class="fas fa-edit mr-1"></i> Edit Limited
                                        </a>
                                    </td>
                                    <td class="font-weight-bold text-primary"><?php echo $row['sb_number']; ?></td>
                                    <td><?php echo $row['promo_name']; ?></td>
                                    <td class="small text-muted"><?php echo $period; ?></td>
                                    <td><span class="badge badge-danger px-3">Reopened</span></td>
                                </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

      </div>
    </section>
  </div>

  <footer class="main-footer text-center text-sm"><strong>Copyright &copy; 2025 Sales Brief System.</strong></footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>