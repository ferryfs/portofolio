<section id="home" class="min-h-screen flex items-center relative py-20 overflow-hidden">
    <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-blue-50 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/3 pointer-events-none opacity-70"></div>
    
    <div class="max-w-7xl mx-auto px-6 w-full relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            
            <div data-aos="fade-right" data-aos-duration="1000" class="-mt-20 lg:-mt-32 order-2 lg:order-1">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-gray-200 shadow-sm text-secondary text-[10px] font-bold uppercase tracking-widest mb-6">
                    <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span>
                    <?php echo $txt['status_avail']; ?>
                </div>
                
                <div class="mb-6">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4"> 
                        <span class="text-accent"><?php echo $txt['hero_pre']; ?></span> <br class="md:hidden">
                        <span class="text-primary"><?php echo $txt['hero_greeting']; ?></span>,
                    </h2>
                    <h1 class="text-5xl md:text-7xl font-black leading-[1.1] text-primary tracking-tight">
                        <?php echo str_replace('| ', '| <br class="hidden md:block">', $txt['hero_title_raw']); ?>
                    </h1>
                </div>

                <div class="relative w-full max-w-xs mx-auto my-10 lg:hidden flex justify-center order-1 lg:order-2" data-aos="fade-up">
                    <div class="relative w-full z-10">
                        <div class="rounded-[2.5rem] overflow-hidden shadow-2xl rotate-2 border-[6px] border-white bg-white relative z-10">
                            <img src="<?php echo $foto_profile; ?>" alt="Profile" class="w-full h-auto object-cover" loading="lazy">
                        </div>
                    </div>
                </div>

                <div class="flex gap-6 mb-10 border-l-4 border-accent pl-6 mt-4 lg:mt-0">
                    <p class="text-lg text-gray-600 leading-relaxed max-w-lg font-medium"><?php echo $txt['hero_desc']; ?></p>
                </div>
                <div class="flex flex-wrap gap-4">
                    <a href="#projects" class="bg-primary text-white px-8 py-3.5 rounded-full font-bold text-sm hover:bg-accent transition shadow-lg hover:shadow-glow hover:-translate-y-1"><?php echo $txt['btn_port']; ?></a>
                    <a href="<?php echo $cv_url; ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-8 py-3.5 rounded-full font-bold text-sm hover:bg-gray-50 transition shadow-sm hover:-translate-y-1"><?php echo $txt['btn_cv']; ?></a>
                </div>
            </div>
            
            <div class="relative hidden lg:flex justify-end order-1 lg:order-2" data-aos="fade-left" data-aos-duration="1200">
                <div class="relative w-full max-w-md z-10">
                    <div class="rounded-[3rem] overflow-hidden shadow-2xl rotate-2 hover:rotate-0 transition duration-700 ease-out border-[8px] border-white bg-white">
                        <img src="<?php echo $foto_profile; ?>" alt="Profile" class="w-full h-auto object-cover grayscale hover:grayscale-0 transition duration-700" loading="lazy">
                    </div>
                    
                    <div class="absolute top-10 right-6 flex flex-col gap-4 z-20">
                        <div class="w-24 h-24 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_3s_infinite] p-2">
                            <span class="text-3xl font-black text-primary leading-none mb-1"><?php echo $p['years_exp']; ?>+</span>
                            <span class="text-[9px] font-bold text-gray-400 uppercase text-center leading-none"><?php echo $txt['stat_exp']; ?></span>
                        </div>
                        <div class="w-24 h-24 bg-primary rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-800 animate-[bounce_3.5s_infinite] p-2">
                            <span class="text-3xl font-black text-white leading-none mb-1"><?php echo $p['projects_done']; ?>+</span>
                            <span class="text-[9px] font-bold text-gray-400 uppercase text-center leading-none"><?php echo $txt['stat_proj']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[110%] h-[110%] border border-dashed border-gray-300 rounded-full -z-10 animate-[spin_30s_linear_infinite] opacity-50"></div>
            </div>

        </div>
    </div>
</section>