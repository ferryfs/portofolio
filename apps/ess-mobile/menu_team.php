<?php
session_name("ESS_PORTAL_SESSION");
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION['ess_user'])) { header("Location: landing.php"); exit(); }

$my_id   = $_SESSION['ess_user'];
$my_role = $_SESSION['ess_role'];
$today   = date('Y-m-d');

// Ambil SEMUA karyawan kecuali diri sendiri, semua role
$members = safeGetAll($pdo,
    "SELECT * FROM ess_users WHERE employee_id != ? ORDER BY
     FIELD(role,'Manager','Supervisor','Staff'), fullname ASC", [$my_id]);

// Hitung total per status untuk summary
$total_all   = count($members);
$total_hadir = 0; $total_cuti = 0;
foreach($members as $m) {
    $cuti = safeGetOne($pdo,
        "SELECT id FROM ess_leaves WHERE employee_id=? AND status='Approved' AND ? BETWEEN start_date AND end_date",
        [$m['employee_id'], $today]);
    if($cuti) $total_cuti++;
    else $total_hadir++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tim Saya</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f1f5f9; font-family:'DM Sans',sans-serif; margin:0; }
        .shell { max-width:430px; margin:0 auto; min-height:100vh; background:#fff; display:flex; flex-direction:column; }

        .page-header {
            background:linear-gradient(135deg,#7c3aed,#4f46e5);
            padding:20px 20px 44px; color:#fff; position:relative;
        }
        .page-header::after { content:''; position:absolute; bottom:0; left:0; right:0; height:24px; background:#f8fafc; border-radius:24px 24px 0 0; }
        .back-btn { background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none; }

        /* Summary strip */
        .summary-strip { display:flex; gap:8px; padding:0 16px 16px; background:#f8fafc; }
        .sum-box { flex:1; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:10px; text-align:center; }
        .sum-num { font-size:1.2rem; font-weight:800; }
        .sum-lbl { font-size:0.62rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.4px; }

        /* Search */
        .search-wrap { padding:0 16px 12px; background:#f8fafc; }
        .search-input { width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:10px 14px 10px 38px; font-size:0.85rem; font-family:'DM Sans',sans-serif; outline:none; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.868-3.834zm-5.242 1.1a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") no-repeat 12px center; }
        .search-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,0.1); }

        /* Role divider */
        .role-divider { font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#94a3b8; padding:12px 16px 6px; background:#f8fafc; }

        /* Member card */
        .body-area { padding:0 16px 32px; background:#f8fafc; flex-grow:1; }
        .member-card {
            background:#fff; border-radius:14px; border:1px solid #f1f5f9;
            padding:12px; margin-bottom:8px;
            display:flex; align-items:center; gap:12px;
        }
        .member-card.is-cuti { border-color:#fde68a; background:#fffbeb; }
        .avatar-wrap { position:relative; flex-shrink:0; }
        .avatar-wrap img { width:46px; height:46px; border-radius:12px; }
        .status-dot {
            position:absolute; bottom:-2px; right:-2px;
            width:13px; height:13px; border-radius:50%;
            border:2px solid #fff;
        }
        .dot-hadir { background:#10b981; }
        .dot-cuti  { background:#f59e0b; }
        .member-name { font-size:0.88rem; font-weight:700; }
        .member-meta { font-size:0.72rem; color:#64748b; }
        .member-status { font-size:0.7rem; font-weight:600; }
        .status-hadir { color:#10b981; }
        .status-cuti  { color:#f59e0b; }

        .role-chip { font-size:0.62rem; font-weight:700; padding:2px 7px; border-radius:5px; margin-left:4px; }
        .chip-manager    { background:#fee2e2; color:#991b1b; }
        .chip-supervisor { background:#fef3c7; color:#92400e; }
        .chip-staff      { background:#f1f5f9; color:#475569; }

        .empty-state { text-align:center; padding:40px; color:#94a3b8; }
        .empty-state i { font-size:2.5rem; margin-bottom:10px; display:block; opacity:0.2; }
    </style>
</head>
<body>
<div class="shell">

    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
            <div>
                <div style="font-size:0.75rem; opacity:0.8;">Direktori</div>
                <h5 class="fw-bold mb-0">Tim Saya</h5>
            </div>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="summary-strip">
        <div class="sum-box">
            <div class="sum-num text-primary"><?= $total_all ?></div>
            <div class="sum-lbl">Total</div>
        </div>
        <div class="sum-box">
            <div class="sum-num text-success"><?= $total_hadir ?></div>
            <div class="sum-lbl">Hadir</div>
        </div>
        <div class="sum-box">
            <div class="sum-num text-warning"><?= $total_cuti ?></div>
            <div class="sum-lbl">Cuti</div>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="search-wrap">
        <input type="text" class="search-input" placeholder="Cari nama atau divisi..." oninput="filterMembers(this.value)" id="searchInput">
    </div>

    <!-- LIST -->
    <div class="body-area" id="memberList">
        <?php if(empty($members)): ?>
        <div class="empty-state"><i class="fa fa-users"></i>Belum ada anggota tim.</div>
        <?php else:
            $last_role = '';
            foreach($members as $m):
                // Cek status cuti
                $cuti = safeGetOne($pdo,
                    "SELECT leave_type FROM ess_leaves WHERE employee_id=? AND status='Approved' AND ? BETWEEN start_date AND end_date",
                    [$m['employee_id'], $today]);
                $is_cuti = (bool)$cuti;

                // Role divider
                if ($m['role'] !== $last_role) {
                    $last_role = $m['role'];
                    echo '<div class="role-divider">' . htmlspecialchars($m['role']) . '</div>';
                }

                $chip_class = ['Manager'=>'chip-manager','Supervisor'=>'chip-supervisor','Staff'=>'chip-staff'][$m['role']] ?? 'chip-staff';
        ?>
        <div class="member-card <?= $is_cuti ? 'is-cuti' : '' ?> member-item">
            <div class="avatar-wrap">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($m['fullname']) ?>&background=<?= $is_cuti ? 'fde68a&color=92400e' : 'eef2ff&color=4f46e5' ?>&size=64&bold=true">
                <span class="status-dot <?= $is_cuti ? 'dot-cuti' : 'dot-hadir' ?>"></span>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="member-name text-truncate">
                    <?= htmlspecialchars($m['fullname']) ?>
                    <span class="role-chip <?= $chip_class ?>"><?= htmlspecialchars($m['role']) ?></span>
                </div>
                <div class="member-meta"><?= htmlspecialchars($m['division']) ?> &bull; <?= htmlspecialchars($m['employee_id']) ?></div>
                <div class="member-status <?= $is_cuti ? 'status-cuti' : 'status-hadir' ?>">
                    <i class="fa fa-circle me-1" style="font-size:0.45rem;"></i>
                    <?= $is_cuti ? 'Sedang Cuti (' . htmlspecialchars($cuti['leave_type']) . ')' : 'Hadir / Available' ?>
                </div>
            </div>
            <button onclick="showDetail(<?= htmlspecialchars(json_encode([
                'nama'  => $m['fullname'],
                'email' => $m['email'],
                'phone' => $m['phone_number'] ?? '-',
                'div'   => $m['division'],
                'role'  => $m['role'],
                'join'  => $m['join_date'] ?? '-'
            ])) ?>)" class="btn btn-light btn-sm rounded-circle border" style="width:34px;height:34px;flex-shrink:0;">
                <i class="fa fa-info text-primary" style="font-size:0.75rem;"></i>
            </button>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:20px; border:none; overflow:hidden;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="modalName"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="modalBody"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterMembers(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.member-item').forEach(el => {
        const text = el.textContent.toLowerCase();
        el.style.display = text.includes(q) ? '' : 'none';
    });
    // Sembunyikan divider kalau semua member di bawahnya hidden
    document.querySelectorAll('.role-divider').forEach(div => {
        let next = div.nextElementSibling;
        let hasVisible = false;
        while (next && next.classList.contains('member-item')) {
            if (next.style.display !== 'none') { hasVisible = true; break; }
            next = next.nextElementSibling;
        }
        div.style.display = hasVisible ? '' : 'none';
    });
}

function showDetail(data) {
    document.getElementById('modalName').textContent = data.nama;
    document.getElementById('modalBody').innerHTML = `
        <div style="display:flex;flex-direction:column;gap:8px;font-size:0.83rem;">
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748b;">Divisi</span><strong>${data.div}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748b;">Jabatan</span><strong>${data.role}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748b;">Email</span><span>${data.email}</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748b;">WhatsApp</span><span>${data.phone || '—'}</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748b;">Join Date</span><span>${data.join}</span>
            </div>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}
</script>
</body>
</html>
