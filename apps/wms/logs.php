<?php 
// apps/wms/logs.php (PDO FULL)
session_name("WMS_APP_SESSION");
session_start();

if(!isset($_SESSION['wms_login'])) { exit("Akses Ditolak."); }
require_once __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>System Logs (Audit Trail)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .log-create { border-left: 4px solid #198754; }
        .log-delete { border-left: 4px solid #dc3545; }
        .log-update { border-left: 4px solid #ffc107; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h3><i class="bi bi-shield-lock"></i> Audit Trail & Logs</h3>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <span>System Activity History</span>
            <span class="badge bg-secondary">Last 50 Activities</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Time</th><th>User</th><th>Module</th><th>Action</th><th>Description</th><th>IP Addr</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $stmt = $pdo->query("SELECT * FROM wms_system_logs ORDER BY log_id DESC LIMIT 50");
                    if($stmt->rowCount() > 0) {
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $class = "";
                            if($row['action_type'] == 'CREATE') $class = "log-create";
                            if($row['action_type'] == 'DELETE') $class = "log-delete";
                            if($row['action_type'] == 'UPDATE') $class = "log-update";
                    ?>
                    <tr class="<?= $class ?>">
                        <td class="small text-muted"><?= $row['log_date'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['user_id']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['module']) ?></span></td>
                        <td>
                            <?php 
                            if($row['action_type'] == 'CREATE') echo '<span class="text-success fw-bold">CREATE</span>';
                            elseif($row['action_type'] == 'DELETE') echo '<span class="text-danger fw-bold">DELETE</span>';
                            elseif($row['action_type'] == 'UPDATE') echo '<span class="text-warning fw-bold text-dark">UPDATE</span>';
                            else echo htmlspecialchars($row['action_type']);
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td class="small text-monospace text-muted"><?= htmlspecialchars($row['ip_address']) ?></td>
                    </tr>
                    <?php endwhile; 
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-4'>Belum ada aktivitas tercatat.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>