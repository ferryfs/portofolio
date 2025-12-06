<?php
session_start();
// CEK APAKAH SUDAH LOGIN?
if ($_SESSION['status'] != "login") {
    header("Location: login.php?pesan=belum_login");
    exit();
}

include 'koneksi.php';
// ... lanjut kodingan admin di bawah ...

// 1. Ambil ID dari URL
$id = $_GET['id'];

// 2. Ambil Data Lama dari Database
$query = mysqli_query($conn, "SELECT * FROM projects WHERE id = '$id'");
$data  = mysqli_fetch_array($query);

// Kalau data gak ada, tendang balik
if (!$data) {
    header("Location: admin.php");
}

// 3. PROSES UPDATE (SAAT TOMBOL DITEKAN)
if (isset($_POST['update'])) {
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $tech  = $_POST['tech_stack'];
    $link  = $_POST['link_demo'];
    $case  = $_POST['link_case']; // <--- INI BARU
    
    $nama_file = $_FILES['image']['name'];
    
    if ($nama_file != "") {
        $source = $_FILES['image']['tmp_name'];
        move_uploaded_file($source, './assets/img/' . $nama_file);
        
        // Update dengan Gambar Baru & Link Case
        $update = mysqli_query($conn, "UPDATE projects SET 
            title='$title', 
            description='$desc', 
            image='$nama_file', 
            tech_stack='$tech', 
            link_demo='$link',
            link_case='$case'
            WHERE id='$id'");
    } else {
        // Update Tanpa Ganti Gambar
        $update = mysqli_query($conn, "UPDATE projects SET 
            title='$title', 
            description='$desc', 
            tech_stack='$tech', 
            link_demo='$link',
            link_case='$case' 
            WHERE id='$id'");
    }

    if ($update) {
        echo "<script>alert('Data Berhasil Diupdate!'); window.location='admin.php';</script>";
    } else {
        echo "Gagal: " . mysqli_error($conn);
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow col-md-8 mx-auto">
            <div class="card-header bg-warning text-white fw-bold">Edit Proyek</div>
            <div class="card-body">
                
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label>Judul Proyek</label>
                        <input type="text" name="title" class="form-control" value="<?= $data['title'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Tech Stack</label>
                        <input type="text" name="tech_stack" class="form-control" value="<?= $data['tech_stack'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Deskripsi Singkat</label>
                        <textarea name="description" class="form-control" rows="3" required><?= $data['description'] ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label>Gambar Saat Ini</label><br>
                        <img src="assets/img/<?= $data['image'] ?>" width="150" class="rounded border mb-2">
                        <input type="file" name="image" class="form-control">
                        <small class="text-muted text-danger">*Biarkan kosong jika tidak ingin mengganti gambar.</small>
                    </div>

                    <div class="mb-3">
                        <label>Link Demo</label>
                        <input type="text" name="link_demo" class="form-control" value="<?= $data['link_demo'] ?>">
                    </div>
                    <div class="mb-3">
                        <label>Link Studi Kasus</label>
                        <input type="text" name="link_case" class="form-control" value="<?= $data['link_case'] ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="update" class="btn btn-success w-100">SIMPAN PERUBAHAN</button>
                        <a href="admin.php" class="btn btn-secondary w-100">BATAL</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>
</html>