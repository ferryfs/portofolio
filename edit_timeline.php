<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("Location: login.php"); exit(); }
include 'koneksi.php';

$id = $_GET['id'];
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM timeline WHERE id='$id'"));

// Helper Flash
function setFlash($msg, $type='success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if(isset($_POST['update'])){
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $comp = mysqli_real_escape_string($conn, $_POST['company']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    if(mysqli_query($conn, "UPDATE timeline SET year='$year', role='$role', company='$comp', description='$desc' WHERE id='$id'")) {
        setFlash('Timeline Berhasil Diupdate!');
    } else {
        setFlash('Gagal: '.mysqli_error($conn), 'error');
    }

    // Balik ke Tab Journey
    header("Location: admin.php?tab=time-pane"); exit();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Career</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="card col-md-6 mx-auto shadow border-0 rounded-4">
        <div class="card-header bg-info text-white fw-bold p-3">✏️ Edit Career Journey</div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="fw-bold small">Tahun</label>
                    <input type="text" name="year" class="form-control" value="<?=$d['year']?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Role / Posisi</label>
                    <input type="text" name="role" class="form-control" value="<?=$d['role']?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Perusahaan</label>
                    <input type="text" name="company" class="form-control" value="<?=$d['company']?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="4"><?=$d['description']?></textarea>
                </div>
                <button type="submit" name="update" class="btn btn-info text-white w-100 fw-bold py-2">UPDATE DATA</button>
                <a href="admin.php?tab=time-pane" class="btn btn-light w-100 text-muted mt-2">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>