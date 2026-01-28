<section id="certificates" class="pt-0 pb-12 md:pb-20 max-w-7xl mx-auto px-6" data-aos="fade-up">
    <div class="island-box bg-white border border-gray-100 shadow-xl">
        
        <div class="text-center mb-10">
            <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block">
                <?php echo $txt['sect_skills_label']; ?> 
            </span>
            <h2 class="text-3xl md:text-5xl font-black text-primary">
                <?php echo $txt['cert_title']; ?>
            </h2>
        </div>

        <?php if (!empty($certificates)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($certificates as $cert): 
                // 1. Handle Image (Cek fisik file biar aman)
                $img_filename = $cert['image'] ?? '';
                $cert_img = !empty($img_filename) && file_exists(__DIR__ . '/../assets/img/' . $img_filename) 
                            ? 'assets/img/' . $img_filename 
                            : 'assets/img/default.jpg';
                
                // 2. Handle Link (Sesuai JSON: credential_link)
                $cert_link = $cert['credential_link'] ?? '#';
                
                // Cek validitas link
                $has_link = ($cert_link !== '#' && !empty($cert_link));
            ?>
            
            <<?php echo $has_link ? 'a href="' . $cert_link . '" target="_blank"' : 'div'; ?> 
                class="group relative bg-gray-50 rounded-2xl p-4 flex items-center gap-4 border border-transparent hover:border-accent/20 hover:bg-white hover:shadow-lg transition-all duration-300 cursor-<?php echo $has_link ? 'pointer' : 'default'; ?>">
                
                <div class="w-16 h-16 flex-none bg-white rounded-xl p-2 shadow-sm border border-gray-100 group-hover:scale-105 transition overflow-hidden flex items-center justify-center">
                    <img src="<?php echo $cert_img; ?>" class="max-w-full max-h-full object-contain" alt="Cert Logo">
                </div>

                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-primary text-sm leading-tight mb-1 group-hover:text-accent transition line-clamp-2">
                        <?php echo clean($cert['name']); ?>
                    </h4>
                    <p class="text-xs text-gray-500 mb-1 truncate">
                        <?php echo clean($cert['issuer']); ?>
                    </p>
                    
                    <span class="text-[10px] font-bold bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full inline-block">
                        <?php echo clean($cert['date_issued']); ?>
                    </span>
                </div>

                <?php if($has_link): ?>
                <div class="absolute top-4 right-4 text-gray-300 group-hover:text-accent opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
                <?php endif; ?>
            
            </<?php echo $has_link ? 'a' : 'div'; ?>>

            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="text-center py-10 bg-gray-50 rounded-2xl border border-dashed border-gray-300">
                <p class="text-gray-400 text-sm">Belum ada sertifikat.</p>
            </div>
        <?php endif; ?>

    </div>
</section>