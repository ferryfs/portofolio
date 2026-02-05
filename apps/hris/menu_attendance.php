<?php
session_name("HRIS_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

$filter_date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi & Cuti - HRIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; background: #1e293b; color: #adb5bd; padding-top: 20px; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #adb5bd; padding: 12px 25px; text-decoration: none; display: flex; align-items: center; }
        .nav-link:hover, .nav-link.active { background: #0d6efd; color: white; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="text-center text-white mb-4 fs-4 fw-bold"><i class="fa fa-cube"></i> HRIS PRO</div>
        <a href="index.php" class="nav-link"><i class="fa fa-gauge-high me-3"></i> Dashboard</a>
        <a href="menu_employee.php" class="nav-link"><i class="fa fa-users me-3"></i> Data Karyawan</a>
        <a href="menu_attendance.php" class="nav-link active"><i class="fa fa-clock me-3"></i> Absensi & Cuti</a>
        <a href="auth.php?logout=true" class="nav-link text-danger mt-5"><i class="fa fa-sign-out-alt me-3"></i> Logout</a>
    </div>

    <div class="main-content">
        <h4 class="fw-bold mb-4">Monitoring Kehadiran & Cuti</h4>

        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa fa-fingerprint me-2 text-primary"></i>Log Absensi (<?php echo $filter_date; ?>)</h6>
                <form class="d-flex" method="GET">
                    <input type="date" name="date" class="form-control form-control-sm me-2" value="<?php echo $filter_date; ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>NIK</th><th>Nama</th><th>Masuk</th><th>Pulang</th><th>Tipe</th><th>Status</th><th>Task</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM ess_attendance WHERE date_log = ? ORDER BY check_in_time DESC");
                            $stmt->execute([$filter_date]);
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if(count($rows) == 0) echo "<tr><td colspan='7' class='text-center text-muted'>Tidak ada data.</td></tr>";

                            foreach($rows as $row) {
                                $masuk = date('H:i', strtotime($row['check_in_time']));
                                $pulang = $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '-';
                                $badge = ($row['type'] == 'WFO') ? 'bg-primary' : 'bg-success';
                            ?>
                            <tr>
                                <td><?php echo $row['employee_id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo $masuk; ?></td>
                                <td><?php echo $pulang; ?></td>
                                <td><span class="badge <?php echo $badge; ?>"><?php echo $row['type']; ?></span></td>
                                <td><?php echo $row['check_out_time'] ? '<span class="badge bg-secondary">Selesai</span>' : '<span class="badge bg-warning text-dark">Bekerja</span>'; ?></td>
                                <td class="small text-muted fst-italic"><?php echo substr($row['tasks'], 0, 30); ?>...</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>