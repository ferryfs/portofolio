<?php
// Shared CSS — include di semua halaman LogiTrack
// Usage: $page_title = 'Dashboard'; include '_head.php';
$page_title = $page_title ?? 'LogiTrack TMS';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | LogiTrack TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --bg-body: #f1f5f9;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); overflow-x: hidden; color: var(--text); }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 240px; height: 100vh;
            position: fixed; top: 0; left: 0;
            background: var(--sidebar-bg);
            color: #94a3b8; z-index: 1000;
            display: flex; flex-direction: column;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 18px 20px; font-size: 1.3rem; font-weight: 800;
            color: white; border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
        }
        .sidebar-tenant {
            padding: 10px 20px 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .tenant-badge {
            font-size: 0.68rem; font-weight: 700;
            background: rgba(245,158,11,0.15); color: var(--accent);
            border: 1px solid rgba(245,158,11,0.3);
            padding: 3px 10px; border-radius: 20px;
            display: inline-block; margin-bottom: 3px;
        }
        .tenant-user { font-size: 0.72rem; color: #475569; }
        .nav-section {
            font-size: 0.6rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: #334155; padding: 14px 20px 4px;
        }
        .nav-link {
            color: #94a3b8; padding: 10px 20px;
            font-weight: 500; font-size: 0.875rem;
            display: flex; align-items: center; gap: 10px;
            transition: 0.15s; position: relative;
            text-decoration: none;
        }
        .nav-link i { width: 16px; text-align: center; }
        .nav-link span { flex: 1; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.04); }
        .nav-link.active { color: white; background: rgba(245,158,11,0.1); border-right: 3px solid var(--accent); }
        .nav-link.nav-danger { color: #f87171; margin-top: auto; }
        .nav-link.nav-danger:hover { background: rgba(239,68,68,0.08); }
        .nav-badge {
            background: var(--danger); color: #fff;
            font-size: 0.6rem; font-weight: 800;
            min-width: 18px; height: 18px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 4px; margin-left: auto;
        }

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: 240px; padding: 24px; min-height: 100vh; }

        /* ── PAGE HEADER ── */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h3 { font-weight: 800; margin: 0; font-size: 1.3rem; }
        .page-header p { color: var(--muted); margin: 2px 0 0; font-size: 0.82rem; }

        /* ── KPI CARDS ── */
        .kpi-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 18px 20px;
            display: flex; align-items: center; justify-content: space-between;
            transition: 0.2s; height: 100%;
        }
        .kpi-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.07); transform: translateY(-2px); }
        .kpi-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .kpi-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.4px; margin-top: 3px; }
        .kpi-sub { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }

        /* ── DATA CARD ── */
        .data-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .data-card-header { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .data-card-header h6 { font-weight: 700; margin: 0; font-size: 0.9rem; }

        /* ── TABLE ── */
        .table thead th { background: #f8fafc; color: var(--muted); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; padding: 11px 14px; border-bottom: 1px solid var(--border); border-top: none; }
        .table tbody td { padding: 12px 14px; vertical-align: middle; font-size: 0.875rem; border-color: var(--border); }
        .table tbody tr:hover { background: #fafbff; }
        .table tbody tr:last-child td { border-bottom: none; }

        /* ── BADGES ── */
        .badge-soft { padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
        .bs-success  { background: #d1fae5; color: #065f46; }
        .bs-warning  { background: #fef3c7; color: #92400e; }
        .bs-danger   { background: #fee2e2; color: #991b1b; }
        .bs-info     { background: #cffafe; color: #0c4a6e; }
        .bs-muted    { background: #f1f5f9; color: #475569; }
        .bs-primary  { background: #eef2ff; color: #3730a3; }
        .bs-orange   { background: #fff7ed; color: #c2410c; }

        /* ── BUTTONS ── */
        .btn-accent { background: var(--accent); color: #000; border: none; font-weight: 700; border-radius: 10px; }
        .btn-accent:hover { background: var(--accent-dark); color: #000; }
        .btn-accent-outline { background: transparent; color: var(--accent); border: 2px solid var(--accent); font-weight: 700; border-radius: 10px; }

        /* ── MISC ── */
        .modal-content { border-radius: 16px; border: none; }
        .form-control, .form-select { border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 0.875rem; }
        .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .form-label { font-size: 0.78rem; font-weight: 700; color: #374151; }
        .empty-state { text-align: center; padding: 40px; color: var(--muted); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: 0.2; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>