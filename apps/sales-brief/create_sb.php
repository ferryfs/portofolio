<?php
session_start();
// 1. KONEKSI DATABASE (Wajib ada di sini buat ngecek nomor terakhir)
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

// 2. MOCK SESSION
if(!isset($_SESSION['sb_user'])) { 
    $_SESSION['sb_user'] = 'dev'; $_SESSION['sb_name'] = 'Developer Mode'; $_SESSION['sb_div'] = 'Trade Marketing'; 
}

// 3. LOGIC AUTO NUMBER (SB-YYYY/MM/XXX)
$tahun_ini = date('Y');
$bulan_ini = date('m'); // 01, 02, ... 12

// Cek nomor terakhir di bulan & tahun ini
$cek_last = mysqli_query($conn, "SELECT sb_number FROM sales_briefs 
                                 WHERE YEAR(created_at) = '$tahun_ini' 
                                 AND MONTH(created_at) = '$bulan_ini' 
                                 ORDER BY id DESC LIMIT 1");

if(mysqli_num_rows($cek_last) > 0) {
    // Kalau ada, ambil nomor terakhir
    $row_last = mysqli_fetch_assoc($cek_last);
    // Format DB: SB-2025/12/059. Kita ambil 3 digit terakhir
    $last_seq = intval(substr($row_last['sb_number'], -3)); 
    $new_seq  = $last_seq + 1;
} else {
    // Kalau bulan baru, reset ke 1
    $new_seq = 1;
}

// Pad dengan 0 (misal 1 jadi 001, 59 jadi 059)
$seq_str = str_pad($new_seq, 3, "0", STR_PAD_LEFT);
$sb_number_auto = "SB-$tahun_ini/$bulan_ini/$seq_str";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sales Brief | Create New Proposal</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

  <style>
      /* --- ULTIMATE CORPORATE UI CSS --- */
      body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #343a40; }
      .card-pro { border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; background: #fff; overflow: hidden; }
      .card-header-pro { background: #fff; border-bottom: 1px solid #edf2f7; padding: 15px 20px; display: flex; align-items: center; }
      .card-title-pro { font-size: 0.95rem; font-weight: 700; color: #1f2937; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
      .card-icon-box { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 14px; }
      .label-pro { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 5px; display: block; }
      .form-control, .form-control-sm, .select2-selection { border-radius: 6px !important; border: 1px solid #cbd5e1; color: #334155; font-size: 0.9rem; padding: 0.5rem 0.75rem; height: auto; box-shadow: none !important; transition: border-color 0.2s; }
      .form-control:focus { border-color: #3b82f6; }
      .select2-container .select2-selection--single { height: 38px !important; display: flex !important; align-items: center !important; justify-content: flex-start !important; }
      .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { line-height: 1.5 !important; margin-top: -2px; padding-left: 0 !important; color: #334155; text-align: left !important; width: 100%; }
      .select2-container--bootstrap4 .select2-selection--multiple { min-height: 38px !important; border: 1px solid #cbd5e1 !important; border-radius: 6px !important; display: flex; align-items: center; }
      .select2-search__field { margin-top: 0 !important; margin-bottom: 0 !important; height: 28px !important; }
      .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder { color: #9ca3af !important; font-style: normal; }
      .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field::placeholder { color: #9ca3af !important; }
      .custom-switch .custom-control-label::before { height: 1.5rem; width: 2.75rem; border-radius: 2rem; }
      .custom-switch .custom-control-label::after { width: calc(1.5rem - 4px); height: calc(1.5rem - 4px); border-radius: 50%; }
      .custom-switch .custom-control-input:checked ~ .custom-control-label::before { background-color: #10b981; border-color: #10b981; }
      .switch-container { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e0; }
      .table-pro thead th { background-color: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; border: 1px solid #e2e8f0; padding: 10px; vertical-align: middle; text-align: center; }
      .table-pro tbody td { vertical-align: middle; font-size: 0.9rem; border: 1px solid #e2e8f0; padding: 5px; }
      .input-table { border: 1px solid transparent; background: transparent; text-align: right; width: 100%; font-weight: 500; font-size: 0.9rem; padding: 5px; }
      .input-table:focus { border: 1px solid #3b82f6; background: #fff; border-radius: 4px; }
      .input-disabled { background-color: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
      .nav-tabs-pro { border-bottom: 1px solid #e2e8f0; }
      .nav-tabs-pro .nav-link { color: #64748b; font-weight: 600; border: none; padding: 12px 20px; border-bottom: 2px solid transparent; }
      .nav-tabs-pro .nav-link.active { color: #3b82f6; border-bottom: 2px solid #3b82f6; background: transparent; }
      .hierarchy-box { display: none; margin-top: 15px; padding-left: 15px; border-left: 2px solid #e2e8f0; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed sidebar-collapse">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-arrow-left text-dark"></i> <span class="font-weight-bold ml-2 text-dark">Back to List</span></a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-none d-sm-inline-block">
        <span class="nav-link font-weight-bold text-dark"><i class="fas fa-user-circle text-primary"></i> <?php echo $_SESSION['sb_name']; ?></span>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-light-primary elevation-1" style="background: #fff; border-right: 1px solid #eee;">
    <a href="#" class="brand-link border-0 text-center p-3">
      <i class="fas fa-layer-group text-primary fa-lg"></i> 
    </a>
    <div class="sidebar"></div>
  </aside>

  <div class="content-wrapper">
    <div class="content-header pb-0">
      <div class="container-fluid">
        <div class="row mb-4 mt-2">
          <div class="col-sm-8">
            <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.8rem;">Create Proposal</h1>
            <p class="text-muted">Configure your new sales brief parameters below.</p>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid pb-5">
        <form id="form-sb" action="process_sb.php" method="post" enctype="multipart/form-data">
            
            <input type="hidden" name="submit_sb" value="1">

            <div class="card card-pro">
                <div class="card-header-pro">
                    <div class="card-icon-box bg-primary text-white"><i class="fas fa-sliders-h"></i></div>
                    <h3 class="card-title-pro">Configuration Schema</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-5 pr-lg-5 border-right">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="label-pro">Proposal No</label>
                                    <input type="text" class="form-control font-weight-bold text-primary bg-light" name="SalesBriefNo" value="<?php echo $sb_number_auto; ?>" readonly>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="label-pro">Ref No</label>
                                    <input type="text" class="form-control" name="original_salesbrief_no" placeholder="Optional">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="label-pro">Promo Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="SalesBriefName" placeholder="Enter promo title..." required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="label-pro">Start Date</label>
                                    <input type="date" name="fromdate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="label-pro">End Date</label>
                                    <input type="date" name="todate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="switch-container mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="font-weight-bold text-sm">Capping (Max Disc)</span>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="capping-promo" name="capping_promo" value="1">
                                        <label class="custom-control-label" for="capping-promo"></label>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="font-weight-bold text-sm">Is Consignment</span>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is-consigment" name="is_consigment" value="1">
                                        <label class="custom-control-label" for="is-consigment"></label>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="font-weight-bold text-sm">Mix Item Scheme</span>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is-mix-item" name="is_mix_item" value="1">
                                        <label class="custom-control-label" for="is-mix-item"></label>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center" id="div-kelipatan" style="display:none;">
                                    <span class="font-weight-bold text-sm text-success">Apply Multiples (Berlaku Kelipatan)</span>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="berlaku-kelipatan" name="berlaku_kelipatan" value="1">
                                        <label class="custom-control-label" for="berlaku-kelipatan"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group bg-light p-3 rounded border mt-4">
                                <label class="label-pro text-primary">Target Calculation <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="target_by" name="target_by" style="width: 100%;" data-placeholder="Select Target Calculation">
                                    <option></option>
                                    <option value="qty">Qty Berjenjang (Based on Quantity)</option>
                                    <option value="amount">Amount Berjenjang (Based on IDR)</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-7 pl-lg-5">
                             <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="label-pro">Promo Mechanism <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="promo_mechanism" id="promo_mechanism" data-placeholder="Select Mechanism...">
                                        <option></option>
                                        <option value="Direct Promo">Direct Promo</option>
                                        <option value="Accumulation">Accumulation</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="label-pro">Promo Type <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="pilihan-promo" name="promo_selection" data-placeholder="Select Type...">
                                        <option></option>
                                        <option value="2">Cashback Percentage</option>
                                        <option value="4">Free Gift</option>
                                        <option value="20">Free Gift Claim</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 form-group">
                                    <label class="label-pro">Promo Condition (Multiple)</label>
                                    <select class="form-control select2" name="promo_condition[]" multiple="multiple" data-placeholder="Select Condition...">
                                        <option value="Sell In">Sell In</option>
                                        <option value="Sell Out">Sell Out</option>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <div class="card bg-light border-0">
                                        <div class="card-body py-3 px-4">
                                            <div class="form-group">
                                                <label class="label-pro text-primary">1. Product Type (Jenis Barang)</label>
                                                <select class="form-control select2" id="product_type" name="product_type[]" multiple="multiple" data-placeholder="Select Product Type">
                                                    <option value="Laminates">Laminates</option>
                                                    <option value="Flooring">Flooring</option>
                                                </select>
                                            </div>

                                            <div class="hierarchy-box" id="box-category">
                                                <div class="form-group">
                                                    <label class="label-pro text-primary">2. Item Category</label>
                                                    <select class="form-control select2" id="item_category" name="item_category[]" multiple="multiple" data-placeholder="Select Item Category">
                                                        <option value="HPL">HPL</option>
                                                        <option value="Edging">Edging</option>
                                                        <option value="Vinyl">Vinyl</option>
                                                        <option value="SPC">SPC</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="hierarchy-box" id="box-group">
                                                <div class="form-group">
                                                    <label class="label-pro text-primary">3. Product Group</label>
                                                    <select class="form-control select2" id="product_group" name="product_group[]" multiple="multiple" data-placeholder="Select Product Group">
                                                        <option value="Wood">Wood Series</option>
                                                        <option value="Stone">Stone Series</option>
                                                        <option value="Solid">Solid Series</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="hierarchy-box" id="box-item">
                                                <div class="form-group">
                                                    <label class="label-pro text-primary">4. Selected Items</label>
                                                    <select class="form-control select2" name="selected_items[]" multiple="multiple" data-placeholder="Select Items">
                                                        <option value="TH-001">TH-001 AA (Wood)</option>
                                                        <option value="TH-002">TH-002 G (Gloss)</option>
                                                        <option value="TV-200">TV-200 (Vinyl 2mm)</option>
                                                        <option value="TV-300">TV-300 (Vinyl 3mm)</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group mt-3" id="mandatory-wrapper">
                                                    <label class="label-pro text-danger" id="label-mandatory">Have / Must Buy (Mandatory Item) <span class="asterisk">*</span></label>
                                                    <select class="form-control select2" id="mandatory_items" name="mandatory_items[]" multiple="multiple" data-placeholder="Select Item">
                                                        <option value="TH-001">TH-001 AA (Wood)</option>
                                                        <option value="TV-200">TV-200 (Vinyl 2mm)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 form-group mt-2">
                                    <label class="label-pro">UOM</label>
                                    <select class="form-control select2" name="uom" id="uom" data-placeholder="Select UOM">
                                        <option></option>
                                        <option value="PCS">PCS</option>
                                        <option value="BOX">BOX</option>
                                    </select>
                                </div>

                                <div class="col-md-6 form-group mt-2">
                                    <label class="label-pro">Budget Allocation (IDR)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0">Rp</span></div>
                                        <input type="text" class="form-control border-left-0 text-right font-weight-bold format-currency" name="budget_amount" value="0">
                                    </div>
                                </div>
                            
                                <input type="hidden" name="region[]" value="ALL">
                                <input type="hidden" name="sales_category[]" value="ALL">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <div class="card-icon-box bg-warning text-dark"><i class="fas fa-info"></i></div>
                    <h3 class="card-title-pro">Promo Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="label-pro">Upload Banner</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="file-banner" name="file_banner">
                                    <label class="custom-file-label">Choose file...</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="label-pro">Terms & Conditions</label>
                            <textarea class="summernote" name="syarat"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="label-pro">Mechanism</label>
                            <textarea class="summernote" name="mekanisme"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro" id="section-target">
                <div class="card-header-pro">
                    <div class="card-icon-box bg-success text-white"><i class="fas fa-bullseye"></i></div>
                    <h3 class="card-title-pro">Management Target</h3>
                </div>
                <div class="card-body">
                     <div class="col-md-11 mx-auto">
                        <table class="table table-bordered table-pro">
                            <thead>
                                <tr class="text-center">
                                    <th width="5%" rowspan="2" style="vertical-align: middle;">#</th>
                                    <th colspan="3" class="bg-light text-primary" style="border-bottom: 2px solid #3b82f6;">JUMLAH LEVEL</th>
                                    <th width="5%" rowspan="2"></th>
                                    <th width="25%" rowspan="2" class="col-discount" style="vertical-align: middle;">DISCOUNT %</th>
                                </tr>
                                <tr class="text-center">
                                    <th width="20%">MOTIF MIN</th>
                                    <th width="20%">JUMLAH MIN</th>
                                    <th width="25%">AMOUNT MIN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($i=1; $i<=5; $i++) { ?>
                                <tr>
                                    <td class="text-center text-muted font-weight-bold"><?php echo $i; ?></td>
                                    <td><input type="number" class="form-control form-control-sm input-table" name="jml_min_motif[]" value="0"></td>
                                    <td><input type="number" class="form-control form-control-sm input-table input-qty" name="jml_min_qty[]" value="0"></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend"><span class="input-group-text border-0 bg-transparent" style="font-size:10px;">Rp</span></div>
                                            <input type="text" class="form-control form-control-sm input-table input-amt format-currency" name="jml_min_amount[]" value="0">
                                        </div>
                                    </td>
                                    <td class="text-center text-muted">=</td>
                                    <td class="col-discount">
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" class="form-control input-table" name="discount_percent[]" value="0">
                                            <div class="input-group-append"><span class="input-group-text border-0 bg-transparent">%</span></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                     </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <div class="card-icon-box bg-purple text-white" style="background-color: #8b5cf6;"><i class="fas fa-store"></i></div>
                    <h3 class="card-title-pro">Store Coverage</h3>
                </div>
                <div class="card-body">
                    
                    <ul class="nav nav-tabs nav-tabs-pro mb-4" id="storeTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="manual-tab" data-toggle="tab" href="#manual" role="tab"><i class="fas fa-edit mr-2"></i> Manual Input</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="upload-tab" data-toggle="tab" href="#upload" role="tab"><i class="fas fa-cloud-upload-alt mr-2"></i> Upload Excel</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="storeTabContent">
                        <div class="tab-pane fade show active" id="manual" role="tabpanel">
                            <div class="p-3 rounded border mb-4" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                                <label class="label-pro text-primary mb-3">Add Customer Data</label>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <input type="text" id="input_cust_code" class="form-control form-control-sm" placeholder="Customer Code">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <input type="text" id="input_cust_name" class="form-control form-control-sm" placeholder="Customer Name">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <input type="number" id="input_target_qty" class="form-control form-control-sm text-right input-disabled" placeholder="Target Qty" disabled>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <input type="text" id="input_target_amt" class="form-control form-control-sm text-right format-currency input-disabled" value="0" placeholder="Target Amount" disabled>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <button type="button" class="btn btn-primary btn-sm btn-block" onclick="addCustomerRow()"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="upload" role="tabpanel">
                            <div class="bg-white p-4 rounded border mb-4 text-center" style="border-style: dashed !important; border-width: 2px !important;">
                                <i class="fas fa-file-excel text-success fa-3x mb-3"></i>
                                <h6 class="font-weight-bold">Import Data</h6>
                                <div class="d-flex justify-content-center mt-3">
                                    <a href="#" class="btn btn-outline-secondary btn-sm mr-2"><i class="fas fa-download mr-1"></i> Download Template</a>
                                    <div class="custom-file text-left" style="max-width: 250px;">
                                        <input type="file" class="custom-file-input" id="uploadExcel">
                                        <label class="custom-file-label" for="uploadExcel">Choose file</label>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm ml-2">Upload</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="table-customer" class="table table-bordered table-hover table-sm w-100 table-pro">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="5%" rowspan="2" style="vertical-align: middle;">No</th>
                                    <th width="15%" rowspan="2" style="vertical-align: middle;">Code</th>
                                    <th width="25%" rowspan="2" style="vertical-align: middle;">Customer Name</th>
                                    <th width="20%" colspan="2" class="text-center" style="border-bottom: 1px solid #dee2e6;">Target Minimal</th>
                                    <th width="20%" colspan="2" class="text-center" style="border-bottom: 1px solid #dee2e6;">Remain Capping</th>
                                    <th width="5%" rowspan="2" style="vertical-align: middle;">UOM</th>
                                    <th width="10%" rowspan="2" style="vertical-align: middle;">Action</th>
                                </tr>
                                <tr>
                                    <th>Qty Min</th>
                                    <th>Amount Min</th>
                                    <th>Target Qty</th>
                                    <th>Target Amount</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="card-pro p-3 text-right sticky-bottom shadow-lg" style="position: sticky; bottom: 0; z-index: 1000; background: rgba(255,255,255,0.95); border-top: 1px solid #e5e7eb; border-radius: 0;">
                <a href="index.php" class="btn btn-secondary mr-2 px-4">Cancel</a>
                <button type="button" id="btn-pre-submit" class="btn btn-primary px-5 font-weight-bold shadow-sm">
                    <i class="fas fa-paper-plane mr-2"></i> Review Proposal
                </button>
            </div>

        </form>
      </div>
    </section>
  </div>
</div>

<div class="modal fade" id="modal-summary" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <div class="modal-header bg-primary text-white" style="border-radius: 12px 12px 0 0;">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-clipboard-check mr-2"></i> Review Proposal Summary</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <div class="alert alert-light border-left-primary shadow-sm mb-4" role="alert" style="border-left: 4px solid #007bff;">
            <i class="fas fa-info-circle text-primary mr-1"></i> Please review the details below before submitting.
        </div>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted font-weight-bold" width="40%">Proposal No</td><td class="font-weight-bold text-dark" id="sum-no">-</td></tr>
                    <tr><td class="text-muted font-weight-bold">Promo Name</td><td class="font-weight-bold text-dark" id="sum-name">-</td></tr>
                    <tr><td class="text-muted font-weight-bold">Period</td><td class="text-dark"><span id="sum-start"></span> s/d <span id="sum-end"></span></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted font-weight-bold" width="40%">Mechanism</td><td class="text-dark"><span id="sum-mech" class="badge badge-info"></span></td></tr>
                    <tr><td class="text-muted font-weight-bold">Type</td><td class="text-dark"><span id="sum-type" class="badge badge-warning"></span></td></tr>
                     <tr><td class="text-muted font-weight-bold">Total Budget</td><td class="font-weight-bold text-success" id="sum-budget" style="font-size: 1.1rem;">Rp 0</td></tr>
                </table>
            </div>
        </div>
        <hr class="my-3">
        <div class="row text-center">
             <div class="col-4"><div class="p-2 bg-white rounded border"><small class="text-muted d-block font-weight-bold">TOTAL STORES</small><h4 class="mb-0 font-weight-bold text-primary" id="sum-store-count">0</h4></div></div>
             <div class="col-4"><div class="p-2 bg-white rounded border"><small class="text-muted d-block font-weight-bold">TARGET CALC</small><h6 class="mb-0 font-weight-bold text-dark mt-1" id="sum-calc">-</h6></div></div>
            <div class="col-4"><div class="p-2 bg-white rounded border"><small class="text-muted d-block font-weight-bold">CAPPING</small><h6 class="mb-0 font-weight-bold text-dark mt-1" id="sum-capping">OFF</h6></div></div>
        </div>
      </div>
      <div class="modal-footer bg-white">
        <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Edit Again</button>
        <button type="button" class="btn btn-success px-4 font-weight-bold shadow" id="btn-confirm-submit"><i class="fas fa-check-circle mr-2"></i> Confirm & Submit</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap4', allowClear: true });
        $('.summernote').summernote({ height: 100, toolbar: [['style', ['bold', 'italic', 'ul']]] });
        bsCustomFileInput.init();
        // 1. CURRENCY FORMATTER
        $(document).on('input', '.format-currency', function() {
            var val = $(this).val().replace(/\D/g, ''); 
            if (val !== '') { val = parseInt(val, 10).toLocaleString('id-ID'); }
            $(this).val(val);
        });
        $(document).on('focus', '.format-currency', function() { if ($(this).val() === '0') $(this).val(''); });
        $(document).on('blur', '.format-currency', function() { if ($(this).val() === '') $(this).val('0'); });
        // 2. CONSIGNMENT LOGIC
        $('#is-consigment').change(function() {
            var isConsign = $(this).is(':checked');
            if(isConsign) {
                $('#capping-promo').prop('checked', false).prop('disabled', true).trigger('change');
                $('#berlaku-kelipatan').prop('checked', false).prop('disabled', true);
                $('#is-mix-item').prop('checked', true).prop('disabled', true).trigger('change');
                $('select[name="promo_mechanism"]').val('Direct Promo').trigger('change').prop('disabled', true);
                $('#pilihan-promo').val('2').trigger('change').prop('disabled', true);
                $('#target_by').val('qty').trigger('change').prop('disabled', true);
            } else {
                $('#capping-promo').prop('disabled', false);
                $('#berlaku-kelipatan').prop('disabled', false);
                $('#is-mix-item').prop('checked', false).prop('disabled', false).trigger('change');
                $('select[name="promo_mechanism"]').val(null).trigger('change').prop('disabled', false);
                $('#pilihan-promo').val(null).trigger('change').prop('disabled', false);
                $('#target_by').val(null).trigger('change').prop('disabled', false);
            }
        });
        // 3. LOGIC CAPPING
        function updateCappingLogic() {
            var isCapping = $('#capping-promo').is(':checked');
            var targetBy = $('#target_by').val();
            if(isCapping) {
                $('#is-consigment').prop('disabled', true).prop('checked', false);
            } else {
                if(!$('#is-consigment').is(':checked')) { $('#is-consigment').prop('disabled', false); }
            }
            $('#input_target_qty').prop('disabled', true).addClass('input-disabled');
            $('#input_target_amt').prop('disabled', true).addClass('input-disabled');
            if(isCapping) {
                if(targetBy === 'qty') {
                    $('#input_target_qty').prop('disabled', false).removeClass('input-disabled');
                } else if(targetBy === 'amount') {
                    $('#input_target_amt').prop('disabled', false).removeClass('input-disabled');
                }
            }
        }
        $('#capping-promo, #target_by').change(updateCappingLogic);
        // 4. HIERARCHY LOGIC
        $('#product_type').change(function() {
            var val = $(this).val();
            if(val && val.length > 0) { $('#box-category').slideDown(); } 
            else { 
                $('#box-category, #box-group, #box-item').slideUp();
                $('#item_category').val(null).trigger('change');
            }
        });
        $('#item_category').change(function() {
            if($(this).val() && $(this).val().length > 0) { $('#box-group').slideDown(); } 
            else { $('#box-group, #box-item').slideUp(); $('#product_group').val(null).trigger('change'); }
        });
        $('#product_group').change(function() {
            if($(this).val() && $(this).val().length > 0) { $('#box-item').slideDown(); } 
            else { $('#box-item').slideUp(); }
        });
        // 5. MIX ITEM LOGIC
        $('#is-mix-item').change(function() {
            var label = $('#label-mandatory');
            if($(this).is(':checked')) { label.html('Have / Must Buy (Optional)'); } 
            else { label.html('Have / Must Buy (Mandatory Item) <span class="asterisk">*</span>'); }
        });
        // 6. PROMO TYPE LOGIC
        $('#pilihan-promo').change(function() {
            var val = $(this).val();
            if(val == '4' || val == '20') { 
                $('.col-discount').hide(); $('#div-kelipatan').fadeIn(); 
            } else {
                $('.col-discount').show(); $('#div-kelipatan').hide();
            }
        });
        // 7. TARGET TABLE LOGIC
        function toggleTableTarget() {
            var targetBy = $('#target_by').val();
            if(!targetBy) return;
            if(targetBy === 'qty') {
                $('.input-qty').prop('readonly', false).removeClass('input-disabled');
                $('.input-amt').prop('readonly', true).addClass('input-disabled').val(0);
            } else {
                $('.input-qty').prop('readonly', true).addClass('input-disabled').val(0);
                $('.input-amt').prop('readonly', false).removeClass('input-disabled');
            }
        }
        $('#target_by').change(toggleTableTarget);
        toggleTableTarget();
        // 8. CUSTOMER TABLE LOGIC
        var t = $('#table-customer').DataTable({
            "paging": false, "info": false, "searching": false,
            "language": { "emptyTable": "No customers added yet." }
        });
        window.addCustomerRow = function() {
            var code = $('#input_cust_code').val();
            var name = $('#input_cust_name').val();
            var qty  = $('#input_target_qty').val();
            var amt  = $('#input_target_amt').val();
            var isCapping = $('#capping-promo').is(':checked');
            var minQty = $('input[name="jml_min_qty[]"]').eq(0).val() || 0;
            var minAmt = $('input[name="jml_min_amount[]"]').eq(0).val() || 0;
            var uom    = $('#uom').val() || '-';
            if(code === "" || name === "") { alert("Please fill Code and Name!"); return; }
            if(isCapping) {
                var targetBy = $('#target_by').val();
                if(targetBy === 'qty' && (!qty || qty == 0)) { alert("Target Qty is required when Capping is ON"); return; }
                if(targetBy === 'amount' && (!amt || amt == '0')) { alert("Target Amount is required when Capping is ON"); return; }
            }
            t.row.add([
                1, 
                code + '<input type="hidden" name="cust_code[]" value="'+code+'">', 
                name + '<input type="hidden" name="cust_name[]" value="'+name+'">',
                '<div class="text-right">' + minQty + '<input type="hidden" name="cust_min_qty[]" value="'+minQty+'"></div>',
                '<div class="text-right">' + minAmt + '<input type="hidden" name="cust_min_amt[]" value="'+minAmt+'"></div>',
                '<div class="text-right">' + qty + '<input type="hidden" name="cust_target_qty[]" value="'+qty+'"></div>',
                '<div class="text-right">' + amt + '<input type="hidden" name="cust_target_amt[]" value="'+amt+'"></div>',
                '<div class="text-center">' + uom + '<input type="hidden" name="cust_uom[]" value="'+uom+'"></div>',
                '<div class="text-center"><button type="button" class="btn btn-xs btn-danger text-white remove-row"><i class="fas fa-trash"></i></button></div>'
            ]).draw(false);
            $('#input_cust_code').val(''); $('#input_cust_name').val('');
            $('#input_target_qty').val(''); $('#input_target_amt').val('0');
            updateRowNumbers();
        };
        $('#table-customer tbody').on('click', '.remove-row', function () {
            t.row($(this).parents('tr')).remove().draw();
            updateRowNumbers();
        });
        function updateRowNumbers() {
            t.on('order.dt search.dt', function () {
                t.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
                    cell.innerHTML = i+1;
                });
            }).draw();
        }
        updateCappingLogic();
        // 9. LOGIC MODAL SUMMARY
        $('#btn-pre-submit').click(function() {
            var form = $('#form-sb')[0];
            if(form.checkValidity() === false) { form.reportValidity(); return; }
            var sbNo    = $('input[name="SalesBriefNo"]').val();
            var name    = $('input[name="SalesBriefName"]').val();
            var start   = $('input[name="fromdate"]').val();
            var end     = $('input[name="todate"]').val();
            var mech    = $('select[name="promo_mechanism"]').val() || '-';
            var typeText= $('#pilihan-promo option:selected').text(); 
            if(!$('#pilihan-promo').val()) typeText = '-';
            var budget  = $('input[name="budget_amount"]').val();
            var calc    = $('#target_by option:selected').text();
            if(!$('#target_by').val()) calc = '-';
            var isCap   = $('#capping-promo').is(':checked') ? '<span class="text-success">ON</span>' : '<span class="text-secondary">OFF</span>';
            var storeCount = $('#table-customer tbody tr').length;
            $('#sum-no').text(sbNo);
            $('#sum-name').text(name);
            $('#sum-start').text(start);
            $('#sum-end').text(end);
            $('#sum-mech').text(mech);
            $('#sum-type').text(typeText);
            $('#sum-budget').text('Rp ' + budget);
            $('#sum-store-count').text(storeCount);
            $('#sum-calc').text(calc.split('(')[0]);
            $('#sum-capping').html(isCap);
            $('#modal-summary').modal('show');
        });
        $('#btn-confirm-submit').click(function() {
            $('#form-sb').submit(); 
        });
    });
</script>

</body>
</html>