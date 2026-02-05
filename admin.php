<?php
session_name("PORTFOLIO_CMS_SESSION");
session_start();

// Security Headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php"); exit(); 
}

// LOAD KONEKSI PDO & SECURITY HELPERS
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/security.php';

// HELPER
function purify($text) { return strip_tags($text ?? '', '<ul><ol><li><b><strong><i><em><u><br><p>'); }
function setFlash($msg, $type='success') { $_SESSION['flash_msg'] = $msg; $_SESSION['flash_type'] = $type; }
function v($arr, $key) { return htmlspecialchars($arr[$key] ?? ''); }

try {
    // ==========================================
    // 1. PROFILE & HERO ACTIONS
    // ==========================================
    if (isset($_POST['save_hero'])) {
        $sql = "UPDATE profile SET hero_pre=?, hero_pre_en=?, hero_greeting=?, hero_greeting_en=?, hero_title=?, hero_title_en=?, hero_desc=?, hero_desc_en=?, cv_link=? WHERE id=1";
        $pdo->prepare($sql)->execute([$_POST['hero_pre'], $_POST['hero_pre_en'], $_POST['hero_greeting'], $_POST['hero_greeting_en'], $_POST['hero_title'], $_POST['hero_title_en'], $_POST['hero_desc'], $_POST['hero_desc_en'], $_POST['cv_link']]);
        setFlash('Hero Updated!'); header("Location: admin.php?tab=prof-pane"); exit();
    }

    if (isset($_POST['save_labels'])) {
        $sql = "UPDATE profile SET label_about=?, label_about_en=?, about_title=?, about_title_en=?, label_skills=?, label_skills_en=?, title_skills=?, title_skills_en=?, title_contact_1=?, title_contact_1_en=?, title_contact_2=?, title_contact_2_en=? WHERE id=1";
        $pdo->prepare($sql)->execute([$_POST['label_about'], $_POST['label_about_en'], $_POST['about_title'], $_POST['about_title_en'], $_POST['label_skills'], $_POST['label_skills_en'], $_POST['title_skills'], $_POST['title_skills_en'], $_POST['title_contact_1'], $_POST['title_contact_1_en'], $_POST['title_contact_2'], $_POST['title_contact_2_en']]);
        setFlash('Labels Updated!'); header("Location: admin.php?tab=prof-pane"); exit();
    }

    if (isset($_POST['save_bento'])) {
        $sql = "UPDATE profile SET bento_title_1=?, bento_desc_1=?, bento_desc_1_en=?, bento_title_2=?, bento_desc_2=?, bento_desc_2_en=?, bento_title_3=?, bento_desc_3=?, bento_desc_3_en=? WHERE id=1";
        $pdo->prepare($sql)->execute([$_POST['bento_title_1'], $_POST['bento_desc_1'], $_POST['bento_desc_1_en'], $_POST['bento_title_2'], $_POST['bento_desc_2'], $_POST['bento_desc_2_en'], $_POST['bento_title_3'], $_POST['bento_desc_3'], $_POST['bento_desc_3_en']]);
        setFlash('Bento Grid Updated!'); header("Location: admin.php?tab=prof-pane"); exit();
    }

    if (isset($_POST['save_contact'])) {
        $pdo->prepare("UPDATE profile SET email=?, whatsapp=?, linkedin=? WHERE id=1")->execute([$_POST['email'], $_POST['whatsapp'], $_POST['linkedin']]);
        setFlash('Contact Updated!'); header("Location: admin.php?tab=prof-pane"); exit();
    }

    if (isset($_POST['save_images'])) {
        $q = $pdo->query("SELECT * FROM profile WHERE id=1")->fetch(PDO::FETCH_ASSOC);
        
        // Using centralized security.php handleFileUpload
        $pic = $q['profile_pic'] ?? 'default.jpg';
        $img1 = $q['about_img_1'] ?? 'default.jpg';
        $img2 = $q['about_img_2'] ?? 'default.jpg';
        $img3 = $q['about_img_3'] ?? 'default.jpg';
        
        // Upload profile picture
        if(!empty($_FILES['profile_pic']['name'])) {
            $upload = handleFileUpload($_FILES['profile_pic'], 'assets/img/');
            if($upload['success']) {
                if($q['profile_pic'] && $q['profile_pic'] != 'default.jpg' && file_exists('assets/img/'.$q['profile_pic'])) {
                    @unlink('assets/img/'.$q['profile_pic']);
                }
                $pic = $upload['filename'];
            }
        }
        
        // Upload about image 1
        if(!empty($_FILES['about_img_1']['name'])) {
            $upload = handleFileUpload($_FILES['about_img_1'], 'assets/img/');
            if($upload['success']) {
                if($q['about_img_1'] && $q['about_img_1'] != 'default.jpg' && file_exists('assets/img/'.$q['about_img_1'])) {
                    @unlink('assets/img/'.$q['about_img_1']);
                }
                $img1 = $upload['filename'];
            }
        }
        
        // Upload about image 2
        if(!empty($_FILES['about_img_2']['name'])) {
            $upload = handleFileUpload($_FILES['about_img_2'], 'assets/img/');
            if($upload['success']) {
                if($q['about_img_2'] && $q['about_img_2'] != 'default.jpg' && file_exists('assets/img/'.$q['about_img_2'])) {
                    @unlink('assets/img/'.$q['about_img_2']);
                }
                $img2 = $upload['filename'];
            }
        }
        
        // Upload about image 3
        if(!empty($_FILES['about_img_3']['name'])) {
            $upload = handleFileUpload($_FILES['about_img_3'], 'assets/img/');
            if($upload['success']) {
                if($q['about_img_3'] && $q['about_img_3'] != 'default.jpg' && file_exists('assets/img/'.$q['about_img_3'])) {
                    @unlink('assets/img/'.$q['about_img_3']);
                }
                $img3 = $upload['filename'];
            }
        }

        $pdo->prepare("UPDATE profile SET profile_pic=?, about_img_1=?, about_img_2=?, about_img_3=? WHERE id=1")->execute([$pic, $img1, $img2, $img3]);
        setFlash('Images Uploaded!'); logSecurityEvent('Profile images updated', 'INFO'); header("Location: admin.php?tab=prof-pane"); exit();
    }

    // ==========================================
    // 2. PROJECT ACTIONS
    // ==========================================
    if (isset($_POST['save_project_text'])) {
        $pdo->prepare("UPDATE profile SET project_title=?, project_title_en=?, project_desc=?, project_desc_en=? WHERE id=1")->execute([$_POST['project_title'], $_POST['project_title_en'], $_POST['project_desc'], $_POST['project_desc_en']]);
        setFlash('Project Header Updated!'); header("Location: admin.php?tab=proj-pane"); exit();
    }

    if (isset($_POST['save_project'])) { 
        $img_name = "default.jpg";
        if(!empty($_FILES['image']['name'])) {
            $upload = handleFileUpload($_FILES['image'], 'assets/img/');
            if($upload['success']) {
                $img_name = $upload['filename'];
            }
        }

        $title = trim($_POST['title']);
        $cat = $_POST['category'];
        $comp_ref = ($cat == 'Personal') ? '' : trim($_POST['company_ref']); // Logic Company Ref
        
        $sql = "INSERT INTO projects (title, category, company_ref, tech_stack, description, description_en, challenge, impact, link_demo, image) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$title, $cat, $comp_ref, $_POST['tech_stack'], purify($_POST['description']), purify($_POST['description_en']), purify($_POST['challenge']), purify($_POST['impact']), $_POST['link_demo'], $img_name]);
        
        logSecurityEvent('Project created: ' . $title, 'INFO');
        setFlash("Project Added!"); header("Location: admin.php?tab=proj-pane"); exit();
    }

    if (isset($_POST['delete_project'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('Invalid request. CSRF token mismatch.', 'error');
            header("Location: admin.php?tab=proj-pane");
            exit();
        }
        
        $id = sanitizeInt($_POST['project_id'] ?? 0);
        if ($id === false || $id <= 0) {
            setFlash('Invalid project ID.', 'error');
            header("Location: admin.php?tab=proj-pane");
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT image FROM projects WHERE id=?");
        $stmt->execute([$id]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($d && $d['image'] != 'default.jpg' && file_exists('assets/img/'.$d['image'])) {
            @unlink('assets/img/'.$d['image']);
        }
        
        $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
        logSecurityEvent('Project deleted: ID ' . $id, 'INFO');
        setFlash('Project Deleted!');
        header("Location: admin.php?tab=proj-pane");
        exit();
    }

    // ==========================================
    // 3. JOURNEY ACTIONS
    // ==========================================
    if (isset($_POST['save_timeline'])) {
        $img_name = "";
        if(!empty($_FILES['image']['name'])) {
            $upload = handleFileUpload($_FILES['image'], 'assets/img/');
            if($upload['success']) {
                $img_name = $upload['filename'];
            }
        }
        
        $sql = "INSERT INTO timeline (year, sort_date, role, company, description, image) VALUES (?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$_POST['year'], $_POST['sort_date'], $_POST['role'], $_POST['company'], purify($_POST['description']), $img_name]);
        
        logSecurityEvent('Timeline added: ' . $_POST['role'], 'INFO');
        setFlash("Timeline Added!"); header("Location: admin.php?tab=time-pane"); exit();
    }

    if (isset($_POST['delete_timeline'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('Invalid request. CSRF token mismatch.', 'error');
            header("Location: admin.php?tab=time-pane");
            exit();
        }
        
        $id = sanitizeInt($_POST['timeline_id'] ?? 0);
        if ($id === false || $id <= 0) {
            setFlash('Invalid timeline ID.', 'error');
            header("Location: admin.php?tab=time-pane");
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT image FROM timeline WHERE id=?");
        $stmt->execute([$id]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);

        if($d && !empty($d['image']) && file_exists('assets/img/'.$d['image'])) {
            @unlink('assets/img/'.$d['image']);
        }

        $pdo->prepare("DELETE FROM timeline WHERE id=?")->execute([$id]);
        logSecurityEvent('Timeline deleted: ID ' . $id, 'INFO');
        setFlash('Timeline Deleted!');
        header("Location: admin.php?tab=time-pane");
        exit();
    }

    // ==========================================
    // 4. SKILLS ACTIONS
    // ==========================================
    if (isset($_POST['save_tech'])) {
        $pdo->prepare("INSERT INTO tech_stacks (name, category, icon) VALUES (?,?,?)")->execute([$_POST['tech_name'], $_POST['tech_category'], $_POST['tech_icon']]);
        setFlash("Skill Added!"); header("Location: admin.php?tab=tech-pane"); exit();
    }
    if (isset($_POST['delete_tech'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('Invalid request. CSRF token mismatch.', 'error');
            header("Location: admin.php?tab=tech-pane");
            exit();
        }
        
        $id = sanitizeInt($_POST['tech_id'] ?? 0);
        if ($id === false || $id <= 0) {
            setFlash('Invalid tech ID.', 'error');
            header("Location: admin.php?tab=tech-pane");
            exit();
        }
        
        $pdo->prepare("DELETE FROM tech_stacks WHERE id=?")->execute([$id]);
        logSecurityEvent('Tech skill deleted: ID ' . $id, 'INFO');
        setFlash('Skill Deleted!');
        header("Location: admin.php?tab=tech-pane");
        exit();
    }

    // ==========================================
    // 5. CERTIFICATE ACTIONS
    // ==========================================
    if (isset($_POST['save_cert'])) {
        $img_name = "default_cert.png";
        if (!empty($_FILES['cert_img']['name'])) {
            $ext = pathinfo($_FILES['cert_img']['name'], PATHINFO_EXTENSION);
            $new_img = "cert_" . time() . "_" . uniqid() . "." . $ext;
            if(move_uploaded_file($_FILES['cert_img']['tmp_name'], 'assets/img/' . $new_img)) $img_name = $new_img;
        }
        $pdo->prepare("INSERT INTO certifications (name, issuer, date_issued, credential_link, image) VALUES (?,?,?,?,?)")
            ->execute([$_POST['cert_name'], $_POST['cert_issuer'], $_POST['cert_date'], $_POST['cert_link'], $img_name]);
        setFlash("Certificate Added!"); header("Location: admin.php?tab=cert-pane"); exit();
    }
    if (isset($_POST['delete_cert'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('Invalid request. CSRF token mismatch.', 'error');
            header("Location: admin.php?tab=cert-pane");
            exit();
        }
        
        $id = sanitizeInt($_POST['cert_id'] ?? 0);
        if ($id === false || $id <= 0) {
            setFlash('Invalid certificate ID.', 'error');
            header("Location: admin.php?tab=cert-pane");
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT image FROM certifications WHERE id=?");
        $stmt->execute([$id]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);

        if($d && !empty($d['image']) && file_exists('assets/img/'.$d['image'])) {
            @unlink('assets/img/'.$d['image']);
        }
        
        $pdo->prepare("DELETE FROM certifications WHERE id=?")->execute([$id]);
        logSecurityEvent('Certificate deleted: ID ' . $id, 'INFO');
        setFlash('Certificate Deleted!');
        header("Location: admin.php?tab=cert-pane");
        exit();
    }

} catch (PDOException $e) { setFlash("DB Error: " . $e->getMessage(), 'error'); }

// AMBIL DATA PROFILE UTAMA
$stmt = $pdo->query("SELECT * FROM profile LIMIT 1");
$p = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --primary: #0f172a; --accent: #3b82f6; --bg: #f8fafc; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; padding-bottom: 50px; }
        .nav-link { color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 8px !important; margin-right: 5px; }
        .nav-link.active { background-color: var(--primary) !important; color: white !important; }
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-header { background: white; border-bottom: 1px solid #e2e8f0; font-weight: 700; padding: 1rem; color: var(--primary); }
        .form-floating label { color: #94a3b8; }
        .table img { object-fit: cover; border-radius: 6px; }
        .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark sticky-top shadow-sm mb-4" style="background: var(--primary) !important;">
    <div class="container">
        <span class="navbar-brand fw-bold"><i class="bi bi-grid-fill me-2"></i> CMS PANEL</span>
        <div class="d-flex gap-2">
            <a href="index.php" target="_blank" class="btn btn-sm btn-outline-light"><i class="bi bi-eye"></i> Web</a>
            <a href="logout.php" class="btn btn-sm btn-danger"><i class="bi bi-power"></i></a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <?php if(isset($_SESSION['flash_msg'])): ?>
    <script>Swal.fire({ icon: '<?= $_SESSION['flash_type'] ?>', title: '<?= $_SESSION['flash_msg'] ?>', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });</script>
    <?php unset($_SESSION['flash_msg']); unset($_SESSION['flash_type']); endif; ?>

    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm d-flex flex-nowrap overflow-auto" id="adminTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-target="#prof-pane" data-bs-toggle="tab">👤 Profile</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#proj-pane" data-bs-toggle="tab">📁 Projects</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#time-pane" data-bs-toggle="tab">⏳ Journey</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#cert-pane" data-bs-toggle="tab">🏅 Certs</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#tech-pane" data-bs-toggle="tab">🛠️ Skills</button></li>
    </ul>

    <div class="tab-content">
        
        <div class="tab-pane fade show active" id="prof-pane">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white"><i class="bi bi-window me-2"></i> Hero Section</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row g-2">
                                    <div class="col-md-6 form-floating mb-2"><input type="text" class="form-control" name="hero_pre" value="<?= v($p, 'hero_pre') ?>"><label>Opening (ID)</label></div>
                                    <div class="col-md-6 form-floating mb-2"><input type="text" class="form-control" name="hero_pre_en" value="<?= v($p, 'hero_pre_en') ?>"><label>Opening (EN)</label></div>
                                    <div class="col-md-6 form-floating mb-2"><input type="text" class="form-control" name="hero_greeting" value="<?= v($p, 'hero_greeting') ?>"><label>Name (ID)</label></div>
                                    <div class="col-md-6 form-floating mb-2"><input type="text" class="form-control" name="hero_greeting_en" value="<?= v($p, 'hero_greeting_en') ?>"><label>Name (EN)</label></div>
                                    <div class="col-md-6 form-floating mb-2"><input type="text" class="form-control" name="hero_title" value="<?= v($p, 'hero_title') ?>"><label>Headline (ID)</label></div>
                                    <div class="col-md-6 form-floating mb-2"><input type="text" class="form-control" name="hero_title_en" value="<?= v($p, 'hero_title_en') ?>"><label>Headline (EN)</label></div>
                                    <div class="col-12 form-floating mb-2"><textarea class="form-control" name="hero_desc" style="height:80px"><?= v($p, 'hero_desc') ?></textarea><label>Desc (ID)</label></div>
                                    <div class="col-12 form-floating mb-2"><textarea class="form-control" name="hero_desc_en" style="height:80px"><?= v($p, 'hero_desc_en') ?></textarea><label>Desc (EN)</label></div>
                                    <div class="col-12 form-floating"><input type="text" class="form-control" name="cv_link" value="<?= v($p, 'cv_link') ?>"><label>CV Link</label></div>
                                    <div class="col-12 mt-3 text-end"><button type="submit" name="save_hero" class="btn btn-primary">Update Hero</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-person-lines-fill me-2"></i> Contact</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-2"><label class="small text-muted">Email</label><input type="text" name="email" class="form-control form-control-sm" value="<?= v($p, 'email') ?>"></div>
                                <div class="mb-2"><label class="small text-muted">WhatsApp</label><input type="text" name="whatsapp" class="form-control form-control-sm" value="<?= v($p, 'whatsapp') ?>"></div>
                                <div class="mb-2"><label class="small text-muted">LinkedIn</label><input type="text" name="linkedin" class="form-control form-control-sm" value="<?= v($p, 'linkedin') ?>"></div>
                                <button type="submit" name="save_contact" class="btn btn-dark btn-sm w-100 mt-2">Save Contact</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><i class="bi bi-images me-2"></i> Images</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-2"><label class="small text-muted">Profile Pic</label><input type="file" name="profile_pic" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small text-muted">About Main</label><input type="file" name="about_img_1" class="form-control form-control-sm"></div>
                                <div class="row g-2">
                                    <div class="col-6"><label class="small text-muted">About 2</label><input type="file" name="about_img_2" class="form-control form-control-sm"></div>
                                    <div class="col-6"><label class="small text-muted">About 3</label><input type="file" name="about_img_3" class="form-control form-control-sm"></div>
                                </div>
                                <button type="submit" name="save_images" class="btn btn-outline-primary btn-sm w-100 mt-3">Upload</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100 border-warning border-start-4">
                        <div class="card-header bg-white">Bento Grid</div>
                        <div class="card-body">
                            <form method="POST">
                                <?php for($i=1; $i<=3; $i++): ?>
                                <div class="mb-3 border-bottom pb-2">
                                    <input type="text" name="bento_title_<?=$i?>" class="form-control form-control-sm fw-bold mb-1" value="<?= v($p, 'bento_title_'.$i) ?>" placeholder="Title <?=$i?>">
                                    <textarea name="bento_desc_<?=$i?>" class="form-control form-control-sm mb-1" rows="2" placeholder="ID"><?= v($p, 'bento_desc_'.$i) ?></textarea>
                                    <textarea name="bento_desc_<?=$i?>_en" class="form-control form-control-sm" rows="2" placeholder="EN"><?= v($p, 'bento_desc_'.$i.'_en') ?></textarea>
                                </div>
                                <?php endfor; ?>
                                <button type="submit" name="save_bento" class="btn btn-warning w-100 btn-sm">Update Bento</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">Section Titles</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row g-2">
                                    <div class="col-6"><input type="text" class="form-control form-control-sm" name="label_about" value="<?= v($p, 'label_about') ?>" placeholder="About Label"></div>
                                    <div class="col-6"><input type="text" class="form-control form-control-sm" name="about_title" value="<?= v($p, 'about_title') ?>" placeholder="About Title"></div>
                                    <div class="col-6"><input type="text" class="form-control form-control-sm" name="label_skills" value="<?= v($p, 'label_skills') ?>" placeholder="Skills Label"></div>
                                    <div class="col-6"><input type="text" class="form-control form-control-sm" name="title_skills" value="<?= v($p, 'title_skills') ?>" placeholder="Skills Title"></div>
                                    <input type="hidden" name="label_about_en" value="<?= v($p, 'label_about_en') ?>"><input type="hidden" name="about_title_en" value="<?= v($p, 'about_title_en') ?>"><input type="hidden" name="label_skills_en" value="<?= v($p, 'label_skills_en') ?>"><input type="hidden" name="title_skills_en" value="<?= v($p, 'title_skills_en') ?>"><input type="hidden" name="title_contact_1" value="<?= v($p, 'title_contact_1') ?>"><input type="hidden" name="title_contact_1_en" value="<?= v($p, 'title_contact_1_en') ?>"><input type="hidden" name="title_contact_2" value="<?= v($p, 'title_contact_2') ?>"><input type="hidden" name="title_contact_2_en" value="<?= v($p, 'title_contact_2_en') ?>">
                                </div>
                                <button type="submit" name="save_labels" class="btn btn-secondary w-100 btn-sm mt-3">Update Titles</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="proj-pane">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Header & List</span>
                    <button class="btn btn-sm btn-primary" onclick="openProjModal()"><i class="bi bi-plus-lg"></i> Add New Project</button>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-6"><input type="text" name="project_title" class="form-control form-control-sm" value="<?= v($p, 'project_title') ?>"></div>
                            <div class="col-md-6"><input type="text" name="project_title_en" class="form-control form-control-sm" value="<?= v($p, 'project_title_en') ?>"></div>
                            <div class="col-md-6"><textarea name="project_desc" class="form-control form-control-sm"><?= v($p, 'project_desc') ?></textarea></div>
                            <div class="col-md-6"><textarea name="project_desc_en" class="form-control form-control-sm"><?= v($p, 'project_desc_en') ?></textarea></div>
                            <div class="col-12 text-end"><button type="submit" name="save_project_text" class="btn btn-sm btn-outline-primary">Update Header</button></div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover">
                            <thead class="table-light"><tr><th>Img</th><th>Title</th><th>Category</th><th>Company</th><th>Act</th></tr></thead>
                            <tbody>
                                <?php 
                                $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
                                while($r = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <tr>
                                    <td width="60"><img src="assets/img/<?= $r['image'] ?>" width="50"></td>
                                    <td>
                                        <div class="fw-bold"><?= $r['title'] ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $r['category'] ?></span></td>
                                    <td>
                                        <?php if(!empty($r['company_ref'])): ?>
                                            <span class="badge bg-info text-white"><?= $r['company_ref'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_project.php?id=<?= $r['id'] ?>" class="btn btn-icon btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this project?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="project_id" value="<?= $r['id'] ?>">
                                            <button type="submit" name="delete_project" class="btn btn-icon btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="time-pane">
            <button class="btn btn-primary mb-3" onclick="openTimeModal()"><i class="bi bi-plus-lg"></i> Add Experience</button>
            <div class="card">
                <table class="table align-middle mb-0 table-hover">
                    <thead class="table-light"><tr><th>Year</th><th>Role</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM timeline ORDER BY sort_date DESC");
                        while($r = $stmt->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= $r['year'] ?></span></td>
                            <td><div class="fw-bold"><?= $r['role'] ?></div><small><?= $r['company'] ?></small></td>
                            <td>
                                <a href="edit_timeline.php?id=<?= $r['id'] ?>" class="btn btn-icon btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this experience?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="timeline_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="delete_timeline" class="btn btn-icon btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="cert-pane">
            <button class="btn btn-success mb-3" onclick="openCertModal()"><i class="bi bi-plus-lg"></i> Add Certificate</button>
            <div class="card">
                <table class="table align-middle mb-0 table-hover">
                    <thead class="table-light"><tr><th>Img</th><th>Name</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM certifications ORDER BY id DESC");
                        while($r = $stmt->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <tr>
                            <td width="50"><img src="assets/img/<?= $r['image'] ?>" width="40"></td>
                            <td><div class="fw-bold"><?= $r['name'] ?></div><small><?= $r['date_issued'] ?></small></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this certificate?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="cert_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="delete_cert" class="btn btn-icon btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tech-pane">
            <button class="btn btn-warning mb-3" onclick="openTechModal()"><i class="bi bi-plus-lg"></i> Add Skill</button>
            <div class="card">
                <table class="table align-middle mb-0 table-hover">
                    <thead class="table-light"><tr><th>Icon</th><th>Name</th><th>Cat</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM tech_stacks ORDER BY category ASC");
                        while($r = $stmt->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <tr>
                            <td><i class="<?= $r['icon'] ?>"></i></td>
                            <td class="fw-bold"><?= $r['name'] ?></td>
                            <td><span class="badge bg-secondary"><?= $r['category'] ?></span></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this skill?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="tech_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="delete_tech" class="btn btn-icon btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="projModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title">Add New Project</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-8"><label class="small fw-bold">Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="col-md-4">
                <label class="small fw-bold">Category</label>
                <select name="category" id="cat_select_add" class="form-select" onchange="toggleAddComp()">
                    <option value="Work">Work</option>
                    <option value="Personal">Personal</option>
                </select>
            </div>
            
            <div class="col-12" id="comp_box_add">
                <label class="small fw-bold text-primary">Related Company (from Timeline)</label>
                <select name="company_ref" class="form-select bg-light">
                    <option value="">-- Select Company --</option>
                    <?php 
                    $stmt = $pdo->query("SELECT DISTINCT company FROM timeline ORDER BY sort_date DESC");
                    while($c = $stmt->fetch(PDO::FETCH_ASSOC)): 
                    ?>
                    <option value="<?= $c['company'] ?>"><?= $c['company'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6"><label class="small fw-bold">Tech Stack</label><input type="text" name="tech_stack" class="form-control" required></div>
            <div class="col-md-6"><label class="small fw-bold">Demo Link</label><input type="text" name="link_demo" class="form-control"></div>
            <div class="col-12"><label class="small fw-bold">Cover Image</label><input type="file" name="image" class="form-control"></div>
            <div class="col-md-6"><label class="small fw-bold">Description (Indo)</label><textarea name="description" class="summernote"></textarea></div>
            <div class="col-md-6"><label class="small fw-bold">Description (English)</label><textarea name="description_en" class="summernote"></textarea></div>
            <div class="col-md-6"><label class="small fw-bold">Challenge</label><textarea name="challenge" class="summernote"></textarea></div>
            <div class="col-md-6"><label class="small fw-bold">Impact</label><textarea name="impact" class="summernote"></textarea></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" name="save_project" class="btn btn-primary">Save Project</button></div>
</form></div></div></div>

<div class="modal fade" id="timeModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title">Journey Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-8"><label>Year</label><input type="text" name="year" class="form-control" required></div>
            <div class="col-4"><label>Sort Date</label><input type="date" name="sort_date" class="form-control" required></div>
            <div class="col-12"><label>Role</label><input type="text" name="role" class="form-control" required></div>
            <div class="col-12"><label>Company</label><input type="text" name="company" class="form-control" required></div>
            <div class="col-12"><label>Description</label><textarea name="description" class="summernote"></textarea></div>
            <div class="col-12"><label>Image (Optional)</label><input type="file" name="image" class="form-control"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" name="save_timeline" class="btn btn-primary">Save</button></div>
</form></div></div></div>

<div class="modal fade" id="techModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST">
    <div class="modal-header"><h5 class="modal-title">Skill Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="fw-bold">Skill Name</label><input type="text" name="tech_name" class="form-control" required></div>
        <div class="mb-3">
            <label class="fw-bold">Category</label>
            <select name="tech_category" class="form-select">
                <option value="Analysis">Analysis</option><option value="Enterprise">Enterprise</option><option value="Development">Development</option>
            </select>
        </div>
        <div class="mb-2"><label class="fw-bold">Icon Class</label><input type="text" name="tech_icon" class="form-control" required></div>
    </div>
    <div class="modal-footer"><button type="submit" name="save_tech" class="btn btn-primary">Save Skill</button></div>
</form></div></div></div>

<div class="modal fade" id="certModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title">Certificate Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-6"><label>Name</label><input type="text" name="cert_name" class="form-control" required></div>
            <div class="col-6"><label>Issuer</label><input type="text" name="cert_issuer" class="form-control"></div>
            <div class="col-6"><label>Date Issued</label><input type="text" name="cert_date" class="form-control"></div>
            <div class="col-6"><label>Credential Link</label><input type="text" name="cert_link" class="form-control"></div>
            <div class="col-12"><label>Image</label><input type="file" name="cert_img" class="form-control"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" name="save_cert" class="btn btn-primary">Save</button></div>
</form></div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $('.summernote').summernote({ height: 120, toolbar: [['style', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol']], ['view', ['codeview']]] });
    const tab = new URLSearchParams(window.location.search).get('tab');
    if(tab) { const el = document.querySelector(`[data-bs-target="#${tab}"]`); if(el) new bootstrap.Tab(el).show(); }

    function openProjModal() { new bootstrap.Modal(document.getElementById('projModal')).show(); }
    function openTimeModal() { new bootstrap.Modal(document.getElementById('timeModal')).show(); }
    function openTechModal() { new bootstrap.Modal(document.getElementById('techModal')).show(); }
    function openCertModal() { new bootstrap.Modal(document.getElementById('certModal')).show(); }

    function toggleAddComp() {
        let cat = document.getElementById('cat_select_add').value;
        document.getElementById('comp_box_add').style.display = (cat === 'Personal') ? 'none' : 'block';
    }
</script>
</body>
</html>