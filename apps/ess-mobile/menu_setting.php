<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// 1. CEK LOGIN
if(!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$nik = $_SESSION['ess_user'];

// 2. LOGIC UPDATE PROFIL
if(isset($_POST['update_profile'])) {
    $hp     = $_POST['phone'];
    $alamat = $_POST['address'];
    
    $sql = "UPDATE ess_users SET phone_number='$hp', address='$alamat' WHERE employee_id='$nik'";
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Profil Berhasil Diupdate!'); window.location='menu_setting.php';</script>";
    }
}

// 3. LOGIC GANTI PASSWORD
if(isset($_POST['change_password'])) {
    $pass_lama = md5($_POST['old_pass']);
    $pass_baru = md5($_POST['new_pass']);
    
    // Cek password lama bener gak?
    $cek = mysqli_query($conn, "SELECT * FROM ess_users WHERE employee_id='$nik' AND password='$pass_lama'");
    if(mysqli_num_rows($cek) > 0) {
        // Kalau bener, update ke yang baru
        mysqli_query($conn, "UPDATE ess_users SET password='$pass_baru' WHERE employee_id='$nik'");
        echo "<script>alert('Password Berhasil Diganti! Silakan login ulang.'); window.location='auth.php?logout=true';</script>";
    } else {
        echo "<script>alert('Password Lama Salah!');</script>";
    }
}

// 4. AMBIL DATA USER TERBARU
$query = mysqli_query($conn, "SELECT * FROM ess_users WHERE employee_id='$nik'");
$data  = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengaturan Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .avatar-section { background: linear-gradient(135deg, #0d6efd, #0dcaf0); padding: 40px 20px; text-align: center; color: white; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; }
        .card-setting { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
        .form-control:focus { box-shadow: none; border-color: #0d6efd; }
    </style>
</head>
<body>
    <div class="mobile-frame mx-auto" style="max-width: 450px; background: #eef2f6; min-height: 100vh;">
        
        <div class="avatar-section">
            <div class="d-flex align-items-center mb-3">
                <a href="index.php" class="text-white me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
                <h5 class="fw-bold mb-0">Pengaturan Akun</h5>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?php echo $data['fullname']; ?>&background=fff&color=0d6efd&size=128" class="rounded-circle border border-4 border-white shadow-sm mb-2" width="100">
            <h4 class="fw-bold"><?php echo $data['fullname']; ?></h4>
            <p class="mb-0 opacity-75"><?php echo $data['division']; ?> • <?php echo $data['employee_id']; ?></p>
        </div>

        <div class="container py-4">

            <div class="card card-setting">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fa fa-user-edit text-primary me-2"></i> Edit Data Diri
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small text-muted">Email</label>
                            <input type="text" class="form-control bg-light" value="<?php echo $data['email']; ?>" readonly>
                            <small class="text-secondary" style="font-size: 0.7rem;">*Email tidak dapat diubah</small>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Nomor WhatsApp</label>
                            <input type="number" name="phone" class="form-control" value="<?php echo $data['phone_number']; ?>" placeholder="08123456789">
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Alamat Domisili</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Jl. Sudirman No..."><?php echo $data['address']; ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary w-100 rounded-pill">Simpan Perubahan</button>
                    </form>
                </div>
            </div>

            <div class="card card-setting">
                <div class="card-header bg-white fw-bold py-3 text-danger">
                    <i class="fa fa-lock me-2"></i> Keamanan
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Password Lama</label>
                            <input type="password" name="old_pass" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Password Baru</label>
                            <input type="password" name="new_pass" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-danger w-100 rounded-pill">Ganti Password</button>
                    </form>
                </div>
            </div>

            <a href="auth.php?logout=true" class="btn btn-secondary w-100 py-3 rounded-pill fw-bold mb-5">
                <i class="fa fa-sign-out-alt me-2"></i> KELUAR APLIKASI
            </a>

        </div>
    </div>
</body>
</html>