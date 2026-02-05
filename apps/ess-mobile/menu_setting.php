<?php
// apps/ess-mobile/menu_setting.php (PRO UI + PDO SECURITY)

session_name('ESS_PORTAL_SESSION');
session_start();

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

// 1. CEK LOGIN
$uid = $_SESSION['ess_user'] ?? '';
if (empty($uid)) {
    header("Location: landing.php");
    exit();
}

// Variabel buat SweetAlert
$swal_title = '';
$swal_text = '';
$swal_icon = '';

// 2. LOGIC UPDATE PROFIL
if (isset($_POST['update_profile'])) {
    if (!verifyCSRFToken()) {
        $swal_title = 'Error!'; $swal_text = 'Security Token Invalid.'; $swal_icon = 'error';
    } else {
        $hp     = sanitizeInput($_POST['phone']);
        $alamat = sanitizeInput($_POST['address']);
        
        if (safeQuery($pdo, "UPDATE ess_users SET phone_number = ?, address = ? WHERE employee_id = ?", [$hp, $alamat, $uid])) {
            logSecurityEvent("Profile Updated: $uid");
            $swal_title = 'Berhasil!'; $swal_text = 'Data profil berhasil diperbarui.'; $swal_icon = 'success';
        } else {
            $swal_title = 'Gagal!'; $swal_text = 'Terjadi kesalahan sistem.'; $swal_icon = 'error';
        }
    }
}

// 3. LOGIC GANTI PASSWORD
if (isset($_POST['change_password'])) {
    if (!verifyCSRFToken()) {
        $swal_title = 'Error!'; $swal_text = 'Security Token Invalid.'; $swal_icon = 'error';
    } else {
        $pass_lama = $_POST['old_pass'];
        $pass_baru = $_POST['new_pass'];
        
        if (empty($pass_lama) || empty($pass_baru)) {
            $swal_title = 'Peringatan'; $swal_text = 'Password tidak boleh kosong.'; $swal_icon = 'warning';
        } elseif (strlen($pass_baru) < 6) {
            $swal_title = 'Tidak Aman'; $swal_text = 'Password baru minimal 6 karakter.'; $swal_icon = 'warning';
        } else {
            $user = safeGetOne($pdo, "SELECT password FROM ess_users WHERE employee_id = ?", [$uid]);
            
            if ($user && verifyPassword($pass_lama, $user['password'])) {
                $newHash = hashPassword($pass_baru);
                if (safeQuery($pdo, "UPDATE ess_users SET password = ? WHERE employee_id = ?", [$newHash, $uid])) {
                    logSecurityEvent("Password Changed: $uid");
                    $swal_title = 'Sukses!'; $swal_text = 'Password berhasil diganti. Silakan login ulang.'; $swal_icon = 'success';
                    // Auto logout biar aman
                    echo "<script>setTimeout(function(){ window.location.href='auth.php?logout=true'; }, 2000);</script>";
                } else {
                    $swal_title = 'Gagal!'; $swal_text = 'Database error.'; $swal_icon = 'error';
                }
            } else {
                logSecurityEvent("Failed Password Change: $uid", "WARNING");
                $swal_title = 'Akses Ditolak'; $swal_text = 'Password Lama Salah!'; $swal_icon = 'error';
            }
        }
    }
}

