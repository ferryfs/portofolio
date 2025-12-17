<?php
session_start();
// 1. CEK LOGIN
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php"); 
    exit(); 
}

include 'koneksi.php';

// ============================================================
// 👉 1. LOGIC UPDATE PROFILE (FOTO, TEKS INDO/INGGRIS, KONTAK)
// ============================================================
if (isset($_POST['update_profile'])) {
    // Ambil Input Teks (Multi-Bahasa)
    $greeting = mysqli_real_escape_string($conn, $_POST['hero_greeting']); 
    $greeting_en = mysqli_real_escape_string($conn, $_POST['hero_greeting_en']);
    
    $title = mysqli_real_escape_string($conn, $_POST['hero_title']);    
    $title_en = mysqli_real_escape_string($conn, $_POST['hero_title_en']);
    
    $desc = mysqli_real_escape_string($conn, $_POST['hero_desc']);     
    $desc_en = mysqli_real_escape_string($conn, $_POST['hero_desc_en']);
    
    $about = mysqli_real_escape_string($conn, $_POST['about_text']);    
    $about_en = mysqli_real_escape_string($conn, $_POST['about_text_en']);
    
    $p_title = mysqli_real_escape_string($conn, $_POST['project_title']); 
    $p_title_en = mysqli_real_escape_string($conn, $_POST['project_title_en']);
    
    $p_desc = mysqli_real_escape_string($conn, $_POST['project_desc']);  
    $p_desc_en = mysqli_real_escape_string($conn, $_POST['project_desc_en']);

    // Data Statistik & Kontak
    $exp = $_POST['years_exp']; 
    $proj = $_POST['projects_done']; 
    $happy = $_POST['client_happy'];
    $email = $_POST['email']; 
    $wa = $_POST['whatsapp']; 
    $linkd = $_POST['linkedin']; 
    $cv = $_POST['cv_link'];

    // LOGIC UPLOAD FOTO PROFIL (YANG SEMPAT HILANG)
    // Ambil nama foto lama dulu
    $q_old = mysqli_query($conn, "SELECT profile_pic FROM profile WHERE id=1");
    $d_old = mysqli_fetch_assoc($q_old);
    $foto_db = $d_old['profile_pic']; // Default pake yang lama

    // Kalau user upload foto baru
    if(!empty($_FILES['profile_pic']['name'])){
        $foto_baru = "profile_" . time() . ".jpg"; // Rename biar unik
        // Upload ke folder
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], './assets/img/' . $foto_baru);
        $foto_db = $foto_baru; // Update nama file buat database
    }

    // Update Database Profile
    $sql = "UPDATE profile SET 
            hero_greeting='$greeting', hero_greeting_en='$greeting_en',
            hero_title='$title', hero_title_en='$title_en',
            hero_desc='$desc', hero_desc_en='$desc_en',
            about_text='$about', about_text_en='$about_en',
            project_title='$p_title', project_title_en='$p_title_en',
            project_desc='$p_desc', project_desc_en='$p_desc_en',
            years_exp='$exp', projects_done='$proj', client_happy='$happy',
            email='$email', whatsapp='$wa', linkedin='$linkd', cv_link='$cv',
            profile_pic='$foto_db' 
            WHERE id=1";

    if(mysqli_query($conn, $sql)) echo "<script>alert('Profile & Foto Berhasil Diupdate!'); window.location='admin.php';</script>";
}

