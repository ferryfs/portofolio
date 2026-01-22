<?php
session_start();
// Cek sesi login (sesuaikan dengan login.php lu)
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php"); 
    exit(); 
}
include 'koneksi.php'; // Pastikan path ini benar

// HELPER FUNCTION BUAT FLASH MESSAGE
function setFlash($msg, $type='success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

// ==================================================================================
// 🟢 LOGIC PHP
// ==================================================================================

// 1. UPDATE PROFILE
if (isset($_POST['save_hero'])) {
    $greeting = mysqli_real_escape_string($conn, $_POST['hero_greeting']);
    $greeting_en = mysqli_real_escape_string($conn, $_POST['hero_greeting_en']);
    $title = mysqli_real_escape_string($conn, $_POST['hero_title']);
    $title_en = mysqli_real_escape_string($conn, $_POST['hero_title_en']);
    $desc = mysqli_real_escape_string($conn, $_POST['hero_desc']);
    $desc_en = mysqli_real_escape_string($conn, $_POST['hero_desc_en']);
    $cv = mysqli_real_escape_string($conn, $_POST['cv_link']);
    
    if(mysqli_query($conn, "UPDATE profile SET hero_greeting='$greeting', hero_greeting_en='$greeting_en', hero_title='$title', hero_title_en='$title_en', hero_desc='$desc', hero_desc_en='$desc_en', cv_link='$cv' WHERE id=1")) {
        setFlash('Hero Section Berhasil Diupdate!');
    } else {
        setFlash('Gagal Update: '.mysqli_error($conn), 'error');
    }
    header("Location: admin.php?tab=prof-pane"); exit();
}

// 🔥 UPDATE ABOUT TITLE (PENGGANTI RUNNING TEXT)
if (isset($_POST['save_about'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['about_title']);
    $judul_en = mysqli_real_escape_string($conn, $_POST['about_title_en']);
    
    // Kita update kolom about_title & about_title_en
    mysqli_query($conn, "UPDATE profile SET about_title='$judul', about_title_en='$judul_en' WHERE id=1");
    setFlash('Judul About (Tengah) Berhasil Diupdate!');
    header("Location: admin.php?tab=prof-pane"); exit();
}

if (isset($_POST['save_bento'])) {
    $bt1 = mysqli_real_escape_string($conn, $_POST['bento_title_1']);
    $bd1 = mysqli_real_escape_string($conn, $_POST['bento_desc_1']);
    $bd1_en = mysqli_real_escape_string($conn, $_POST['bento_desc_1_en']);
    $bt2 = mysqli_real_escape_string($conn, $_POST['bento_title_2']);
    $bd2 = mysqli_real_escape_string($conn, $_POST['bento_desc_2']);
    $bd2_en = mysqli_real_escape_string($conn, $_POST['bento_desc_2_en']);
    $bt3 = mysqli_real_escape_string($conn, $_POST['bento_title_3']);
    $bd3 = mysqli_real_escape_string($conn, $_POST['bento_desc_3']);
    $bd3_en = mysqli_real_escape_string($conn, $_POST['bento_desc_3_en']);

    mysqli_query($conn, "UPDATE profile SET 
        bento_title_1='$bt1', bento_desc_1='$bd1', bento_desc_1_en='$bd1_en',
        bento_title_2='$bt2', bento_desc_2='$bd2', bento_desc_2_en='$bd2_en',
        bento_title_3='$bt3', bento_desc_3='$bd3', bento_desc_3_en='$bd3_en'
        WHERE id=1");
    setFlash('Bento Grid Info Berhasil Diupdate!');
    header("Location: admin.php?tab=prof-pane"); exit();
}

if (isset($_POST['save_contact'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $wa = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $li = mysqli_real_escape_string($conn, $_POST['linkedin']);
    mysqli_query($conn, "UPDATE profile SET email='$email', whatsapp='$wa', linkedin='$li' WHERE id=1");
    setFlash('Kontak Berhasil Diupdate!');
    header("Location: admin.php?tab=prof-pane"); exit();
}

if (isset($_POST['save_images'])) {
    $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));
    function uploadImg($file, $old) {
        if(!empty($file['name'])){
            if(file_exists('./assets/img/'.$old) && $old != '') unlink('./assets/img/'.$old);
            $new = time() . "_" . $file['name'];
            move_uploaded_file($file['tmp_name'], './assets/img/' . $new);
            return $new;
        } return $old;
    }
    $pic = uploadImg($_FILES['profile_pic'], $q['profile_pic']);
    $img1 = uploadImg($_FILES['about_img_1'], $q['about_img_1']);
    $img2 = uploadImg($_FILES['about_img_2'], $q['about_img_2']);
    $img3 = uploadImg($_FILES['about_img_3'], $q['about_img_3']);
    
    mysqli_query($conn, "UPDATE profile SET profile_pic='$pic', about_img_1='$img1', about_img_2='$img2', about_img_3='$img3' WHERE id=1");
    setFlash('Gambar Berhasil Diupload!');
    header("Location: admin.php?tab=prof-pane"); exit();
}

// 2. ADD PROJECT
if (isset($_POST['add_project'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $cat = $_POST['category'];
    $link = mysqli_real_escape_string($conn, $_POST['link_demo']);
    
    $img_name = "default.jpg";
    if(!empty($_FILES['image']['name'])) {
        $img_name = "proj_" . time() . ".jpg";
        move_uploaded_file($_FILES['image']['tmp_name'], './assets/img/' . $img_name);
    }

    $pdf_name = "#";
    if(!empty($_FILES['file_case']['name'])) {
        $pdf_name = time() . "_" . $_FILES['file_case']['name'];
        move_uploaded_file($_FILES['file_case']['tmp_name'], './assets/docs/' . $pdf_name);
    }

    $sql = "INSERT INTO projects (title, description, category, image, link_demo, link_case) VALUES ('$title', '$desc', '$cat', '$img_name', '$link', '$pdf_name')";
    if(mysqli_query($conn, $sql)){
        setFlash('Project Baru Berhasil Ditambah!');
    } else {
        setFlash('Gagal: '.mysqli_error($conn), 'error');
    }
    header("Location: admin.php?tab=proj-pane"); exit();
}

if (isset($_GET['hapus_proj'])) {
    $id = $_GET['hapus_proj'];
    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image, link_case FROM projects WHERE id='$id'"));
    if($d['image'] != 'default.jpg' && file_exists('./assets/img/'.$d['image'])) unlink('./assets/img/'.$d['image']);
    if($d['link_case'] != '#' && file_exists('./assets/docs/'.$d['link_case'])) unlink('./assets/docs/'.$d['link_case']);
    
    mysqli_query($conn, "DELETE FROM projects WHERE id='$id'");
    setFlash('Project Berhasil Dihapus!');
    header("Location: admin.php?tab=proj-pane"); exit();
}

// 3. ADD TECH STACK
if (isset($_POST['add_tech'])) {
    $name = mysqli_real_escape_string($conn, $_POST['tech_name']);
    $cat = $_POST['tech_category'];
    $icon = mysqli_real_escape_string($conn, $_POST['tech_icon']);
    
    mysqli_query($conn, "INSERT INTO tech_stacks (name, category, icon) VALUES ('$name', '$cat', '$icon')");
    setFlash('Skill Baru Berhasil Ditambah!');
    header("Location: admin.php?tab=tech-pane"); exit();
}
if (isset($_GET['hapus_tech'])) {
    mysqli_query($conn, "DELETE FROM tech_stacks WHERE id='$_GET[hapus_tech]'");
    setFlash('Skill Berhasil Dihapus!');
    header("Location: admin.php?tab=tech-pane"); exit();
}

// 4. ADD TIMELINE (JOURNEY) - 🔥 ADA UPLOAD GAMBAR & SUMMERNOTE
if (isset($_POST['add_timeline'])) {
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $s_date = mysqli_real_escape_string($conn, $_POST['sort_date']); // Ambil Tanggal Sortir
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $comp = mysqli_real_escape_string($conn, $_POST['company']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    $img_name = "";
    if(!empty($_FILES['image']['name'])) {
        $img_name = "cartoon_" . time() . ".png";
        move_uploaded_file($_FILES['image']['tmp_name'], '../assets/img/' . $img_name);
    }

    // Insert Sort Date
    mysqli_query($conn, "INSERT INTO timeline (year, sort_date, role, company, description, image) VALUES ('$year', '$s_date', '$role', '$comp', '$desc', '$img_name')");
    setFlash('Timeline Karir Berhasil Ditambah!');
    header("Location: index.php?tab=time-pane"); exit();
}

if (isset($_GET['hapus_time'])) {
    // Hapus gambar kalo ada
    $id = $_GET['hapus_time'];
    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM timeline WHERE id='$id'"));
    if(!empty($d['image']) && file_exists('./assets/img/'.$d['image'])) unlink('./assets/img/'.$d['image']);

    mysqli_query($conn, "DELETE FROM timeline WHERE id='$id'");
    setFlash('Timeline Berhasil Dihapus!');
    header("Location: admin.php?tab=time-pane"); exit();
}

// 5. ADD CERTIFICATE
if (isset($_POST['add_cert'])) {
    $name = mysqli_real_escape_string($conn, $_POST['cert_name']);
    $issuer = mysqli_real_escape_string($conn, $_POST['cert_issuer']);
    $date = mysqli_real_escape_string($conn, $_POST['cert_date']);
    $link = mysqli_real_escape_string($conn, $_POST['cert_link']);
    
    $img_name = "cert_" . time() . ".png";
    move_uploaded_file($_FILES['cert_img']['tmp_name'], './assets/img/' . $img_name);
    
    mysqli_query($conn, "INSERT INTO certifications (name, issuer, date, link_credential, image) VALUES ('$name', '$issuer', '$date', '$link', '$img_name')");
    setFlash('Sertifikat Berhasil Ditambah!');
    header("Location: admin.php?tab=cert-pane"); exit();
}
if (isset($_GET['hapus_cert'])) {
    mysqli_query($conn, "DELETE FROM certifications WHERE id='$_GET[hapus_cert]'");
    setFlash('Sertifikat Berhasil Dihapus!');
    header("Location: admin.php?tab=cert-pane"); exit();
}

$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CMS Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .form-floating > .form-control:focus { box-shadow: none; border-color: #0d6efd; }
        .nav-pills .nav-link.active { background-color: #0d6efd; font-weight: bold; }
        .nav-pills .nav-link { color: #475569; font-weight: 500; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { background: white; font-weight: 700; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem; }
        
        /* Fix Summernote Style */
        .note-editor .dropdown-toggle::after { all: unset; } 
        .note-editor .note-dropdown-menu { box-sizing: content-box; }
        .note-editor .note-modal-footer { box-sizing: content-box; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">⚙️ CMS PANEL</a>
        <div class="d-flex gap-2">
            <a href="index.php" target="_blank" class="btn btn-sm btn-outline-light">Lihat Web</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm gap-2" id="myTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#prof-pane">👤 Profile</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#time-pane">⏳ Journey</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#proj-pane">📁 Projects</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tech-pane">🛠️ Tech Stack</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cert-pane">🏅 Certificates</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="prof-pane">
            <div class="row g-4">
                
                <div class="col-md-12">
                    <div class="card border-primary border-2">
                        <div class="card-header text-primary">JUDUL TENGAH (ABOUT TITLE)</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 form-floating mb-2">
                                        <input type="text" class="form-control" name="about_title" value="<?=$p['about_title']?>">
                                        <label>Judul (ID) - misal: Analis & Pemimpin</label>
                                    </div>
                                    <div class="col-md-6 form-floating mb-2">
                                        <input type="text" class="form-control" name="about_title_en" value="<?=$p['about_title_en']?>">
                                        <label>Judul (EN) - misal: Analyst & Leader</label>
                                    </div>
                                </div>
                                <button type="submit" name="save_about" class="btn btn-primary w-100 fw-bold">UPDATE JUDUL</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header text-primary">HERO SECTION</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-floating mb-2"><input type="text" class="form-control" name="hero_greeting" value="<?=$p['hero_greeting']?>"><label>Greeting (ID)</label></div>
                                <div class="form-floating mb-2"><input type="text" class="form-control" name="hero_greeting_en" value="<?=$p['hero_greeting_en']?>"><label>Greeting (EN)</label></div>
                                <div class="form-floating mb-2"><input type="text" class="form-control" name="hero_title" value="<?=$p['hero_title']?>"><label>Headline (ID)</label></div>
                                <div class="form-floating mb-2"><input type="text" class="form-control" name="hero_title_en" value="<?=$p['hero_title_en']?>"><label>Headline (EN)</label></div>
                                <div class="form-floating mb-2"><textarea class="form-control" name="hero_desc" style="height:100px"><?=$p['hero_desc']?></textarea><label>Description (ID)</label></div>
                                <div class="form-floating mb-3"><textarea class="form-control" name="hero_desc_en" style="height:100px"><?=$p['hero_desc_en']?></textarea><label>Description (EN)</label></div>
                                <div class="form-floating mb-3"><input type="text" class="form-control" name="cv_link" value="<?=$p['cv_link']?>"><label>Link CV</label></div>
                                <button type="submit" name="save_hero" class="btn btn-primary w-100 fw-bold">SIMPAN HERO</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">IMAGES (3 SLOT FOTO ABOUT)</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="small fw-bold">Foto Utama (Hero Section)</label>
                                    <input type="file" name="profile_pic" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold">Foto About Utama (Besar Kiri)</label>
                                    <input type="file" name="about_img_1" class="form-control">
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="small fw-bold">Foto Kecil 1</label>
                                        <input type="file" name="about_img_2" class="form-control">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small fw-bold">Foto Kecil 2</label>
                                        <input type="file" name="about_img_3" class="form-control">
                                    </div>
                                </div>
                                <button type="submit" name="save_images" class="btn btn-dark w-100 fw-bold mt-3">UPLOAD GAMBAR</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-warning border-2">
                        <div class="card-header text-warning bg-warning bg-opacity-10">BENTO GRID (INFO BOX)</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="fw-bold">Kotak 1: Analysis</h6>
                                        <input type="text" name="bento_title_1" class="form-control mb-2 fw-bold" value="<?=$p['bento_title_1']?>">
                                        <textarea name="bento_desc_1" class="form-control mb-2" rows="3"><?=$p['bento_desc_1']?></textarea>
                                        <textarea name="bento_desc_1_en" class="form-control mb-3" rows="3"><?=$p['bento_desc_1_en']?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="fw-bold">Kotak 2: Enterprise</h6>
                                        <input type="text" name="bento_title_2" class="form-control mb-2 fw-bold" value="<?=$p['bento_title_2']?>">
                                        <textarea name="bento_desc_2" class="form-control mb-2" rows="3"><?=$p['bento_desc_2']?></textarea>
                                        <textarea name="bento_desc_2_en" class="form-control mb-3" rows="3"><?=$p['bento_desc_2_en']?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="fw-bold">Kotak 3: Development</h6>
                                        <input type="text" name="bento_title_3" class="form-control mb-2 fw-bold" value="<?=$p['bento_title_3']?>">
                                        <textarea name="bento_desc_3" class="form-control mb-2" rows="3"><?=$p['bento_desc_3']?></textarea>
                                        <textarea name="bento_desc_3_en" class="form-control mb-3" rows="3"><?=$p['bento_desc_3_en']?></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="save_bento" class="btn btn-warning w-100 fw-bold">SIMPAN BENTO GRID</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-header text-success">CONTACT INFO</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4 form-floating mb-2"><input type="email" class="form-control" name="email" value="<?=$p['email']?>"><label>Email</label></div>
                                    <div class="col-md-4 form-floating mb-2"><input type="text" class="form-control" name="whatsapp" value="<?=$p['whatsapp']?>"><label>WhatsApp</label></div>
                                    <div class="col-md-4 form-floating mb-3"><input type="text" class="form-control" name="linkedin" value="<?=$p['linkedin']?>"><label>LinkedIn</label></div>
                                </div>
                                <button type="submit" name="save_contact" class="btn btn-success w-100 fw-bold">SIMPAN KONTAK</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="proj-pane">
            <div class="card mb-4 border-primary border-2">
                <div class="card-header bg-primary text-white">TAMBAH PROJECT BARU</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-2">
                            <div class="col-md-6 form-floating">
                                <input type="text" name="title" class="form-control" placeholder="Title" required>
                                <label>Judul Project</label>
                            </div>
                            <div class="col-md-3 form-floating">
                                <select name="category" class="form-select">
                                    <option value="Work">Work Project</option>
                                    <option value="Personal">Personal Project</option>
                                </select>
                                <label>Kategori</label>
                            </div>
                            <div class="col-md-3">
                                <input type="file" name="image" class="form-control h-100 pt-3" required>
                            </div>
                            <div class="col-12 form-floating">
                                <textarea name="description" class="form-control" style="height:80px" placeholder="Desc" required></textarea>
                                <label>Deskripsi Singkat</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <input type="text" name="link_demo" class="form-control" placeholder="Link">
                                <label>Link Website / Demo</label>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Upload PDF Case Study</label>
                                <input type="file" name="file_case" class="form-control" accept=".pdf">
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_project" class="btn btn-primary w-100 fw-bold">TAMBAH PROJECT</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Cover</th><th>Judul</th><th>Kategori</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php $qp = mysqli_query($conn, "SELECT * FROM projects ORDER BY id DESC"); while($d = mysqli_fetch_assoc($qp)): ?>
                        <tr>
                            <td width="80"><img src="assets/img/<?=$d['image']?>" width="60" class="rounded"></td>
                            <td class="fw-bold"><?=$d['title']?></td>
                            <td><span class="badge bg-light text-dark border"><?=$d['category']?></span></td>
                            <td>
                                <a href="edit_timeline?id=<?=$d['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="admin?hapus_proj=<?=$d['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tech-pane">
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4 border-warning border-2">
                        <div class="card-header bg-warning text-dark fw-bold">TAMBAH ICON SKILL</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-floating mb-2">
                                    <input type="text" name="tech_name" class="form-control" placeholder="Name" required>
                                    <label>Nama Skill (mis: PHP, JIRA)</label>
                                </div>
                                <div class="form-floating mb-2">
                                    <select name="tech_category" class="form-select">
                                        <option value="Analysis">Analysis (Kiri)</option>
                                        <option value="Enterprise">Enterprise (Tengah)</option>
                                        <option value="Development">Development (Kanan)</option>
                                    </select>
                                    <label>Masuk Kotak Mana?</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" name="tech_icon" class="form-control" placeholder="Icon" required>
                                    <label>Icon Class (bi-kanban)</label>
                                    <small class="text-muted d-block mt-1">
                                        Cari icon di <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>
                                    </small>
                                </div>
                                <button type="submit" name="add_tech" class="btn btn-warning w-100 fw-bold">TAMBAH</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-white fw-bold">DAFTAR SKILL / TOOLS</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark"><tr><th>Icon</th><th>Skill</th><th>Kotak</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php $qt = mysqli_query($conn, "SELECT * FROM tech_stacks ORDER BY category ASC"); while($ts = mysqli_fetch_assoc($qt)): ?>
                                    <tr>
                                        <td class="text-center"><i class="<?=$ts['icon']?> fs-5"></i></td>
                                        <td class="fw-bold"><?=$ts['name']?></td>
                                        <td><span class="badge bg-secondary"><?=$ts['category']?></span></td>
                                        <td><a href="admin.php?hapus_tech=<?=$ts['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="time-pane">
            <div class="row">
                <div class="col-md-5">
                    <div class="card mb-4 border-info border-2">
                        <div class="card-header bg-info text-white fw-bold">TAMBAH PENGALAMAN</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row g-2">
                                    <div class="col-md-8 form-floating">
                                        <input type="text" name="year" class="form-control" placeholder="Year" required>
                                        <label>Teks Tahun (Cth: March 2022 - Now)</label>
                                    </div>
                                    <div class="col-md-4 form-floating">
                                        <input type="date" name="sort_date" class="form-control" required>
                                        <label>Tgl Mulai (Utk Urutan)</label>
                                    </div>
                                    
                                    <div class="col-12 form-floating"><input type="text" name="role" class="form-control" placeholder="Role" required><label>Jabatan / Role</label></div>
                                    <div class="col-12 form-floating"><input type="text" name="company" class="form-control" placeholder="Comp" required><label>Perusahaan</label></div>
                                    
                                    <div class="col-12">
                                        <label class="small text-muted fw-bold mb-1">Deskripsi Pekerjaan</label>
                                        <textarea id="summernote" name="description" class="form-control" required></textarea>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="small fw-bold text-muted mb-1">Kartun Popup (Opsional)</label>
                                        <input type="file" name="image" class="form-control form-control-sm">
                                        <small style="font-size:10px" class="text-danger">*Sistem akan otomatis cari gambar di PT ini.</small>
                                    </div>

                                    <div class="col-12 mt-2"><button type="submit" name="add_timeline" class="btn btn-info text-white w-100 fw-bold">SIMPAN</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-white fw-bold">RIWAYAT KARIR</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark"><tr><th>Info</th><th>Kartun</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php $qtime = mysqli_query($conn, "SELECT * FROM timeline ORDER BY id DESC"); while($tm = mysqli_fetch_assoc($qtime)): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary mb-1"><?=$tm['year']?></span><br>
                                            <b><?=$tm['role']?></b><br>
                                            <small class="text-muted"><?=$tm['company']?></small>
                                        </td>
                                        <td>
                                            <?php if(!empty($tm['image'])): ?>
                                                <img src="./assets/img/<?=$tm['image']?>" width="40" class="rounded border">
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_timeline.php?id=<?=$tm['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                            <a href="admin.php?hapus_time=<?=$tm['id']?>" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="cert-pane">
            <div class="card mb-4 border-success border-2">
                <div class="card-header bg-success text-white fw-bold">TAMBAH SERTIFIKAT</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-2">
                            <div class="col-md-4 form-floating"><input type="text" name="cert_name" class="form-control" placeholder="Name"><label>Nama Sertifikat</label></div>
                            <div class="col-md-4 form-floating"><input type="text" name="cert_issuer" class="form-control" placeholder="Issuer"><label>Penerbit</label></div>
                            <div class="col-md-4 form-floating"><input type="text" name="cert_date" class="form-control" placeholder="Date"><label>Tgl Terbit</label></div>
                            <div class="col-md-6 form-floating"><input type="text" name="cert_link" class="form-control" placeholder="Link"><label>Link Credential</label></div>
                            <div class="col-md-6"><input type="file" name="cert_img" class="form-control h-100 pt-3" required></div>
                            <div class="col-12"><button type="submit" name="add_cert" class="btn btn-success w-100 fw-bold">SIMPAN SERTIFIKAT</button></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Logo</th><th>Nama</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php $qcert = mysqli_query($conn, "SELECT * FROM certifications ORDER BY id DESC"); while($c = mysqli_fetch_assoc($qcert)): ?>
                        <tr>
                            <td width="60"><img src="assets/img/<?=$c['image']?>" width="40"></td>
                            <td><b><?=$c['name']?></b><br><small><?=$c['issuer']?></small></td>
                            <td><a href="admin.php?hapus_cert=<?=$c['id']?>" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script>
    // 🔥 INISIALISASI SUMMERNOTE
    $('#summernote').summernote({
        placeholder: 'Ketik deskripsi di sini...',
        tabsize: 2,
        height: 150,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']], // Tombol Bullet Point (UL) ada di sini
            ['insert', ['link']],
            ['view', ['codeview']]
        ]
    });

    // Tab Persistence Logic
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if(tab){
        const tabBtn = document.querySelector(`[data-bs-target="#${tab}"]`);
        if(tabBtn) { const t = new bootstrap.Tab(tabBtn); t.show(); }
    }
</script>

<?php if(isset($_SESSION['flash_msg'])): ?>
<script>
    Swal.fire({
        title: 'Sukses!',
        text: '<?php echo $_SESSION['flash_msg']; ?>',
        icon: '<?php echo isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success'; ?>',
        timer: 3000,
        showConfirmButton: false
    });
</script>
<?php 
unset($_SESSION['flash_msg']);
unset($_SESSION['flash_type']);
endif; 
?>

</body>
</html>