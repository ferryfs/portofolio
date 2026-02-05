<?php
// apps/ess-mobile/menu_team.php (PDO VERSION)

session_name("ESS_PORTAL_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php'; // $pdo
require_once __DIR__ . '/../../config/security.php'; // Helper

// 1. CEK LOGIN & ROLE
// Manager & SPV boleh lihat. Staff juga boleh lihat (buat koordinasi tim), 
// tapi kalau Bos mau batasi, uncomment baris exit di bawah.
if (!isset($_SESSION['ess_user'])) {
    header("Location: landing.php");
    exit();
}

// Opsional: Batasi Staff kalau perlu
// if($_SESSION['ess_role'] == 'Staff') { header("Location: index.php"); exit(); }

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
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .search-box { border-radius: 20px; overflow: hidden; border: 1px solid #dee2e6; }
        .search-box input { border: none; box-shadow: none; }
        .card-team { transition: transform 0.2s; cursor: default; }
        .card-team:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container py-4" style="max-width: 450px;">
        
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="text-dark me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h5 class="fw-bold mb-0">Anggota Tim Saya</h5>
        </div>

        <div class="input-group mb-4 shadow-sm search-box">
            <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-search text-muted"></i></span>
            <input type="text" class="form-control" placeholder="Cari karyawan..." onkeyup="filterTeam(this.value)">
        </div>

        <div id="teamList">
        <?php
        // AMBIL SEMUA STAFF (PDO)
        // Kita exclude diri sendiri biar gak bingung
        $my_id = $_SESSION['ess_user'];
        $stmt = $pdo->prepare("SELECT * FROM ess_users WHERE role='Staff' AND employee_id != ? ORDER BY fullname ASC");
        $stmt->execute([$my_id]);
        
        $count = 0;
        while ($staff = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            $nik_staff = $staff['employee_id'];
            
            // LOGIC CEK STATUS CUTI HARI INI (PDO)
            // Cek apakah ada cuti APPROVED yang tanggalnya mencakup HARI INI
            $sql_cuti = "SELECT * FROM ess_leaves 
                         WHERE employee_id = ? 
                         AND status = 'Approved' 
                         AND ? BETWEEN start_date AND end_date";
            
            $cuti = safeGetOne($pdo, $sql_cuti, [$nik_staff, $today]);
            
            $status_text  = "Hadir / Available";
            $status_class = "text-success";
            $bg_class     = "bg-success";
            $card_border  = "border-start border-4 border-success"; // Visual indicator
            
            if ($cuti) {
                $status_text  = "Sedang Cuti (" . htmlspecialchars($cuti['leave_type']) . ")";
                $status_class = "text-warning";
                $bg_class     = "bg-warning";
                $card_border  = "border-start border-4 border-warning";
            }
        ?>

        <div class="card card-team border-0 shadow-sm mb-3 <?php echo $card_border; ?> team-item">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="position-relative">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($staff['fullname']); ?>&background=random&color=fff&size=128" class="rounded-circle border border-2 border-white shadow-sm" width="50" height="50">
                        <span class="position-absolute bottom-0 end-0 p-1 <?php echo $bg_class; ?> border border-light rounded-circle"></span>
                    </div>
                    
                    <div class="ms-3 flex-grow-1 overflow-hidden">
                        <h6 class="fw-bold mb-0 text-truncate team-name"><?php echo htmlspecialchars($staff['fullname']); ?></h6>
                        <small class="text-muted d-block text-truncate" style="font-size: 0.75rem;">
                            <?php echo htmlspecialchars($staff['division']); ?> • <?php echo htmlspecialchars($staff['employee_id']); ?>
                        </small>
                        
                        <small class="<?php echo $status_class; ?> fw-bold" style="font-size: 0.75rem;">
                            <i class="fa fa-circle me-1" style="font-size: 0.5rem;"></i> <?php echo $status_text; ?>
                        </small>
                    </div>

                    <button class="btn btn-light btn-sm rounded-circle shadow-sm" onclick="showDetail('<?php echo htmlspecialchars($staff['fullname']); ?>', '<?php echo htmlspecialchars($staff['email']); ?>', '<?php echo htmlspecialchars($staff['phone_number']); ?>')">
                        <i class="fa fa-info-circle text-primary"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php } 
        
        if($count == 0) {
            echo "<div class='text-center text-muted mt-5'><i class='fa fa-users-slash fa-3x mb-3 text-secondary'></i><p>Belum ada anggota tim lain.</p></div>";
        }
        ?>
        </div>

    </div>

    <script>
        function filterTeam(keyword) {
            const list = document.getElementById('teamList');
            const items = list.getElementsByClassName('team-item');
            
            for (let i = 0; i < items.length; i++) {
                const name = items[i].getElementsByClassName('team-name')[0].innerText;
                if (name.toLowerCase().indexOf(keyword.toLowerCase()) > -1) {
                    items[i].style.display = "";
                } else {
                    items[i].style.display = "none";
                }
            }
        }

        function showDetail(name, email, phone) {
            alert("Detail Karyawan:\n\nNama: " + name + "\nEmail: " + email + "\nWhatsApp: " + (phone ? phone : "-"));
        }
    </script>
</body>
</html>