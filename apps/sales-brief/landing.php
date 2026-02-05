<?php
// apps/sales-brief/landing.php
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/security.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Sales Brief</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .modal-header { background: #28a745; color: white; }
        .btn-taco { background: #28a745; color: white; }
        .btn-taco:hover { background: #218838; color: white; }
        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

    <div class="text-center">
        <h1 class="text-secondary fw-bold mb-2"><i class="fa fa-chart-line text-success"></i> SALES BRIEF</h1>
        <p class="text-muted">Sistem Manajemen Promo & Budgeting</p>
        <div class="mt-4">
             <a href="../../index.php" class="text-decoration-none text-muted small border px-3 py-2 rounded-pill bg-white shadow-sm">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Portofolio
            </a>
        </div>
    </div>

    <div class="modal fade" id="modalIntro" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selamat Datang</h5>
                </div>
                <div class="modal-body">
                    <p>Aplikasi ini digunakan untuk memonitoring Draft Sales Brief, Informasi Promo, dan Approval System secara terintegrasi.</p>
                    <p class="mb-0 text-muted small">Silakan login untuk melanjutkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-taco" onclick="showModal('modalCheckAccount')">Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCheckAccount" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Akses Pengguna</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <h5 class="mb-4">Apakah Anda sudah memiliki akun?</h5>
                    <button class="btn btn-outline-secondary me-2 px-4" onclick="showModal('modalRegister')">Belum Punya</button>
                    <button class="btn btn-primary px-4 fw-bold shadow-sm" onclick="showModal('modalLogin')">Sudah Punya</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRegister" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">Registrasi User Baru</h5>
                    <button type="button" class="btn-close" onclick="showModal('modalCheckAccount')"></button>
                </div>
                <form action="auth.php" method="POST">
                    <?php echo csrfTokenField(); ?>
                    
                    <div class="modal-body">
                        <div class="mb-3"><label>Nama Lengkap</label><input type="text" name="fullname" class="form-control" required></div>
                        <div class="mb-3"><label>Divisi</label>
                            <select name="division" class="form-select" required>
                                <option value="">-- Pilih Divisi --</option>
                                <option value="Sales">Sales</option><option value="Marketing">Marketing</option><option value="Finance">Finance</option><option value="IT">IT</option>
                            </select>
                        </div>
                        <div class="mb-3"><label>Email Kantor</label><input type="email" name="email" class="form-control" required></div>
                        <hr>
                        <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="register" class="btn btn-success w-100 fw-bold">Daftar Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLogin" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Login</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="showModal('modalCheckAccount')"></button>
                </div>
                <form action="auth.php" method="POST">
                    <?php echo csrfTokenField(); ?>

                    <div class="modal-body">
                        <div class="alert alert-info p-2 mb-3 text-center cursor-pointer small" onclick="fillTamu()">
                            <i class="fa fa-magic me-1"></i> Klik untuk <b>Akun Tamu</b>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Username</label>
                            <input type="text" id="log_user" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Password</label>
                            <input type="password" id="log_pass" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="login" id="btnLogin" class="btn btn-primary w-100 rounded-3 fw-bold">MASUK</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var currentModal = null;

        function showModal(modalId) {
            if (currentModal) {
                var oldEl = document.getElementById(currentModal);
                var oldModal = bootstrap.Modal.getInstance(oldEl);
                if (oldModal) oldModal.hide();
            }
            var newEl = document.getElementById(modalId);
            var newModal = new bootstrap.Modal(newEl);
            newModal.show();
            currentModal = modalId;
        }

        function fillTamu() {
            document.getElementById('log_user').value = 't4mu';    
            document.getElementById('log_pass').value = 'Tamu123'; 
            let btn = document.getElementById('btnLogin');
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';
            setTimeout(() => { btn.click(); }, 800);
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'login') { showModal('modalLogin'); } 
            else if (urlParams.get('tab') === 'register') { showModal('modalRegister'); }
            else { showModal('modalIntro'); }
        };
    </script>
</body>
</html>