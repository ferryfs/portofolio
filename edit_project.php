<?php
session_name("PORTFOLIO_CMS_SESSION");
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("Location: login.php"); exit(); }

// LOAD KONEKSI PDO
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/security.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$d) { header("Location: admin.php"); exit(); }

function purify($text) { return strip_tags($text ?? '', '<ul><ol><li><b><strong><i><em><u><br><p>'); }
function setFlash($msg, $type='success') { $_SESSION['flash_msg'] = $msg; $_SESSION['flash_type'] = $type; }

if(isset($_POST['update'])){
    $title = trim($_POST['title']);
    $cat = $_POST['category'];
    $comp_ref = ($cat == 'Personal') ? '' : trim($_POST['company_ref']);
    $tech = trim($_POST['tech_stack']);
    $link = trim($_POST['link_demo']);
    $desc = purify($_POST['description']);
    $desc_en = purify($_POST['description_en']);
    $chal = purify($_POST['challenge']);
    $imp = purify($_POST['impact']);
    
    // Update dengan image validation via security.php
    $sql = "UPDATE projects SET title=?, category=?, company_ref=?, tech_stack=?, link_demo=?, description=?, description_en=?, challenge=?, impact=? WHERE id=?";
    $params = [$title, $cat, $comp_ref, $tech, $link, $desc, $desc_en, $chal, $imp, $id];

    if(!empty($_FILES['image']['name'])) {
        $upload = handleFileUpload($_FILES['image'], 'assets/img/');
        if($upload['success']) {
            if(!empty($d['image']) && $d['image'] != 'default.jpg' && file_exists('assets/img/'.$d['image'])) {
                @unlink('assets/img/'.$d['image']);
            }
            
            // Update dengan gambar baru
            $sql = "UPDATE projects SET title=?, category=?, company_ref=?, tech_stack=?, link_demo=?, description=?, description_en=?, challenge=?, impact=?, image=? WHERE id=?";
            $params = [$title, $cat, $comp_ref, $tech, $link, $desc, $desc_en, $chal, $imp, $upload['filename'], $id];
        } else {
            setFlash($upload['error'], 'error');
            header("Location: edit_project.php?id=" . urlencode($id));
            exit();
        }
    }

    try {
        $pdo->prepare($sql)->execute($params);
        logSecurityEvent('Project updated: ID ' . $id, 'INFO');
        setFlash('Project Updated!');
    } catch(PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        setFlash('Terjadi kesalahan saat menyimpan', 'error');
    }
    header("Location: admin.php?tab=proj-pane"); exit();
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>body { background: #f8f9fa; } .note-editor .dropdown-toggle::after { all: unset; }</style>
</head>
<body class="p-4">
    <div class="container">
        <div class="card col-md-10 mx-auto shadow border-0 rounded-4">
            <div class="card-header bg-primary text-white fw-bold p-3 d-flex justify-content-between align-items-center">
                <span>📁 Edit Project</span>
                <a href="admin.php?tab=proj-pane" class="btn btn-sm btn-light text-primary fw-bold">Kembali</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-8"><label class="fw-bold small">Nama Project</label><input type="text" name="title" class="form-control" value="<?=$d['title']?>" required></div>
                        <div class="col-md-4">
                            <label class="fw-bold small">Kategori</label>
                            <select name="category" id="cat_select" class="form-select" onchange="toggleComp()">
                                <option value="Work" <?=($d['category']=='Work')?'selected':''?>>Work</option>
                                <option value="Personal" <?=($d['category']=='Personal')?'selected':''?>>Personal</option>
                            </select>
                        </div>
                        
                        <div class="col-12" id="comp_box" style="display: <?=($d['category']=='Personal')?'none':'block'?>;">
                            <label class="fw-bold small text-primary">Related Company</label>
                            <select name="company_ref" class="form-select bg-light">
                                <option value="">-- Select Company --</option>
                                <?php 
                                $qComp = $pdo->query("SELECT DISTINCT company FROM timeline ORDER BY sort_date DESC");
                                while($row = $qComp->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <option value="<?=$row['company']?>" <?=($d['company_ref'] == $row['company'])?'selected':''?>><?=$row['company']?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-12"><label class="fw-bold small">Tech Stack</label><input type="text" name="tech_stack" class="form-control" value="<?=$d['tech_stack']?>"></div>
                        <div class="col-12"><label class="fw-bold small">Link Demo</label><input type="text" name="link_demo" class="form-control" value="<?=$d['link_demo']?>"></div>
                        
                        <div class="col-12 mt-3"><label class="fw-bold small">Image</label><br>
                        <img src="assets/img/<?=$d['image']?>" width="100" class="mb-2 rounded">
                        <input type="file" name="image" class="form-control"></div>

                        <div class="col-md-6"><textarea id="sn1" name="description"><?=$d['description']?></textarea></div>
                        <div class="col-md-6"><textarea id="sn2" name="description_en"><?=$d['description_en']?></textarea></div>
                        <div class="col-md-6"><textarea id="sn3" name="challenge"><?=$d['challenge']?></textarea></div>
                        <div class="col-md-6"><textarea id="sn4" name="impact"><?=$d['impact']?></textarea></div>

                        <div class="col-12 mt-4"><button type="submit" name="update" class="btn btn-primary w-100 fw-bold">UPDATE PROJECT</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        $('textarea').summernote({ height: 100 });
        function toggleComp() {
            let cat = document.getElementById('cat_select').value;
            document.getElementById('comp_box').style.display = (cat === 'Personal') ? 'none' : 'block';
        }
    </script>
</body>
</html>