<?php
session_name("TMS_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
if (!isset($_SESSION['tms_status'])) { header("Location: index.php"); exit(); }

$page_title  = 'User Guide';
$active_page = 'help';

// Ambil CSS guide dulu via output buffering
ob_start();
include '_guide_content.php';
$guide_css = ob_get_clean();

// Include head (output HTML head tag)
include '_head.php';

// Inject guide CSS (masuk setelah </style> dari _head, sebelum </head>)
echo $guide_css;
?>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <div class="guide-hero">
        <div style="font-size:0.75rem; opacity:0.6; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">LogiTrack TMS</div>
        <h2 style="font-weight:800; margin-bottom:6px;">User Guide</h2>
        <p style="opacity:0.75; margin:0; font-size:0.9rem;">Panduan operasional lengkap — setiap fitur, menu, dan tombol dijelaskan secara detail</p>
        <div class="mt-3 p-3 rounded-3" style="background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3); max-width:640px;">
            <small style="color:#fde68a;"><i class="fa fa-info-circle me-2"></i>
            <strong>Demo Mode:</strong> Aplikasi ini merupakan simulasi sistem TMS yang dikembangkan di lingkungan distribusi TACO Group. Seluruh fitur mencerminkan implementasi nyata di lapangan. Data bersifat fiktif untuk keperluan demonstrasi portofolio teknis.</small>
        </div>
    </div>
    <div class="guide-tabs d-flex gap-2 mb-4 flex-wrap">
        <button class="nav-link active" onclick="showTab('intro',this)">🏠 Overview</button>
        <button class="nav-link" onclick="showTab('dashboard',this)">📊 Dashboard</button>
        <button class="nav-link" onclick="showTab('orders',this)">📦 Orders</button>
        <button class="nav-link" onclick="showTab('outbound',this)">✍️ POD</button>
        <button class="nav-link" onclick="showTab('exception',this)">⚠️ Exception</button>
        <button class="nav-link" onclick="showTab('fleet',this)">🚛 Fleet</button>
        <button class="nav-link" onclick="showTab('drivers',this)">👤 Drivers</button>
        <button class="nav-link" onclick="showTab('billing',this)">💰 Billing</button>
        <button class="nav-link" onclick="showTab('status',this)">📋 Status Flow</button>
    </div>
    <div id="guide-body"><?php include '_guide_tabs.php'; ?></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showTab(tab, btn) {
    document.querySelectorAll('.guide-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.guide-tabs .nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('gs-' + tab).classList.add('active');
    btn.classList.add('active');
}
</script>
</body></html>