<?php

session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("Location: login.php"); exit(); }

include 'koneksi.php';



// ============================================================

// 👉 LOGIC UPDATE PROFILE

// ============================================================

if (isset($_POST['update_profile'])) {

    // ... (Bagian ini sama persis kayak sebelumnya, gue singkat biar ga kepanjangan)

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

    $exp = $_POST['years_exp']; $proj = $_POST['projects_done']; $happy = $_POST['client_happy'];

    $email = $_POST['email']; $wa = $_POST['whatsapp']; $linkd = $_POST['linkedin']; $cv = $_POST['cv_link'];



    $q_old = mysqli_query($conn, "SELECT profile_pic FROM profile WHERE id=1");

    $d_old = mysqli_fetch_assoc($q_old);

    $foto_db = $d_old['profile_pic'];



    if(!empty($_FILES['profile_pic']['name'])){

        $foto_baru = "profile_" . time() . ".jpg";

        move_uploaded_file($_FILES['profile_pic']['tmp_name'], './assets/img/' . $foto_baru);

        $foto_db = $foto_baru;

    }



    $sql = "UPDATE profile SET

            hero_greeting='$greeting', hero_greeting_en='$greeting_en',

            hero_title='$title', hero_title_en='$title_en',

            hero_desc='$desc', hero_desc_en='$desc_en',

            about_text='$about', about_text_en='$about_en',

            project_title='$p_title', project_title_en='$p_title_en',

            project_desc='$p_desc', project_desc_en='$p_desc_en',

            years_exp='$exp', projects_done='$proj', client_happy='$happy',

            email='$email', whatsapp='$wa', linkedin='$linkd', cv_link='$cv',

            profile_pic='$foto_db' WHERE id=1";

    if(mysqli_query($conn, $sql)) echo "<script>alert('Profile Updated!'); window.location='admin.php';</script>";

}



// ============================================================

// 👉 LOGIC PROJECT

// ============================================================

if (isset($_POST['simpan_project'])) {

    $title = mysqli_real_escape_string($conn, $_POST['title']);

    $desc  = mysqli_real_escape_string($conn, $_POST['description']);

    $tech  = mysqli_real_escape_string($conn, $_POST['tech_stack']);

    $link  = mysqli_real_escape_string($conn, $_POST['link_demo']);

    $creds = mysqli_real_escape_string($conn, $_POST['credentials']);

    $cat   = $_POST['category'];

    $nama_gambar = $_FILES['image']['name'];

    move_uploaded_file($_FILES['image']['tmp_name'], './assets/img/' . $nama_gambar);



    $final_case = "#";

    if(!empty($_FILES['file_case']['name'])) {

        $nama_pdf = time()."_".$_FILES['file_case']['name'];

        move_uploaded_file($_FILES['file_case']['tmp_name'], './assets/docs/'.$nama_pdf);

        $final_case = $nama_pdf;

    } elseif (!empty($_POST['url_case'])) { $final_case = $_POST['url_case']; }



    $ins = mysqli_query($conn, "INSERT INTO projects VALUES (NULL, '$title', '$desc', '$nama_gambar', '$tech', '$link', '$final_case', '$creds', '$cat')");

    if($ins) echo "<script>alert('Project Added!'); window.location='admin.php';</script>";

}



// ============================================================

// 👉 LOGIC TIMELINE

// ============================================================

if (isset($_POST['simpan_timeline'])) {

    $year = mysqli_real_escape_string($conn, $_POST['year']);

    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $comp = mysqli_real_escape_string($conn, $_POST['company']);

    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    $ins_time = mysqli_query($conn, "INSERT INTO timeline VALUES (NULL, '$year', '$role', '$comp', '$desc')");

    if($ins_time) echo "<script>alert('Timeline Added!'); window.location='admin.php';</script>";

}



// ============================================================

// 👉 LOGIC SERTIFIKAT (DEBUG MODE)

// ============================================================