// ============================================================
// 👉 2. LOGIC TAMBAH PROJECT (KATEGORI, CREDENTIALS, HYBRID)
// ============================================================
if (isset($_POST['simpan_project'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $tech  = mysqli_real_escape_string($conn, $_POST['tech_stack']);
    $link  = mysqli_real_escape_string($conn, $_POST['link_demo']);
    $creds = mysqli_real_escape_string($conn, $_POST['credentials']);
    $cat   = $_POST['category']; // Kategori Work/Personal

    // Upload Gambar Project
    $nama_gambar = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], './assets/img/' . $nama_gambar);

    // Hybrid Case Study
    $final_case = "#";
    if(!empty($_FILES['file_case']['name'])) {
        $nama_pdf = time()."_".$_FILES['file_case']['name'];
        move_uploaded_file($_FILES['file_case']['tmp_name'], './assets/docs/'.$nama_pdf);
        $final_case = $nama_pdf;
    } elseif (!empty($_POST['url_case'])) {
        $final_case = $_POST['url_case'];
    }

    $ins = mysqli_query($conn, "INSERT INTO projects VALUES (NULL, '$title', '$desc', '$nama_gambar', '$tech', '$link', '$final_case', '$creds', '$cat')");
    if($ins) echo "<script>alert('Project Berhasil Ditambah!'); window.location='admin.php';</script>";
}

