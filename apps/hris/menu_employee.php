<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

// --- LOGIC UPDATE GAJI & JABATAN ---
if(isset($_POST['update_employee'])) {
    $id   = $_POST['id_user'];
    $role = $_POST['role'];
    $gaji = str_replace(".", "", $_POST['gaji']); // Hapus titik format ribuan
    
    mysqli_query($conn, "UPDATE ess_users SET role='$role', basic_salary='$gaji' WHERE id='$id'");
    echo "<script>alert('Data Karyawan Diupdate!'); window.location='menu_employee.php';</script>";
}

// --- LOGIC HAPUS KARYAWAN ---
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM ess_users WHERE id='$id'");
    echo "<script>alert('Karyawan Dihapus (Resign).'); window.location='menu_employee.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - HRIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; background: #1e293b; color: #adb5bd; padding-top: 20px; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #adb5bd; padding: 12px 25px; text-decoration: none; display: flex; align-items: center; }
        .nav-link:hover, .nav-link.active { background: #0d6efd; color: white; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="text-center text-white mb-4 fs-4 fw-bold"><i class="fa fa-cube"></i> HRIS PRO</div>
        
        <a href="index.php" class="nav-link"><i class="fa fa-gauge-high me-3"></i> Dashboard</a>
        <a href="menu_employee.php" class="nav-link active"><i class="fa fa-users me-3"></i> Data Karyawan</a>
        
        <a href="menu_attendance.php" class="nav-link"><i class="fa fa-clock me-3"></i> Absensi & Cuti</a>

        <a href="auth.php?logout=true" class="nav-link text-danger mt-5"><i class="fa fa-sign-out-alt me-3"></i> Logout</a>
    </div>

    <div class="main-content">
        <h4 class="fw-bold mb-4">Database Karyawan</h4>

        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">List Semua Pegawai Aktif</h6>
                    <button class="btn btn-sm btn-primary"><i class="fa fa-user-plus me-1"></i> Tambah Manual</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NIK</th>
                                <th>Nama Lengkap</th>
                                <th>Divisi</th>
                                <th>Jabatan</th>
                                <th>Gaji Pokok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM ess_users ORDER BY fullname ASC");
                            while($row = mysqli_fetch_assoc($query)) {
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $row['employee_id']; ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo $row['fullname']; ?>&background=random" class="rounded-circle me-2" width="35">
                                        <div>
                                            <div class="fw-bold"><?php echo $row['fullname']; ?></div>
                                            <small class="text-muted"><?php echo $row['email']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $row['division']; ?></td>
                                <td><?php echo $row['role']; ?></td>
                                <td class="fw-bold text-success">Rp <?php echo number_format($row['basic_salary'], 0, ',', '.'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <a href="menu_employee.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus data ini?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Data: <?php echo $row['fullname']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_user" value="<?php echo $row['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Jabatan</label>
                                                    <select name="role" class="form-select">
                                                        <option value="Staff" <?php if($row['role']=='Staff') echo 'selected'; ?>>Staff</option>
                                                        <option value="Supervisor" <?php if($row['role']=='Supervisor') echo 'selected'; ?>>Supervisor</option>
                                                        <option value="Manager" <?php if($row['role']=='Manager') echo 'selected'; ?>>Manager</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Gaji Pokok (Basic Salary)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="number" name="gaji" class="form-control" value="<?php echo $row['basic_salary']; ?>" required>
                                                    </div>
                                                    <div class="form-text">Masukkan angka tanpa titik.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_employee" class="btn btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>