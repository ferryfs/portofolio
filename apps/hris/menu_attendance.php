<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['hris_user'])) { header("Location: login.php"); exit(); }

// Filter Tanggal (Default Hari Ini)
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
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
        .table th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
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
                <h6 class="mb-0 fw-bold"><i class="fa fa-fingerprint me-2 text-primary"></i>Log Absensi Harian</h6>
                <form class="d-flex" method="GET">
                    <input type="date" name="date" class="form-control form-control-sm me-2" value="<?php echo $filter_date; ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Task Report</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_absen = mysqli_query($conn, "SELECT * FROM ess_attendance WHERE date_log='$filter_date' ORDER BY check_in_time DESC");
                            
                            if(mysqli_num_rows($q_absen) == 0) {
                                echo "<tr><td colspan='7' class='text-center text-muted'>Tidak ada data absensi pada tanggal ini.</td></tr>";
                            }

                            while($row = mysqli_fetch_assoc($q_absen)) {
                                $jam_masuk = date('H:i', strtotime($row['check_in_time']));
                                $jam_pulang = ($row['check_out_time']) ? date('H:i', strtotime($row['check_out_time'])) : '-';
                                $badge_tipe = ($row['type'] == 'WFO') ? 'bg-primary' : 'bg-success';
                            ?>
                            <tr>
                                <td><?php echo $row['employee_id']; ?></td>
                                <td class="fw-bold"><?php echo $row['fullname']; ?></td>
                                <td><?php echo $jam_masuk; ?></td>
                                <td><?php echo $jam_pulang; ?></td>
                                <td><span class="badge <?php echo $badge_tipe; ?>"><?php echo $row['type']; ?></span></td>
                                <td>
                                    <?php if($row['check_out_time']) { ?>
                                        <span class="badge bg-secondary">Selesai</span>
                                    <?php } else { ?>
                                        <span class="badge bg-warning text-dark">Bekerja</span>
                                    <?php } ?>
                                </td>
                                <td class="small text-muted fst-italic">
                                    <?php echo ($row['tasks']) ? substr($row['tasks'], 0, 30) . '...' : '-'; ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fa fa-calendar-check me-2 text-warning"></i>Rekap Cuti & Izin (Bulan Ini)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Divisi</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Approved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $month = date('m');
                            $q_cuti = mysqli_query($conn, "SELECT * FROM ess_leaves WHERE MONTH(start_date)='$month' ORDER BY id DESC");
                            
                            while($c = mysqli_fetch_assoc($q_cuti)) {
                                $badge_status = 'bg-warning text-dark';
                                if($c['status'] == 'Approved') $badge_status = 'bg-success';
                                if($c['status'] == 'Rejected') $badge_status = 'bg-danger';
                            ?>
                            <tr>
                                <td><?php echo $c['employee_id']; ?></td>
                                <td class="fw-bold"><?php echo $c['fullname']; ?></td>
                                <td><?php echo $c['division']; ?></td>
                                <td><?php echo $c['leave_type']; ?></td>
                                <td><small><?php echo $c['start_date']; ?> <br> s/d <?php echo $c['end_date']; ?></small></td>
                                <td class="small"><?php echo $c['reason']; ?></td>
                                <td><span class="badge <?php echo $badge_status; ?>"><?php echo $c['status']; ?></span></td>
                                <td class="small text-muted"><?php echo ($c['approved_by']) ? $c['approved_by'] : '-'; ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>