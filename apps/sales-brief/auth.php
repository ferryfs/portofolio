<?php
// 🔥 SESSION KHUSUS SALES BRIEF
session_name("SB_APP_SESSION");
session_start();

$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

// Helper IP
function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

// --- REGISTER ---
if(isset($_POST['register'])) {
    $nama = $_POST['fullname'];
    $div  = $_POST['division'];
    $email= $_POST['email'];
    $user = $_POST['username'];
    
    // Default pakai MD5 dulu kalau register manual (atau ganti password_hash kalau mau full secure)
    $pass = md5($_POST['password']); 

    $cek = mysqli_query($conn, "SELECT * FROM sales_brief_users WHERE username='$user'");
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah dipakai!'); window.location='landing.php';</script>";
    } else {
        $sql = "INSERT INTO sales_brief_users (fullname, division, email, username, password) 
                VALUES ('$nama', '$div', '$email', '$user', '$pass')";
        if(mysqli_query($conn, $sql)) {
            echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='landing.php?open=login';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// --- LOGIN (SUPPORT BCRYPT & MD5) ---
if(isset($_POST['login'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // Pakai Prepared Statement
    $stmt = $conn->prepare("SELECT * FROM sales_brief_users WHERE username=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $db_pass = $data['password'];
        $login_sukses = false;

        // Cek Password (Prioritas Bcrypt buat Tamu, Fallback MD5 buat user lama)
        if(password_verify($pass, $db_pass)) {
            $login_sukses = true;
        } elseif (md5($pass) === $db_pass) {
            $login_sukses = true;
        }

        if($login_sukses) {
            // 🔥 CATAT IP
            $ip = getUserIP();
            $uid = $data['id'];
            $conn->query("UPDATE sales_brief_users SET last_ip='$ip', last_login=NOW() WHERE id='$uid'");

            // SET SESSION
            $_SESSION['sb_user'] = $data['username'];
            $_SESSION['sb_name'] = $data['fullname'];
            $_SESSION['sb_div']  = $data['division'];
            
            header("Location: index.php");
            exit();
        }
    }
    
    echo "<script>alert('Username atau Password Salah!'); window.location='landing.php?open=login';</script>";
}

// --- LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); 
}
?>