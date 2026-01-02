<?php
session_start();

// 1. KOSONGKAN SEMUA VARIABEL SESSION
$_SESSION = [];

// 2. BUNUH COOKIE SESSION (PENTING BUAT SECURITY)
// Ini biar browser dipaksa lupa sama ID session lama
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. HANCURKAN SESSION DI SERVER
session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f1f5f9; 
            height: 100vh; display: flex; align-items: center; justify-content: center; 
            font-family: 'Segoe UI', sans-serif;
        }
        .logout-card {
            text-center; background: white; padding: 40px; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 350px;
        }
        .spinner-border { width: 3rem; height: 3rem; color: #2563eb; }
    </style>
</head>
<body>

    <div class="logout-card">
        <div class="spinner-border mb-4" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="fw-bold text-dark">Logging Out...</h5>
        <p class="text-muted small">Membersihkan sesi & cache keamanan.</p>
    </div>

    <script>
        // Redirect otomatis setelah 1.5 detik (Biar user liat prosesnya)
        setTimeout(function() {
            window.location.href = '.../.../login.php?msg=logout'; // Balik ke Login Page
        }, 1500);
    </script>

</body>
</html>