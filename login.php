<?php
// 1. ISOLASI SESSION (PENTING BUAT HOSTING)
// Kita kasih nama khusus biar ga bentrok sama session project lain
session_name("PORTFOLIO_CMS_SESSION");
session_start();

// 2. KONEKSI (Pakai Config PDO yang sudah kita buat sebelumnya)
require_once __DIR__ . '/config/database.php';

// Kalau sudah login, lempar ke dashboard
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: admin.php");
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // 3. CEK USER DENGAN PREPARED STATEMENT (ANTI SQL INJECTION)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. VERIFIKASI PASSWORD (BCRYPT)
        // password_verify otomatis ngecek hash modern, bukan MD5 lagi
        if ($user && password_verify($password, $user['password'])) {
            
            // Login Sukses
            $_SESSION['status'] = "login";
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect
            header("Location: admin.php");
            exit();

        } else {
            $error = "Username atau Password salah!";
        }

    } catch (PDOException $e) {
        $error = "System Error: " . $e->getMessage();
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Administrator</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #00abbb; 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .card-login { 
            width: 100%; 
            max-width: 400px; 
            background: #ffffff; 
            border: 1px solid #0067f7f7; 
            border-radius: 16px; 
            overflow: hidden;
        }
        .form-control {
            background-color: #ffffff;
            border: 1px solid #004eba;
            color: white;
            padding: 12px;
        }
        .form-control:focus {
            background-color: #0f172a;
            border-color: #3b82f6;
            color: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .btn-primary {
            background-color: #3b82f6;
            border: none;
            padding: 12px;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>

    <div class="card card-login shadow-lg">
        <div class="p-4 border-bottom border-secondary border-opacity-25 bg-dark bg-opacity-25 text-center">
            <h4 class="fw-bold m-0">🔐 CMS PANEL</h4>
        </div>
        <div class="p-4">
            
            <?php if($error): ?>
                <div class="alert alert-danger py-2 text-sm text-center border-0 mb-4" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">USERNAME</label>
                    <input type="text" name="username" class="form-control" placeholder="admin" required autocomplete="off">
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold">PASSWORD</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 fw-bold rounded-3 mb-3">LOGIN SYSTEM</button>
                <a href="index.php" class="text-decoration-none text-secondary small d-block text-center hover-white">
                    &larr; Kembali ke Website
                </a>
            </form>
        </div>
    </div>
    <script>
        // Hapus history pas nyampe login page
        // Jadi user gak bisa tekan 'Forward' buat balik ke admin
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>