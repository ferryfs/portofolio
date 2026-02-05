<?php
session_name("PORTFOLIO_CMS_SESSION");
session_start();

// Security Check
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("Location: login.php"); exit(); 
}

// LOAD KONEKSI PDO
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/security.php';

// Validasi ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: admin.php"); exit(); }

// Ambil Data Lama (PDO)
$stmt = $pdo->prepare("SELECT * FROM timeline WHERE id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) { header("Location: admin.php"); exit(); }

// Helper
function purify($text) { return strip_tags($text ?? '', '<ul><ol><li><b><strong><i><em><u><br><p>'); }
function setFlash($msg, $type='success') { $_SESSION['flash_msg'] = $msg; $_SESSION['flash_type'] = $type; }

// LOGIC UPDATE
if(isset($_POST['update'])){
    try {
        $year = trim($_POST['year']);
        $s_date = $_POST['sort_date'];
        $role = trim($_POST['role']);
        $comp = trim($_POST['company']);
        $desc = purify($_POST['description']);
        
        // Update dengan image validation via security.php
        $sql = "UPDATE timeline SET year=?, sort_date=?, role=?, company=?, description=? WHERE id=?";
        $params = [$year, $s_date, $role, $comp, $desc, $id];

        if(!empty($_FILES['image']['name'])) {
            $upload = handleFileUpload($_FILES['image'], 'assets/img/');
            if($upload['success']) {
                // Hapus gambar lama
                if(!empty($d['image']) && file_exists('assets/img/'.$d['image'])) {
                    @unlink('assets/img/'.$d['image']);
                }
                
                // Update Query + Image
                $sql = "UPDATE timeline SET year=?, sort_date=?, role=?, company=?, description=?, image=? WHERE id=?";
                $params = [$year, $s_date, $role, $comp, $desc, $upload['filename'], $id];
            } else {
                setFlash($upload['error'], 'error');
                header("Location: edit_timeline.php?id=" . urlencode($id));
                exit();
            }
        }
        
        // Eksekusi PDO
        $pdo->prepare($sql)->execute($params);
        
        logSecurityEvent('Timeline updated: ID ' . $id, 'INFO');
        setFlash('Timeline berhasil diupdate!');
        header("Location: admin.php?tab=time-pane");
        exit();
        
    } catch (PDOException $e) {
        setFlash('Error Database: ' . $e->getMessage(), 'error');
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Career</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>body { background: #f8f9fa; } .note-editor .dropdown-toggle::after { all: unset; }</style>
</head>
<body class="p-4">
    <div class="container">
        <div class="card col-md-8 mx-auto shadow border-0 rounded-4">
            <div class="card-header bg-info text-white fw-bold p-3 d-flex justify-content-between align-items-center">
                <span>✏️ Edit Career Journey</span>
                <a href="admin.php?tab=time-pane" class="btn btn-sm btn-light text-info fw-bold">Kembali</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="fw-bold small text-muted">Teks Tahun</label>
                            <input type="text" name="year" class="form-control" value="<?=$d['year']?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold small text-muted">Tgl Mulai (Sorting)</label>
                            <input type="date" name="sort_date" class="form-control" value="<?=$d['sort_date']?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold small text-muted">Perusahaan</label>
                            <input type="text" name="company" class="form-control" value="<?=$d['company']?>" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="fw-bold small text-muted">Role / Posisi</label>
                            <input type="text" name="role" class="form-control" value="<?=$d['role']?>" required>
                        </div>
                        
                        <div class="col-12 mb-4">
                            <label class="fw-bold small text-muted mb-1">Deskripsi & Jobdesc</label>
                            <textarea id="summernote" name="description" required><?=$d['description']?></textarea>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="fw-bold small text-muted">Update Kartun Popup</label>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <?php if(!empty($d['image'])): ?>
                                    <img src="assets/img/<?=$d['image']?>" width="60" class="rounded border p-1">
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border p-2">No Image</span>
                                <?php endif; ?>
                                <input type="file" name="image" class="form-control">
                            </div>
                            <small class="text-muted" style="font-size:11px">*Biarkan kosong jika tidak ingin mengubah gambar</small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="update" class="btn btn-info text-white w-100 fw-bold py-2">UPDATE DATA</button>
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
        $('#summernote').summernote({
            placeholder: 'Tulis deskripsi...',
            tabsize: 2, height: 200,
            toolbar: [['style', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']]]
        });
    </script>
</body>
</html>