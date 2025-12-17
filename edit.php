<?php
session_start();
// 1. CEK LOGIN
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php"); 
    exit(); 
}

include 'koneksi.php';

// 2. AMBIL DATA LAMA
$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM projects WHERE id='$id'");
$data  = mysqli_fetch_assoc($query);

// Kalau ID gak ketemu, balik ke admin
if(mysqli_num_rows($query) < 1) {
    header("Location: admin.php");
    exit();
}

// 3. PROSES UPDATE
if (isset($_POST['update'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $tech  = mysqli_real_escape_string($conn, $_POST['tech_stack']);
    $link  = mysqli_real_escape_string($conn, $_POST['link_demo']);
    $creds = mysqli_real_escape_string($conn, $_POST['credentials']); // NEW

    // --- A. LOGIC GAMBAR ---
    $img_db = $data['image']; // Default pake gambar lama
    
    if(!empty($_FILES['image']['name'])){
        // Hapus gambar lama
        if(file_exists('./assets/img/'.$data['image'])) {
            unlink('./assets/img/'.$data['image']);
        }
        // Upload gambar baru
        move_uploaded_file($_FILES['image']['tmp_name'], './assets/img/'.$_FILES['image']['name']);
        $img_db = $_FILES['image']['name'];
    }

    // --- B. LOGIC STUDI KASUS (HYBRID & BERSIH-BERSIH) ---
    $case_db = $data['link_case']; // Default pake data lama

    // Cek 1: User Upload PDF Baru?
    if(!empty($_FILES['file_case']['name'])){
        // Hapus file lama jika itu PDF (Bukan URL)
        if($data['link_case'] != '#' && strpos($data['link_case'], 'http') === false) {
            if(file_exists('./assets/docs/'.$data['link_case'])) unlink('./assets/docs/'.$data['link_case']);
        }
        
        // Upload baru
        $new_case = time()."_".$_FILES['file_case']['name'];
        move_uploaded_file($_FILES['file_case']['tmp_name'], './assets/docs/'.$new_case);
        $case_db = $new_case;
    } 
    // Cek 2: User Isi Link URL Baru?
    elseif(!empty($_POST['url_case'])){
        // Hapus file lama jika itu PDF (karena diganti jadi URL)
        if($data['link_case'] != '#' && strpos($data['link_case'], 'http') === false) {
            if(file_exists('./assets/docs/'.$data['link_case'])) unlink('./assets/docs/'.$data['link_case']);
        }
        $case_db = mysqli_real_escape_string($conn, $_POST['url_case']);
    } 
    // Cek 3: User Centang Hapus?
    elseif(isset($_POST['hapus_case'])){
        // Hapus file fisik
        if($data['link_case'] != '#' && strpos($data['link_case'], 'http') === false) {
            if(file_exists('./assets/docs/'.$data['link_case'])) unlink('./assets/docs/'.$data['link_case']);
        }
        $case_db = "#";
    }

    // --- C. UPDATE DATABASE ---
    mysqli_query($conn, "UPDATE projects SET title='$title', description='$desc', image='$img_db', tech_stack='$tech', link_demo='$link', link_case='$case_db', credentials='$creds' WHERE id='$id'");
    
    echo "<script>alert('Data Berhasil Diupdate!'); window.location='admin.php';</script>";
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light p-5">

    <div class="card col-md-8 mx-auto shadow">
        <div class="card-header bg-warning text-white fw-bold">
            <i class="bi bi-pencil-square"></i> Edit Project
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label class="fw-bold">Judul Proyek</label>
                    <input type="text" name="title" class="form-control" value="<?=$data['title']?>" required>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Tech Stack</label>
                    <input type="text" name="tech_stack" class="form-control" value="<?=$data['tech_stack']?>" required>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" required><?=$data['description']?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold text-warning"><i class="bi bi-key"></i> Credentials / Akses Login</label>
                    <textarea name="credentials" class="form-control bg-light" rows="2"><?=$data['credentials']?></textarea>
                    <small class="text-muted">Isi username/password demo di sini.</small>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Gambar Saat Ini</label><br>
                    <img src="assets/img/<?=$data['image']?>" width="150" class="rounded border mb-2">
                    <input type="file" name="image" class="form-control">
                    <small class="text-danger">*Biarkan kosong jika tidak ingin ganti gambar.</small>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Link Demo</label>
                    <input type="text" name="link_demo" class="form-control" value="<?=$data['link_demo']?>">
                </div>
                
                <div class="mb-3">
                    <div class="card bg-light border-secondary">
                        <div class="card-body">
                            <label class="fw-bold text-primary mb-2">Studi Kasus Saat Ini:</label>
                            <div class="mb-3">
                                <?php 
                                if(empty($data['link_case']) || $data['link_case'] == '#') { 
                                    echo '<span class="badge bg-secondary">Belum Ada</span>'; 
                                } elseif(strpos($data['link_case'], 'http') !== false) { 
                                    echo '<a href="'.$data['link_case'].'" target="_blank" class="text-decoration-none">🔗 '.$data['link_case'].'</a>'; 
                                } else { 
                                    echo '<span class="badge bg-danger">📄 File PDF: '.$data['link_case'].'</span>'; 
                                }
                                ?>
                            </div>

                            <label class="small fw-bold">Ganti Dengan:</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="file" name="file_case" class="form-control" accept=".pdf">
                                    <small class="text-muted">Upload PDF Baru</small>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="url_case" class="form-control" placeholder="Atau Paste Link URL Baru">
                                </div>
                            </div>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="hapus_case" value="yes" id="hapusCheck">
                                <label class="form-check-label text-danger small" for="hapusCheck">
                                    Hapus/Kosongkan Studi Kasus
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" name="update" class="btn btn-success w-100 fw-bold">
                        <i class="bi bi-save"></i> SIMPAN PERUBAHAN
                    </button>
                    <a href="admin.php" class="btn btn-secondary w-100 fw-bold">
                        BATAL
                    </a>
                </div>

            </form>
        </div>
    </div>

</body>
</html>