<?php
// apps/sales-brief/monitoring.php (PDO)
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

// LOGIC FILTER (PDO)
$f_start = $_GET['start'] ?? '';
$f_end   = $_GET['end'] ?? '';
$params  = [];

$sql = "SELECT c.*, s.sb_number, s.promo_name, s.start_date, s.end_date, s.status, s.id as sb_id 
        FROM sb_customers c 
        JOIN sales_briefs s ON c.sb_id = s.id";

if(!empty($f_start) && !empty($f_end)) {
    $sql .= " WHERE s.start_date >= ? AND s.start_date <= ?";
    $params[] = $f_start;
    $params[] = $f_end;
}
$sql .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Monitoring Promo</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .filter-box { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 4px solid #17a2b8; }
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
        <div class="row mb-2"><div class="col-sm-6"><h1 class="m-0 font-weight-bold text-dark">Monitoring Promo Customer</h1></div></div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <div class="filter-box">
            <form method="GET" action="">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="small text-muted mb-1">Start Promo:</label>
                        <input type="date" name="start" class="form-control" value="<?php echo $f_start; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted mb-1">End Promo:</label>
                        <input type="date" name="end" class="form-control" value="<?php echo $f_end; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="filter" value="true" class="btn btn-info font-weight-bold mr-1"><i class="fas fa-filter mr-1"></i> Filter</button>
                        <?php if(isset($_GET['filter'])) { ?>
                            <a href="monitoring.php" class="btn btn-secondary font-weight-bold"><i class="fas fa-sync-alt mr-1"></i> Reset</a>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="table-monitoring" class="table table-bordered table-hover">
                        <thead class="bg-info text-white">
                            <tr><th>Customer Name</th><th>SB Number</th><th>Promo Name</th><th>Period</th><th>Target Qty</th><th>Target Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            if(count($rows) == 0) {
                                echo "<tr><td colspan='7' class='text-center py-4 text-muted'>Tidak ada data di periode ini.</td></tr>";
                            }

                            foreach($rows as $row) {
                                $badge = ($row['status'] == 'Approved') ? 'success' : (($row['status'] == 'Draft') ? 'warning' : 'danger');
                            ?>
                                <tr>
                                    <td class="font-weight-bold"><?php echo htmlspecialchars($row['cust_name']); ?> <br><small class="text-muted"><?php echo htmlspecialchars($row['cust_code']); ?></small></td>
                                    <td><a href="view_sb.php?id=<?php echo $row['sb_id']; ?>&source=monitoring"><?php echo htmlspecialchars($row['sb_number']); ?></a></td>
                                    <td><?php echo htmlspecialchars($row['promo_name']); ?></td>
                                    <td class="small"><?php echo $row['start_date']; ?> <br> s/d <br> <?php echo $row['end_date']; ?></td>
                                    <td class="text-right"><?php echo number_format($row['target_qty'], 0, ',', '.'); ?></td>
                                    <td class="text-right">Rp <?php echo number_format($row['target_amount'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><span class="badge badge-<?php echo $badge; ?>"><?php echo $row['status']; ?></span></td>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() { $('#table-monitoring').DataTable({ "order": [] }); });
</script>
</body>
</html>