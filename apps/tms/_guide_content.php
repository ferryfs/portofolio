<style>
.guide-hero { background:linear-gradient(135deg,#0f172a,#1e293b); border-radius:16px; padding:32px; color:#fff; margin-bottom:28px; position:relative; overflow:hidden; }
.guide-hero::after { content:'🚛'; position:absolute; right:32px; top:50%; transform:translateY(-50%); font-size:5rem; opacity:0.12; }
.guide-tabs .nav-link { color:#64748b; font-weight:600; font-size:0.82rem; border-radius:10px; padding:7px 14px; border:1.5px solid #e2e8f0; background:#fff; cursor:pointer; white-space:nowrap; transition:0.15s; }
.guide-tabs .nav-link.active { background:#f59e0b; color:#000; border-color:#f59e0b; }
.guide-section { display:none; }
.guide-section.active { display:block; }

.step-item { display:flex; gap:14px; align-items:flex-start; padding:12px 16px; background:#f8fafc; border-radius:12px; margin-bottom:8px; border:1px solid #e2e8f0; }
.step-num { min-width:28px; height:28px; background:#f59e0b; color:#000; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.8rem; flex-shrink:0; }
.step-item strong { font-size:0.875rem; color:#0f172a; display:block; margin-bottom:2px; }
.step-item span { font-size:0.78rem; color:#64748b; line-height:1.55; }

.btn-doc { display:inline-flex; align-items:center; gap:6px; background:#0f172a; color:#fff; font-size:0.72rem; font-weight:700; padding:4px 10px; border-radius:6px; margin:2px; }
.btn-doc.yellow { background:#f59e0b; color:#000; }
.btn-doc.green  { background:#059669; color:#fff; }
.btn-doc.red    { background:#dc2626; color:#fff; }
.btn-doc.blue   { background:#4f46e5; color:#fff; }
.btn-doc.outline { background:transparent; color:#374151; border:1.5px solid #e2e8f0; }

.info-tbl { width:100%; border-collapse:collapse; font-size:0.82rem; margin:10px 0; }
.info-tbl th { background:#0f172a; color:#fff; padding:9px 12px; text-align:left; font-weight:700; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.4px; }
.info-tbl td { padding:9px 12px; border-bottom:1px solid #f1f5f9; color:#374151; vertical-align:top; line-height:1.5; }
.info-tbl tr:last-child td { border-bottom:none; }
.info-tbl td:first-child { font-weight:700; color:#0f172a; white-space:nowrap; }
.info-tbl tr:nth-child(even) td { background:#f8fafc; }

.gn { padding:12px 16px; border-radius:10px; font-size:0.78rem; margin:10px 0; line-height:1.55; }
.gn.yellow { background:#fffbeb; color:#78350f; border-left:4px solid #f59e0b; }
.gn.blue   { background:#eff6ff; color:#1e40af; border-left:4px solid #3b82f6; }
.gn.green  { background:#f0fdf4; color:#065f46; border-left:4px solid #10b981; }
.gn.red    { background:#fef2f2; color:#991b1b; border-left:4px solid #ef4444; }

.section-h { font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.7px; color:#94a3b8; margin:20px 0 10px; }
.feature-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:12px; margin:12px 0; }
.feature-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
.feature-box .fb-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; margin-bottom:8px; }
.feature-box strong { font-size:0.875rem; display:block; margin-bottom:3px; }
.feature-box p { font-size:0.75rem; color:#64748b; margin:0; line-height:1.5; }
</style>
