<section id="projects" class="py-12 md:py-20 mt-0 md:mt-12 max-w-7xl mx-auto px-4 relative z-10" data-aos="fade-up">
        
        <div class="island-box bg-primary text-white relative overflow-hidden shadow-2xl">
            
            <div class="absolute top-0 right-0 w-96 h-96 bg-accent/20 blur-[100px] rounded-full pointer-events-none"></div>
            
            <div class="mb-6 relative z-10 text-center md:text-left">
                <h2 class="text-3xl md:text-5xl font-black mb-2"><?php echo $txt['sect_proj_title']; ?></h2>
                <p class="text-gray-400 max-w-xl text-sm md:text-base"><?php echo $txt['sect_proj_desc']; ?></p>
            </div>

            <div class="flex gap-2 mb-6 relative z-10 overflow-x-auto pb-2 scrollbar-hide">
                <button onclick="switchTab('work')" id="btn-work" class="tab-btn active px-5 py-2 rounded-full text-xs md:text-sm font-bold bg-white text-primary transition-all border border-transparent shadow-md"><i class="bi bi-briefcase-fill me-2"></i> <?php echo $txt['tab_work']; ?></button>
                <button onclick="switchTab('personal')" id="btn-personal" class="tab-btn px-5 py-2 rounded-full text-xs md:text-sm font-bold bg-white/10 text-white hover:bg-white/20 transition-all border border-white/10"><i class="bi bi-code-square me-2"></i> <?php echo $txt['tab_personal']; ?></button>
            </div>

            <div class="relative w-full z-10">
                <div id="list-work" class="flex flex-nowrap gap-5 overflow-x-auto pb-6 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php foreach($projects as $idx => $d): if(strtolower($d['category']) == 'work'): ?>
                    <div class="flex-none w-[300px] md:w-[380px] snap-center group cursor-pointer" onclick="openProjectModal(<?php echo $idx; ?>)">
                        <div class="bg-gray-800 rounded-[2rem] border border-white/10 overflow-hidden relative shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 h-full flex flex-col">
                            <div class="relative h-44 md:h-52 overflow-hidden flex-none">
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent z-10 opacity-60"></div>
                                <img src="<?php echo $d['image_url']; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                                <div class="absolute top-4 right-4 z-20"><span class="bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">WORK</span></div>
                            </div>
                            <div class="p-5 flex flex-col flex-1">
                                <h3 class="text-lg font-bold mb-2 group-hover:text-accent transition line-clamp-2 leading-tight"><?php echo clean($d['title']); ?></h3>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if(!empty($d['tech_stack'])) { foreach(explode(',', $d['tech_stack']) as $t): if(trim($t) == '') continue; ?>
                                    <span class="text-[10px] font-bold bg-black/30 text-gray-300 px-2 py-1 rounded-md border border-white/5"><?php echo clean(trim($t)); ?></span>
                                    <?php endforeach; } ?>
                                </div>
                                <div class="mt-auto flex items-center gap-2 text-accent text-xs font-bold uppercase tracking-wider group-hover:translate-x-2 transition"><?php echo $txt['read_more']; ?> <i class="bi bi-arrow-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>

                <div id="list-personal" class="hidden flex flex-nowrap gap-5 overflow-x-auto pb-6 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php foreach($projects as $idx => $d): if(strtolower($d['category']) == 'personal'): ?>
                    <div class="flex-none w-[300px] md:w-[380px] snap-center group cursor-pointer" onclick="openProjectModal(<?php echo $idx; ?>)">
                        <div class="bg-gray-800 rounded-[2rem] border border-white/10 overflow-hidden relative shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 h-full flex flex-col">
                            <div class="relative h-44 md:h-52 overflow-hidden flex-none">
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent z-10 opacity-60"></div>
                                <img src="<?php echo $d['image_url']; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                                <div class="absolute top-4 right-4 z-20"><span class="bg-accent/20 backdrop-blur-md border border-accent/30 text-accent text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">PERSONAL</span></div>
                            </div>
                            <div class="p-5 flex flex-col flex-1">
                                <h3 class="text-lg font-bold mb-2 group-hover:text-accent transition line-clamp-2 leading-tight"><?php echo clean($d['title']); ?></h3>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if(!empty($d['tech_stack'])) { foreach(explode(',', $d['tech_stack']) as $t): if(trim($t) == '') continue; ?>
                                    <span class="text-[10px] font-bold bg-black/30 text-gray-300 px-2 py-1 rounded-md border border-white/5"><?php echo clean(trim($t)); ?></span>
                                    <?php endforeach; } ?>
                                </div>
                                <div class="mt-auto flex items-center gap-2 text-accent text-xs font-bold uppercase tracking-wider group-hover:translate-x-2 transition"><?php echo $txt['read_more']; ?> <i class="bi bi-arrow-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </section>