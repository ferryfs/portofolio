<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['sb_user'])) { header("Location: landing.php"); exit(); }

// --- QUERY STATISTIK ---
// 1. Total Draft
$q1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales_briefs WHERE status='Draft'");
$d1 = mysqli_fetch_assoc($q1);

// 2. Waiting Approval (Bisa dianggap Reopened atau Draft yang nunggu)
$q2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales_briefs WHERE status='Reopened'");
$d2 = mysqli_fetch_assoc($q2);

// 3. Active Promo (Approved)
$q3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales_briefs WHERE status='Approved'");
$d3 = mysqli_fetch_assoc($q3);

// 4. Total Budget (Approved Only)
$q4 = mysqli_query($conn, "SELECT SUM(budget_allocation) as total FROM sales_briefs WHERE status='Approved'");
$d4 = mysqli_fetch_assoc($q4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard | Sales Brief</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .welcome-banner {
          background: linear-gradient(45deg, #007bff, #6610f2);
          color: white;
          padding: 30px;
          border-radius: 10px;
          margin-bottom: 25px;
          box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li></ul>
    <ul class="navbar-nav ml-auto"><li class="nav-item"><a class="nav-link text-danger font-weight-bold" href="auth.php?logout=true">LOGOUT</a></li></ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0 font-weight-bold text-dark">Executive Dashboard</h1></div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <div class="welcome-banner">
            <h2 class="font-weight-bold">Halo, <?php echo $_SESSION['sb_name']; ?>! 👋</h2>
            <p class="mb-0 opacity-75">Selamat datang di Sales Brief System. Berikut adalah ringkasan aktivitas promo Anda hari ini.</p>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-primary"><?php echo $d1['total']; ?></h3>
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">Draft Proposal</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-alt text-primary opacity-25"></i></div>
                    <a href="list_draft.php" class="small-box-footer bg-light text-primary">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-warning"><?php echo $d2['total']; ?></h3>
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">On Revision (Reopen)</p>
                    </div>
                    <div class="icon"><i class="fas fa-edit text-warning opacity-25"></i></div>
                    <a href="informasi_promo.php" class="small-box-footer bg-light text-warning">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-success"><?php echo $d3['total']; ?></h3>
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">Active Promo</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle text-success opacity-25"></i></div>
                    <a href="monitoring.php" class="small-box-footer bg-light text-success">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary shadow-sm">
                    <div class="inner">
                        <?php 
                        $val = $d4['total'];
                        $display = number_format($val);
                        if($val >= 1000000000) $display = round($val/1000000000, 1) . ' M';
                        elseif($val >= 1000000) $display = round($val/1000000, 1) . ' Jt';
                        ?>
                        <h3 class="text-white"><?php echo $display; ?></h3>
                        <p class="text-white font-weight-bold text-uppercase" style="font-size:12px;">Total Budget (Approved)</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave text-white opacity-25"></i></div>
                    <a href="report_summary.php" class="small-box-footer" style="color: rgba(255,255,255,0.8);">Lihat Report <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom-0">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-history mr-2"></i> 5 Proposal Terakhir</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr><th>SB Number</th><th>Promo Name</th><th>Status</th><th>Date Created</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_recent = mysqli_query($conn, "SELECT * FROM sales_briefs ORDER BY id DESC LIMIT 5");
                        while($r = mysqli_fetch_assoc($q_recent)) {
                            $sts = $r['status'];
                            $clr = ($sts == 'Approved') ? 'success' : (($sts == 'Draft') ? 'warning' : 'danger');
                            $date = date('d M Y', strtotime($r['start_date'])); // Asumsi pake start date sbg indikator
                        ?>
                        <tr>
                            <td class="text-primary font-weight-bold"><?php echo $r['sb_number']; ?></td>
                            <td><?php echo $r['promo_name']; ?></td>
                            <td><span class="badge badge-<?php echo $clr; ?>"><?php echo $sts; ?></span></td>
                            <td class="text-muted small"><?php echo $date; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

      </div>
    </section>
  </div>
  
  <footer class="main-footer text-center text-sm"><strong>Copyright &copy; 2025 Sales Brief System.</strong></footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>