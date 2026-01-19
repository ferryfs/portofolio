<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("Location: login.php"); exit(); }
include 'koneksi.php';

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM projects WHERE id='$id'"));

// Helper Flash Message (Copy dari admin.php biar sama)
function setFlash($msg, $type='success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if (isset($_POST['update'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $link  = mysqli_real_escape_string($conn, $_POST['link_demo']);
    $cat   = $_POST['category'];

    // Gambar Logic
    $img_db = $data['image'];
    if(!empty($_FILES['image']['name'])){
        if(file_exists('./assets/img/'.$data['image']) && $data['image'] != 'default.jpg') unlink('./assets/img/'.$data['image']);
        $new_img = "proj_" . time() . ".jpg";
        move_uploaded_file($_FILES['image']['tmp_name'], './assets/img/' . $new_img);
        $img_db = $new_img;
    }

    // PDF Logic
    $case_db = $data['link_case'];
    if(!empty($_FILES['file_case']['name'])){
        if($data['link_case'] != '#' && file_exists('./assets/docs/'.$data['link_case'])) unlink('./assets/docs/'.$data['link_case']);
        $new_case = time()."_".$_FILES['file_case']['name'];
        move_uploaded_file($_FILES['file_case']['tmp_name'], './assets/docs/'.$new_case);
        $case_db = $new_case;
    }

    $sql = "UPDATE projects SET title='$title', description='$desc', image='$img_db', link_demo='$link', link_case='$case_db', category='$cat' WHERE id='$id'";
    
    if(mysqli_query($conn, $sql)) {
        setFlash('Project Berhasil Diupdate!');
    } else {
        setFlash('Gagal Update: '.mysqli_error($conn), 'error');
    }
    
    // Balik ke Tab Project di Admin
    header("Location: admin.php?tab=proj-pane"); exit();
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="card col-md-6 mx-auto shadow border-0 rounded-4">
        <div class="card-header bg-primary text-white fw-bold p-3">✏️ Edit Project</div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="fw-bold small">Judul</label>
                    <input type="text" name="title" class="form-control" value="<?=$data['title']?>" required>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="Work" <?=($data['category']=='Work')?'selected':''?>>Work</option>
                        <option value="Personal" <?=($data['category']=='Personal')?'selected':''?>>Personal</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="4" required><?=$data['description']?></textarea>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small">Link Demo</label>
                    <input type="text" name="link_demo" class="form-control" value="<?=$data['link_demo']?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small">Ganti Gambar</label>
                        <input type="file" name="image" class="form-control">
                        <small class="text-muted d-block mt-1">Saat ini: <?=$data['image']?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small">Ganti PDF</label>
                        <input type="file" name="file_case" class="form-control" accept=".pdf">
                        <small class="text-muted d-block mt-1">Saat ini: <?=$data['link_case']?></small>
                    </div>
                </div>

                <button type="submit" name="update" class="btn btn-primary w-100 fw-bold py-2">UPDATE DATA</button>
                <a href="admin.php?tab=proj-pane" class="btn btn-light w-100 text-muted mt-2">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>