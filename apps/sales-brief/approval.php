<?php
// apps/sales-brief/approval.php (PDO)
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>APPROVAL | Sales Brief</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .table-custom thead th { background-color: #343a40; color: white; border: none; font-size: 0.85rem; vertical-align: middle; }
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
          <div class="col-sm-6"><h1 class="m-0 font-weight-bold text-dark">Approval List</h1></div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="card border-0 shadow-sm card-warning card-outline">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0 text-nowrap">
                        <thead class="bg-dark text-white">
                            <tr><th class="text-center" width="10%">Action</th><th>SB Number</th><th>Promo Name</th><th>Period</th><th>Budget</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM sales_briefs WHERE status = ? ORDER BY id DESC");
                            $stmt->execute(['Draft']);
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if(count($rows) == 0) {
                              echo '<tr><td colspan="6" class="text-center py-5 text-muted">No pending approvals.</td></tr>';
                            } else {
                              foreach($rows as $row) {
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <a href="view_sb.php?id=<?php echo $row['id']; ?>&source=approval" class="btn btn-primary btn-sm font-weight-bold shadow-sm"><i class="fas fa-search mr-1"></i> Review</a>
                                    </td>
                                    <td class="font-weight-bold"><?php echo htmlspecialchars($row['sb_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['promo_name']); ?></td>
                                    <td><?php echo $row['start_date']; ?> - <?php echo $row['end_date']; ?></td>
                                    <td class="text-success font-weight-bold">Rp <?php echo number_format($row['budget_allocation'], 0, ',', '.'); ?></td>
                                    <td><span class="badge badge-warning">Waiting Approval</span></td>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>