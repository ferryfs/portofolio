<?php
session_start();
// CEK APAKAH SUDAH LOGIN?
if ($_SESSION['status'] != "login") {
    header("Location: login.php?pesan=belum_login");
    exit();
}

include 'koneksi.php';
// ... lanjut kodingan admin di bawah ...

// --- LOGIC TAMBAH DATA (CREATE) ---
if (isset($_POST['simpan'])) {
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $tech  = $_POST['tech_stack'];
    $link  = $_POST['link_demo'];
    
    // Logic Upload Gambar
    $nama_file = $_FILES['image']['name'];
    $source    = $_FILES['image']['tmp_name'];
    $folder    = './assets/img/';

    // Pindahkan gambar dari sementara ke folder aset
    move_uploaded_file($source, $folder . $nama_file);

    // Masukkan ke Database
    $insert = mysqli_query($conn, "INSERT INTO projects VALUES (
        NULL, 
        '$title', 
        '$desc', 
        '$nama_file', 
        '$tech', 
        '$link', 
        '#'
    )");

    if ($insert) {
        echo "<script>alert('Berhasil Nambah Proyek!'); window.location='admin.php';</script>";
    } else {
        echo "Gagal: " . mysqli_error($conn);
    }
}

// --- LOGIC HAPUS DATA (DELETE) ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM projects WHERE id = '$id'");
    echo "<script>alert('Data Terhapus!'); window.location='admin.php';</script>";
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Admin - Ferry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>⚙️ CMS Admin Panel</h2>
    <div>
        <a href="index.php" class="btn btn-secondary me-2">Lihat Web</a>
        <a href="logout.php" class="btn btn-danger">Logout</a> </div>
    </div>

        <div class="card shadow mb-5">
            <div class="card-header bg-primary text-white fw-bold">Tambah Proyek Baru</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Judul Proyek</label>
                            <input type="text" name="title" class="form-control" required placeholder="Contoh: Aplikasi Kasir">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tech Stack</label>
                            <input type="text" name="tech_stack" class="form-control" required placeholder="Contoh: PHP, MySQL, Bootstrap">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Deskripsi Singkat</label>
                            <textarea name="description" class="form-control" rows="2" required placeholder="Jelasin dikit tentang aplikasinya..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Upload Gambar (Screenshot)</label>
                            <input type="file" name="image" class="form-control" required>
                            <small class="text-muted">Format: JPG/PNG. Pastikan landscape biar bagus.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Link Demo (Folder Apps)</label>
                            <input type="text" name="link_demo" class="form-control" placeholder="Contoh: apps/kasir/">
                        </div>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-success w-100 fw-bold">
                        <i class="bi bi-save"></i> SIMPAN PROYEK
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white fw-bold">Daftar Proyek Saat Ini</div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Gambar</th>
                            <th>Judul & Deskripsi</th>
                            <th>Tech Stack</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $tampil = mysqli_query($conn, "SELECT * FROM projects ORDER BY id DESC");
                        while($data = mysqli_fetch_array($tampil)) :
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td>
                                <img src="assets/img/<?= $data['image'] ?>" width="80" class="rounded border">
                            </td>
                            <td>
                                <strong><?= $data['title'] ?></strong><br>
                                <small class="text-muted"><?= $data['description'] ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?= $data['tech_stack'] ?></span></td>
                            <td>
                                <a href="edit.php?id=<?= $data['id'] ?>" class="btn btn-warning btn-sm text-white mb-1">
                                  <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="admin.php?hapus=<?= $data['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin mau hapus proyek ini?')">
                                  <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>