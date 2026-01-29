<?php
session_name("SB_APP_SESSION");
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

$id = $_GET['id'];
$q_head = mysqli_query($conn, "SELECT * FROM sales_briefs WHERE id = '$id'");
$d = mysqli_fetch_assoc($q_head);

function formatJson($json) {
    $arr = json_decode($json, true);
    if(is_array($arr) && count($arr) > 0) { return implode(", ", $arr); }
    return "-";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rangkuman Promo | <?php echo $d['sb_number']; ?></title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .nav-pills .nav-link.active { background-color: #007bff !important; color: #fff !important; }
      .bg-report-head { background-color: #343a40; color: #fff; }
      .report-label { color: #6c757d; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; }
      .report-val { font-weight: 600; color: #212529; font-size: 1rem; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="report_summary.php"><i class="fas fa-arrow-left text-dark"></i> <span class="font-weight-bold ml-2 text-dark">Back to Summary</span></a></li>
    </ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-8">
            <h1 class="m-0 font-weight-bold text-dark">Rangkuman Distribusi Promo</h1>
            <p class="text-muted mb-0">Sales Brief No: <strong><?php echo $d['sb_number']; ?></strong></p>
          </div>
          <div class="col-sm-4 text-right">
              <button onclick="window.print()" class="btn btn-default shadow-sm"><i class="fas fa-print mr-2"></i> Cetak Rangkuman</button>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-report-head">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Detail Promo</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="report-label">Promo Name</div>
                        <div class="report-val"><?php echo $d['promo_name']; ?></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="report-label">Periode</div>
                        <div class="report-val"><?php echo date('d M Y', strtotime($d['start_date'])); ?> - <?php echo date('d M Y', strtotime($d['end_date'])); ?></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="report-label">Budget Allocation</div>
                        <div class="report-val text-success">Rp <?php echo number_format($d['budget_allocation'], 0, ',', '.'); ?></div>
                    </div>
                    <div class="col-md-12 border-top pt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="report-label mb-1">Selected Products</div>
                                <p class="text-muted"><?php echo formatJson($d['selected_items']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <div class="report-label mb-1">Mechanism</div>
                                <p class="text-muted"><?php echo $d['promo_mechanism']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom-0">
                <h3 class="card-title font-weight-bold text-primary"><i class="fas fa-users mr-2"></i> Daftar Customer & Target</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Customer Code</th>
                                <th>Customer Name</th>
                                <th class="text-right">Target Qty</th>
                                <th class="text-right">Target Amount (IDR)</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_cust = mysqli_query($conn, "SELECT * FROM sb_customers WHERE sb_id = '$id'");
                            if(mysqli_num_rows($q_cust) > 0) {
                                $no = 1;
                                $total_tgt_qty = 0;
                                $total_tgt_amt = 0;
                                while($c = mysqli_fetch_assoc($q_cust)) {
                                    $total_tgt_qty += $c['target_qty'];
                                    $total_tgt_amt += $c['target_amount'];
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td class="font-weight-bold"><?php echo $c['cust_code']; ?></td>
                                    <td><?php echo $c['cust_name']; ?></td>
                                    <td class="text-right"><?php echo number_format($c['target_qty'], 0, ',', '.'); ?></td>
                                    <td class="text-right"><?php echo number_format($c['target_amount'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><span class="badge badge-success"><i class="fas fa-check mr-1"></i> Active</span></td>
                                </tr>
                            <?php } ?>
                                <tr class="bg-warning font-weight-bold">
                                    <td colspan="3" class="text-right">GRAND TOTAL</td>
                                    <td class="text-right"><?php echo number_format($total_tgt_qty, 0, ',', '.'); ?></td>
                                    <td class="text-right">Rp <?php echo number_format($total_tgt_amt, 0, ',', '.'); ?></td>
                                    <td></td>
                                </tr>
                            <?php } else { echo "<tr><td colspan='6' class='text-center py-4'>Tidak ada data customer.</td></tr>"; } ?>
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