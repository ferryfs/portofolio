<?php
// apps/sales-brief/edit_reopen.php (PDO FULL)

session_name("SB_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

$id = sanitizeInt($_GET['id']);
if($id === false) { header("Location: informasi_promo.php"); exit(); }

// 1. AMBIL HEADER
$d = safeGetOne($pdo, "SELECT * FROM sales_briefs WHERE id=?", [$id]);
if(!$d) { echo "Data not found!"; exit(); }

// Decode JSON items
$current_items = json_decode($d['selected_items'], true) ?? [];

// 2. AMBIL CUSTOMER LAMA (Buat di-load ke JS Table)
$stmt = $pdo->prepare("SELECT * FROM sb_customers WHERE sb_id=?");
$stmt->execute([$id]);
$cust_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Reopen | <?php echo $d['sb_number']; ?></title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

  <style>
      body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
      .card-reopen { border-top: 3px solid #ffc107; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="informasi_promo.php"><i class="fas fa-arrow-left text-dark"></i> <span class="font-weight-bold ml-2 text-dark">Cancel Edit</span></a></li>
    </ul>
  </nav>

  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8"><h1 class="m-0 font-weight-bold text-dark">Edit Proposal (Reopen Mode)</h1><p class="text-muted small">Anda hanya dapat mengubah data krusial. Field lain dikunci.</p></div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        
        <form action="process_reopen.php" method="POST">
            <?php echo csrfTokenField(); ?>
            <input type="hidden" name="sb_id" value="<?php echo $id; ?>">

            <div class="card card-reopen shadow-sm">
                <div class="card-header bg-white"><h3 class="card-title font-weight-bold text-warning"><i class="fas fa-edit mr-2"></i> Editable Fields</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Ref Number</label>
                            <input type="text" name="ref_number" class="form-control" value="<?php echo htmlspecialchars($d['ref_number']); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Promo Name <span class="text-danger">*</span></label>
                            <input type="text" name="promo_name" class="form-control" value="<?php echo htmlspecialchars($d['promo_name']); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Start Date (No Backdate) <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $d['start_date']; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $d['end_date']; ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm bg-light">
                <div class="card-header border-0"><h3 class="card-title font-weight-bold text-muted"><i class="fas fa-lock mr-2"></i> Locked Information</h3></div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="text-muted small text-uppercase">Mechanism</label>
                            <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($d['promo_mechanism']); ?>" disabled>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="text-muted small text-uppercase">Budget Allocation</label>
                            <input type="text" class="form-control form-control-sm" value="Rp <?php echo number_format($d['budget_allocation'], 0, ',', '.'); ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-reopen shadow-sm">
                <div class="card-header bg-white"><h3 class="card-title font-weight-bold text-warning"><i class="fas fa-box mr-2"></i> Selected Items</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Update Items List</label>
                        <select name="selected_items[]" class="form-control select2" multiple="multiple" style="width: 100%;">
                            <?php 
                            $options = ["TH-001" => "TH-001 AA (Wood)", "TH-002" => "TH-002 G (Gloss)", "TV-200" => "TV-200 (Vinyl 2mm)", "TV-300" => "TV-300 (Vinyl 3mm)"];
                            foreach($options as $val => $label) {
                                $selected = in_array($val, $current_items) ? 'selected' : '';
                                echo "<option value='$val' $selected>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card card-reopen shadow-sm">
                <div class="card-header bg-white"><h3 class="card-title font-weight-bold text-warning"><i class="fas fa-users mr-2"></i> Update Customers</h3></div>
                <div class="card-body">
                    
                    <div class="p-3 rounded border mb-3" style="background-color: #fff8e1; border-color: #ffeeba !important;">
                        <div class="row">
                            <div class="col-md-3 mb-2"><input type="text" id="input_cust_code" class="form-control form-control-sm" placeholder="Cust Code"></div>
                            <div class="col-md-3 mb-2"><input type="text" id="input_cust_name" class="form-control form-control-sm" placeholder="Cust Name"></div>
                            <div class="col-md-2 mb-2"><input type="number" id="input_target_qty" class="form-control form-control-sm" placeholder="Tgt Qty"></div>
                            <div class="col-md-2 mb-2"><input type="text" id="input_target_amt" class="form-control form-control-sm format-currency" placeholder="Tgt Amount"></div>
                            <div class="col-md-2 mb-2"><button type="button" class="btn btn-warning btn-sm btn-block font-weight-bold" onclick="addNewRow()">Add</button></div>
                        </div>
                    </div>

                    <table id="table-customer" class="table table-bordered table-sm">
                        <thead class="bg-light"><tr><th>Code</th><th>Name</th><th>Target Qty</th><th>Target Amount</th><th>Action</th></tr></thead>
                        <tbody></tbody>
                    </table>

                </div>
            </div>

            <div class="card p-3 text-right sticky-bottom shadow-lg" style="position: sticky; bottom: 0; z-index: 1000; background: rgba(255,255,255,0.95); border-top: 1px solid #e5e7eb;">
                <a href="informasi_promo.php" class="btn btn-secondary mr-2 px-4">Cancel</a>
                <button type="submit" class="btn btn-success px-5 font-weight-bold shadow-sm">
                    <i class="fas fa-save mr-2"></i> SAVE & RE-APPROVE
                </button>
            </div>

        </form>

      </div>
    </section>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap4' });

        $(document).on('input', '.format-currency', function() {
            var val = $(this).val().replace(/\D/g, ''); 
            if (val !== '') { val = parseInt(val, 10).toLocaleString('id-ID'); }
            $(this).val(val);
        });

        var t = $('#table-customer').DataTable({
            "paging": false, "info": false, "searching": false,
            "language": { "emptyTable": "No customers." }
        });

        window.addCustomerRow = function(code, name, qty, amt) {
            var amtDisplay = amt;
            if(!isNaN(amt)) { amtDisplay = parseInt(amt).toLocaleString('id-ID'); }
            var amtClean = amtDisplay.toString().replace(/\./g, '');

            t.row.add([
                code + '<input type="hidden" name="cust_code[]" value="'+code+'">',
                name + '<input type="hidden" name="cust_name[]" value="'+name+'">',
                '<div class="text-right">' + qty + '<input type="hidden" name="cust_target_qty[]" value="'+qty+'"></div>',
                '<div class="text-right">' + amtDisplay + '<input type="hidden" name="cust_target_amt[]" value="'+amtClean+'"></div>',
                '<div class="text-center"><button type="button" class="btn btn-xs btn-danger remove-row"><i class="fas fa-trash"></i></button></div>'
            ]).draw(false);
        };

        // LOAD EXISTING DATA
        var existingCust = <?php echo json_encode($cust_data); ?>;
        existingCust.forEach(function(c) {
            addCustomerRow(c.cust_code, c.cust_name, c.target_qty, c.target_amount);
        });

        window.addNewRow = function() {
            var code = $('#input_cust_code').val();
            var name = $('#input_cust_name').val();
            var qty  = $('#input_target_qty').val() || 0;
            var amt  = $('#input_target_amt').val() || '0';

            if(code === "" || name === "") { alert("Code & Name required!"); return; }
            addCustomerRow(code, name, qty, amt);
            
            $('#input_cust_code').val(''); $('#input_cust_name').val('');
            $('#input_target_qty').val(''); $('#input_target_amt').val('');
        };

        $('#table-customer tbody').on('click', '.remove-row', function () {
            t.row($(this).parents('tr')).remove().draw();
        });
    });
</script>

</body>
</html>