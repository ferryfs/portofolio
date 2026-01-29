<?php
// 🔥 SESSION KHUSUS ESS BIAR GAK BENTROK
session_name("ESS_PORTAL_SESSION");
session_start();

$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

// Helper IP Address
function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

// --- 1. REGISTER (DAFTAR BARU - Masih pakai MD5 sementara jika mau konsisten, tapi disarankan ganti hash) ---
if(isset($_POST['register'])) {
    $nama  = $_POST['fullname'];
    $email = $_POST['email'];      
    $role  = $_POST['role'];       
    $div   = $_POST['division'];
    $nik   = $_POST['employee_id'];
    
    // Default Password MD5 (Untuk pendaftaran manual)
    // Kalau mau secure, ganti md5() jadi password_hash()
    $pass  = md5($_POST['password']); 

    $cek = mysqli_query($conn, "SELECT * FROM ess_users WHERE employee_id='$nik'");
    
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('ID Karyawan sudah terdaftar! Silakan Login.'); window.location='landing.php?open=login';</script>";
    } else {
        $sql = "INSERT INTO ess_users (fullname, email, division, employee_id, password, role, annual_leave_quota) 
                VALUES ('$nama', '$email', '$div', '$nik', '$pass', '$role', 12)";
        if(mysqli_query($conn, $sql)) {
            echo "<script>alert('Registrasi Berhasil! Anda dapat kuota cuti 12 hari.'); window.location='landing.php?open=login';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// --- 2. LOGIN (SUPPORT MD5 LAMA & BCRYPT BARU) ---
if(isset($_POST['login'])) {
    $nik  = trim($_POST['employee_id']);
    $pass = trim($_POST['password']);

    // Pakai Prepared Statement (Anti SQL Injection)
    $stmt = $conn->prepare("SELECT * FROM ess_users WHERE employee_id = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $db_pass = $data['password'];
        $login_sukses = false;

        // Cek Password: Bisa MD5 (User Lama) atau Bcrypt (User Baru/Tamu)
        if (password_verify($pass, $db_pass)) {
            $login_sukses = true; // Match Bcrypt
        } elseif (md5($pass) === $db_pass) {
            $login_sukses = true; // Match MD5
        }

        if ($login_sukses) {
            // 🔥 CATAT IP & WAKTU LOGIN
            $ip = getUserIP();
            $uid = $data['id'];
            $conn->query("UPDATE ess_users SET last_ip='$ip', last_login=NOW() WHERE id='$uid'");

            // Set Session
            $_SESSION['ess_user'] = $data['employee_id'];
            $_SESSION['ess_name'] = $data['fullname'];
            $_SESSION['ess_role'] = $data['role'];
            $_SESSION['ess_div']  = $data['division'] ?? "General";
            $_SESSION['LAST_ACTIVITY'] = time();

            header("Location: index.php");
            exit();
        }
    }
    
    echo "<script>alert('ID Karyawan atau Password Salah!'); window.location='landing.php?open=login';</script>";
}

// --- 3. LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php"); 
}
?>