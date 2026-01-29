<?php
// 🔥 SESUAIKAN NAMA SESSION
session_name("ESS_PORTAL_SESSION");
session_start();

// SET TIMEZONE
date_default_timezone_set('Asia/Jakarta');

// KONEKSI DATABASE
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// CEK LOGIN
if(!isset($_SESSION['ess_user'])) {
    header("Location: landing.php"); // Atau login.php tergantung nama file
    exit();
}

// LOGIC TIMEOUT (1 MENIT)
$time_limit = 60;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $time_limit)) {
    session_unset(); session_destroy(); 
    header("Location: landing.php?msg=timeout"); 
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// AMBIL DATA USER
$nama_user = $_SESSION['ess_name'];
$role_user = $_SESSION['ess_role'];
$nik_user  = $_SESSION['ess_user'];

// ... (SISA KODINGAN DI BAWAH SAMA PERSIS DENGAN PUNYA BOS) ...
// ... Query Sisa Cuti, Absen, Libur, HTML Tampilan, dll ...
// ... Pastikan PHP tag penutup ada di akhir file ...

// 6. QUERY SISA KUOTA (Buat Badge Merah)
$q_user = mysqli_query($conn, "SELECT annual_leave_quota FROM ess_users WHERE employee_id='$nik_user'");
$d_user = mysqli_fetch_assoc($q_user);
$sisa_cuti = $d_user['annual_leave_quota'];

// --- 7. LOGIC CEK STATUS ABSEN HARI INI ---
$today_date = date('Y-m-d');
$cek_absen = mysqli_query($conn, "SELECT * FROM ess_attendance WHERE employee_id='$nik_user' AND date_log='$today_date'");
$data_absen = mysqli_fetch_assoc($cek_absen);

$status_absen = 'BELUM';
if ($data_absen) {
    if ($data_absen['check_out_time'] == NULL) {
        $status_absen = 'SUDAH_MASUK'; 
    } else {
        $status_absen = 'SELESAI'; 
    }
}

// --- 8. LOGIC CEK HARI LIBUR ---
$day_num = date('N'); 
$is_holiday = false;
$holiday_reason = "";

if ($day_num >= 6) { 
    $is_holiday = true; 
    $holiday_reason = "Hari Libur (Weekend)"; 
}

$q_libur = mysqli_query($conn, "SELECT description FROM ess_holidays WHERE holiday_date='$today_date'");
if (mysqli_num_rows($q_libur) > 0) { 
    $d_libur = mysqli_fetch_assoc($q_libur); 
    $is_holiday = true; 
    $holiday_reason = $d_libur['description']; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard ESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS KHUSUS MOBILE */
        body { background-color: #eef2f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .mobile-frame { width: 100%; max-width: 400px; height: 100vh; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; overflow-y: auto; display: flex; flex-direction: column; }
        
        .app-header { background: linear-gradient(135deg, #0d6efd, #0dcaf0); padding: 30px 20px 60px 20px; color: white; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; flex-shrink: 0; }
        
        .main-card { background: white; margin: -40px 20px 20px 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); padding: 20px; text-align: center; position: relative; z-index: 10; }
        
        /* Tombol Check In */
        .btn-checkin { width: 130px; height: 130px; border-radius: 50%; background: linear-gradient(135deg, #198754, #20c997); color: white; border: 5px solid rgba(255,255,255,0.3); font-weight: bold; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 10px 20px rgba(25, 135, 84, 0.4); margin: 0 auto; cursor: pointer; transition: transform 0.2s; }
        .btn-checkin:active { transform: scale(0.95); }
        
        /* Tombol Pulang (Merah) */
        .btn-checkout { background: linear-gradient(135deg, #dc3545, #fd7e14); box-shadow: 0 10px 20px rgba(220, 53, 69, 0.4); }
        
        /* Tombol Disabled (Abu) */
        .btn-done { background: #6c757d; cursor: default; box-shadow: none; }

        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; padding: 0 20px; margin-top: 20px; }
        .menu-item { text-align: center; font-size: 0.75rem; color: #64748b; cursor: pointer; text-decoration: none; display: block; }
        .menu-icon { width: 50px; height: 50px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #0d6efd; margin: 0 auto 5px auto; transition: 0.3s; position: relative; }
        .menu-item:hover .menu-icon { background: #e0e7ff; color: #0043a8; }

        .bottom-nav { position: sticky; bottom: 0; background: white; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-around; padding: 15px 0; z-index: 99; margin-top: auto; }
        .nav-item-mobile { text-align: center; color: #94a3b8; font-size: 1.2rem; cursor: pointer; text-decoration: none; }
        .nav-text { display: block; font-size: 0.7rem; margin-top: 2px; }
        
        .badge-role { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.5); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>

    <div class="mobile-frame">
        
        <div class="app-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="opacity-75">Selamat Pagi,</small>
                    <h4 class="fw-bold mb-1"><?php echo $nama_user; ?></h4>
                    <span class="badge-role">
                        <i class="fa fa-id-badge me-1"></i> <?php echo $role_user; ?>
                    </span>
                </div>
                <div class="bg-white text-primary rounded-circle p-2 shadow-sm">
                    <i class="fa fa-bell"></i>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="row text-center">
                <div class="col-4 border-end">
                    <h5 class="fw-bold text-success mb-0">20</h5>
                    <small class="text-muted" style="font-size: 0.7rem;">Hadir</small>
                </div>
                <div class="col-4 border-end">
                    <h5 class="fw-bold text-warning mb-0">1</h5>
                    <small class="text-muted" style="font-size: 0.7rem;">Telat</small>
                </div>
                <div class="col-4">
                    <h5 class="fw-bold text-danger mb-0">0</h5>
                    <small class="text-muted" style="font-size: 0.7rem;">Alpha</small>
                </div>
            </div>
        </div>

        <div class="text-center py-2">
            <p class="fw-bold text-secondary mb-3">
                <i class="fa fa-calendar-alt me-1"></i> <?php echo date("d M Y"); ?> <br>
                <i class="fa fa-clock me-1"></i> <span style="font-size: 1.2rem; color: #0d6efd;"><?php echo date("H:i"); ?> WIB</span>
            </p>

            <?php if ($is_holiday) { ?>
                <div class="btn-checkin btn-done" onclick="alert('GAGAL: <?php echo $holiday_reason; ?>. Tidak dapat melakukan absen.')">
                    <i class="fa fa-ban fa-2x mb-2"></i><span>LIBUR</span>
                </div>
                <small class="text-danger fw-bold d-block mt-3"><?php echo $holiday_reason; ?></small>

            <?php } elseif ($status_absen == 'BELUM') { ?>
                <div class="btn-checkin" onclick="openModal('modalCheckIn')">
                    <i class="fa fa-fingerprint fa-2x mb-2"></i><span>ABSEN<br>MASUK</span>
                </div>
                <small class="text-muted d-block mt-3">Lokasi: Terdeteksi (GPS)</small>

            <?php } elseif ($status_absen == 'SUDAH_MASUK') { ?>
                <div class="btn-checkin btn-checkout" onclick="openModal('modalCheckOut')">
                    <i class="fa fa-sign-out-alt fa-2x mb-2"></i><span>ABSEN<br>PULANG</span>
                </div>
                <small class="text-success fw-bold d-block mt-3">Masuk: <?php echo substr($data_absen['check_in_time'], 11, 5); ?></small>

            <?php } else { ?>
                <div class="btn-checkin btn-done">
                    <i class="fa fa-check-circle fa-2x mb-2"></i><span>SUDAH<br>PULANG</span>
                </div>
                <small class="text-success fw-bold d-block mt-3">Pulang: <?php echo substr($data_absen['check_out_time'], 11, 5); ?></small>
            <?php } ?>
        </div>

        <h6 class="fw-bold px-4 mt-4 mb-3">Menu Karyawan</h6>
        <div class="menu-grid">
            <a href="menu_cuti.php" class="menu-item">
                <div class="menu-icon text-warning">
                    <i class="fa fa-calendar-minus"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; left: 85%!important;">
                        <?php echo $sisa_cuti; ?>
                    </span>
                </div>
                <span class="text-dark">Cuti/Izin</span>
            </a>

            <a href="menu_history.php" class="menu-item">
                <div class="menu-icon text-secondary"><i class="fa fa-history"></i></div>
                <span class="text-dark">Riwayat</span>
            </a>

            <div class="menu-item" onclick="alert('Fitur Sakit sedang dikembangkan')">
                <div class="menu-icon text-info"><i class="fa fa-file-medical"></i></div><span class="text-dark">Sakit</span>
            </div>
            <div class="menu-item" onclick="alert('Fitur Lembur sedang dikembangkan')">
                <div class="menu-icon text-danger"><i class="fa fa-clock"></i></div><span class="text-dark">Lembur</span>
            </div>
            <div class="menu-item" onclick="alert('Fitur Slip Gaji sedang dikembangkan')">
                <div class="menu-icon text-success"><i class="fa fa-file-invoice-dollar"></i></div><span class="text-dark">Slip Gaji</span>
            </div>
            <div class="menu-item"><div class="menu-icon text-primary"><i class="fa fa-tasks"></i></div><span class="text-dark">Tugas</span></div>
            
            <a href="menu_team.php" class="menu-item">
                <div class="menu-icon text-purple" style="color: purple;"><i class="fa fa-users"></i></div>
                <span class="text-dark">Tim Saya</span>
            </a>
            
            <a href="menu_setting.php" class="menu-item text-decoration-none">
                <div class="menu-icon text-dark"><i class="fa fa-cog"></i></div>
                <span class="text-dark">Setting</span>
            </a>
        </div>

        <?php if($role_user == 'Manager' || $role_user == 'Supervisor') { ?>
        <div class="mt-4 px-3">
            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-user-tie me-2"></i>Menu Atasan</h6>
            <a href="menu_approval.php" class="text-decoration-none text-dark">
                <div class="bg-white p-3 rounded-3 shadow-sm mb-2 d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa fa-check-double"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold">Approval Cuti</h6>
                        <small class="text-muted">Kelola pengajuan tim</small>
                    </div>
                    <i class="fa fa-chevron-right text-muted"></i>
                </div>
            </a>
        </div>
        <?php } ?>

        <div class="bottom-nav">
            <div class="nav-item-mobile text-primary">
                <i class="fa fa-home"></i><span class="nav-text">Home</span>
            </div>
            <div class="nav-item-mobile">
                <i class="fa fa-calendar-alt"></i><span class="nav-text">Jadwal</span>
            </div>
            <a href="auth.php?logout=true" class="nav-item-mobile text-decoration-none" style="color: #94a3b8;">
                <i class="fa fa-sign-out-alt text-danger"></i>
                <span class="nav-text text-danger" style="display: block; font-size: 0.7rem; margin-top: 2px;">Keluar</span>
            </a>
        </div>

    </div>

    <div class="modal fade" id="modalCheckIn" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Tipe Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <a href="attendance.php?type=WFO" class="btn btn-outline-primary w-100 mb-2 py-3"><i class="fa fa-building fa-2x mb-1"></i><br>WFO (Kantor)</a>
                    <a href="attendance.php?type=WFH" class="btn btn-outline-success w-100 py-3"><i class="fa fa-home fa-2x mb-1"></i><br>WFH (Rumah)</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCheckOut" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white"><h5 class="modal-title">Laporan Kerja (Checkout)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form action="attendance.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Apa yang dikerjakan hari ini?</label>
                            <textarea name="tasks" class="form-control" rows="4" placeholder="Contoh: Meeting Project A..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="checkout" class="btn btn-danger w-100 fw-bold">KIRIM & PULANG</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(id) {
            new bootstrap.Modal(document.getElementById(id)).show();
        }
    </script>
    <script>
    // Cari semua elemen <a> di halaman ini
    document.querySelectorAll('a').forEach(function(link) {
        // Cek dulu, jangan ubah link logout atau yang punya target="_blank"
        if(link.getAttribute('href') && !link.getAttribute('href').includes('logout') && link.getAttribute('target') !== '_blank') {
            
            let urlTujuan = link.getAttribute('href');
            
            // Hapus href asli biar gak muncul di pojok
            link.setAttribute('href', 'javascript:void(0);');
            
            // Tambahin fungsi klik manual
            link.addEventListener('click', function() {
                window.location.href = urlTujuan;
            });
        }
    });
</script>
</body>
</html>