<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

// --- 1. REGISTER (DAFTAR BARU) ---
if(isset($_POST['register'])) {
    $nama  = $_POST['fullname'];
    $email = $_POST['email'];      
    $role  = $_POST['role'];       
    $div   = $_POST['division']; // Tangkap Divisi
    
    $nik   = $_POST['employee_id'];
    $pass  = md5($_POST['password']);

    // Cek NIK kembar
    $cek = mysqli_query($conn, "SELECT * FROM ess_users WHERE employee_id='$nik'");
    
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('ID Karyawan sudah terdaftar! Silakan Login.'); window.location='landing.php?open=login';</script>";
    } else {
        // Masukkan data lengkap + KUOTA DEFAULT 12 HARI
        $sql = "INSERT INTO ess_users (fullname, email, division, employee_id, password, role, annual_leave_quota) 
                VALUES ('$nama', '$email', '$div', '$nik', '$pass', '$role', 12)";
        
        if(mysqli_query($conn, $sql)) {
            echo "<script>alert('Registrasi Berhasil! Anda dapat kuota cuti 12 hari.'); window.location='landing.php?open=login';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// --- 2. LOGIN ---
if(isset($_POST['login'])) {
    $nik  = $_POST['employee_id'];
    $pass = md5($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM ess_users WHERE employee_id='$nik' AND password='$pass'");
    
    if(mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Simpan Data ke Session
        $_SESSION['ess_user'] = $data['employee_id'];
        $_SESSION['ess_name'] = $data['fullname'];
        $_SESSION['ess_role'] = $data['role'];
        
        // Simpan Divisi ke Session (Penting buat form cuti)
        if(isset($data['division'])) {
            $_SESSION['ess_div'] = $data['division'];
        } else {
            $_SESSION['ess_div'] = "General"; 
        }
        
        // Reset waktu timeout
        $_SESSION['LAST_ACTIVITY'] = time();

        header("Location: index.php");
    } else {
        echo "<script>alert('ID Karyawan atau Password Salah!'); window.location='landing.php?open=login';</script>";
    }
}

// --- 3. LOGOUT ---
if(isset($_GET['logout'])) {
    session_destroy();
    // Balik ke Halaman Utama Web Portofolio (Naik 2 folder)
    header("Location: ../../index.php"); 
}
?>