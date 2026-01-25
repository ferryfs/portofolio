<section id="skills" class="pt-0 pb-8 md:pt-10 md:pb-16 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box">
            <div class="text-center mb-16">
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block"><?php echo $txt['sect_skills_label']; ?></span>
                <h2 class="text-4xl md:text-5xl font-black text-primary"><?php echo $txt['sect_skills']; ?></h2>
            </div>
            <?php
            $skills = ['Analysis' => [], 'Enterprise' => [], 'Development' => []];
            foreach($skillsData as $s) {
                $cat_db = trim($s['category']); 
                if(in_array($cat_db, ['Analysis', 'Jira', 'Design'])) $skills['Analysis'][] = $s; 
                else if(in_array($cat_db, ['Enterprise', 'System', 'SAP'])) $skills['Enterprise'][] = $s; 
                else $skills['Development'][] = $s;
            }
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach(['Analysis', 'Enterprise', 'Development'] as $cat): 
                    $num = ($cat == 'Analysis' ? 1 : ($cat == 'Enterprise' ? 2 : 3));
                    $desc_key = 'bento_desc_'.$num;
                    $title_key = 'bento_title_'.$num;
                    
                    // Panggil data dengan @ untuk suppress warning kalau kolom kosong
                    $desc_val = $is_en ? db_val(@$p[$desc_key.'_en'], '') : db_val(@$p[$desc_key], '');
                    $title_val = db_val(@$p[$title_key], $cat); // Default ke nama kategori kalau kosong
                    
                    $icon = $cat == 'Analysis' ? 'bi-diagram-3' : ($cat == 'Enterprise' ? 'bi-kanban' : 'bi-code-slash');
                    $color = $cat == 'Analysis' ? 'text-accent' : ($cat == 'Enterprise' ? 'text-green-500' : 'text-purple-500');
                ?>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl <?php echo $color; ?> mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi <?php echo $icon; ?>"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $title_val; ?></h3>
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow"><p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $desc_val; ?></p></div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills[$cat] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm transition-all"><i class="<?php echo clean($item['icon']); ?>"></i> <?php echo clean($item['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>