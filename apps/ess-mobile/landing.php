<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESS Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #eef2f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .mobile-frame { width: 100%; max-width: 400px; height: 100vh; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; overflow: hidden; display: flex; flex-direction: column; }
        .login-header { background: linear-gradient(135deg, #0d6efd, #0dcaf0); padding: 50px 30px; color: white; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; text-align: center; }
        .login-body { padding: 30px; flex-grow: 1; }
    </style>
</head>
<body>

    <div class="mobile-frame">
        <div class="login-header">
            <i class="fa-solid fa-fingerprint fa-4x mb-3"></i>
            <h2 class="fw-bold">ESS Portal</h2>
            <p class="mb-0 opacity-75">Employee Self Service</p>
        </div>
        
        <div class="login-body d-flex flex-column justify-content-center">
            <div class="d-grid gap-3">
                <button class="btn btn-primary py-3 rounded-pill fw-bold shadow-sm" onclick="showModal('modalLogin')">
                    MASUK (LOGIN)
                </button>
                <button class="btn btn-outline-primary py-3 rounded-pill fw-bold" onclick="showModal('modalRegister')">
                    DAFTAR AKUN BARU
                </button>
            </div>
            <div class="text-center mt-4">
                <small class="text-muted">Aplikasi Absensi & HRIS</small>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRegister" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold">Pendaftaran Karyawan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="auth.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Nama Lengkap</label>
                            <input type="text" name="fullname" class="form-control" placeholder="Nama Sesuai KTP" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Email Kantor/Pribadi</label>
                            <input type="email" name="email" class="form-control" placeholder="contoh@gmail.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">ID Karyawan (NIK)</label>
                            <input type="text" name="employee_id" class="form-control" placeholder="Contoh: 2024001" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Jabatan (Role)</label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <option value="Staff">Staff / Admin</option>
                                <option value="Supervisor">Supervisor (SPV)</option>
                                <option value="Manager">Manager / Asmen</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Buat Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="register" class="btn btn-success w-100 rounded-pill fw-bold">Daftar Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLogin" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold">Login Karyawan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="auth.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="small text-muted">ID Karyawan</label>
                            <input type="text" name="employee_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="login" class="btn btn-primary w-100 rounded-pill">Masuk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // 1. Cek kalau habis Daftar
            if (urlParams.get('open') === 'login') {
                var modal = new bootstrap.Modal(document.getElementById('modalLogin'));
                modal.show();
            }
            
            // 2. Cek kalau habis Kena Auto Logout (Timeout)
            if (urlParams.get('msg') === 'timeout') {
                alert("Sesi Anda telah habis karena tidak aktif selama 1 menit. Silakan login kembali.");
                var modal = new bootstrap.Modal(document.getElementById('modalLogin'));
                modal.show();
            }
        };
        
        function showModal(id) {
            var el = document.getElementById(id);
            if(el) {
                var myModal = new bootstrap.Modal(el);
                myModal.show();
            } else {
                console.error("Modal not found: " + id);
            }
        }
    </script>
</body>
</html>