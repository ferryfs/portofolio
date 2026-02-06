<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/security.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TMS Login | Logistics Command Center</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --tms-primary: #f59e0b; --tms-dark: #111827; --glass-bg: rgba(17, 24, 39, 0.85); }
        body { font-family: 'Outfit', sans-serif; background: #1f2937; height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .login-card { background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; color: white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .brand-title { font-weight: 800; font-size: 1.8rem; letter-spacing: 1px; margin-bottom: 5px; color: white; }
        .text-accent { color: var(--tms-primary); }
        .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); color: white; padding: 12px; }
        .form-control:focus { background: rgba(255, 255, 255, 0.1); border-color: var(--tms-primary); color: white; box-shadow: none; }
        .btn-login { background: var(--tms-primary); color: #000; font-weight: 700; padding: 12px; border: none; transition: 0.3s; }
        .btn-login:hover { background: #d97706; color: white; transform: translateY(-2px); }
        .demo-badge { background: rgba(245, 158, 11, 0.1); border: 1px dashed var(--tms-primary); color: var(--tms-primary); font-size: 0.75rem; padding: 8px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .demo-badge:hover { background: rgba(245, 158, 11, 0.2); }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="mb-3"><i class="fa fa-map-location-dot fa-3x text-accent"></i></div>
            <h1 class="brand-title">Logi<span class="text-accent">Track</span> TMS</h1>
            <p class="text-white-50 small">Transport Management System</p>
        </div>

        <?php if(isset($_GET['err'])): ?>
            <div class="alert alert-danger bg-danger bg-opacity-25 border-0 text-white small py-2 mb-4">
                <i class="fa fa-circle-exclamation me-2"></i> Akses Ditolak! Cek username/password.
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 demo-badge" onclick="fillTamu()" style="border-color: #34d399; color: #34d399; background: rgba(52, 211, 153, 0.1);">
            <div class="d-flex align-items-center gap-2">
                <i class="fa fa-user-secret"></i>
                <div><div class="fw-bold">LOGIN TAMU</div><div class="font-monospace" style="font-size: 0.7rem;">Akses Cepat (Demo)</div></div>
            </div>
            <i class="fa fa-rocket"></i>
        </div>

        <form action="auth.php" method="POST">
            <?php echo csrfTokenField(); ?>

            <div class="mb-3">
                <label class="small fw-bold text-white-50 mb-1">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary text-white-50"><i class="fa fa-user"></i></span>
                    <input type="text" id="user" name="username" class="form-control" placeholder="ID Pengguna" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="small fw-bold text-white-50 mb-1">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary text-white-50"><i class="fa fa-lock"></i></span>
                    <input type="password" id="pass" name="password" class="form-control" placeholder="••••••" required>
                </div>
            </div>

            <button type="submit" name="btn_login" id="btnLogin" class="btn btn-primary w-100 btn-login rounded-3">
                LOGIN DASHBOARD <i class="fa fa-truck-fast ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary">
            <a href="../../index.php" class="text-decoration-none text-white-50 small">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Portofolio
            </a>
        </div>
    </div>

    <script>
        function fillTamu() {
            document.getElementById('user').value = 't4mu';
            document.getElementById('pass').value = 'Tamu123';
            let btn = document.getElementById('btnLogin');
            btn.innerHTML = 'Mempersiapkan Dashboard...';
            setTimeout(() => { btn.click(); }, 500);
        }
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>