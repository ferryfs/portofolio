<?php
// apps/sales-brief/report_summary.php (PDO)
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

function cutText($text, $limit = 100) {
    return (strlen(strip_tags($text)) > $limit) ? substr(strip_tags($text), 0, $limit) . '...' : strip_tags($text);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Report | Summary Promo</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .card-report { transition: all 0.3s ease; border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(5, 180, 255, 0.05); cursor: pointer; height: 100%; background: #fff; overflow: hidden; }
      .card-report:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: 1px solid #007bff; }
      .card-head-custom { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
      .sb-title { font-size: 1.1rem; font-weight: 700; color: #343a40; margin-bottom: 5px; }
      .sb-period { font-size: 0.85rem; color: #6c757d; }
      .sb-text-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #adb5bd; margin-bottom: 2px; }
      .sb-text-val { font-size: 0.9rem; color: #495057; margin-bottom: 10px; line-height: 1.4; }
      .status-corner { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; }
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
        <div class="row mb-3 align-items-center">
          <div class="col-sm-6"><h1 class="m-0 font-weight-bold text-dark">Summary Distribution</h1><p class="text-muted">Klik kartu untuk melihat detail customer.</p></div>
          <div class="col-sm-6 text-right">
              <a href="export_excel.php" target="_blank" class="btn btn-success shadow-sm font-weight-bold"><i class="fas fa-file-excel mr-2"></i> Export to Excel</a>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM sales_briefs ORDER BY id DESC");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $id = $row['id'];
                $bg_status = ($row['status'] == 'Approved') ? 'success' : (($row['status'] == 'Draft') ? 'warning' : 'danger');
                $period = date('d M Y', strtotime($row['start_date'])) . ' - ' . date('d M Y', strtotime($row['end_date']));
                $terms = cutText($row['terms_conditions'], 80);
                $mech  = cutText($row['mechanism'], 80);
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-report" onclick="window.location.href='report_detail.php?id=<?php echo $id; ?>'">
                    <div class="card-head-custom">
                        <span class="font-weight-bold text-primary"><?php echo htmlspecialchars($row['sb_number']); ?></span>
                        <span class="status-corner badge-<?php echo $bg_status; ?>"><?php echo $row['status']; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="sb-title"><?php echo htmlspecialchars($row['promo_name']); ?></div>
                        <div class="sb-period mb-3"><i class="far fa-calendar-alt mr-1"></i> <?php echo $period; ?></div>
                        <hr style="border-top: 1px dashed #dee2e6;">
                        <div class="sb-text-label">Mechanism</div>
                        <div class="sb-text-val"><?php echo $mech ?: '-'; ?></div>
                        <div class="sb-text-label">Terms & Conditions</div>
                        <div class="sb-text-val mb-0"><?php echo $terms ?: '-'; ?></div>
                    </div>
                    <div class="card-footer bg-white border-0 text-center pb-3">
                        <small class="text-info font-weight-bold">Lihat Detail <i class="fas fa-arrow-right ml-1"></i></small>
                    </div>
                </div>
            </div>
            <?php } ?>
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