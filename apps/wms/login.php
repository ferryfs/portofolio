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
        /* BRAND COLORS FROM YOUR IMAGE */
        :root {
            --brand-dark: #002B5B; /* Dark Blue Background */
            --brand-primary: #0056D2; /* Bright Blue Accent */
            --brand-accent: #00C2FF; /* Cyan Highlight */
            --text-dark: #1e293b;
        }

        body {
            /* Gradient Background matching the "Smart WMS" vibe */
            background: linear-gradient(135deg, var(--brand-dark) 0%, #004e92 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* Background Pattern (Optional Tech Vibe) */
        body::before {
            content: '';
            position: absolute;
            width: 100%; height: 100%;
            background-image: radial-gradient(#ffffff 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.05;
            pointer-events: none;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 45px;
            border-radius: 24px;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.5); /* Deep Shadow */
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 10;
            border-top: 5px solid var(--brand-accent); /* Accent Border */
        }

        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-dark);
            margin: 0;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        
        .brand-subtitle {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--brand-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            font-weight: 500;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(0, 86, 210, 0.1);
        }

        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #94a3b8;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .btn-login {
            background: linear-gradient(to right, var(--brand-primary), #0046b0);
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 20px -5px rgba(0, 86, 210, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(0, 86, 210, 0.4);
            background: linear-gradient(to right, #0046b0, var(--brand-primary));
        }

        .btn-demo {
            background: #eff6ff;
            color: var(--brand-primary);
            border: 1px dashed var(--brand-primary);
            font-weight: 700;
            font-size: 0.75rem;
            padding: 5px 12px;
            border-radius: 50px;
        }
        .btn-demo:hover { background: #dbeafe; }

        .copyright-text {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
        }
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

        <div class="bg-primary bg-opacity-10 p-3 rounded-3 mb-4 d-flex align-items-center justify-content-between">
            <div class="small text-primary">
                <div class="fw-bold text-uppercase" style="font-size: 0.65rem; opacity: 0.7;">Demo Credentials</div>
                <div class="fw-bold font-monospace mt-1">adminwms | admin123</div>
            </div>
            <button onclick="fillDemo()" class="btn btn-demo shadow-sm">
                Auto Fill <i class="fa fa-magic ms-1"></i>
            </button>
        </div>

        <form action="auth.php" method="POST">
            <div class="mb-3">
                <label class="form-label">USERNAME ID</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" id="user" name="username" class="form-control" placeholder="Enter ID" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock-open"></i></span>
                    <input type="password" id="pass" name="password" class="form-control" placeholder="••••••" required>
                </div>
            </div>

            <button type="submit" name="btn_login" class="btn btn-primary w-100 btn-login">
                Access Dashboard <i class="fa fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <div class="copyright-text">
            &copy; <?php echo date('Y'); ?> <strong>Ferry Fernando</strong>.<br>
            All rights reserved.
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