<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['sb_user'])) { header("Location: landing.php"); exit(); }

// --- 1. DATA KARTU ATAS ---
$q1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales_briefs WHERE status='Draft'");
$d1 = mysqli_fetch_assoc($q1);

$q2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales_briefs WHERE status='Reopened'");
$d2 = mysqli_fetch_assoc($q2);

$q3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales_briefs WHERE status='Approved'");
$d3 = mysqli_fetch_assoc($q3);

$q4 = mysqli_query($conn, "SELECT SUM(budget_allocation) as total FROM sales_briefs WHERE status='Approved'");
$d4 = mysqli_fetch_assoc($q4);

// --- 2. DATA CHART ---
// A. Bar Chart: Top 5 Budget
$q_chart_bar = mysqli_query($conn, "SELECT promo_name, budget_allocation FROM sales_briefs WHERE status='Approved' ORDER BY budget_allocation DESC LIMIT 5");
$chart_bar_labels = [];
$chart_bar_data = [];
while($row = mysqli_fetch_assoc($q_chart_bar)) {
    $short_name = (strlen($row['promo_name']) > 15) ? substr($row['promo_name'],0,15).'...' : $row['promo_name'];
    $chart_bar_labels[] = $short_name;
    $chart_bar_data[] = $row['budget_allocation'];
}

