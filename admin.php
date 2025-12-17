<?php
session_start();

// ============================================================
// 👉 1. CEK KEAMANAN (Wajib Paling Atas)
// Kalau belum login, tendang balik ke login.php
// ============================================================
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php?pesan=belum_login"); 
    exit(); 
}

include 'koneksi.php';

// ============================================================
// 👉 2. LOGIC UPDATE PROFIL (Tab: Kelola Profile)
// Ini buat update "Tentang Saya", "Kontak", "Statistik", & "Link CV"
// ============================================================
if (isset($_POST['update_profile'])) {
    $about = mysqli_real_escape_string($conn, $_POST['about_text']);
    $exp   = $_POST['years_exp'];
    $proj  = $_POST['projects_done'];
    $happy = $_POST['client_happy'];
    $email = $_POST['email'];
    $wa    = $_POST['whatsapp'];
    $linkd = $_POST['linkedin'];
    $cv    = $_POST['cv_link'];

    // Update data di tabel 'profile' (selalu ID=1)
    $upd = mysqli_query($conn, "UPDATE profile SET about_text='$about', years_exp='$exp', projects_done='$proj', client_happy='$happy', email='$email', whatsapp='$wa', linkedin='$linkd', cv_link='$cv' WHERE id=1");
    
    if($upd) echo "<script>alert('Profile Berhasil Diupdate!'); window.location='admin.php';</script>";
}

// ============================================================
// 👉 3. LOGIC TAMBAH PROJECT BARU (Tab: Kelola Projects)
// ============================================================
if (isset($_POST['simpan_project'])) {
    // Amankan Input Teks
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $tech  = mysqli_real_escape_string($conn, $_POST['tech_stack']);
    $link  = mysqli_real_escape_string($conn, $_POST['link_demo']);
    $creds = mysqli_real_escape_string($conn, $_POST['credentials']); // 👉 Data Credentials Baru
    
    // A. Upload Gambar Utama
    $nama_gambar = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], './assets/img/' . $nama_gambar);

    // B. Logic Hybrid Case Study (PDF vs URL)
    $final_case = "#"; // Default kosong
    
    // Cek: User upload PDF gak?
    if(!empty($_FILES['file_case']['name'])) {
        $nama_pdf = time()."_".$_FILES['file_case']['name']; // Kasih waktu biar nama gak bentrok
        move_uploaded_file($_FILES['file_case']['tmp_name'], './assets/docs/'.$nama_pdf);
        $final_case = $nama_pdf;
    } 
    // Cek: Kalau gak upload, dia isi Link URL gak?
    elseif (!empty($_POST['url_case'])) {
        $final_case = $_POST['url_case'];
    }

    // C. Masukin ke Database
    $ins = mysqli_query($conn, "INSERT INTO projects VALUES (NULL, '$title', '$desc', '$nama_gambar', '$tech', '$link', '$final_case', '$creds')");
    
    if($ins) echo "<script>alert('Project Baru Berhasil Ditambah!'); window.location='admin.php';</script>";
}

// ============================================================
// 👉 4. LOGIC HAPUS PROJECT
// Sekalian hapus file fisiknya (Gambar & PDF) biar server bersih
// ============================================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Ambil nama file dulu sebelum dihapus datanya
    $q = mysqli_query($conn, "SELECT image, link_case FROM projects WHERE id='$id'");
    $d = mysqli_fetch_assoc($q);
    
    // Hapus Gambar
    if(file_exists('./assets/img/'.$d['image'])) unlink('./assets/img/'.$d['image']);
    
    // Hapus PDF (Cuma kalau dia file, bukan link http)
    if(strpos($d['link_case'], 'http') === false && file_exists('./assets/docs/'.$d['link_case'])) {
        unlink('./assets/docs/'.$d['link_case']);
    }
    
    // Hapus dari Database
    mysqli_query($conn, "DELETE FROM projects WHERE id='$id'");
    echo "<script>alert('Data Terhapus!'); window.location='admin.php';</script>";
}

