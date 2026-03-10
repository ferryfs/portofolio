<?php
// Resolve image
$imgSrc = 'assets/img/default.jpg';
if (!empty($d['image_url'])) { $imgSrc = $d['image_url']; }
elseif (!empty($d['image'])) { $imgSrc = 'assets/img/' . $d['image']; }

// Tech stacks
$stacks = [];
if (!empty($d['tech_stack'])) {
    $stacks = array_slice(
        array_filter(array_map('trim', explode(',', $d['tech_stack']))),
        0, 4
    );
}
$extra = !empty($d['tech_stack'])
    ? max(0, count(array_filter(array_map('trim', explode(',', $d['tech_stack'])))) - 4)
    : 0;

// Category color
$is_work = strtolower($d['category']) === 'work';
$cat_color = $is_work ? '#3B82F6' : '#8B5CF6';
$cat_bg    = $is_work ? 'rgba(59,130,246,0.15)' : 'rgba(139,92,246,0.15)';
?>

<div class="flex-none w-[300px] md:w-[340px] snap-center group cursor-pointer"
     onclick="showProjectDetail(<?php echo (int)$d['id']; ?>)">

    <div style="
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        height: 100%;
        display: flex;
        flex-direction: column;
    " class="project-card-inner">

        <!-- Top: image strip -->
        <div style="height: 160px; overflow: hidden; position: relative; background: #0f0f1a; flex-shrink: 0;">
            <img src="<?php echo $imgSrc; ?>"
                 alt="<?php echo clean($d['title']); ?>"
                 loading="lazy"
                 style="width:100%; height:100%; object-fit:cover; opacity:0.6; transition: opacity 0.4s, transform 0.6s;">
            <!-- Gradient overlay biar judul terbaca -->
            <div style="position:absolute; inset:0; background: linear-gradient(to bottom, transparent 20%, rgba(12,12,14,0.95) 100%);"></div>

            <!-- Category badge -->
            <div style="
                position: absolute; top: 12px; left: 12px;
                padding: 4px 10px; border-radius: 99px;
                font-size: 9px; font-weight: 800;
                text-transform: uppercase; letter-spacing: 0.1em;
                background: <?php echo $cat_bg; ?>;
                color: <?php echo $cat_color; ?>;
                border: 1px solid <?php echo $cat_color; ?>33;
                backdrop-filter: blur(6px);
            "><?php echo clean($d['category']); ?></div>

            <!-- Judul di atas gradient -->
            <div style="position:absolute; bottom:0; left:0; right:0; padding: 14px 16px;">
                <h3 style="
                    font-size: 15px; font-weight: 900;
                    color: #ffffff; line-height: 1.3;
                    display: -webkit-box; -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical; overflow: hidden;
                    text-shadow: 0 1px 4px rgba(0,0,0,0.5);
                "><?php echo clean($d['title']); ?></h3>
            </div>
        </div>

        <!-- Bottom: meta info -->
        <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 12px; border-top: 1px solid rgba(255,255,255,0.06);">

            <!-- Tech tags -->
            <?php if (!empty($stacks)): ?>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php foreach ($stacks as $t): ?>
                <span style="
                    padding: 3px 9px;
                    background: rgba(255,255,255,0.06);
                    border: 1px solid rgba(255,255,255,0.1);
                    border-radius: 6px;
                    font-size: 10px; font-weight: 600;
                    color: rgba(255,255,255,0.55);
                "><?php echo $t; ?></span>
                <?php endforeach; ?>
                <?php if ($extra > 0): ?>
                <span style="font-size:10px; color:rgba(255,255,255,0.3); padding: 3px 4px;">+<?php echo $extra; ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- CTA row -->
            <div style="
                margin-top: auto;
                display: flex; align-items: center; justify-content: space-between;
                padding-top: 12px;
                border-top: 1px solid rgba(255,255,255,0.05);
            ">
                <span style="
                    font-size: 11px; font-weight: 800;
                    color: <?php echo $cat_color; ?>;
                    text-transform: uppercase; letter-spacing: 0.08em;
                    display: flex; align-items: center; gap: 6px;
                    transition: gap 0.3s;
                " class="card-cta-text">
                    View Case Study <i class="bi bi-arrow-right" style="font-size: 11px;"></i>
                </span>
                <div style="
                    width: 28px; height: 28px; border-radius: 50%;
                    background: rgba(255,255,255,0.05);
                    border: 1px solid rgba(255,255,255,0.08);
                    display: flex; align-items: center; justify-content: center;
                    color: rgba(255,255,255,0.3);
                    font-size: 11px;
                    transition: all 0.3s;
                " class="card-arrow-btn">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.project-card-inner:hover {
    transform: translateY(-6px) !important;
    border-color: rgba(255,255,255,0.15) !important;
    box-shadow: 0 20px 50px rgba(0,0,0,0.4) !important;
}
.project-card-inner:hover img {
    opacity: 0.75 !important;
    transform: scale(1.05) !important;
}
.group:hover .card-arrow-btn {
    background: #2563EB !important;
    border-color: #2563EB !important;
    color: white !important;
}
</style>