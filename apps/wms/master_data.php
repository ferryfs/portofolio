<?php
// 🔥 1. PASANG SESSION DI PALING ATAS
session_name("WMS_APP_SESSION");
session_start();

// 🔥 2. CEK KEAMANAN (Opsional tapi PENTING)
// Biar orang gak bisa buka file ini langsung lewat URL tanpa login
if(!isset($_SESSION['wms_login'])) {
    exit("Akses Ditolak. Silakan Login.");
}
include '../../koneksi.php';

// --- LOGIC CRUD PRODUK ---
if(isset($_POST['save_product'])) {
    $uuid = $_POST['product_uuid'] ?: "PROD-" . rand(1000,9999);
    $code = $_POST['product_code'];
    $desc = $_POST['description'];
    $uom  = $_POST['base_uom'];
    
    // Cek Edit atau Baru
    if($_POST['is_edit'] == '1') {
        $sql = "UPDATE wms_products SET product_code='$code', description='$desc', base_uom='$uom' WHERE product_uuid='$uuid'";
        $msg = "✅ Product Updated: $code";
        
        // [LOG] MENCATAT UPDATE PRODUK
        catat_log($conn, 'ADMIN', 'UPDATE', 'PRODUCT', "Mengubah data produk: $code ($desc)");
        
    } else {
        $sql = "INSERT INTO wms_products VALUES ('$uuid', '$code', '$desc', '$uom', 'PAL', 50)"; 
        $msg = "✅ Product Created: $code";
        
        // [LOG] MENCATAT PRODUK BARU
        catat_log($conn, 'ADMIN', 'CREATE', 'PRODUCT', "Menambah produk baru: $code ($desc)");
    }
    mysqli_query($conn, $sql);
}

if(isset($_GET['del_prod'])) {
    $id = $_GET['del_prod'];
    
    // Ambil kode produk dulu sebelum dihapus (buat isi log)
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_code FROM wms_products WHERE product_uuid='$id'"));
    $kode_lama = $cek['product_code'];
    
    mysqli_query($conn, "DELETE FROM wms_products WHERE product_uuid='$id'");
    $msg = "🗑️ Product Deleted.";
    
    // [LOG] MENCATAT PENGHAPUSAN
    catat_log($conn, 'ADMIN', 'DELETE', 'PRODUCT', "Menghapus produk: $kode_lama");
}

// --- LOGIC CRUD BIN ---
if(isset($_POST['save_bin'])) {
    $bin  = $_POST['lgpla'];
    $type = $_POST['lgtyp'];
    $max  = $_POST['max_weight'];
    
    $sql = "INSERT INTO wms_storage_bins (lgpla, lgtyp, max_weight) VALUES ('$bin', '$type', '$max') 
            ON DUPLICATE KEY UPDATE max_weight='$max', lgtyp='$type'";
    
    if(mysqli_query($conn, $sql)) {
        $msg = "✅ Bin Saved: $bin";
        // [LOG] BIN SAVED
        catat_log($conn, 'ADMIN', 'UPSERT', 'BIN', "Menyimpan Bin: $bin (Type: $type)");
    }
}