// ============================================================
// 👉 3. LOGIC TAMBAH TIMELINE (PENGALAMAN KERJA)
// ============================================================
if (isset($_POST['simpan_timeline'])) {
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $comp = mysqli_real_escape_string($conn, $_POST['company']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    $ins_time = mysqli_query($conn, "INSERT INTO timeline VALUES (NULL, '$year', '$role', '$comp', '$desc')");
    if($ins_time) echo "<script>alert('Timeline Berhasil Ditambah!'); window.location='admin.php';</script>";
}

// ============================================================
// 👉 4. LOGIC HAPUS (PROJECT & TIMELINE)
// ============================================================
// Hapus Project
if (isset($_GET['hapus_proj'])) {
    $id = $_GET['hapus_proj'];
    $q = mysqli_query($conn, "SELECT image, link_case FROM projects WHERE id='$id'");
    $d = mysqli_fetch_assoc($q);
    if(file_exists('./assets/img/'.$d['image'])) unlink('./assets/img/'.$d['image']);
    if(strpos($d['link_case'], 'http') === false && file_exists('./assets/docs/'.$d['link_case'])) unlink('./assets/docs/'.$d['link_case']);
    mysqli_query($conn, "DELETE FROM projects WHERE id='$id'");
    echo "<script>alert('Project Dihapus!'); window.location='admin.php';</script>";
}
// Hapus Timeline
if (isset($_GET['hapus_time'])) {
    $id = $_GET['hapus_time'];
    mysqli_query($conn, "DELETE FROM timeline WHERE id='$id'");
    echo "<script>alert('Timeline Dihapus!'); window.location='admin.php';</script>";
}

// Ambil Data Profile buat ditampilin di form
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel Lengkap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .nav-tabs .nav-link.active { font-weight: bold; border-top: 3px solid #0d6efd; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>⚙️ Admin Control Center</h2>
            <div>
                <a href="index.php" class="btn btn-secondary me-2" target="_blank"><i class="bi bi-eye"></i> Lihat Web</a>
                <a href="logout.php" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" id="prof-tab" data-bs-toggle="tab" data-bs-target="#prof-pane">👤 Profile & Teks</button></li>
            <li class="nav-item"><button class="nav-link" id="proj-tab" data-bs-toggle="tab" data-bs-target="#proj-pane">📁 Projects</button></li>
            <li class="nav-item"><button class="nav-link" id="time-tab" data-bs-toggle="tab" data-bs-target="#time-pane">⏳ Timeline Karir</button></li>
        </ul>

        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="prof-pane">
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="card p-4 mb-4">
                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-camera"></i> Foto Profil</h5>
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <img src="assets/img/<?=$p['profile_pic']?>" width="100" height="100" class="rounded-circle border" style="object-fit:cover;">
                            </div>
                            <div class="col-md-10">
                                <label class="form-label">Ganti Foto Profil</label>
                                <input type="file" name="profile_pic" class="form-control" accept="image/*">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengganti foto.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 mb-4">
                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-translate"></i> Edit Konten (Indo & English)</h5>
                        
                        <div class="row mb-3">
                            <div class="col-6"><label class="fw-bold">🇮🇩 Salam (ID)</label><input type="text" name="hero_greeting" class="form-control" value="<?=$p['hero_greeting']?>"></div>
                            <div class="col-6"><label class="fw-bold">🇬🇧 Salam (EN)</label><input type="text" name="hero_greeting_en" class="form-control" value="<?=$p['hero_greeting_en']?>"></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6"><label class="fw-bold">🇮🇩 Judul Besar (ID)</label><input type="text" name="hero_title" class="form-control" value="<?=$p['hero_title']?>"></div>
                            <div class="col-6"><label class="fw-bold">🇬🇧 Judul Besar (EN)</label><input type="text" name="hero_title_en" class="form-control" value="<?=$p['hero_title_en']?>"></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6"><label class="fw-bold">🇮🇩 Deskripsi Hero (ID)</label><textarea name="hero_desc" class="form-control" rows="2"><?=$p['hero_desc']?></textarea></div>
                            <div class="col-6"><label class="fw-bold">🇬🇧 Deskripsi Hero (EN)</label><textarea name="hero_desc_en" class="form-control" rows="2"><?=$p['hero_desc_en']?></textarea></div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-6"><label class="fw-bold">🇮🇩 Tentang Saya (ID)</label><textarea name="about_text" class="form-control" rows="4"><?=$p['about_text']?></textarea></div>
                            <div class="col-6"><label class="fw-bold">🇬🇧 Tentang Saya (EN)</label><textarea name="about_text_en" class="form-control" rows="4"><?=$p['about_text_en']?></textarea></div>
                        </div>
                    </div>

                    <div class="card p-4 mb-4">
                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-graph-up"></i> Statistik & Kontak</h5>
                        <div class="row g-3">
                            <div class="col-md-4"><label>Tahun Exp</label><input type="number" name="years_exp" class="form-control" value="<?=$p['years_exp']?>"></div>
                            <div class="col-md-4"><label>Jml Project</label><input type="number" name="projects_done" class="form-control" value="<?=$p['projects_done']?>"></div>
                            <div class="col-md-4"><label>Klien Happy (%)</label><input type="number" name="client_happy" class="form-control" value="<?=$p['client_happy']?>"></div>
                            
                            <div class="col-12"><hr></div>

                            <div class="col-md-6"><label>Link CV (GDrive/PDF)</label><input type="text" name="cv_link" class="form-control" value="<?=$p['cv_link']?>"></div>
                            <div class="col-md-6"><label>Email</label><input type="email" name="email" class="form-control" value="<?=$p['email']?>"></div>
                            <div class="col-md-6"><label>WhatsApp</label><input type="text" name="whatsapp" class="form-control" value="<?=$p['whatsapp']?>"></div>
                            <div class="col-md-6"><label>LinkedIn</label><input type="text" name="linkedin" class="form-control" value="<?=$p['linkedin']?>"></div>
                        </div>
                    </div>

                    <input type="hidden" name="project_title" value="<?=$p['project_title']?>">
                    <input type="hidden" name="project_title_en" value="<?=$p['project_title_en']?>">
                    <input type="hidden" name="project_desc" value="<?=$p['project_desc']?>">
                    <input type="hidden" name="project_desc_en" value="<?=$p['project_desc_en']?>">

                    <button type="submit" name="update_profile" class="btn btn-primary w-100 fw-bold py-2">SIMPAN SEMUA PERUBAHAN PROFILE</button>
                </form>
            </div>

            <div class="tab-pane fade" id="proj-pane">
                <div class="card p-4 shadow mb-4">
                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-plus-circle"></i> Tambah Project Baru</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Judul Project</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label text-danger fw-bold">Kategori (Work / Personal)</label>
                                <select name="category" class="form-select">
                                    <option value="work">🏢 Work Project (Kantor/Profesional)</option>
                                    <option value="personal">🚀 Personal Project (Belajar/Iseng)</option>
                                </select>
                            </div>

                            <div class="col-md-12"><label class="form-label">Tech Stack</label><input type="text" name="tech_stack" class="form-control" placeholder="PHP, Laravel, MySQL..." required></div>
                            <div class="col-md-12"><label class="form-label">Deskripsi Singkat</label><textarea name="description" class="form-control" rows="2" required></textarea></div>
                            
                            <div class="col-md-12">
                                <label class="form-label text-warning fw-bold"><i class="bi bi-key"></i> Credentials / Akses Demo</label>
                                <textarea name="credentials" class="form-control bg-light" rows="2" placeholder="Contoh: Username: admin | Password: 123"></textarea>
                            </div>

                            <div class="col-md-6"><label class="form-label">Upload Gambar</label><input type="file" name="image" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Link Demo</label><input type="text" name="link_demo" class="form-control"></div>
                            
                            <div class="col-md-12">
                                <div class="card bg-light border p-3">
                                    <label class="form-label fw-bold">Studi Kasus (Pilih Salah Satu)</label>
                                    <div class="row g-2">
                                        <div class="col-6"><input type="file" name="file_case" class="form-control"><small class="text-muted">Upload PDF</small></div>
                                        <div class="col-6"><input type="text" name="url_case" class="form-control" placeholder="Atau Link URL Notion/Medium"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="simpan_project" class="btn btn-success w-100 fw-bold mt-3">SIMPAN PROJECT</button>
                    </form>
                </div>

                <div class="card shadow p-0 overflow-hidden">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr><th>No</th><th>Judul</th><th>Kategori</th><th>Credentials</th><th class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php $no=1; $qp=mysqli_query($conn,"SELECT * FROM projects ORDER BY id DESC"); while($d=mysqli_fetch_assoc($qp)): ?>
                            <tr>
                                <td><?=$no++?></td>
                                <td class="fw-bold"><?=$d['title']?></td>
                                <td>
                                    <?php if($d['category']=='work') echo '<span class="badge bg-primary">🏢 Work</span>'; else echo '<span class="badge bg-success">🚀 Personal</span>'; ?>
                                </td>
                                <td><small class="text-muted"><?=substr($d['credentials'],0,30)?>...</small></td>
                                <td class="text-end">
                                    <a href="edit.php?id=<?=$d['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                    <a href="admin.php?hapus_proj=<?=$d['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus Project?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="time-pane">
                <div class="card p-4 shadow mb-4">
                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-clock-history"></i> Tambah Pengalaman Kerja</h5>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Tahun</label><input type="text" name="year" class="form-control" placeholder="2023 - Sekarang" required></div>
                            <div class="col-md-4"><label class="form-label">Role / Posisi</label><input type="text" name="role" class="form-control" placeholder="Fullstack Dev" required></div>
                            <div class="col-md-4"><label class="form-label">Perusahaan</label><input type="text" name="company" class="form-control" placeholder="PT Mencari Cinta" required></div>
                            <div class="col-12"><label class="form-label">Deskripsi Pekerjaan</label><textarea name="description" class="form-control" placeholder="Apa yang kamu kerjakan di sana?" required></textarea></div>
                        </div>
                        <button type="submit" name="simpan_timeline" class="btn btn-success w-100 fw-bold mt-3">TAMBAH TIMELINE</button>
                    </form>
                </div>

                <div class="card shadow p-0 overflow-hidden">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark"><tr><th>Tahun</th><th>Role</th><th>Perusahaan</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            <?php $qt=mysqli_query($conn,"SELECT * FROM timeline ORDER BY id DESC"); while($t=mysqli_fetch_assoc($qt)): ?>
                            <tr>
                                <td><?=$t['year']?></td>
                                <td class="fw-bold"><?=$t['role']?></td>
                                <td><?=$t['company']?></td>
                                <td class="text-end">
                                    <a href="admin.php?hapus_time=<?=$t['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus Timeline?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>