// 4. AMBIL DATA USER
$data = safeGetOne($pdo, "SELECT * FROM ess_users WHERE employee_id = ?", [$uid]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengaturan Akun</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f3f4f6; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        .mobile-frame { width: 100%; max-width: 450px; min-height: 100vh; background: #fff; margin: 0 auto; position: relative; box-shadow: 0 0 40px rgba(0,0,0,0.05); }
        
        /* Header Modern */
        .setting-header {
            background: #ffffff;
            padding: 20px 20px 10px;
            position: sticky; top: 0; z-index: 10;
            border-bottom: 1px solid #f1f5f9;
        }
        
        /* Profile Card */
        .profile-card {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            color: white;
            margin: 20px;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.3);
            position: relative; overflow: hidden;
        }
        .profile-card::after {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
            transform: rotate(30deg); pointer-events: none;
        }
        .avatar-img { border: 4px solid rgba(255,255,255,0.2); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        
        /* Section Title */
        .section-title {
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            color: #94a3b8; margin: 25px 20px 10px;
        }

        /* Modern Input Group */
        .form-floating > .form-control { border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .form-floating > .form-control:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
        .form-floating > label { color: #64748b; }
        
        .input-group-text { border-radius: 0 12px 12px 0; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; }
        
        /* Buttons */
        .btn-save {
            background: #2563eb; border: none; color: white; border-radius: 12px; padding: 12px;
            font-weight: 600; width: 100%; margin-top: 15px; box-shadow: 0 4px 12px rgba(37,99,235,0.3);
            transition: all 0.2s;
        }
        .btn-save:active { transform: scale(0.98); }
        
        .btn-logout {
            background: #fee2e2; color: #ef4444; border: none; border-radius: 12px; padding: 14px;
            font-weight: 700; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-top: 40px; margin-bottom: 40px; transition: all 0.2s;
        }
        .btn-logout:hover { background: #fecaca; }
    </style>
</head>
<body>

    <div class="mobile-frame">
        
        <div class="setting-header d-flex align-items-center">
            <a href="index.php" class="btn btn-light rounded-circle shadow-sm" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;">
                <i class="fa fa-arrow-left"></i>
            </a>
            <h5 class="fw-bold mb-0 ms-3">Settings</h5>
        </div>

        <div class="profile-card">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($data['fullname']); ?>&background=random&color=fff&size=128&bold=true" class="rounded-circle avatar-img mb-3" width="80">
            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($data['fullname']); ?></h5>
            <div class="d-inline-block bg-white/20 px-3 py-1 rounded-pill text-xs backdrop-blur-sm">
                <?php echo htmlspecialchars($data['division']); ?> • <?php echo htmlspecialchars($data['employee_id']); ?>
            </div>
        </div>

        <div class="section-title">Personal Info</div>
        <div class="px-4">
            <form method="POST">
                <?php echo csrfTokenField(); ?>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($data['email']); ?>" readonly disabled>
                    <label>Email Address</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="number" name="phone" class="form-control" value="<?php echo htmlspecialchars($data['phone_number']); ?>" placeholder="08xx">
                    <label>WhatsApp Number</label>
                </div>

                <div class="form-floating mb-1">
                    <textarea name="address" class="form-control" placeholder="Address" style="height: 80px"><?php echo htmlspecialchars($data['address']); ?></textarea>
                    <label>Domisili / Alamat</label>
                </div>

                <button type="submit" name="update_profile" class="btn btn-save">
                    <i class="fa fa-save me-2"></i> Simpan Profil
                </button>
            </form>
        </div>

        <hr class="my-4 border-light">

        <div class="section-title text-danger">Keamanan Akun</div>
        <div class="px-4">
            <form method="POST" id="formPass">
                <?php echo csrfTokenField(); ?>
                
                <div class="form-floating mb-3">
                    <input type="password" name="old_pass" class="form-control" placeholder="***">
                    <label>Password Lama</label>
                </div>

                <div class="input-group mb-3">
                    <div class="form-floating flex-grow-1">
                        <input type="password" name="new_pass" id="newPass" class="form-control" placeholder="***" style="border-radius: 12px 0 0 12px;">
                        <label>Password Baru</label>
                    </div>
                    <span class="input-group-text px-3" onclick="togglePass()">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </span>
                </div>

                <button type="submit" name="change_password" class="btn btn-dark w-100 py-3 rounded-3 fw-bold shadow-sm">
                    <i class="fa fa-lock me-2"></i> Update Password
                </button>
            </form>
        </div>

        <div class="px-4">
            <a href="auth.php?logout=true" class="btn-logout">
                <i class="fa fa-sign-out-alt"></i> Keluar Aplikasi
            </a>
        </div>

    </div>

    <script>
        // SweetAlert Trigger (PHP to JS)
        <?php if(!empty($swal_title)): ?>
        Swal.fire({
            title: '<?php echo $swal_title; ?>',
            text: '<?php echo $swal_text; ?>',
            icon: '<?php echo $swal_icon; ?>',
            confirmButtonColor: '#0f172a',
            confirmButtonText: 'Oke'
        });
        <?php endif; ?>

        // Toggle Password Visibility
        function togglePass() {
            var x = document.getElementById("newPass");
            var icon = document.getElementById("eyeIcon");
            if (x.type === "password") {
                x.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                x.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>
</html>