if (isset($_POST['simpan_cert'])) {

    $name = mysqli_real_escape_string($conn, $_POST['cert_name']);

    $issuer = mysqli_real_escape_string($conn, $_POST['cert_issuer']);

    $date = mysqli_real_escape_string($conn, $_POST['cert_date']);

    $link = mysqli_real_escape_string($conn, $_POST['cert_link']);

   

    // Default Gambar kalau user gak upload

    $nama_logo = "default_cert.png";



    // Cek apakah user upload file?

    if(!empty($_FILES['cert_img']['name'])){

       

        // 1. Cek Error Bawaan PHP

        if($_FILES['cert_img']['error'] !== UPLOAD_ERR_OK) {

            echo "<script>alert('Gagal Upload! Kode Error: " . $_FILES['cert_img']['error'] . "'); window.history.back();</script>";

            exit();

        }



        // 2. Cek Ekstensi

        $ext = strtolower(pathinfo($_FILES['cert_img']['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if(!in_array($ext, $allowed)) {

            echo "<script>alert('Format file tidak didukung! Harus JPG/PNG.'); window.history.back();</script>";

            exit();

        }



        // 3. Buat Nama Baru & Upload

        $nama_logo = "cert_" . time() . "." . $ext;

        $tujuan = './assets/img/' . $nama_logo;



        if(move_uploaded_file($_FILES['cert_img']['tmp_name'], $tujuan)){

            // Sukses Upload

        } else {

            echo "<script>alert('Gagal memindahkan file! Cek permission folder assets/img'); window.history.back();</script>";

            exit();

        }

    }



    // Simpan ke Database

    $ins_cert = mysqli_query($conn, "INSERT INTO certifications VALUES (NULL, '$name', '$issuer', '$date', '$link', '$nama_logo')");

   

    if($ins_cert) {

        echo "<script>alert('Sertifikat Berhasil Ditambah!'); window.location='admin.php';</script>";

    } else {

        echo "<script>alert('Gagal simpan database: " . mysqli_error($conn) . "');</script>";

    }

}



// ============================================================

// 👉 LOGIC DELETE

// ============================================================

if (isset($_GET['hapus_proj'])) {

    $id = $_GET['hapus_proj'];

    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM projects WHERE id='$id'"));

    if(file_exists('./assets/img/'.$d['image'])) unlink('./assets/img/'.$d['image']);

    mysqli_query($conn, "DELETE FROM projects WHERE id='$id'");

    echo "<script>window.location='admin.php';</script>";

}

if (isset($_GET['hapus_time'])) {

    mysqli_query($conn, "DELETE FROM timeline WHERE id='$_GET[hapus_time]'");

    echo "<script>window.location='admin.php';</script>";

}

if (isset($_GET['hapus_cert'])) {

    $id = $_GET['hapus_cert'];

    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM certifications WHERE id='$id'"));

    if(file_exists('./assets/img/'.$d['image'])) unlink('./assets/img/'.$d['image']);

    mysqli_query($conn, "DELETE FROM certifications WHERE id='$id'");

    echo "<script>window.location='admin.php';</script>";

}



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

            <li class="nav-item"><button class="nav-link active" id="prof-tab" data-bs-toggle="tab" data-bs-target="#prof-pane">👤 Profile</button></li>

            <li class="nav-item"><button class="nav-link" id="proj-tab" data-bs-toggle="tab" data-bs-target="#proj-pane">📁 Projects</button></li>

            <li class="nav-item"><button class="nav-link" id="cert-tab" data-bs-toggle="tab" data-bs-target="#cert-pane">🏅 Certifications</button></li>

            <li class="nav-item"><button class="nav-link" id="time-tab" data-bs-toggle="tab" data-bs-target="#time-pane">⏳ Timeline</button></li>

        </ul>



        <div class="tab-content">

           

            <div class="tab-pane fade show active" id="prof-pane">

                <form method="POST" enctype="multipart/form-data">

                    <div class="card p-4 mb-4">

                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-camera"></i> Foto Profil</h5>

                        <div class="row align-items-center">

                            <div class="col-md-2 text-center"><img src="assets/img/<?=$p['profile_pic']?>" width="100" class="rounded-circle border"></div>

                            <div class="col-md-10"><input type="file" name="profile_pic" class="form-control"></div>

                        </div>

                    </div>

                    <div class="card p-4 mb-4">

                        <h5 class="text-primary fw-bold mb-3">Konten Text</h5>

                        <div class="row g-3">

                            <div class="col-6"><label>ID Salam</label><input type="text" name="hero_greeting" class="form-control" value="<?=$p['hero_greeting']?>"></div>

                            <div class="col-6"><label>EN Salam</label><input type="text" name="hero_greeting_en" class="form-control" value="<?=$p['hero_greeting_en']?>"></div>

                            <div class="col-6"><label>ID Judul</label><input type="text" name="hero_title" class="form-control" value="<?=$p['hero_title']?>"></div>

                            <div class="col-6"><label>EN Judul</label><input type="text" name="hero_title_en" class="form-control" value="<?=$p['hero_title_en']?>"></div>

                            <div class="col-6"><label>ID Desc</label><textarea name="hero_desc" class="form-control"><?=$p['hero_desc']?></textarea></div>

                            <div class="col-6"><label>EN Desc</label><textarea name="hero_desc_en" class="form-control"><?=$p['hero_desc_en']?></textarea></div>

                            <div class="col-12"><hr></div>

                            <div class="col-6"><label>ID About</label><textarea name="about_text" class="form-control" rows="3"><?=$p['about_text']?></textarea></div>

                            <div class="col-6"><label>EN About</label><textarea name="about_text_en" class="form-control" rows="3"><?=$p['about_text_en']?></textarea></div>

                        </div>

                    </div>

                    <div class="card p-4 mb-4">

                        <h5 class="text-primary fw-bold mb-3">Stats & Contact</h5>

                        <div class="row g-3">

                            <div class="col-md-4"><label>Exp (Tahun)</label><input type="number" name="years_exp" class="form-control" value="<?=$p['years_exp']?>"></div>

                            <div class="col-md-4"><label>Project Done</label><input type="number" name="projects_done" class="form-control" value="<?=$p['projects_done']?>"></div>

                            <div class="col-md-4"><label>Client Happy</label><input type="number" name="client_happy" class="form-control" value="<?=$p['client_happy']?>"></div>

                            <div class="col-md-6"><label>Email</label><input type="text" name="email" class="form-control" value="<?=$p['email']?>"></div>

                            <div class="col-md-6"><label>WhatsApp</label><input type="text" name="whatsapp" class="form-control" value="<?=$p['whatsapp']?>"></div>

                            <div class="col-md-6"><label>LinkedIn</label><input type="text" name="linkedin" class="form-control" value="<?=$p['linkedin']?>"></div>

                            <div class="col-md-6"><label>Link CV</label><input type="text" name="cv_link" class="form-control" value="<?=$p['cv_link']?>"></div>

                        </div>

                    </div>

                    <input type="hidden" name="project_title" value="<?=$p['project_title']?>">

                    <input type="hidden" name="project_title_en" value="<?=$p['project_title_en']?>">

                    <input type="hidden" name="project_desc" value="<?=$p['project_desc']?>">

                    <input type="hidden" name="project_desc_en" value="<?=$p['project_desc_en']?>">

                    <button type="submit" name="update_profile" class="btn btn-primary w-100 fw-bold">SIMPAN PROFILE</button>

                </form>

            </div>



            <div class="tab-pane fade" id="proj-pane">

                <div class="card p-4 shadow mb-4">

                    <h5 class="text-primary fw-bold mb-3">Tambah Project</h5>

                    <form method="POST" enctype="multipart/form-data">

                        <div class="row g-3">

                            <div class="col-md-6"><input type="text" name="title" class="form-control" placeholder="Judul" required></div>

                            <div class="col-md-6">

                                <select name="category" class="form-select">

                                    <option value="work">Work Project</option>

                                    <option value="personal">Personal Project</option>

                                </select>

                            </div>

                            <div class="col-12"><input type="text" name="tech_stack" class="form-control" placeholder="Tech Stack" required></div>

                            <div class="col-12"><textarea name="description" class="form-control" placeholder="Deskripsi" required></textarea></div>

                            <div class="col-12"><textarea name="credentials" class="form-control" placeholder="Credentials"></textarea></div>

                            <div class="col-md-6"><input type="file" name="image" class="form-control" required></div>

                            <div class="col-md-6"><input type="text" name="link_demo" class="form-control" placeholder="Link Demo"></div>

                            <div class="col-md-6"><input type="file" name="file_case" class="form-control"></div>

                            <div class="col-md-6"><input type="text" name="url_case" class="form-control" placeholder="Link Studi Kasus"></div>

                        </div>

                        <button type="submit" name="simpan_project" class="btn btn-success w-100 mt-3">SIMPAN PROJECT</button>

                    </form>

                </div>

                <div class="card shadow p-0">

                    <table class="table table-hover mb-0">

                        <thead class="table-dark"><tr><th>Judul</th><th>Kategori</th><th>Aksi</th></tr></thead>

                        <tbody>

                            <?php $qp=mysqli_query($conn,"SELECT * FROM projects ORDER BY id DESC"); while($d=mysqli_fetch_assoc($qp)): ?>

                            <tr>

                                <td><?=$d['title']?></td>

                                <td><?=$d['category']?></td>

                                <td>

                                    <a href="edit.php?id=<?=$d['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>

                                    <a href="admin.php?hapus_proj=<?=$d['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>

                                </td>

                            </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            </div>



            <div class="tab-pane fade" id="cert-pane">

                <div class="card p-4 shadow mb-4">

                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-award"></i> Tambah Sertifikat</h5>

                    <form method="POST" enctype="multipart/form-data">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">Nama Sertifikat</label>

                                <input type="text" name="cert_name" class="form-control" placeholder="Contoh: AWS Certified" required>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Penerbit (Issuer)</label>

                                <input type="text" name="cert_issuer" class="form-control" placeholder="Contoh: Amazon / Dicoding" required>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Tanggal Terbit</label>

                                <input type="text" name="cert_date" class="form-control" placeholder="Contoh: Jan 2024" required>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Link Credential</label>

                                <input type="text" name="cert_link" class="form-control" placeholder="https://..." required>

                            </div>

                            <div class="col-12">

                                <label class="form-label">Logo Penerbit / Sertifikat</label>

                                <input type="file" name="cert_img" class="form-control" required>

                            </div>

                        </div>

                        <button type="submit" name="simpan_cert" class="btn btn-success w-100 mt-3 fw-bold">SIMPAN SERTIFIKAT</button>

                    </form>

                </div>



                <div class="card shadow p-0">

                    <table class="table table-hover mb-0">

                        <thead class="table-dark"><tr><th>Logo</th><th>Nama</th><th>Penerbit</th><th class="text-end">Aksi</th></tr></thead>

                        <tbody>

                            <?php $qc=mysqli_query($conn,"SELECT * FROM certifications ORDER BY id DESC"); while($c=mysqli_fetch_assoc($qc)): ?>

                            <tr>

                                <td><img src="assets/img/<?=$c['image']?>" width="40"></td>

                                <td class="fw-bold"><?=$c['name']?></td>

                                <td><?=$c['issuer']?></td>

                                <td class="text-end">

                                    <a href="admin.php?hapus_cert=<?=$c['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus Sertifikat?')"><i class="bi bi-trash"></i></a>

                                </td>

                            </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            </div>



            <div class="tab-pane fade" id="time-pane">

                <div class="card p-4 shadow mb-4">

                    <h5 class="text-primary fw-bold mb-3">Tambah Timeline</h5>

                    <form method="POST">

                        <div class="row g-3">

                            <div class="col-md-4"><input type="text" name="year" class="form-control" placeholder="Tahun" required></div>

                            <div class="col-md-4"><input type="text" name="role" class="form-control" placeholder="Role" required></div>

                            <div class="col-md-4"><input type="text" name="company" class="form-control" placeholder="Perusahaan" required></div>

                            <div class="col-12"><textarea name="description" class="form-control" placeholder="Deskripsi"></textarea></div>

                        </div>

                        <button type="submit" name="simpan_timeline" class="btn btn-success w-100 mt-3">SIMPAN TIMELINE</button>

                    </form>

                </div>

                <div class="card shadow p-0">

                    <table class="table table-hover mb-0">

                        <thead class="table-dark"><tr><th>Tahun</th><th>Role</th><th class="text-end">Aksi</th></tr></thead>

                        <tbody>

                            <?php $qt=mysqli_query($conn,"SELECT * FROM timeline ORDER BY id DESC"); while($t=mysqli_fetch_assoc($qt)): ?>

                            <tr>

                                <td><?=$t['year']?></td>

                                <td><?=$t['role']?></td>

                                <td class="text-end"><a href="admin.php?hapus_time=<?=$t['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>

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