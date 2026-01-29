<?php
session_name("ESS_PORTAL_SESSION"); // <--- Kunci harus sama kayak auth.php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
date_default_timezone_set('Asia/Jakarta');

// 1. CEK LOGIN & ROLE (Cuma Manager/SPV)
if(!isset($_SESSION['ess_user']) || $_SESSION['ess_role'] == 'Staff') {
    echo "<script>alert('Akses Ditolak! Hanya untuk Manager/SPV.'); window.location='index.php';</script>";
    exit();
}

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tim Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>
    <div class="container py-4" style="max-width: 450px;">
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="text-dark me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h5 class="fw-bold mb-0">Anggota Tim Saya</h5>
        </div>

        <div class="input-group mb-4 shadow-sm">
            <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" placeholder="Cari karyawan...">
        </div>

        <?php
        // AMBIL SEMUA STAFF
        $query = mysqli_query($conn, "SELECT * FROM ess_users WHERE role='Staff' ORDER BY fullname ASC");
        
        while($staff = mysqli_fetch_assoc($query)) {
            $nik_staff = $staff['employee_id'];
            
            // LOGIC CEK STATUS CUTI HARI INI
            // Cari di tabel cuti: Apakah staff ini punya cuti APPROVED yang tanggalnya mencakup HARI INI?
            $cek_cuti = mysqli_query($conn, "SELECT * FROM ess_leaves 
                WHERE employee_id='$nik_staff' 
                AND status='Approved' 
                AND '$today' BETWEEN start_date AND end_date");
            
            $status_text = "Hadir / Available";
            $status_class = "text-success";
            $bg_class = "bg-success";
            
            if(mysqli_num_rows($cek_cuti) > 0) {
                $data_cuti = mysqli_fetch_assoc($cek_cuti);
                $status_text = "Sedang Cuti (" . $data_cuti['leave_type'] . ")";
                $status_class = "text-warning";
                $bg_class = "bg-warning";
            }
        ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="position-relative">
                        <img src="https://ui-avatars.com/api/?name=<?php echo $staff['fullname']; ?>&background=random" class="rounded-circle" width="50" height="50">
                        <span class="position-absolute bottom-0 end-0 p-1 <?php echo $bg_class; ?> border border-light rounded-circle"></span>
                    </div>
                    
                    <div class="ms-3 flex-grow-1">
                        <h6 class="fw-bold mb-0"><?php echo $staff['fullname']; ?></h6>
                        <small class="text-muted d-block" style="font-size: 0.75rem;"><?php echo $staff['division']; ?> • <?php echo $staff['employee_id']; ?></small>
                        
                        <small class="<?php echo $status_class; ?> fw-bold" style="font-size: 0.75rem;">
                            <i class="fa fa-circle me-1" style="font-size: 0.5rem;"></i> <?php echo $status_text; ?>
                        </small>
                    </div>

                    <button class="btn btn-light btn-sm rounded-circle" onclick="alert('Detail Karyawan: <?php echo $staff['fullname']; ?>')">
                        <i class="fa fa-info-circle text-secondary"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php } ?>

    </div>
</body>
</html>