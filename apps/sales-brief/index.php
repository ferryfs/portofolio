<?php
// apps/sales-brief/index.php (PDO VERSION)

session_name("SB_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Cek Login
if(!isset($_SESSION['sb_user'])) { header("Location: landing.php"); exit(); }

// --- LOGIC STATISTIK (PDO) ---
// 1. Count Draft
$d1 = safeGetOne($pdo, "SELECT COUNT(*) as total FROM sales_briefs WHERE status = 'Draft'");
$count_draft = $d1['total'] ?? 0;

// 2. Count Reopen
$d2 = safeGetOne($pdo, "SELECT COUNT(*) as total FROM sales_briefs WHERE status = 'Reopened'");
$count_reopen = $d2['total'] ?? 0;

// 3. Count Approved
$d3 = safeGetOne($pdo, "SELECT COUNT(*) as total FROM sales_briefs WHERE status = 'Approved'");
$count_approved = $d3['total'] ?? 0;

// 4. Sum Budget (Hanya Approved)
$d4 = safeGetOne($pdo, "SELECT SUM(budget_allocation) as total FROM sales_briefs WHERE status = 'Approved'");
$total_budget = $d4['total'] ?? 0;

// --- CHART DATA (PDO) ---
$chart_bar_labels = []; 
$chart_bar_data = [];

// Top 5 Budget
$stmt = $pdo->query("SELECT promo_name, budget_allocation FROM sales_briefs WHERE status = 'Approved' ORDER BY budget_allocation DESC LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $short = (strlen($row['promo_name']) > 15) ? substr($row['promo_name'],0,15).'...' : $row['promo_name'];
    $chart_bar_labels[] = $short;
    $chart_bar_data[] = $row['budget_allocation'];
}

// Chart Pie (Hitung ulang manual dari variable di atas biar hemat query)
$pie_draft  = $count_draft;
$pie_appr   = $count_approved;
$pie_reopen = $count_reopen;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard | Sales Brief</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .welcome-banner { background: linear-gradient(45deg, #007bff, #6610f2); color: white; padding: 30px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
      .chart-container { position: relative; height: 250px; width: 100%; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

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
            <h2 class="font-weight-bold">Halo, <?php echo htmlspecialchars($_SESSION['sb_name']); ?>! 👋</h2>
            <p class="mb-0 opacity-75">Berikut adalah ringkasan performa dan aktivitas Sales Brief Anda.</p>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-primary"><?php echo $count_draft; ?></h3>
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">Draft Proposal</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-alt text-primary opacity-25"></i></div>
                    <a href="list_draft.php" class="small-box-footer bg-light text-primary">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-warning"><?php echo $count_reopen; ?></h3>
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">Reopened</p>
                    </div>
                    <div class="icon"><i class="fas fa-edit text-warning opacity-25"></i></div>
                    <a href="informasi_promo.php" class="small-box-footer bg-light text-warning">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-success"><?php echo $count_approved; ?></h3>
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">Approved</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle text-success opacity-25"></i></div>
                    <a href="monitoring.php" class="small-box-footer bg-light text-success">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary shadow-sm">
                    <div class="inner">
                        <?php 
                        $val = $total_budget;
                        $display = number_format($val);
                        if($val >= 1000000000) $display = round($val/1000000000, 1) . ' M';
                        elseif($val >= 1000000) $display = round($val/1000000, 1) . ' Jt';
                        ?>
                        <h3 class="text-white"><?php echo $display; ?></h3>
                        <p class="text-white font-weight-bold text-uppercase" style="font-size:12px;">Total Budget</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave text-white opacity-25"></i></div>
                    <a href="report_summary.php" class="small-box-footer" style="color: rgba(255,255,255,0.8);">Lihat Report <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 mt-2">
                        <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-chart-bar mr-2 text-primary"></i> Top 5 Budget Allocation</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container"><canvas id="budgetChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 mt-2">
                        <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-chart-pie mr-2 text-warning"></i> Proposal Status Ratio</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;"><canvas id="statusChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom-0">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-history mr-2"></i> Recent Activities</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr><th>SB Number</th><th>Promo Name</th><th>Status</th><th>Date Created</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM sales_briefs ORDER BY id DESC LIMIT 5");
                            while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $sts = $r['status'];
                                $clr = ($sts == 'Approved') ? 'success' : (($sts == 'Draft') ? 'warning' : 'danger');
                                $date = date('d M Y', strtotime($r['start_date'])); 
                            ?>
                            <tr>
                                <td class="text-primary font-weight-bold"><?php echo htmlspecialchars($r['sb_number']); ?></td>
                                <td><?php echo htmlspecialchars($r['promo_name']); ?></td>
                                <td><span class="badge badge-<?php echo $clr; ?>"><?php echo $sts; ?></span></td>
                                <td class="text-muted small"><?php echo $date; ?></td>
                            </tr>
                            <?php } ?>
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

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        // Chart Bar
        var ctxBar = document.getElementById('budgetChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_bar_labels); ?>,
                datasets: [{ label: 'Budget (IDR)', data: <?php echo json_encode($chart_bar_data); ?>, backgroundColor: '#007bff', borderColor: '#0056b3', borderWidth: 1 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return 'Rp ' + value.toLocaleString(); } } }] }, legend: { display: false } }
        });

        // Chart Donut
        var ctxPie = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Draft', 'Reopened'],
                datasets: [{ data: [<?php echo $pie_appr; ?>, <?php echo $pie_draft; ?>, <?php echo $pie_reopen; ?>], backgroundColor: ['#28a745', '#ffc107', '#dc3545'], borderWidth: 2 }]
            },
            options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' } }
        });
    });
</script>
</body>
</html>