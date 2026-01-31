<div class="flex-none w-[300px] md:w-[360px] snap-center group" 
     onclick="showProjectDetail(<?php echo $d['id']; ?>)">
    
    <div class="bg-gray-800/80 backdrop-blur-sm h-full rounded-[2rem] border border-white/10 overflow-hidden relative shadow-lg hover:shadow-2xl hover:shadow-accent/20 transition-all duration-300 hover:-translate-y-2 flex flex-col cursor-pointer group-hover:border-accent/30">
        
        <div class="relative h-48 overflow-hidden bg-gray-900 flex-none">
            <?php 
                // Logic Image URL vs Local
                $imgSrc = 'assets/img/default.jpg';
                if(!empty($d['image_url'])) { $imgSrc = $d['image_url']; }
                elseif(!empty($d['image'])) { $imgSrc = 'assets/img/'.$d['image']; }
            ?>
            <img src="<?php echo $imgSrc; ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition duration-700 opacity-90 group-hover:opacity-100" loading="lazy">
            
            <div class="absolute top-4 right-4 z-20">
                <span class="backdrop-blur-md bg-black/60 border border-white/20 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                    <?php echo $d['category']; ?>
                </span>
            </div>
            
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent opacity-60"></div>
        </div>

        <div class="p-6 flex flex-col flex-1 border-t border-white/5">
            <h3 class="text-lg font-bold text-white mb-3 group-hover:text-accent transition-colors line-clamp-2 leading-tight">
                <?php echo $d['title']; ?>
            </h3>
            
            <div class="flex flex-wrap gap-2 mb-4">
                <?php 
                if(!empty($d['tech_stack'])) { 
                    $stacks = explode(',', $d['tech_stack']);
                    $count = 0;
                    foreach($stacks as $t): 
                        if(trim($t) == '' || $count >= 3) continue; 
                ?>
                    <span class="text-[10px] font-semibold bg-gray-700/50 text-gray-300 px-2.5 py-1 rounded-lg border border-white/5">
                        <?php echo trim($t); ?>
                    </span>
                <?php 
                        $count++; 
                    endforeach; 
                    if(count($stacks) > 3) echo '<span class="text-[10px] text-gray-500 py-1 px-1">+ '.(count($stacks)-3).'</span>';
                } 
                ?>
            </div>

            <div class="mt-auto flex items-center justify-between border-t border-white/5 pt-4">
                <span class="text-accent text-xs font-bold uppercase tracking-wider flex items-center gap-2 group-hover:gap-3 transition-all">
                    View Case Study <i class="bi bi-arrow-right"></i>
                </span>
            </div>
        </div>
    </div>
</div>