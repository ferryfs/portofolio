<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

// 1. SECURITY: Cuma Manager/SVP yang boleh masuk
$role = $_SESSION['ess_role'];
if($role == 'Staff' || !isset($_SESSION['ess_user'])) {
    echo "<script>alert('Anda tidak memiliki akses!'); window.location='index.php';</script>";
    exit();
}

// --- FUNGSI HITUNG HARI KERJA (SKIP SABTU, MINGGU & LIBUR) ---
function hitungHariKerja($start, $end, $conn) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

    $days = 0;
    
    // Ambil list tanggal merah dari database
    $holidays = [];
    $q_libur = mysqli_query($conn, "SELECT holiday_date FROM ess_holidays");
    while($h = mysqli_fetch_assoc($q_libur)) {
        $holidays[] = $h['holiday_date'];
    }

    foreach ($period as $dt) {
        $curr = $dt->format('Y-m-d');
        $dayOfWeek = $dt->format('N'); // 1 (Mon) - 7 (Sun)

        // Jika bukan Sabtu (6) dan bukan Minggu (7) DAN bukan tanggal merah
        if ($dayOfWeek < 6 && !in_array($curr, $holidays)) {
            $days++;
        }
    }
    return $days;
}

// 2. LOGIC APPROVE / REJECT
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id_cuti = $_GET['id'];
    $action  = $_GET['action']; // 'Approved' atau 'Rejected'
    $approver= $_SESSION['ess_name'];
    
    // Ambil detail pengajuan dulu
    $cek_data = mysqli_query($conn, "SELECT * FROM ess_leaves WHERE id='$id_cuti'");
    $data_cuti = mysqli_fetch_assoc($cek_data);
    $pemohon_id = $data_cuti['employee_id'];
    $tipe_cuti  = $data_cuti['leave_type'];

    // JIKA DIA APPROVE DAN TIPENYA CUTI TAHUNAN -> POTONG KUOTA
    if($action == 'Approved' && $tipe_cuti == 'Cuti Tahunan') {
        
        // 1. Hitung durasi efektif
        $jumlah_hari = hitungHariKerja($data_cuti['start_date'], $data_cuti['end_date'], $conn);
        
        // 2. Cek kuota user sekarang
        $cek_user = mysqli_query($conn, "SELECT annual_leave_quota FROM ess_users WHERE employee_id='$pemohon_id'");
        $user_data = mysqli_fetch_assoc($cek_user);
        $sisa_kuota = $user_data['annual_leave_quota'];

        // 3. Validasi cukup gak kuotanya?
        if($sisa_kuota >= $jumlah_hari) {
            // Update Status
            mysqli_query($conn, "UPDATE ess_leaves SET status='$action', approved_by='$approver' WHERE id='$id_cuti'");
            // Potong Kuota
            mysqli_query($conn, "UPDATE ess_users SET annual_leave_quota = annual_leave_quota - $jumlah_hari WHERE employee_id='$pemohon_id'");
            
            echo "<script>alert('Disetujui! Kuota dipotong $jumlah_hari hari.'); window.location='menu_approval.php';</script>";
        } else {
            echo "<script>alert('GAGAL! Kuota cuti karyawan tidak mencukupi (Butuh: $jumlah_hari, Sisa: $sisa_kuota).'); window.location='menu_approval.php';</script>";
        }

    } else {
        // Kalau Reject atau Izin sakit (gak potong kuota), langsung update status aja
        mysqli_query($conn, "UPDATE ess_leaves SET status='$action', approved_by='$approver' WHERE id='$id_cuti'");
        header("Location: menu_approval.php");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Approval Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>
    <div class="container py-4" style="max-width: 450px;">
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="text-dark me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h5 class="fw-bold mb-0">Approval (<?php echo $role; ?>)</h5>
        </div>

        <?php
        $query = mysqli_query($conn, "SELECT * FROM ess_leaves WHERE status='Pending' ORDER BY id DESC");
        
        if(mysqli_num_rows($query) == 0) {
            echo "<div class='text-center text-muted mt-5'><i class='fa fa-clipboard-check fa-3x mb-3'></i><p>Tidak ada pengajuan pending.</p></div>";
        }

        while($row = mysqli_fetch_assoc($query)) {
            // Hitung estimasi hari biar Manager tau mau motong berapa
            $est_hari = hitungHariKerja($row['start_date'], $row['end_date'], $conn);
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="fw-bold mb-0"><?php echo $row['fullname']; ?></h6>
                    <span class="badge bg-warning text-dark">Pending</span>
                </div>
                <small class="text-muted d-block mb-1"><?php echo $row['division']; ?> | <?php echo $row['leave_type']; ?></small>
                
                <div class="alert alert-light border small py-2 mb-2">
                    <i class="fa fa-quote-left text-muted me-1"></i> <?php echo $row['reason']; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <small class="fw-bold text-primary"><i class="fa fa-calendar"></i> <?php echo $row['start_date']; ?> s/d <?php echo $row['end_date']; ?></small>
                    <?php if($row['leave_type'] == 'Cuti Tahunan') { ?>
                        <span class="badge bg-info text-dark">Potong: <?php echo $est_hari; ?> Hari</span>
                    <?php } else { ?>
                        <span class="badge bg-secondary">No Potong</span>
                    <?php } ?>
                </div>
                
                <hr>
                <div class="d-flex gap-2">
                    <a href="menu_approval.php?action=Rejected&id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm w-50" onclick="return confirm('Tolak pengajuan ini?')">Tolak</a>
                    <a href="menu_approval.php?action=Approved&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm w-50" onclick="return confirm('Setujui? Kuota akan dipotong otomatis.')">Setujui</a>
                </div>
            </div>
        </div>
        <?php } ?>

    </div>
</body>
</html>