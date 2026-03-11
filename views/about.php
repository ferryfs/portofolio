<section id="about" class="pt-8 pb-12 md:pt-16 md:pb-20 max-w-7xl mx-auto px-6">
    <div class="text-center mb-10" data-aos="fade-up">
        <span class="text-accent font-bold tracking-widest text-xs uppercase mb-3 block"><?php echo $txt['sect_about']; ?></span>
        <h2 class="text-4xl md:text-5xl font-black text-primary leading-tight"><?php echo $txt['about_title']; ?></h2>
    </div>

    <?php
    function getSafeImg($db_filename) {
        if (empty($db_filename)) return 'assets/img/default.jpg';
        $physical_path = __DIR__ . '/../assets/img/' . $db_filename;
        return file_exists($physical_path) ? 'assets/img/' . $db_filename : 'assets/img/default.jpg';
    }
    $img1 = getSafeImg($p['about_img_1'] ?? '');
    $img2 = getSafeImg($p['about_img_2'] ?? '');
    $img3 = getSafeImg($p['about_img_3'] ?? '');
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:h-[600px]">
        
        <!-- FOTO GRID -->
        <div data-aos="fade-right" class="flex flex-col gap-3 h-full">
            <!-- Foto 1 — besar -->
            <div class="w-full rounded-[2rem] overflow-hidden border-4 border-white shadow-sm bg-gray-200 cursor-zoom-in group"
                 style="height: 260px; md:flex-grow:1;"
                 onclick="openImageModal('<?php echo $img1; ?>')">
                <img src="<?php echo $img1; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700" style="object-position: center 30%;" loading="lazy">
            </div>
            <!-- Foto 2 & 3 — side by side, always visible -->
            <div class="grid grid-cols-2 gap-3" style="height: 160px;">
                <div class="rounded-2xl overflow-hidden shadow-sm bg-gray-200 cursor-zoom-in h-full"
                     onclick="openImageModal('<?php echo $img2; ?>')">
                    <img src="<?php echo $img2; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy">
                </div>
                <div class="rounded-2xl overflow-hidden shadow-sm bg-gray-200 cursor-zoom-in h-full"
                     onclick="openImageModal('<?php echo $img3; ?>')">
                    <img src="<?php echo $img3; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy">
                </div>
            </div>
        </div>

        <!-- CAREER JOURNEY -->
        <div class="bg-white p-6 md:p-8 rounded-[2.5rem] shadow-glass border border-white/60 relative flex flex-col h-[480px] lg:h-full overflow-hidden" data-aos="fade-left">
            
            <div class="flex-none pb-4 border-b border-gray-100 mb-4 bg-white sticky top-0 z-10">
                <h4 class="text-xl font-black flex items-center gap-3">
                    <div class="p-2 bg-blue-50 text-accent rounded-lg"><i class="bi bi-briefcase-fill"></i></div> 
                    <?php echo $txt['career_title']; ?>
                </h4>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scroll pr-2 min-h-0 relative">
                <?php 
                $careers = [];
                if (!empty($timelineData)) {
                    foreach($timelineData as $row) { $careers[$row['company']][] = $row; }
                    foreach($careers as $company => $roles): 
                    ?>
                    <div class="group bg-gray-50 hover:bg-white hover:shadow-xl border border-gray-100 rounded-2xl p-5 cursor-pointer transition-all duration-300 transform hover:-translate-y-1 hover:border-accent/30 mb-3" onclick="openCareerModal('<?php echo clean($company); ?>')">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold bg-accent/10 text-accent px-3 py-1 rounded-full"><?php echo clean($roles[0]['year']); ?></span>
                            <i class="bi bi-arrow-right-circle-fill text-gray-300 group-hover:text-accent text-2xl transition"></i>
                        </div>
                        <h5 class="text-lg font-black text-primary mb-1"><?php echo clean($company); ?></h5>
                        <p class="text-sm text-gray-500 font-medium mb-3 line-clamp-1"><?php echo clean($roles[0]['role']); ?></p>
                        <span class="text-xs font-bold text-accent border-b border-accent/20 group-hover:border-accent pb-0.5 transition"><?php echo $txt['read_more']; ?></span>
                    </div>
                    <?php endforeach; 
                } else {
                    echo '<p class="text-center text-gray-400 py-10">Belum ada data karir.</p>';
                }
                ?>
                <div class="h-4"></div>
            </div>
        </div>
    </div>
</section>