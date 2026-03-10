<section id="contact" class="py-24 md:py-40 text-center relative overflow-hidden" data-aos="zoom-in">

    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[600px] rounded-full pointer-events-none"
         style="background: radial-gradient(ellipse, rgba(37,99,235,0.08) 0%, transparent 70%);"></div>

    <div class="absolute inset-0 pointer-events-none opacity-30"
         style="background-image: radial-gradient(circle, #00000012 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="max-w-3xl mx-auto px-6 relative z-10">

        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-xs font-black uppercase tracking-widest text-gray-500 mb-8">
            <i class="bi bi-chat-dots text-accent"></i> Let's Connect
        </div>

        <h2 class="font-black text-primary mb-6 tracking-tight leading-[0.95]"
            style="font-size: clamp(2.5rem, 8vw, 5.5rem); letter-spacing: -0.04em;">
            <?php echo $txt['sect_contact_1']; ?><br>
            <span class="text-gradient"><?php echo $txt['sect_contact_2']; ?></span>
        </h2>

        <p class="text-lg text-gray-500 mb-12 max-w-lg mx-auto leading-relaxed"><?php echo $txt['contact_sub']; ?></p>

        <div class="flex flex-wrap justify-center gap-4">
            <!-- Email -->
            <a href="mailto:<?php echo sanitizeLink($p['email'] ?? '', 'email'); ?>"
               class="contact-btn-primary">
                <i class="bi bi-envelope-fill"></i> <?php echo $txt['btn_email']; ?>
            </a>

            <!-- WhatsApp -->
            <?php if (!empty($p['whatsapp'])): ?>
            <a href="https://wa.me/<?php echo sanitizeLink($p['whatsapp'], 'number'); ?>"
               target="_blank"
               class="contact-btn-secondary">
                <i class="bi bi-whatsapp text-green-500"></i> WhatsApp
            </a>
            <?php endif; ?>

            <!-- LinkedIn — ambil langsung dari DB, no condition yang bisa miss -->
            <?php
            $linkedin_url = $p['linkedin'] ?? '';
            if (empty($linkedin_url)) {
                // Fallback: coba query ulang kalau $p tidak lengkap
                try {
                    $stmt_li = $pdo->query("SELECT linkedin FROM profile LIMIT 1");
                    $row_li = $stmt_li->fetch(PDO::FETCH_ASSOC);
                    $linkedin_url = $row_li['linkedin'] ?? '';
                } catch (Exception $e) {
                    $linkedin_url = '';
                }
            }
            ?>
            <?php if (!empty($linkedin_url)): ?>
            <a href="<?php echo clean($linkedin_url); ?>"
               target="_blank"
               class="contact-btn-secondary">
                <i class="bi bi-linkedin text-blue-600"></i> LinkedIn
            </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($p['email'])): ?>
        <p class="mt-10 text-sm text-gray-400 font-medium">
            or reach out directly at
            <a href="mailto:<?php echo sanitizeLink($p['email'], 'email'); ?>"
               class="text-accent font-bold hover:underline">
                <?php echo clean($p['email']); ?>
            </a>
        </p>
        <?php endif; ?>

    </div>
</section>