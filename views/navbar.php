<div class="fixed top-6 right-6 z-50 animate-[fadeIn_1s]">
        <div class="glass-nav rounded-full p-1.5 shadow-lg flex items-center gap-1">
            <a href="?lang=id" class="lang-btn <?php echo !$is_en ? 'active' : ''; ?>">ID</a>
            <a href="?lang=en" class="lang-btn <?php echo $is_en ? 'active' : ''; ?>">EN</a>
        </div>
    </div>

    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 w-[90%] max-w-sm">
        <nav class="glass-nav px-8 py-4 rounded-full flex justify-between items-center transition-all duration-300 hover:-translate-y-1">
            <a href="#home" class="text-secondary hover:text-accent transition text-xl"><i class="bi bi-house"></i></a>
            <a href="#about" class="text-xs font-bold text-secondary hover:text-accent transition uppercase tracking-wider">About</a>
            <a href="#skills" class="text-xs font-bold text-secondary hover:text-accent transition uppercase tracking-wider">Skills</a>
            <a href="#projects" class="text-xs font-bold text-secondary hover:text-accent transition uppercase tracking-wider">Work</a>
            <a href="#contact" class="text-secondary hover:text-accent transition text-xl"><i class="bi bi-chat-text"></i></a>
        </nav>
    </div>