if(isset($_GET['del_bin'])) {
    $id = $_GET['del_bin'];
    mysqli_query($conn, "DELETE FROM wms_storage_bins WHERE lgpla='$id'");
    $msg = "🗑️ Bin Deleted.";
    
    // [LOG] BIN DELETED
    catat_log($conn, 'ADMIN', 'DELETE', 'BIN', "Menghapus Bin: $id");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Master Data EWM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h3><i class="bi bi-database-gear"></i> Master Data Management</h3>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-success alert-dismissible fade show'>$msg <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"; ?>

    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active fw-bold" id="prod-tab" data-bs-toggle="tab" href="#products" role="tab"><i class="bi bi-box-seam"></i> Products</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold" id="bin-tab" data-bs-toggle="tab" href="#bins" role="tab"><i class="bi bi-grid-3x3"></i> Storage Bins</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold" id="partner-tab" data-bs-toggle="tab" href="#partners" role="tab"><i class="bi bi-people"></i> Partners (BP)</a>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        
        <div class="tab-pane fade show active" id="products" role="tabpanel">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between">
                    <h5 class="mb-0">Product List</h5>
                    <button class="btn btn-primary btn-sm" onclick="openModalProd()"><i class="bi bi-plus-lg"></i> Add New</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Base UoM</th>
                                <th>Packing Rule</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $q = mysqli_query($conn, "SELECT * FROM wms_products ORDER BY product_code ASC");
                            while($r = mysqli_fetch_assoc($q)):
                            ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= $r['product_code'] ?></td>
                                <td><?= $r['description'] ?></td>
                                <td><?= $r['base_uom'] ?></td>
                                <td class="small text-muted">1 <?= $r['capacity_uom'] ?> = <?= $r['conversion_qty'] ?> <?= $r['base_uom'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='editProd(<?= json_encode($r) ?>)'><i class="bi bi-pencil"></i></button>
                                    <a href="?del_prod=<?= $r['product_uuid'] ?>" class="btn btn-sm btn-danger btn-del"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="bins" role="tabpanel">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between">
                    <h5 class="mb-0">Storage Bin List</h5>
                    <button class="btn btn-info text-white btn-sm" onclick="openModalBin()"><i class="bi bi-plus-lg"></i> Add Bin</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Storage Bin</th>
                                <th>Type</th>
                                <th>Max Weight</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $q = mysqli_query($conn, "SELECT * FROM wms_storage_bins ORDER BY lgpla ASC");
                            while($r = mysqli_fetch_assoc($q)):
                            ?>
                            <tr>
                                <td class="fw-bold"><?= $r['lgpla'] ?></td>
                                <td><span class="badge bg-secondary"><?= $r['lgtyp'] ?></span></td>
                                <td><?= $r['max_weight'] ?> KG</td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='editBin(<?= json_encode($r) ?>)'><i class="bi bi-pencil"></i></button>
                                    <a href="?del_bin=<?= $r['lgpla'] ?>" class="btn btn-sm btn-danger btn-del"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="partners" role="tabpanel">
            <div class="alert alert-info">Fitur Business Partner Management (Vendor/Customer) akan segera hadir.</div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalProd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="prodTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="is_edit" id="is_edit_prod" value="0">
                    <input type="hidden" name="product_uuid" id="prod_uuid">
                    
                    <div class="mb-3">
                        <label>Product Code</label>
                        <input type="text" name="product_code" id="prod_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <input type="text" name="description" id="prod_desc" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Base UoM</label>
                        <select name="base_uom" id="prod_uom" class="form-select">
                            <option value="PCS">PCS</option>
                            <option value="BOX">BOX</option>
                            <option value="KG">KG</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_product" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Bin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Storage Bin (ID)</label>
                        <input type="text" name="lgpla" id="bin_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Storage Type</label>
                        <select name="lgtyp" id="bin_type" class="form-select">
                            <option value="0010">0010 - High Rack</option>
                            <option value="9010">9010 - GR Zone</option>
                            <option value="9020">9020 - GI Zone</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Max Weight</label>
                        <input type="number" name="max_weight" id="bin_weight" class="form-control" value="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_bin" class="btn btn-primary">Save Bin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// JS Modal Logic
var modalProd = new bootstrap.Modal(document.getElementById('modalProd'));
var modalBin = new bootstrap.Modal(document.getElementById('modalBin'));

function openModalProd() {
    document.getElementById('prodTitle').innerText = 'Add New Product';
    document.getElementById('is_edit_prod').value = '0';
    document.getElementById('prod_uuid').value = '';
    document.getElementById('prod_code').value = '';
    document.getElementById('prod_desc').value = '';
    modalProd.show();
}

function editProd(data) {
    document.getElementById('prodTitle').innerText = 'Edit Product';
    document.getElementById('is_edit_prod').value = '1';
    document.getElementById('prod_uuid').value = data.product_uuid;
    document.getElementById('prod_code').value = data.product_code;
    document.getElementById('prod_desc').value = data.description;
    document.getElementById('prod_uom').value = data.base_uom;
    modalProd.show();
}

function openModalBin() {
    document.getElementById('bin_code').value = '';
    document.getElementById('bin_code').readOnly = false; 
    modalBin.show();
}

function editBin(data) {
    document.getElementById('bin_code').value = data.lgpla;
    document.getElementById('bin_code').readOnly = true; 
    document.getElementById('bin_type').value = data.lgtyp;
    document.getElementById('bin_weight').value = data.max_weight;
    modalBin.show();
}

// SweetAlert Delete Confirmation
document.querySelectorAll('.btn-del').forEach(item => {
    item.addEventListener('click', event => {
        event.preventDefault();
        const url = item.getAttribute('href');
        Swal.fire({
            title: 'Are you sure?',
            text: "Data will be deleted permanently!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    })
});
</script>

</body>
</html>