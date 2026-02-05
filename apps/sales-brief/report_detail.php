<?php
// apps/sales-brief/report_detail.php (PDO)
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

$id = sanitizeInt($_GET['id']);
if($id === false) { header("Location: index.php"); exit(); }

$d = safeGetOne($pdo, "SELECT * FROM sales_briefs WHERE id = ?", [$id]);

function formatJson($json) {
    $arr = json_decode($json, true);
    return (is_array($arr) && count($arr) > 0) ? implode(", ", $arr) : "-";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Rangkuman Promo | <?php echo $d['sb_number']; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .bg-report-head { background-color: #343a40; color: #fff; }
      .report-label { color: #6c757d; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; }
      .report-val { font-weight: 600; color: #212529; font-size: 1rem; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="report_summary.php"><i class="fas fa-arrow-left text-dark"></i> <span class="font-weight-bold ml-2 text-dark">Back to Summary</span></a></li></ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-8"><h1 class="m-0 font-weight-bold text-dark">Rangkuman Distribusi Promo</h1><p class="text-muted mb-0">SB No: <strong><?php echo $d['sb_number']; ?></strong></p></div>
          <div class="col-sm-4 text-right"><button onclick="window.print()" class="btn btn-default shadow-sm"><i class="fas fa-print mr-2"></i> Cetak</button></div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-report-head"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Detail Promo</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><div class="report-label">Promo Name</div><div class="report-val"><?php echo htmlspecialchars($d['promo_name']); ?></div></div>
                    <div class="col-md-4 mb-3"><div class="report-label">Periode</div><div class="report-val"><?php echo date('d M Y', strtotime($d['start_date'])) . ' - ' . date('d M Y', strtotime($d['end_date'])); ?></div></div>
                    <div class="col-md-4 mb-3"><div class="report-label">Budget</div><div class="report-val text-success">Rp <?php echo number_format($d['budget_allocation'], 0, ',', '.'); ?></div></div>
                    <div class="col-md-12 border-top pt-3">
                        <div class="row">
                            <div class="col-md-6"><div class="report-label mb-1">Products</div><p class="text-muted"><?php echo formatJson($d['selected_items']); ?></p></div>
                            <div class="col-md-6"><div class="report-label mb-1">Mechanism</div><p class="text-muted"><?php echo htmlspecialchars($d['promo_mechanism']); ?></p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom-0"><h3 class="card-title font-weight-bold text-primary"><i class="fas fa-users mr-2"></i> Daftar Customer</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light">
                            <tr><th width="5%" class="text-center">No</th><th>Code</th><th>Name</th><th class="text-right">Tgt Qty</th><th class="text-right">Tgt Amount</th><th class="text-center">Status</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM sb_customers WHERE sb_id = ?");
                            $stmt->execute([$id]);
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($rows) == 0) {
                                echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No data.</td></tr>";
                            } else {
                                $no = 1; $t_qty = 0; $t_amt = 0;
                                foreach($rows as $c) {
                                    $t_qty += $c['target_qty'];
                                    $t_amt += $c['target_amount'];
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td class="font-weight-bold"><?php echo htmlspecialchars($c['cust_code']); ?></td>
                                    <td><?php echo htmlspecialchars($c['cust_name']); ?></td>
                                    <td class="text-right"><?php echo number_format($c['target_qty'], 0, ',', '.'); ?></td>
                                    <td class="text-right">Rp <?php echo number_format($c['target_amount'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><span class="badge badge-success">Active</span></td>
                                </tr>
                            <?php } ?>
                                <tr class="bg-warning font-weight-bold">
                                    <td colspan="3" class="text-right">GRAND TOTAL</td>
                                    <td class="text-right"><?php echo number_format($t_qty, 0, ',', '.'); ?></td>
                                    <td class="text-right">Rp <?php echo number_format($t_amt, 0, ',', '.'); ?></td>
                                    <td></td>
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
</body>
</html>