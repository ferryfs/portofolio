<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

// Helper buat motong teks panjang (T&C / Mechanism)
function cutText($text, $limit = 100) {
    $clean = strip_tags($text); // Hapus tag HTML (bold, br, dll)
    if (strlen($clean) > $limit) {
        return substr($clean, 0, $limit) . '...';
    }
    return $clean;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Report | Summary Promo</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      
      /* CARD STYLING */
      .card-report {
          transition: all 0.3s ease;
          border: none;
          border-radius: 12px;
          box-shadow: 0 2px 10px rgba(5, 180, 255, 0.05);
          cursor: pointer;
          height: 100%; /* Biar tinggi sama rata */
          background: #fff;
          overflow: hidden;
      }
      .card-report:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 20px rgba(0,0,0,0.1);
          border: 1px solid #007bff;
      }
      .card-head-custom {
          background: #f8f9fa;
          padding: 15px;
          border-bottom: 1px solid #eee;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      .sb-title { font-size: 1.1rem; font-weight: 700; color: #343a40; margin-bottom: 5px; }
      .sb-period { font-size: 0.85rem; color: #6c757d; }
      .sb-text-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #adb5bd; margin-bottom: 2px; }
      .sb-text-val { font-size: 0.9rem; color: #495057; margin-bottom: 10px; line-height: 1.4; }
      
      /* STATUS BADGE POJOK */
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
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold text-dark">Summary Distribution</h1>
            <p class="text-muted">Klik kartu untuk melihat detail customer.</p>
          </div>
          <div class="col-sm-6 text-right">
              <a href="export_excel.php" target="_blank" class="btn btn-success shadow-sm font-weight-bold">
                  <i class="fas fa-file-excel mr-2"></i> Export to Excel
              </a>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        
        <div class="row">
            <?php
            // Ambil semua SB
            $q = mysqli_query($conn, "SELECT * FROM sales_briefs ORDER BY id DESC");
            
            if(mysqli_num_rows($q) > 0) {
                while($row = mysqli_fetch_assoc($q)) {
                    $id = $row['id'];
                    $bg_status = ($row['status'] == 'Approved') ? 'success' : (($row['status'] == 'Draft') ? 'warning' : 'danger');
                    $period = date('d M Y', strtotime($row['start_date'])) . ' - ' . date('d M Y', strtotime($row['end_date']));
                    
                    // Ambil Preview Teks (Bersihin HTML)
                    $terms = cutText($row['terms_conditions'], 80);
                    $mech  = cutText($row['mechanism'], 80);
            ?>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-report" data-toggle="modal" data-target="#modal-cust-<?php echo $id; ?>">
                    
                    <div class="card-head-custom">
                        <span class="font-weight-bold text-primary"><?php echo $row['sb_number']; ?></span>
                        <span class="status-corner badge-<?php echo $bg_status; ?>"><?php echo $row['status']; ?></span>
                    </div>
                    
                    <div class="card-body">
                        <div class="sb-title"><?php echo $row['promo_name']; ?></div>
                        <div class="sb-period mb-3"><i class="far fa-calendar-alt mr-1"></i> <?php echo $period; ?></div>
                        
                        <hr style="border-top: 1px dashed #dee2e6;">

                        <div class="sb-text-label">Mechanism</div>
                        <div class="sb-text-val"><?php echo $mech ?: '-'; ?></div>

                        <div class="sb-text-label">Terms & Conditions</div>
                        <div class="sb-text-val mb-0"><?php echo $terms ?: '-'; ?></div>
                    </div>
                    
                    <div class="card-footer bg-white border-0 text-center pb-3">
                        <small class="text-info font-weight-bold">Klik untuk lihat Customer <i class="fas fa-arrow-right ml-1"></i></small>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-cust-<?php echo $id; ?>" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content border-0">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-users mr-2"></i> List Customer</h5>
                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="p-3 bg-light border-bottom">
                                <strong>Promo:</strong> <?php echo $row['promo_name']; ?> <br>
                                <strong>No:</strong> <?php echo $row['sb_number']; ?>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="bg-secondary">
                                        <tr>
                                            <th>Code</th>
                                            <th>Store Name</th>
                                            <th class="text-right">Tgt Qty</th>
                                            <th class="text-right">Tgt Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query ambil customer KHUSUS untuk ID ini
                                        $q_cust = mysqli_query($conn, "SELECT * FROM sb_customers WHERE sb_id = '$id'");
                                        if(mysqli_num_rows($q_cust) > 0) {
                                            while($c = mysqli_fetch_assoc($q_cust)) {
                                        ?>
                                            <tr>
                                                <td class="font-weight-bold"><?php echo $c['cust_code']; ?></td>
                                                <td><?php echo $c['cust_name']; ?></td>
                                                <td class="text-right"><?php echo number_format($c['target_qty'], 0, ',', '.'); ?></td>
                                                <td class="text-right">Rp <?php echo number_format($c['target_amount'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php 
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Belum ada customer di promo ini.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                } 
            } else {
                echo '<div class="col-12 text-center py-5 text-muted">Belum ada data promo.</div>';
            }
            ?>
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