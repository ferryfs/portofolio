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
            
            <div class="text-center mt-5">
                <a href="../../index.php" class="text-decoration-none text-muted small">
                    <i class="fa fa-arrow-left me-1"></i> Kembali ke Portofolio
                </a>
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
                        <div class="mb-3"><label class="small text-muted fw-bold">Nama Lengkap</label><input type="text" name="fullname" class="form-control" required></div>
                        <div class="mb-3"><label class="small text-muted fw-bold">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="small text-muted fw-bold">ID Karyawan</label><input type="text" name="employee_id" class="form-control" required></div>
                        <div class="mb-3"><label class="small text-muted fw-bold">Jabatan</label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Pilih --</option><option value="Staff">Staff</option><option value="Supervisor">SPV</option><option value="Manager">Manager</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="small text-muted fw-bold">Password</label><input type="password" name="password" class="form-control" required></div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="register" class="btn btn-success w-100 rounded-pill fw-bold">Daftar</button>
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
                        
                        <div class="alert alert-info p-2 mb-3 text-center" style="font-size: 0.8rem; cursor: pointer;" onclick="fillTamu()">
                            <i class="fa fa-magic me-1"></i> Klik disini untuk <b>Akun Tamu</b>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">ID Karyawan</label>
                            <input type="text" id="log_nik" name="employee_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted">Password</label>
                            <input type="password" id="log_pass" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="login" id="btnSubmitLogin" class="btn btn-primary w-100 rounded-pill">Masuk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FUNGSI AUTO FILL TAMU
        function fillTamu() {
            // Isi form dengan akun tamu (sesuai database)
            document.getElementById('log_nik').value = 't4mu';
            document.getElementById('log_pass').value = 'Tamu123';
            
            // Visual Feedback & Auto Submit
            let btn = document.getElementById('btnSubmitLogin');
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Masuk...';
            setTimeout(() => { btn.click(); }, 500);
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('open') === 'login') {
                new bootstrap.Modal(document.getElementById('modalLogin')).show();
            }
            if (urlParams.get('msg') === 'timeout') {
                alert("Sesi habis. Silakan login kembali.");
                new bootstrap.Modal(document.getElementById('modalLogin')).show();
            }
        };
        
        function showModal(id) {
            new bootstrap.Modal(document.getElementById(id)).show();
        }
    </script>
        <script>
        // Biar history browser bersih, jadi user gak bisa tekan Forward balik ke dalam
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>