<?php
// Shared CSS & head — include di semua halaman HRIS
// Usage: $page_title = 'Dashboard'; include '_head.php';
$title = $page_title ?? 'HRIS';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — HRIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #eef2ff;
            --sidebar-bg: #0f172a;
            --sidebar-w: 260px;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            margin: 0;
            overflow-x: hidden;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 24px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .brand-logo {
            display: flex; align-items: center; gap: 10px;
            font-size: 1.4rem; font-weight: 800; color: #fff;
            letter-spacing: -0.5px;
        }
        .brand-logo i { color: var(--primary); font-size: 1.2rem; }
        .brand-sub { font-size: 0.7rem; color: #475569; font-weight: 500; margin-top: 2px; letter-spacing: 0.3px; }

        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .user-avatar { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; }
        .user-name { font-size: 0.82rem; font-weight: 700; color: #f1f5f9; }
        .user-role { font-size: 0.68rem; color: #475569; }

        .sidebar-section {
            font-size: 0.62rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: #334155; padding: 16px 20px 6px;
        }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 20px; color: #94a3b8;
            text-decoration: none; font-size: 0.85rem; font-weight: 500;
            border-radius: 0; transition: all 0.15s;
            position: relative;
        }
        .nav-link i { width: 18px; text-align: center; font-size: 0.9rem; }
        .nav-link span { flex: 1; }
        .nav-link:hover { background: rgba(255,255,255,0.04); color: #e2e8f0; }
        .nav-link.active {
            background: rgba(79,70,229,0.15);
            color: #a5b4fc;
            border-right: 3px solid var(--primary);
        }
        .nav-link-danger { color: #f87171 !important; margin-top: auto; }
        .nav-link-danger:hover { background: rgba(239,68,68,0.08) !important; }
        .nav-badge {
            background: var(--danger); color: #fff;
            font-size: 0.6rem; font-weight: 800;
            min-width: 18px; height: 18px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; padding: 0 4px;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 0;
        }
        .page-topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 28px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .page-topbar h5 { font-weight: 800; margin: 0; font-size: 1.05rem; }
        .page-topbar .breadcrumb { margin: 0; font-size: 0.75rem; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .topbar-date { font-size: 0.78rem; color: var(--muted); }

        .page-body { padding: 24px 28px; }

        /* ── KPI CARDS ── */
        .kpi-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
            display: flex; align-items: center; gap: 16px;
            transition: 0.2s;
            height: 100%;
        }
        .kpi-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.06); transform: translateY(-2px); }
        .kpi-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        .kpi-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 0.75rem; color: var(--muted); font-weight: 600; margin-top: 3px; }
        .kpi-sub { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }

        /* ── TABLES ── */
        .data-card {
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .data-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .data-card-header h6 { font-weight: 700; margin: 0; font-size: 0.9rem; }
        .table { margin: 0; }
        .table thead th {
            background: #f8fafc; color: var(--muted);
            font-size: 0.72rem; text-transform: uppercase;
            letter-spacing: 0.5px; font-weight: 700;
            padding: 12px 16px; border-bottom: 1px solid var(--border);
            border-top: none;
        }
        .table tbody td { padding: 13px 16px; vertical-align: middle; font-size: 0.875rem; border-color: var(--border); }
        .table tbody tr:hover { background: #fafbff; }
        .table tbody tr:last-child td { border-bottom: none; }

        /* ── BADGES ── */
        .badge-soft { padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-info    { background: #cffafe; color: #0c4a6e; }
        .badge-muted   { background: #f1f5f9; color: #475569; }
        .badge-primary { background: #eef2ff; color: #3730a3; }

        /* ── BUTTONS ── */
        .btn-primary-custom {
            background: var(--primary); color: #fff; border: none;
            border-radius: 10px; padding: 8px 16px; font-size: 0.82rem; font-weight: 600;
            transition: 0.15s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-primary-custom:hover { background: var(--primary-dark); color: #fff; }

        /* ── MISC ── */
        .section-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); margin-bottom: 12px; }
        .avatar-sm { width: 32px; height: 32px; border-radius: 8px; object-fit: cover; }
        .empty-state { text-align: center; padding: 40px; color: var(--muted); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: 0.2; }

        /* ── FORM ── */
        .form-label { font-size: 0.78rem; font-weight: 700; color: #374151; }
        .form-control, .form-select {
            border-radius: 10px; font-size: 0.875rem;
            border-color: var(--border); font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }
        .modal-content { border-radius: 16px; border: none; }
        .modal-header { border-bottom: 1px solid var(--border); }
    </style>
