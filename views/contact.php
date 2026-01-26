<section id="contact" class="py-32 text-center relative overflow-hidden" data-aos="zoom-in">
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-blue-100 to-purple-100 rounded-full blur-[120px] -z-10 opacity-70"></div>
    <div class="max-w-4xl mx-auto px-6 relative z-10">
        <h2 class="text-5xl md:text-7xl font-black text-primary mb-8 tracking-tight leading-tight">
            <?php echo $txt['sect_contact_1']; ?><br><span class="text-transparent bg-clip-text bg-gradient-to-r from-accent to-purple-600"><?php echo $txt['sect_contact_2']; ?></span>
        </h2>
        <p class="text-xl text-gray-500 mb-12 max-w-xl mx-auto leading-relaxed"><?php echo $txt['contact_sub']; ?></p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="mailto:<?php echo sanitizeLink($p['email'], 'email'); ?>" class="bg-primary text-white px-10 py-5 rounded-full font-bold text-lg hover:bg-accent transition shadow-xl hover:shadow-glow hover:-translate-y-1"><?php echo $txt['btn_email']; ?></a>
            <a href="https://wa.me/<?php echo sanitizeLink($p['whatsapp'], 'number'); ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-10 py-5 rounded-full font-bold text-lg hover:bg-gray-50 transition shadow-md hover:-translate-y-1">WhatsApp</a>
        </div>
    </div>
</section>