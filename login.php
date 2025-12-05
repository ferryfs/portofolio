<?php
session_start();
include 'koneksi.php';

// Kalau sudah login, langsung lempar ke admin
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: admin.php");
    exit();
}

// LOGIC LOGIN
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Kita enkripsi password yg diinput user

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $cek   = mysqli_num_rows($query);

    if ($cek > 0) {
        // Login Berhasil
        $_SESSION['username'] = $username;
        $_SESSION['status']   = "login";
        header("Location: admin.php");
    } else {
        // Login Gagal
        echo "<script>alert('Username atau Password Salah!');</script>";
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card-login { width: 100%; max-width: 400px; background: #1e293b; border: 1px solid #334155; border-radius: 12px; }
    </style>
</head>
<body>
    <div class="card card-login p-4 shadow-lg">
        <h3 class="text-center mb-4 fw-bold text-white">🔐 Login Admin</h3>
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukan Username" required>
            </div>
            <div class="mb-4">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukan Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 fw-bold">MASUK</button>
            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Kembali ke Web</a>
        </form>
    </div>
</body>
</html>