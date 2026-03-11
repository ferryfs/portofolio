<?php
$work_projects = array_filter($projects, fn($p) => strtolower($p['category']) === 'work');
$personal_projects = array_filter($projects, fn($p) => strtolower($p['category']) === 'personal');

$by_company = [];
foreach ($work_projects as $proj) {
    $comp = !empty($proj['company_ref']) ? $proj['company_ref'] : 'Lainnya';
    $by_company[$comp][] = $proj;
}
?>

<section id="projects" class="py-6 md:py-10 max-w-7xl mx-auto px-4 relative z-10" data-aos="fade-up">

    <div class="projects-island shadow-2xl" style="background: #0C0C0E; color: #ffffff;">

        <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(37,99,235,0.08) 0%, transparent 70%);"></div>
        <div class="absolute bottom-0 left-0 w-[300px] h-[300px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(124,58,237,0.06) 0%, transparent 70%);"></div>

        <!-- Header -->
        <div class="p-5 md:p-10 pb-0 relative z-10">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 mb-5">
                <div>
                    <span style="color:#60a5fa; font-weight:800; font-size:10px; text-transform:uppercase; letter-spacing:0.15em; display:block; margin-bottom:8px;">Portfolio</span>
                    <h2 style="color:#ffffff; font-weight:900; font-size:clamp(1.6rem,4vw,3rem); letter-spacing:-0.03em; margin-bottom:8px;"><?php echo $txt['sect_proj_title']; ?></h2>
                </div>
                <div style="display:flex; align-items:center; gap:6px; color:rgba(255,255,255,0.3); font-size:11px; font-weight:700; flex-shrink:0; padding-bottom:4px;">
                    <i class="bi bi-arrow-left"></i>
                    <span>Geser untuk lihat lebih</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>

            <!-- Main tabs -->
            <div class="flex gap-0 overflow-x-auto scrollbar-hide" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                <div onclick="switchMainTab('work')" id="main-tab-work" class="main-tab-btn active cursor-pointer select-none">
                    <i class="bi bi-briefcase-fill me-2"></i><?php echo $txt['tab_work']; ?>
                </div>
                <div onclick="switchMainTab('personal')" id="main-tab-personal" class="main-tab-btn cursor-pointer select-none">
                    <i class="bi bi-code-square me-2"></i><?php echo $txt['tab_personal']; ?>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="relative z-10 p-5 md:p-10 pt-5">

            <!-- WORK -->
            <div id="content-work" class="tab-content-area">
                <?php if (count($by_company) > 1): ?>
                <div style="display:flex; gap:8px; margin-bottom:20px; overflow-x:auto; padding-bottom:4px; scrollbar-width:none;">
                    <button onclick="switchSubTab('all')" id="sub-btn-all" class="sub-tab-btn active">Semua</button>
                    <?php foreach ($by_company as $compName => $compProjects): ?>
                    <button onclick="switchSubTab('<?php echo md5($compName); ?>')"
                            id="sub-btn-<?php echo md5($compName); ?>"
                            class="sub-tab-btn">
                        <?php echo clean($compName); ?>
                        <span class="ml-1.5 opacity-50"><?php echo count($compProjects); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="mb-5"></div>
                <?php endif; ?>

                <div id="sub-content-all" class="sub-content-area pb-4 overflow-x-auto snap-x scrollbar-hide drag-scroll"
                     style="display:flex; flex-wrap:nowrap; gap:16px;">
                    <?php foreach ($work_projects as $d): include 'component/card_project.php'; endforeach; ?>
                    <?php if (empty($work_projects)): ?>
                    <div class="text-white/30 text-sm italic py-10">Belum ada proyek kerja.</div>
                    <?php endif; ?>
                </div>

                <?php foreach ($by_company as $compName => $compProjects): $slug = md5($compName); ?>
                <div id="sub-content-<?php echo $slug; ?>" class="sub-content-area pb-4 overflow-x-auto snap-x scrollbar-hide drag-scroll"
                     style="display:none; flex-wrap:nowrap; gap:16px;">
                    <?php foreach ($compProjects as $d): include 'component/card_project.php'; endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PERSONAL -->
            <div id="content-personal" class="tab-content-area hidden">
                <div class="mb-5"></div>
                <div class="pb-4 overflow-x-auto snap-x scrollbar-hide drag-scroll"
                     style="display:flex; flex-wrap:nowrap; gap:16px;">
                    <?php foreach ($personal_projects as $d): include 'component/card_project.php'; endforeach; ?>
                    <?php if (empty($personal_projects)): ?>
                    <div class="text-white/30 text-sm italic py-10">Belum ada proyek personal.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
function switchMainTab(tab) {
    ['work', 'personal'].forEach(t => {
        const content = document.getElementById('content-' + t);
        const btn = document.getElementById('main-tab-' + t);
        if (!content || !btn) return;
        if (t === tab) {
            content.classList.remove('hidden');
            btn.classList.add('active');
            btn.style.borderBottomColor = '#2563EB';
            btn.style.color = '#ffffff';
        } else {
            content.classList.add('hidden');
            btn.classList.remove('active');
            btn.style.borderBottomColor = 'transparent';
            btn.style.color = 'rgba(255,255,255,0.4)';
        }
    });
}

function switchSubTab(slug) {
    document.querySelectorAll('[id^="sub-content-"]').forEach(el => {
        el.style.display = 'none';
    });
    const target = document.getElementById('sub-content-' + slug);
    if (target) target.style.display = 'flex';

    document.querySelectorAll('[id^="sub-btn-"]').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById('sub-btn-' + slug);
    if (activeBtn) activeBtn.classList.add('active');
}
</script>