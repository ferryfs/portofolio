<?php
session_name("HRIS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/security.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS Login - Corporate Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #e2e8f0; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); width: 100%; max-width: 420px; }
        .brand-logo { font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-bottom: 5px; letter-spacing: -1px; }
        .btn-login { background-color: #0f172a; border: none; padding: 12px; font-weight: 600; letter-spacing: 1px; transition: 0.3s; }
        .btn-login:hover { background-color: #334155; }
        .btn-demo { background-color: #e0f2fe; color: #0284c7; border: 1px dashed #0284c7; font-size: 0.85rem; font-weight: 600; }
        .btn-demo:hover { background-color: #bae6fd; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-5">
            <div class="brand-logo"><i class="fa fa-layer-group text-primary me-2"></i>HRIS CORE</div>
            <p class="text-muted small">Centralized Human Resource System</p>
        </div>

        <div class="alert alert-primary d-flex align-items-center mb-4" role="alert" style="font-size: 0.85rem;">
            <i class="fa fa-info-circle fa-lg me-3"></i>
            <div>
                <strong>Akses Demo (Tamu):</strong><br>
                User: <code>guest</code> | Pass: <code>tamu123</code>
            </div>
            <button onclick="fillDemo()" class="btn btn-sm btn-demo ms-auto">Auto Fill</button>
        </div>

        <form action="auth.php" method="POST">
            <?php echo csrfTokenField(); ?>

            <div class="mb-3">
                <label class="form-label text-secondary fw-bold small">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa fa-user text-muted"></i></span>
                    <input type="text" id="user" name="username" class="form-control bg-white border-start-0" placeholder="Username" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-bold small">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa fa-lock text-muted"></i></span>
                    <input type="password" id="pass" name="password" class="form-control bg-white border-start-0" placeholder="Password" required>
                </div>
            </div>
            <button type="submit" name="login_hr" class="btn btn-primary w-100 btn-login rounded-3">MASUK DASHBOARD</button>
        </form>
        
        <div class="text-center mt-4">
            <a href="../../index.php" class="text-decoration-none text-muted small">
                 <i class="fa fa-arrow-left me-1"></i> Kembali ke Web Utama
            </a>
        </div>
    </div>

    <script>
        function fillDemo() {
            document.getElementById('user').value = 'guest';
            document.getElementById('pass').value = 'tamu123';
        }
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>