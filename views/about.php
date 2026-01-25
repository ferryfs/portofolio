<section id="about" class="pt-8 pb-12 md:pt-16 md:pb-20 max-w-7xl mx-auto px-6">
        
        <div class="text-center mb-10" data-aos="fade-up">
            <span class="text-accent font-bold tracking-widest text-xs uppercase mb-3 block"><?php echo $txt['sect_about']; ?></span>
            <h2 class="text-4xl md:text-5xl font-black text-primary leading-tight"><?php echo $txt['about_title']; ?></h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:h-[600px]">
            
            <div class="flex flex-col gap-4 h-full" data-aos="fade-right">
                
                <div class="flex-grow basis-0 w-full cursor-zoom-in relative group overflow-hidden rounded-[2rem] border-4 border-white bg-gray-200 shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_1']; ?>')">
                    <img src="assets/img/<?php echo $p['about_img_1']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700" loading="lazy">
                </div>
                
                <div class="h-40 md:h-48 shrink-0 grid grid-cols-2 gap-4">
                    <div class="h-full w-full cursor-zoom-in rounded-2xl overflow-hidden shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_2']; ?>')">
                        <img src="assets/img/<?php echo $p['about_img_2']; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500 bg-gray-200" loading="lazy">
                    </div>
                    <div class="h-full w-full cursor-zoom-in rounded-2xl overflow-hidden shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_3']; ?>')">
                        <img src="assets/img/<?php echo $p['about_img_3']; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500 bg-gray-200" loading="lazy">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 md:p-8 rounded-[2.5rem] shadow-glass border border-white/60 relative flex flex-col h-[500px] lg:h-full overflow-hidden" data-aos="fade-left">
                
                <div class="flex-none pb-4 border-b border-gray-100 mb-4 bg-white sticky top-0 z-10">
                    <h4 class="text-xl font-black flex items-center gap-3">
                        <div class="p-2 bg-blue-50 text-accent rounded-lg"><i class="bi bi-briefcase-fill"></i></div> 
                        <?php echo $txt['career_title']; ?>
                    </h4>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scroll pr-2 min-h-0 relative">
                    <?php 
                    $careers = [];
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
                    <?php endforeach; ?>
                    <div class="h-4"></div>
                </div>
            </div>
        </div>
    </section>