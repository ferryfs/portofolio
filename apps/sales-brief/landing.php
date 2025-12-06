<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Sales Brief</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .modal-header { background: #28a745; color: white; }
        .btn-taco { background: #28a745; color: white; }
        .btn-taco:hover { background: #218838; color: white; }
    </style>
</head>
<body>

    <div class="text-center">
        <h1 class="text-secondary">SALES BRIEF SYSTEM</h1>
        <p>Silakan ikuti proses login.</p>
    </div>

    <div class="modal fade" id="modalIntro" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selamat Datang di Sales Brief Apps</h5>
                </div>
                <div class="modal-body">
                    <p>Aplikasi ini digunakan untuk memonitoring Draft Sales Brief, Informasi Promo, dan Approval System secara terintegrasi.</p>
                    <p>Pastikan Anda membaca panduan penggunaan sebelum memulai.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-taco" onclick="showModal('modalCheckAccount')">Saya Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCheckAccount" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Akses Pengguna</h5>
                </div>
                <div class="modal-body text-center">
                    <h4>Apakah Anda sudah memiliki akun?</h4>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-secondary me-2" onclick="showModal('modalRegister')">Belum Punya</button>
                    <button class="btn btn-primary" onclick="showModal('modalLogin')">Sudah Punya</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRegister" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Registrasi User Baru</h5>
                    <button type="button" class="btn-close" onclick="showModal('modalCheckAccount')"></button>
                </div>
                <form action="auth.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Divisi</label>
                            <select name="division" class="form-select" required>
                                <option value="">-- Pilih Divisi --</option>
                                <option value="Sales">Sales</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Finance">Finance</option>
                                <option value="IT">IT</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Email Kantor</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="register" class="btn btn-success w-100">Daftar Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLogin" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Login</h5>
                    <button type="button" class="btn-close" onclick="showModal('modalCheckAccount')"></button>
                </div>
                <form action="auth.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="login" class="btn btn-primary w-100">Masuk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var currentModal = null;

        function showModal(modalId) {
            // Tutup modal lama kalo ada
            if (currentModal) {
                var oldModalEl = document.getElementById(currentModal);
                var oldModal = bootstrap.Modal.getInstance(oldModalEl);
                if (oldModal) oldModal.hide();
            }
            
            // Buka modal baru
            var newModalEl = document.getElementById(modalId);
            var newModal = new bootstrap.Modal(newModalEl);
            newModal.show();
            
            currentModal = modalId;
        }

        // --- LOGIC BARU DISINI ---
        window.onload = function() {
            // Cek URL, ada tulisan "?open=login" gak?
            const urlParams = new URLSearchParams(window.location.search);
            const openMode = urlParams.get('open');

            if (openMode === 'login') {
                // Kalau ada, langsung buka Pop-up Login
                showModal('modalLogin');
            } else {
                // Kalau ga ada, buka intro kayak biasa
                showModal('modalIntro');
            }
        };
    </script>
</body>
</html>
</html>