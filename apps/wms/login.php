<?php
session_name("WMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/security.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart WMS Enterprise</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --brand-dark: #002B5B; --brand-primary: #0056D2; --brand-accent: #00C2FF; }
        body { background: linear-gradient(135deg, var(--brand-dark) 0%, #004e92 100%); height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; overflow: hidden; position: relative; }
        body::before { content: ''; position: absolute; width: 100%; height: 100%; background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.05; pointer-events: none; }
        .login-card { background: rgba(255, 255, 255, 0.98); padding: 45px; border-radius: 24px; box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.5); width: 100%; max-width: 420px; position: relative; z-index: 10; border-top: 5px solid var(--brand-accent); }
        .brand-header { text-align: center; margin-bottom: 35px; }
        .brand-title { font-size: 2rem; font-weight: 800; color: var(--brand-dark); margin: 0; line-height: 1.1; letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 0.85rem; font-weight: 600; color: var(--brand-primary); text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
        .form-control { padding: 12px 15px; border-radius: 12px; border: 2px solid #e2e8f0; font-weight: 500; transition: all 0.3s; }
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(0, 86, 210, 0.1); }
        .input-group-text { background: #f8fafc; border: 2px solid #e2e8f0; border-right: none; border-radius: 12px 0 0 12px; color: #94a3b8; }
        .input-group .form-control { border-left: none; border-radius: 0 12px 12px 0; }
        .btn-login { background: linear-gradient(to right, var(--brand-primary), #0046b0); border: none; padding: 14px; border-radius: 12px; font-weight: 700; font-size: 1rem; letter-spacing: 0.5px; transition: 0.3s; margin-top: 10px; box-shadow: 0 10px 20px -5px rgba(0, 86, 210, 0.3); }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(0, 86, 210, 0.4); background: linear-gradient(to right, #0046b0, var(--brand-primary)); }
        .btn-demo { background: #e0f2fe; color: #0284c7; border: 1px dashed #0284c7; font-weight: 700; font-size: 0.75rem; padding: 8px 15px; border-radius: 50px; cursor: pointer; transition: 0.2s; }
        .btn-demo:hover { background: #bae6fd; transform: scale(1.02); }
    </style>
</head>
<body>

    <div class="login-card">
        
        <div class="brand-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-4 p-3 mb-3 text-primary shadow-sm">
                <i class="fa fa-warehouse fa-2x"></i>
            </div>
            <h1 class="brand-title">Smart WMS</h1>
            <div class="brand-subtitle">Warehouse Management System</div>
        </div>

        <?php if(isset($_GET['err'])): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger d-flex align-items-center p-3 rounded-3 mb-4">
                <i class="fa fa-circle-exclamation me-2"></i>
                <div class="small fw-bold">Username / Password Salah!</div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mb-4">
            <button onclick="fillTamu()" class="btn btn-demo w-100">
                <i class="fa fa-user-secret me-1"></i> Login Tamu
            </button>
            <button onclick="fillAdmin()" class="btn btn-demo w-100" style="background: #f0fdf4; color: #16a34a; border-color: #16a34a;">
                <i class="fa fa-user-tie me-1"></i> Admin Demo
            </button>
        </div>

        <form action="auth.php" method="POST">
            <?php echo csrfTokenField(); ?>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">USERNAME ID</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" id="user" name="username" class="form-control" placeholder="Enter ID" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock-open"></i></span>
                    <input type="password" id="pass" name="password" class="form-control" placeholder="••••••" required>
                </div>
            </div>

            <button type="submit" name="btn_login" id="btnLogin" class="btn btn-primary w-100 btn-login text-white">
                Access Dashboard <i class="fa fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="../../index.php" class="text-decoration-none text-muted small">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Portofolio
            </a>
        </div>
    </div>

    <script>
        function fillTamu() {
            document.getElementById('user').value = 't4mu';
            document.getElementById('pass').value = 'Tamu123';
            autoSubmit();
        }
        function fillAdmin() {
            document.getElementById('user').value = 'adminwms';
            document.getElementById('pass').value = 'admin123';
        }
        function autoSubmit() {
            let btn = document.getElementById('btnLogin');
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Memuat...';
            setTimeout(() => { btn.click(); }, 500);
        }
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>