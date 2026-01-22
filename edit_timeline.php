<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("Location: login.php"); exit(); }
include 'koneksi.php'; 

$id = $_GET['id'];
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM timeline WHERE id='$id'"));

function setFlash($msg, $type='success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if(isset($_POST['update'])){
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $s_date = mysqli_real_escape_string($conn, $_POST['sort_date']); // 🔥 Tanggal Sortir
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $comp = mysqli_real_escape_string($conn, $_POST['company']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    $img_sql = ""; 
    if(!empty($_FILES['image']['name'])) {
        $img_name = "cartoon_" . time() . ".png";
        if(!empty($d['image']) && file_exists('assets/img/'.$d['image'])) unlink('assets/img/'.$d['image']);
        move_uploaded_file($_FILES['image']['tmp_name'], 'assets/img/' . $img_name);
        $img_sql = ", image='$img_name'";
    }

    // Update Query
    $query = "UPDATE timeline SET year='$year', sort_date='$s_date', role='$role', company='$comp', description='$desc' $img_sql WHERE id='$id'";
    
    if(mysqli_query($conn, $query)) {
        setFlash('Timeline Berhasil Diupdate!');
    } else {
        setFlash('Gagal: '.mysqli_error($conn), 'error');
    }
    header("Location: admin.php?tab=time-pane"); exit();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Career</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>body { background: #f8f9fa; } .note-editor .dropdown-toggle::after { all: unset; }</style>
</head>
<body class="p-4">
    <div class="container">
        <div class="card col-md-8 mx-auto shadow border-0 rounded-4">
            <div class="card-header bg-info text-white fw-bold p-3 d-flex justify-content-between">
                <span>✏️ Edit Career Journey</span>
                <a href="admin.php?tab=time-pane" class="btn btn-sm btn-light text-info fw-bold">Kembali</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="fw-bold small text-muted">Teks Tahun</label>
                            <input type="text" name="year" class="form-control" value="<?=$d['year']?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold small text-muted">Tgl Mulai (Sorting)</label>
                            <input type="date" name="sort_date" class="form-control" value="<?=$d['sort_date']?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold small text-muted">Perusahaan</label>
                            <input type="text" name="company" class="form-control" value="<?=$d['company']?>" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="fw-bold small text-muted">Role / Posisi</label>
                            <input type="text" name="role" class="form-control" value="<?=$d['role']?>" required>
                        </div>
                        <div class="col-12 mb-4">
                            <label class="fw-bold small text-muted mb-1">Deskripsi</label>
                            <textarea id="summernote" name="description" required><?=$d['description']?></textarea>
                        </div>
                        <div class="col-12 mb-4">
                            <label class="fw-bold small text-muted">Update Kartun Popup</label>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <?php if(!empty($d['image'])): ?>
                                    <img src="assets/img/<?=$d['image']?>" width="60" class="rounded border p-1">
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border p-2">No Image</span>
                                <?php endif; ?>
                                <input type="file" name="image" class="form-control">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update" class="btn btn-info text-white w-100 fw-bold py-2">UPDATE DATA</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>$('#summernote').summernote({ tabsize: 2, height: 200 });</script>
</body>
</html>