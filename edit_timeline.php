<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("Location: login.php"); exit(); }
include 'koneksi.php';

$id = $_GET['id'];
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM timeline WHERE id='$id'"));

if(isset($_POST['update'])){
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $comp = mysqli_real_escape_string($conn, $_POST['company']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    mysqli_query($conn, "UPDATE timeline SET year='$year', role='$role', company='$comp', description='$desc' WHERE id='$id'");
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
    <div class="card col-md-6 mx-auto shadow border-0">
        <div class="card-header bg-info text-white fw-bold">Edit Career Journey</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="fw-bold">Tahun</label>
                    <input type="text" name="year" class="form-control" value="<?=$d['year']?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Role / Posisi</label>
                    <input type="text" name="role" class="form-control" value="<?=$d['role']?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Perusahaan</label>
                    <input type="text" name="company" class="form-control" value="<?=$d['company']?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="4"><?=$d['description']?></textarea>
                </div>
                <button type="submit" name="update" class="btn btn-info text-white w-100 fw-bold">UPDATE DATA</button>
                <a href="admin.php?tab=time-pane" class="btn btn-link w-100 text-decoration-none text-muted mt-2">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>