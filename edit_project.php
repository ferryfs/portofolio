<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php"); 
    exit(); 
}
include 'koneksi.php'; 

$id = $_GET['id'];
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM projects WHERE id='$id'"));

// HELPER
function setFlash($msg, $type='success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}
function purify($text) {
    return strip_tags($text, '<ul><ol><li><b><strong><i><em><u><br><p>');
}
function clearCache($file) {
    $path = "cache/" . $file . ".json";
    if (file_exists($path)) unlink($path);
}

// LOGIC UPDATE
if(isset($_POST['update'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $cat = $_POST['category'];
    $tech = mysqli_real_escape_string($conn, $_POST['tech_stack']);
    $link = mysqli_real_escape_string($conn, $_POST['link_demo']);
    
    // Sanitize Deskripsi
    $desc = mysqli_real_escape_string($conn, purify($_POST['description']));
    $desc_en = mysqli_real_escape_string($conn, purify($_POST['description_en']));
    $chal = mysqli_real_escape_string($conn, purify($_POST['challenge'])); // 🔥 Update
    $imp = mysqli_real_escape_string($conn, purify($_POST['impact'])); // 🔥 Update
    
    // Logic Gambar
    $img_sql = ""; 
    if(!empty($_FILES['image']['name'])) {
        $img_name = "proj_" . time() . ".jpg";
        
        // Hapus gambar lama
        if(!empty($d['image']) && $d['image'] != 'default.jpg' && file_exists('assets/img/'.$d['image'])) {
            unlink('assets/img/'.$d['image']);
        }
        
        // Upload baru
        move_uploaded_file($_FILES['image']['tmp_name'], 'assets/img/' . $img_name);
        $img_sql = ", image='$img_name'";
    }

    $query = "UPDATE projects SET 
              title='$title', 
              category='$cat', 
              tech_stack='$tech', 
              link_demo='$link', 
              description='$desc', 
              description_en='$desc_en',
              challenge='$chal',
              impact='$imp'
              $img_sql 
              WHERE id='$id'";
    
    if(mysqli_query($conn, $query)) {
        setFlash('Project Berhasil Diupdate!');
        clearCache('projects'); 
    } else {
        setFlash('Gagal: '.mysqli_error($conn), 'error');
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
                        <div class="col-md-8">
                            <label class="fw-bold small text-muted">Nama Project</label>
                            <input type="text" name="title" class="form-control" value="<?=$d['title']?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-muted">Kategori</label>
                            <select name="category" class="form-select">
                                <option value="Work" <?=($d['category']=='Work')?'selected':''?>>Work Project</option>
                                <option value="Personal" <?=($d['category']=='Personal')?'selected':''?>>Personal Project</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="fw-bold small text-muted">Tech Stack</label>
                            <input type="text" name="tech_stack" class="form-control" value="<?=$d['tech_stack']?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small text-muted">Link Demo / Web</label>
                            <input type="text" name="link_demo" class="form-control" value="<?=$d['link_demo']?>">
                        </div>

                        <div class="col-md-6">
                            <label class="fw-bold small text-muted mb-1">Deskripsi (Indo)</label>
                            <textarea id="summernote_id" name="description" required><?=$d['description']?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small text-muted mb-1">Description (English)</label>
                            <textarea id="summernote_en" name="description_en"><?=$d['description_en']?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="fw-bold small text-muted mb-1">Challenge / Tantangan</label>
                            <textarea id="summernote_chal" name="challenge"><?=$d['challenge']?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small text-muted mb-1">Impact / Hasil</label>
                            <textarea id="summernote_imp" name="impact"><?=$d['impact']?></textarea>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="fw-bold small text-muted">Cover Image</label>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <img src="assets/img/<?=$d['image']?>" width="100" class="rounded border p-1 shadow-sm">
                                <div class="w-100">
                                    <input type="file" name="image" class="form-control">
                                    <small class="text-muted" style="font-size:11px">*Biarkan kosong jika tidak ingin ganti gambar</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" name="update" class="btn btn-primary text-white w-100 fw-bold py-2">UPDATE PROJECT</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        var config = {
            tabsize: 2, height: 150,
            toolbar: [['style', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['view', ['codeview']]]
        };
        $('#summernote_id').summernote(config);
        $('#summernote_en').summernote(config);
        $('#summernote_chal').summernote(config);
        $('#summernote_imp').summernote(config);
    </script>
</body>
</html>