// ============================================================
// 👉 5. AMBIL DATA PROFILE SAAT INI
// Buat nampilin value di form edit profile
// ============================================================
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        
        <div class="d-flex justify-content-between mb-4">
            <h2>⚙️ Control Center</h2>
            <div>
                <a href="index.php" class="btn btn-secondary me-2" target="_blank">Lihat Web</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" id="proj-tab" data-bs-toggle="tab" data-bs-target="#proj-pane" type="button">📁 Kelola Projects</button></li>
            <li class="nav-item"><button class="nav-link" id="prof-tab" data-bs-toggle="tab" data-bs-target="#prof-pane" type="button">👤 Kelola Profile & Kontak</button></li>
        </ul>

        <div class="tab-content" id="myTabContent">
            
            <div class="tab-pane fade show active" id="proj-pane">
                
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white fw-bold">Tambah Project Baru</div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Judul</label><input type="text" name="title" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label>Tech Stack</label><input type="text" name="tech_stack" class="form-control" required></div>
                                <div class="col-md-12 mb-3"><label>Deskripsi</label><textarea name="description" class="form-control" rows="2" required></textarea></div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="fw-bold text-warning"><i class="bi bi-key"></i> Info Login / Credentials (Untuk Modal Popup)</label>
                                    <textarea name="credentials" class="form-control bg-light" rows="2" placeholder="Contoh: Username: admin | Password: 123"></textarea>
                                </div>

                                <div class="col-md-6 mb-3"><label>Gambar</label><input type="file" name="image" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label>Link Demo</label><input type="text" name="link_demo" class="form-control"></div>
                                
                                <div class="col-md-12 mb-3 border p-3 rounded">
                                    <label class="fw-bold text-primary">Studi Kasus (Hybrid)</label>
                                    <div class="row">
                                        <div class="col-6"><input type="file" name="file_case" class="form-control" accept=".pdf"><small>Upload PDF</small></div>
                                        <div class="col-6"><input type="text" name="url_case" class="form-control" placeholder="Atau Link URL"><small>Link Luar</small></div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="simpan_project" class="btn btn-success w-100">SIMPAN PROJECT</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead class="table-dark"><tr><th>No</th><th>Judul</th><th>Credentials</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php $no=1; $qp=mysqli_query($conn,"SELECT * FROM projects ORDER BY id DESC"); while($d=mysqli_fetch_assoc($qp)): ?>
                                <tr>
                                    <td><?=$no++?></td>
                                    <td><?=$d['title']?></td>
                                    <td><small class="text-muted"><?=$d['credentials']?></small></td>
                                    <td>
                                        <a href="edit.php?id=<?=$d['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                        <a href="admin.php?hapus=<?=$d['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="prof-pane">
                <div class="card shadow">
                    <div class="card-header bg-info text-white fw-bold">Edit Profile & Kontak</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="fw-bold">Tentang Saya (Deskripsi Panjang)</label>
                                <textarea name="about_text" class="form-control" rows="4"><?=$p['about_text']?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><label>Tahun Pengalaman</label><input type="number" name="years_exp" class="form-control" value="<?=$p['years_exp']?>"></div>
                                <div class="col-md-4"><label>Proyek Selesai</label><input type="number" name="projects_done" class="form-control" value="<?=$p['projects_done']?>"></div>
                                <div class="col-md-4"><label>Kepuasan Klien (%)</label><input type="number" name="client_happy" class="form-control" value="<?=$p['client_happy']?>"></div>
                            </div>
                            <hr>
                            
                            <div class="mb-3"><label>Link CV (Google Drive/PDF)</label><input type="text" name="cv_link" class="form-control" value="<?=$p['cv_link']?>"></div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><label>Email</label><input type="email" name="email" class="form-control" value="<?=$p['email']?>"></div>
                                <div class="col-md-4"><label>WhatsApp (Format: 628...)</label><input type="text" name="whatsapp" class="form-control" value="<?=$p['whatsapp']?>"></div>
                                <div class="col-md-4"><label>LinkedIn URL</label><input type="text" name="linkedin" class="form-control" value="<?=$p['linkedin']?>"></div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary w-100">UPDATE PROFILE</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>