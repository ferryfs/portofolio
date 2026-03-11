<section id="skills" class="pt-0 pb-6 md:pt-4 md:pb-6 max-w-7xl mx-auto px-4" data-aos="fade-up">
    <div class="island">
        <div class="text-center mb-10 md:mb-16">
            <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block"><?php echo $txt['sect_skills_label']; ?></span>
            <h2 class="text-3xl md:text-5xl font-black text-primary"><?php echo $txt['sect_skills']; ?></h2>
        </div>
        
        <?php
        $skill_groups = ['Analysis' => [], 'Enterprise' => [], 'Development' => []];
        if (!empty($skillsData)) {
            foreach($skillsData as $s) {
                $cat_db = strtolower(trim($s['category'])); 
                if(strpos($cat_db, 'analy') !== false || strpos($cat_db, 'jira') !== false || strpos($cat_db, 'design') !== false) {
                    $skill_groups['Analysis'][] = $s; 
                } else if(strpos($cat_db, 'enter') !== false || strpos($cat_db, 'sys') !== false || strpos($cat_db, 'sap') !== false) {
                    $skill_groups['Enterprise'][] = $s; 
                } else {
                    $skill_groups['Development'][] = $s;
                }
            }
        }
        ?>
        
        <!-- Mobile: stack vertical, Desktop: 3 col -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <?php foreach(['Analysis', 'Enterprise', 'Development'] as $cat): 
                $num = ($cat == 'Analysis' ? 1 : ($cat == 'Enterprise' ? 2 : 3));
                $title_key = 'bento_title_'.$num;
                $title_val = !empty($p[$title_key]) ? $p[$title_key] : $cat;
                $desc_val = $txt['bento_desc_'.$num];
                $icon = $cat == 'Analysis' ? 'bi-diagram-3' : ($cat == 'Enterprise' ? 'bi-kanban' : 'bi-code-slash');
                $color = $cat == 'Analysis' ? 'text-accent' : ($cat == 'Enterprise' ? 'text-green-500' : 'text-purple-500');
                $bg_color = $cat == 'Analysis' ? 'bg-blue-50' : ($cat == 'Enterprise' ? 'bg-green-50' : 'bg-purple-50');
            ?>
            <div class="group p-5 md:p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 flex flex-col border border-transparent hover:border-white/10">
                
                <!-- Icon — pakai inline style biar ga hilang di mobile -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-5 shadow-sm group-hover:scale-110 transition <?php echo $bg_color; ?> <?php echo $color; ?>">
                    <i class="bi <?php echo $icon; ?>"></i>
                </div>
                
                <!-- Title -->
                <h3 class="text-lg md:text-xl font-bold mb-2"><?php echo $title_val; ?></h3>
                
                <!-- Desc -->
                <div class="border-l-2 border-accent pl-4 mb-5">
                    <p class="text-xs md:text-sm text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $desc_val; ?></p>
                </div>
                
                <!-- Skills tags -->
                <div class="flex flex-wrap gap-2 mt-auto">
                    <?php if(!empty($skill_groups[$cat])): ?>
                        <?php foreach($skill_groups[$cat] as $item): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md bg-white border border-gray-200 text-[10px] md:text-xs font-bold text-gray-700 shadow-sm transition-all group-hover:bg-white/10 group-hover:border-white/10 group-hover:text-white">
                            <?php if(!empty($item['icon'])): ?>
                            <i class="<?php echo clean($item['icon']); ?>"></i>
                            <?php endif; ?>
                            <?php echo clean($item['name']); ?>
                        </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-[10px] text-gray-400 italic">No skills added yet.</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>