<?php
// apps/wms/logout.php
session_name("WMS_APP_SESSION");
session_start();

// Hapus semua session WMS
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f1f5f9; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>
    <div class="bg-white p-5 rounded-4 shadow text-center" style="width: 350px;">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5 class="fw-bold text-dark">Logging Out...</h5>
        <p class="text-muted small mb-0">Membersihkan sesi aman WMS.</p>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php'; 
        }, 1500);
    </script>
</body>
</html>