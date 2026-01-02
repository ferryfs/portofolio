<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMS Login - Enterprise Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f1f5f9; /* Slate 100 */
            height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white; padding: 40px; border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            width: 100%; max-width: 400px;
        }
        .brand-logo {
            font-size: 1.8rem; font-weight: 800; color: #0f172a;
            margin-bottom: 5px; letter-spacing: -0.5px;
        }
        .btn-login {
            background-color: #2563eb; border: none; padding: 12px;
            font-weight: 700; letter-spacing: 0.5px; transition: 0.2s;
        }
        .btn-login:hover { background-color: #1d4ed8; transform: translateY(-2px); }
        .btn-demo {
            background-color: #eff6ff; color: #2563eb; border: 1px dashed #2563eb;
            font-size: 0.8rem; font-weight: 600;
        }
        .btn-demo:hover { background-color: #dbeafe; }
        .form-control:focus { box-shadow: none; border-color: #2563eb; }
        .input-group-text { background: #fff; border-right: none; }
        .form-control { border-left: none; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-5">
            <div class="brand-logo">
                <i class="fa fa-cubes text-primary me-2"></i>WMS PRO
            </div>
            <p class="text-muted small fw-bold">Warehouse Management System</p>
        </div>

        <?php if(isset($_GET['err'])): ?>
            <div class="alert alert-danger py-2 small border-0 bg-danger bg-opacity-10 text-danger mb-4">
                <i class="fa fa-exclamation-circle me-1"></i> Username / Password Salah!
            </div>
        <?php endif; ?>

        <div class="alert alert-primary d-flex align-items-center mb-4 p-2 rounded-3 border-0 bg-primary bg-opacity-10" role="alert">
            <div class="small text-primary ps-2">
                <strong>Demo:</strong> <code>adminwms</code> | <code>admin123</code>
            </div>
            <button onclick="fillDemo()" class="btn btn-sm btn-demo ms-auto shadow-sm">Auto Fill</button>
        </div>

        <form action="auth.php" method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold" style="font-size:0.7rem; letter-spacing:1px;">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text text-muted"><i class="fa fa-user"></i></span>
                    <input type="text" id="user" name="username" class="form-control" placeholder="Enter ID" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-bold" style="font-size:0.7rem; letter-spacing:1px;">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text text-muted"><i class="fa fa-lock"></i></span>
                    <input type="password" id="pass" name="password" class="form-control" placeholder="••••••" required>
                </div>
            </div>
            <button type="submit" name="btn_login" class="btn btn-primary w-100 btn-login rounded-3 shadow-lg">
                SECURE LOGIN <i class="fa fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted" style="font-size: 0.7rem;">
                &copy; 2024 PT. Maju Mundur Logistics
            </small>
        </div>
    </div>

    <script>
        function fillDemo() {
            document.getElementById('user').value = 'adminwms';
            document.getElementById('pass').value = 'admin123';
        }
    </script>

</body>
</html>