// B. Donut Chart: Status
$q_chart_pie = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM sales_briefs GROUP BY status");
$pie_draft = 0; $pie_appr = 0; $pie_reopen = 0;
while($row = mysqli_fetch_assoc($q_chart_pie)) {
    if($row['status'] == 'Draft') $pie_draft = $row['jumlah'];
    if($row['status'] == 'Approved') $pie_appr = $row['jumlah'];
    if($row['status'] == 'Reopened') $pie_reopen = $row['jumlah'];
}
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
      .card-chart { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
      .chart-container { position: relative; height: 250px; width: 100%; }
      
      /* GUIDE STYLES */
      .guide-step { border-left: 3px solid #dee2e6; padding-left: 20px; margin-bottom: 20px; position: relative; }
      .guide-step::before { content: ''; width: 12px; height: 12px; background: #dee2e6; border-radius: 50%; position: absolute; left: -7.5px; top: 5px; }
      .guide-step.active { border-left-color: #007bff; }
      .guide-step.active::before { background: #007bff; }
      .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: #007bff; color: #fff; }
      .nav-pills .nav-link { color: #495057; font-weight: 500; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li></ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item mr-3">
            <button class="btn btn-outline-info btn-sm font-weight-bold mt-1" onclick="showGuide()">
                <i class="fas fa-book-reader mr-1"></i> Panduan
            </button>
        </li>
        <li class="nav-item"><a class="nav-link text-danger font-weight-bold" href="auth.php?logout=true"><i class="fas fa-power-off mr-1"></i> LOGOUT</a></li>
    </ul>
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
            <p class="mb-0 opacity-75">Berikut adalah ringkasan performa dan aktivitas Sales Brief Anda.</p>
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
                        <p class="text-muted font-weight-bold text-uppercase" style="font-size:12px;">Reopened</p>
                    </div>
                    <div class="icon"><i class="fas fa-edit text-warning opacity-25"></i></div>
                    <a href="informasi_promo.php" class="small-box-footer bg-light text-warning">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm">
                    <div class="inner">
                        <h3 class="text-success"><?php echo $d3['total']; ?></h3>
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
                        $val = $d4['total'];
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
                <div class="card card-chart">
                    <div class="card-header bg-white border-0 mt-2">
                        <h3 class="card-title font-weight-bold text-dark">
                            <i class="fas fa-chart-bar mr-2 text-primary"></i> Top 5 Budget Allocation
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="budgetChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card card-chart">
                    <div class="card-header bg-white border-0 mt-2">
                        <h3 class="card-title font-weight-bold text-dark">
                            <i class="fas fa-chart-pie mr-2 text-warning"></i> Proposal Status Ratio
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="statusChart"></canvas>
                        </div>
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
                            $q_recent = mysqli_query($conn, "SELECT * FROM sales_briefs ORDER BY id DESC LIMIT 5");
                            while($r = mysqli_fetch_assoc($q_recent)) {
                                $sts = $r['status'];
                                $clr = ($sts == 'Approved') ? 'success' : (($sts == 'Draft') ? 'warning' : 'danger');
                                $date = date('d M Y', strtotime($r['start_date'])); 
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

      </div>
    </section>
  </div>
  
  <footer class="main-footer text-center text-sm"><strong>Copyright &copy; 2025 Sales Brief System.</strong></footer>
</div>

<div class="modal fade" id="guideModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-book-open mr-2"></i> Workflow & Rules System</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="row no-gutters" style="min-height: 450px;">
                    <div class="col-md-3 bg-light border-right p-3">
                        <div class="nav flex-column nav-pills" role="tablist" aria-orientation="vertical">
                            <a class="nav-link active" data-toggle="pill" href="#tab-alur" role="tab">
                                <i class="fas fa-project-diagram mr-2" style="width:20px"></i> Alur Proses
                            </a>
                            <a class="nav-link" data-toggle="pill" href="#tab-create" role="tab">
                                <i class="fas fa-plus-circle mr-2" style="width:20px"></i> Create Proposal
                            </a>
                            <a class="nav-link" data-toggle="pill" href="#tab-approval" role="tab">
                                <i class="fas fa-user-check mr-2" style="width:20px"></i> Approval Status
                            </a>
                            <a class="nav-link" data-toggle="pill" href="#tab-reopen" role="tab">
                                <i class="fas fa-unlock-alt mr-2 text-warning" style="width:20px"></i> Fitur Reopen
                            </a>
                            <a class="nav-link" data-toggle="pill" href="#tab-report" role="tab">
                                <i class="fas fa-chart-line mr-2" style="width:20px"></i> Monitoring
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-9 p-4 bg-white">
                        <div class="tab-content">
                            
                            <div class="tab-pane fade show active" id="tab-alur">
                                <h4 class="font-weight-bold text-primary mb-3">Siklus Hidup Sales Brief</h4>
                                <p>Aplikasi ini mengatur proposal dari ide awal hingga eksekusi promo. Berikut siklusnya:</p>

                                <div class="row text-center mt-4">
                                    <div class="col">
                                        <div class="card shadow-sm border-primary"><div class="card-body p-2"><i class="fas fa-pen-nib fa-2x text-primary"></i><br><b>DRAFT</b><br><small>Edit Bebas</small></div></div>
                                    </div>
                                    <div class="col pt-4"><i class="fas fa-arrow-right text-muted"></i></div>
                                    <div class="col">
                                        <div class="card shadow-sm border-warning"><div class="card-body p-2"><i class="fas fa-search fa-2x text-warning"></i><br><b>REVIEW</b><br><small>Approval Mode</small></div></div>
                                    </div>
                                    <div class="col pt-4"><i class="fas fa-arrow-right text-muted"></i></div>
                                    <div class="col">
                                        <div class="card shadow-sm border-success"><div class="card-body p-2"><i class="fas fa-lock fa-2x text-success"></i><br><b>APPROVED</b><br><small>Locked & Active</small></div></div>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle mr-1"></i> 
                                    <strong>Penting:</strong> Budget hanya akan dihitung ke dalam "Total Budget" dashboard jika status proposal sudah <strong>APPROVED</strong>.
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-create">
                                <h4 class="font-weight-bold text-primary mb-3">Cara Membuat Proposal (New Draft)</h4>
                                <div class="guide-step active">
                                    <strong>1. Konfigurasi Dasar</strong><br>
                                    Nomor SB otomatis terisi (Auto-Number). Pilih tipe promo (Direct/Accumulation) dan jenis insentif (Cashback/Free Gift).
                                </div>
                                <div class="guide-step active">
                                    <strong>2. Target Calculation (Otomatis)</strong><br>
                                    Input target toko anda. Sistem akan otomatis menghitung estimasi budget berdasarkan target tersebut.
                                    <br><em>*Fitur Capping (Max Disc) bisa diaktifkan untuk membatasi pengeluaran.</em>
                                </div>
                                <div class="guide-step active">
                                    <strong>3. Upload Dokumen</strong><br>
                                    Banner promo dan mekanisme detail wajib diisi menggunakan text editor yang tersedia.
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-approval">
                                <h4 class="font-weight-bold text-primary mb-3">Sistem Approval & Lock</h4>
                                <p>Sales Brief menggunakan sistem penguncian data (Data Locking) untuk menjaga integritas budget.</p>
                                <table class="table table-bordered table-sm">
                                    <thead class="bg-light"><tr><th>Status</th><th>Kondisi Data</th><th>Aksi User</th></tr></thead>
                                    <tbody>
                                        <tr><td><span class="badge badge-warning">Draft</span></td><td>Open (Bisa diedit penuh)</td><td>Bisa dihapus / diedit</td></tr>
                                        <tr><td><span class="badge badge-success">Approved</span></td><td><strong>LOCKED (Terkunci)</strong></td><td>Hanya bisa View & Print</td></tr>
                                        <tr><td><span class="badge badge-danger">Reopened</span></td><td>Partial Open (Edit Terbatas)</td><td>Wajib submit ulang</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="tab-pane fade" id="tab-reopen">
                                <h4 class="font-weight-bold text-danger mb-3">Aturan Fitur Reopen</h4>
                                <div class="alert alert-warning border-left-warning shadow-sm">
                                    <i class="fas fa-exclamation-triangle fa-2x float-left mr-3"></i>
                                    <strong>PERHATIAN KERAS:</strong><br>
                                    Tombol Reopen hanya muncul di menu <strong>Monitoring</strong> untuk proposal yang sudah Approved.
                                </div>
                                
                                <h6 class="font-weight-bold mt-4">Ketentuan Reopen:</h6>
                                <ol class="pl-3">
                                    <li class="mb-2"><strong>Limitasi 1x:</strong> Anda hanya bisa melakukan Reopen sebanyak SATU KALI per proposal. Jika sudah pernah direopen, tombol akan hilang selamanya.</li>
                                    <li class="mb-2"><strong>Partial Edit:</strong> Saat status Reopened, Anda <strong>TIDAK BISA</strong> mengubah Budget Allocation dan Mekanisme Inti. Anda hanya bisa merevisi:
                                        <ul>
                                            <li>Tanggal Promo (Start/End Date)</li>
                                            <li>Nama Promo</li>
                                            <li>Daftar Item Produk</li>
                                            <li>Data Customer</li>
                                        </ul>
                                    </li>
                                    <li><strong>Auto-Lock:</strong> Setelah disimpan (Save), status akan langsung kembali menjadi <strong>Approved</strong>.</li>
                                </ol>
                            </div>

                            <div class="tab-pane fade" id="tab-report">
                                <h4 class="font-weight-bold text-primary mb-3">Monitoring & Reporting</h4>
                                <p>Gunakan menu Monitoring untuk pelacakan harian.</p>
                                <ul>
                                    <li><strong>Export Excel:</strong> Download rekapitulasi seluruh promo beserta jumlah tokonya.</li>
                                    <li><strong>View Detail:</strong> Klik pada nomor SB untuk melihat detail customer yang berpartisipasi.</li>
                                    <li><strong>Filter Tanggal:</strong> Mencari promo berdasarkan periode berjalan.</li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <div class="mr-auto text-muted small">
                    <i class="fas fa-info-circle mr-1"></i> Baca dengan teliti aturan Reopen.
                </div>
                <button type="button" class="btn btn-primary px-4 font-weight-bold shadow-sm" data-dismiss="modal">Saya Mengerti</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        // 1. Tampilkan Guide Otomatis
        $('#guideModal').modal('show');

        // 2. Render BAR Chart (Budget)
        var ctxBar = document.getElementById('budgetChart').getContext('2d');
        var budgetChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_bar_labels); ?>,
                datasets: [{
                    label: 'Budget Allocation (IDR)',
                    data: <?php echo json_encode($chart_bar_data); ?>,
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value, index, values) { return 'Rp ' + value.toLocaleString(); }
                        }
                    }]
                },
                legend: { display: false }
            }
        });

        // 3. Render DONUT Chart (Status)
        var ctxPie = document.getElementById('statusChart').getContext('2d');
        var statusChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Draft', 'Reopened'],
                datasets: [{
                    data: [<?php echo $pie_appr; ?>, <?php echo $pie_draft; ?>, <?php echo $pie_reopen; ?>],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' }
            }
        });
    });

    // Fungsi Tombol Manual
    function showGuide() { $('#guideModal').modal('show'); }
</script>
</body>
</html>