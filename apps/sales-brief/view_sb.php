<?php
session_name("SB_APP_SESSION");
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// 1. Cek Login
if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

// 2. Cek ID
if(!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = $_GET['id'];

// 3. Ambil Data Header
$q_header = mysqli_query($conn, "SELECT * FROM sales_briefs WHERE id = '$id'");
$d = mysqli_fetch_assoc($q_header);

if(!$d) { echo "Data not found!"; exit(); }

// 4. TANGKAP SOURCE
$source = isset($_GET['source']) ? $_GET['source'] : ''; 

// Logic Tombol Back & Sidebar Active
$back_url = 'list_draft.php'; 
$menu_active = 'draft';

if($source == 'approval') {
    $back_url = 'approval.php';
    $menu_active = 'approval';
} elseif($source == 'monitoring') {
    $back_url = 'monitoring.php';
    $menu_active = 'monitoring';
}

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
  <title>View Sales Brief | <?php echo $d['sb_number']; ?></title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #333; }
      .card-view { border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
      .card-view .card-header { background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 15px 20px; }
      .card-view .card-title { font-weight: 700; color: #333; font-size: 1rem; }
      .label-view { font-size: 0.75rem; font-weight: 600; color: #888; text-transform: uppercase; margin-bottom: 3px; display: block; }
      .value-view { font-size: 0.95rem; font-weight: 500; color: #000; min-height: 24px; }
      .badge-custom { font-size: 0.85rem; padding: 5px 10px; border-radius: 4px; }
      .table-view thead th { background: #f8f9fa; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #dee2e6; color: #555; }
      .nav-pills .nav-link.active { background-color: #007bff !important; color: #fff !important; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="<?php echo $back_url; ?>"><i class="fas fa-arrow-left text-dark"></i> <span class="font-weight-bold ml-2 text-dark">Back</span></a></li>
    </ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold text-dark">Detail Sales Brief</h1>
            <div class="mt-1">
                <?php 
                $badge_color = 'secondary';
                if($d['status'] == 'Approved') $badge_color = 'success';
                if($d['status'] == 'Draft') $badge_color = 'warning';
                if($d['status'] == 'Reopened') $badge_color = 'danger';
                
                echo "<span class='badge badge-{$badge_color} px-3 py-2 text-uppercase' style='font-size:14px;'>{$d['status']}</span>";
                ?>
                <span class="text-muted ml-2">No: <?php echo $d['sb_number']; ?></span>
            </div>
          </div>
          
          <div class="col-sm-6 text-right">
              
              <?php if($d['status'] == 'Draft' && $source == 'approval') { ?>
                  <a href="change_status.php?id=<?php echo $d['id']; ?>&action=approve" class="btn btn-success shadow-sm font-weight-bold" onclick="return confirm('Approve proposal ini? Status akan dikunci.')">
                      <i class="fas fa-check-circle mr-2"></i> Approve Proposal
                  </a>
              <?php } ?>

              <?php 
                $today = date('Y-m-d');
                $is_expired = ($today > $d['end_date']);
                $is_monitoring = ($source == 'monitoring');
                $is_approved = ($d['status'] == 'Approved');
                // Cek kolom reopen_count dari DB
                $reopen_count = isset($d['reopen_count']) ? $d['reopen_count'] : 0;
                $has_reopened = ($reopen_count > 0);
                
                // SYARAT MUNCUL: Monitoring + Approved + Belum Expired + Belum pernah Reopen
                if($is_monitoring && $is_approved && !$is_expired && !$has_reopened) { 
              ?>
                  <button type="button" class="btn btn-warning shadow-sm font-weight-bold text-white" onclick="confirmReopen(<?php echo $d['id']; ?>)">
                      <i class="fas fa-lock-open mr-2"></i> Reopen
                  </button>
              <?php } ?>

              <?php if($is_monitoring && $has_reopened && $is_approved) { ?>
                   <button class="btn btn-secondary btn-sm" disabled><i class="fas fa-ban"></i> Limit Reopen (1x) Habis</button>
              <?php } ?>

              <?php if($is_monitoring && $is_expired && $is_approved) { ?>
                   <button class="btn btn-secondary btn-sm" disabled><i class="fas fa-calendar-times"></i> Expired</button>
              <?php } ?>

              <button onclick="window.print()" class="btn btn-default shadow-sm ml-2"><i class="fas fa-print mr-1"></i> Print</button>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        
        <div class="card card-view">
                    <a href="print_memo.php?id=<?php echo $d['id']; ?>" target="_blank" class="btn btn-default shadow-sm ml-2 border font-weight-bold">
                <i class="fas fa-file-pdf mr-1 text-danger"></i> Official Memo (PDF)
            </a>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3"><span class="label-view">Ref Number</span><div class="value-view"><?php echo $d['ref_number'] ?: '-'; ?></div></div>
                    <div class="col-md-5 mb-3"><span class="label-view">Promo Name</span><div class="value-view"><?php echo $d['promo_name']; ?></div></div>
                    <div class="col-md-4 mb-3"><span class="label-view">Period</span><div class="value-view"><?php echo date('d M Y', strtotime($d['start_date'])) . ' - ' . date('d M Y', strtotime($d['end_date'])); ?></div></div>
                </div>
                <div class="row border-top pt-3">
                    <div class="col-md-3 mb-3"><span class="label-view">Mechanism</span><span class="badge badge-info badge-custom"><?php echo $d['promo_mechanism']; ?></span></div>
                    <div class="col-md-3 mb-3"><span class="label-view">Type</span><span class="badge badge-warning badge-custom"><?php echo $d['promo_type'] == '2' ? 'Cashback' : 'Free Gift'; ?></span></div>
                    <div class="col-md-3 mb-3"><span class="label-view">Condition</span><div class="value-view"><?php echo formatJson($d['promo_conditions']); ?></div></div>
                    <div class="col-md-3 mb-3"><span class="label-view">Total Budget</span><div class="value-view text-success font-weight-bold">Rp <?php echo number_format($d['budget_allocation'], 0, ',', '.'); ?></div></div>
                </div>
            </div>
        </div>

        <div class="card card-view">
            <div class="card-header"><h3 class="card-title text-purple"><i class="fas fa-layer-group mr-2"></i> Product Scope</h3></div>
            <div class="card-body">
                <table class="table table-bordered table-sm table-view">
                    <tr><th width="20%">Product Type</th><td><?php echo formatJson($d['product_types']); ?></td></tr>
                    <tr><th>Item Category</th><td><?php echo formatJson($d['item_categories']); ?></td></tr>
                    <tr><th>Product Group</th><td><?php echo formatJson($d['product_groups']); ?></td></tr>
                    <tr><th>Selected Items</th><td><?php echo formatJson($d['selected_items']); ?></td></tr>
                    <tr><th class="text-danger">Mandatory Items</th><td class="text-danger"><?php echo formatJson($d['mandatory_items']); ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card card-view">
            <div class="card-header"><h3 class="card-title text-success"><i class="fas fa-bullseye mr-2"></i> Management Targets</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-view mb-0 text-center">
                    <thead><tr><th>Level</th><th>Min Motif</th><th>Min Qty</th><th>Min Amount</th><th>Discount (%)</th></tr></thead>
                    <tbody>
                        <?php
                        $q_target = mysqli_query($conn, "SELECT * FROM sb_targets WHERE sb_id = '$id' ORDER BY tier_level ASC");
                        if(mysqli_num_rows($q_target) > 0) {
                            while($t = mysqli_fetch_assoc($q_target)) {
                                echo "<tr><td>Tier {$t['tier_level']}</td><td>{$t['min_motif']}</td><td>{$t['min_qty']}</td><td>Rp " . number_format($t['min_amount'], 0, ',', '.') . "</td><td>{$t['discount_pct']}%</td></tr>";
                            }
                        } else { echo "<tr><td colspan='5' class='text-muted py-3'>No targets defined.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-view">
            <div class="card-header"><h3 class="card-title text-info"><i class="fas fa-store mr-2"></i> Store Coverage</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-view mb-0">
                        <thead><tr><th width="5%" class="text-center">No</th><th>Code</th><th>Customer Name</th><th class="text-right">Target Qty</th><th class="text-right">Target Amount</th><th class="text-center">UOM</th></tr></thead>
                        <tbody>
                            <?php
                            $q_cust = mysqli_query($conn, "SELECT * FROM sb_customers WHERE sb_id = '$id'");
                            if(mysqli_num_rows($q_cust) > 0) {
                                $no = 1;
                                while($c = mysqli_fetch_assoc($q_cust)) {
                                    echo "<tr><td class='text-center'>{$no}</td><td><span class='badge badge-light border'>{$c['cust_code']}</span></td><td>{$c['cust_name']}</td><td class='text-right'>{$c['target_qty']}</td><td class='text-right'>Rp " . number_format($c['target_amount'], 0, ',', '.') . "</td><td class='text-center'>{$c['uom']}</td></tr>";
                                    $no++;
                                }
                            } else { echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No stores added.</td></tr>"; }
                            ?>
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

<script>
function confirmReopen(id) {
    // POPUP 1: Konfirmasi Awal
    Swal.fire({
        title: 'Lakukan Reopen?',
        text: "Saat anda melakukan reopen, data tersebut akan masuk ke menu Informasi Promo. Lanjutkan?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Lanjutkan'
    }).then((result) => {
        if (result.isConfirmed) {
            // POPUP 2: Warning Keras
            Swal.fire({
                title: 'PERHATIAN!',
                html: "<div class='text-left small'>Pada menu Reopen, field anda terbatas.<br>Pastikan tidak ada lagi data yang akan diubah.<br><br><b>REOPEN HANYA BISA DILAKUKAN SEKALI (1X)!</b></div>",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Saya Mengerti, Proses!'
            }).then((res) => {
                if (res.isConfirmed) {
                    // Redirect ke change_status.php dengan action reopen
                    window.location.href = 'change_status.php?id=' + id + '&action=reopen';
                }
            });
        }
    })
}
</script>

</body>
</html>