<?php
session_name("ESS_PORTAL_SESSION"); // <--- Kunci harus sama kayak auth.php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }
$nik = $_SESSION['ess_user'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Riwayat Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>
    <div class="container py-4" style="max-width: 450px;">
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="text-dark me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h5 class="fw-bold mb-0">Riwayat Pengajuan Saya</h5>
        </div>

        <?php
        // Ambil data milik user yang sedang login saja
        $query = mysqli_query($conn, "SELECT * FROM ess_leaves WHERE employee_id='$nik' ORDER BY id DESC");
        
        while($row = mysqli_fetch_assoc($query)) {
            // Tentukan Warna Badge
            $badge_color = 'bg-warning text-dark';
            if($row['status'] == 'Approved') $badge_color = 'bg-success';
            if($row['status'] == 'Rejected') $badge_color = 'bg-danger';
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-primary"><?php echo $row['leave_type']; ?></span>
                    <span class="badge <?php echo $badge_color; ?>"><?php echo $row['status']; ?></span>
                </div>
                <div class="small text-muted mb-2">
                    <i class="fa fa-calendar me-1"></i> <?php echo $row['start_date']; ?> - <?php echo $row['end_date']; ?>
                </div>
                <p class="small mb-0 fst-italic">"<?php echo $row['reason']; ?>"</p>
                
                <?php if($row['status'] != 'Pending') { ?>
                    <hr class="my-2">
                    <small class="text-muted" style="font-size: 0.7rem;">Diproses oleh: <?php echo $row['approved_by']; ?></small>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

    </div>
</body>
</html>