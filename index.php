<?php 
// ==========================================
// 1. BACKEND LOGIC (SERVER SIDE)
// ==========================================

// Error Reporting (Matikan saat production)
error_reporting(E_ALL); 
ini_set('display_errors', 0);

// Koneksi Database
require_once 'koneksi.php'; 

// Helper: XSS Protection (Wajib biar ga di-hack lewat input CMS)
function clean($str) { 
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); 
}

// Ambil Data Utama Profile
$query = mysqli_query($conn, "SELECT * FROM profile WHERE id=1");

// Error Handling: Kalau data kosong/DB error, pake data dummy biar web tetep jalan
if ($query && mysqli_num_rows($query) > 0) {
    $p = mysqli_fetch_assoc($query);
} else {
    // Fallback Data (Jaga-jaga)
    $p = [
        'hero_title' => 'System Analyst & Developer',
        'hero_desc' => 'Database connection issue. Please check CMS.',
        'about_title' => 'Analyst & Leader.',
        'about_text' => 'IT Supervisor & Functional Analyst',
        'profile_pic' => '',
        'email' => '#',
        'whatsapp' => '#',
        'linkedin' => '#'
    ];
}

// Variable Mapping (Biar kodingan HTML di bawah bersih)
$judul_besar    = $p['about_title']; 
$subtitle_biru  = $p['about_text']; 
$deskripsi_hero = $p['hero_desc'];  

// Asset Logic
$foto_profile = !empty($p['profile_pic']) && file_exists("assets/img/".$p['profile_pic']) 
                ? "assets/img/".$p['profile_pic'] 
                : "https://via.placeholder.com/600x750?text=Profile+Image";

$cv_url = (!empty($p['cv_link']) && strpos($p['cv_link'], 'http') !== false) 
          ? $p['cv_link'] 
          : "assets/doc/" . ($p['cv_link'] ?? '#');
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo clean(substr($deskripsi_hero, 0, 150)); ?>">
    <meta name="author" content="Ferry Fernando">
    
    <title>Ferry Fernando | Tech Architect & Analyst</title>
    
    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,400&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Satoshi', 'sans-serif'] },
                    colors: {
                        bg: '#F3F4F6',       /* Warm Grey Background */
                        primary: '#111827',  /* Hitam Premium */
                        secondary: '#4B5563',/* Abu Text */
                        accent: '#4F46E5',   /* Indigo Premium */
                        paper: '#FFFFFF',
                    },
                    boxShadow: {
                        'island': '0 0 0 1px rgba(0,0,0,0.03), 0 2px 8px rgba(0,0,0,0.04), 0 12px 24px rgba(0,0,0,0.04)',
                        'float': '0 20px 40px -10px rgba(0,0,0,0.15)',
                    }
                }
            }
        }
    </script>

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body { background-color: #F3F4F6; color: #111827; overflow-x: hidden; }
        
        /* Island Bubble Box */
        .island-box {
            background: white; border-radius: 2rem; padding: 3rem 2rem;
            box-shadow: var(--shadow-island); border: 1px solid rgba(255,255,255,0.8);
        }

        /* Glass Navbar */
        .nav-pill {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        /* Timeline Line */
        .timeline-container { border-left: 2px solid #E5E7EB; margin-left: 8px; padding-left: 28px; }
        .timeline-item { position: relative; padding-bottom: 35px; }
        .timeline-dot {
            position: absolute; left: -35px; top: 6px; width: 12px; height: 12px;
            background: #4F46E5; border-radius: 50%; box-shadow: 0 0 0 4px #E0E7FF;
        }
    </style>
</head>

<body class="antialiased">

    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-md">
        <nav class="nav-pill px-6 py-3 rounded-full flex justify-between items-center shadow-2xl">
            <a href="#home" class="text-sm font-bold text-gray-500 hover:text-accent transition"><i class="bi bi-house-door-fill text-xl"></i></a>
            <a href="#about" class="text-sm font-bold text-gray-500 hover:text-accent transition">About</a>
            <a href="#skills" class="text-sm font-bold text-gray-500 hover:text-accent transition">Expertise</a>
            <a href="#projects" class="text-sm font-bold text-gray-500 hover:text-accent transition">Work</a>
            <a href="#contact" class="bg-primary text-white p-2.5 rounded-full hover:bg-accent transition shadow-lg"><i class="bi bi-envelope-fill"></i></a>
        </nav>
    </div>

    <section id="home" class="min-h-screen flex items-center relative py-20 overflow-hidden">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-indigo-200/20 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 w-full relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                
                <div data-aos="fade-up" data-aos-duration="1000">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-secondary text-xs font-bold uppercase tracking-wider mb-8">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Available for Projects
                    </div>
                    
                    <h1 class="text-6xl md:text-8xl font-black leading-[0.95] mb-8 text-primary tracking-tighter">
                        <?php echo $p['hero_title']; ?>
                    </h1>
                    
                    <p class="text-xl text-secondary mb-10 leading-relaxed max-w-lg font-medium">
                        <?php echo $deskripsi_hero; ?>
                    </p>
                    
                    <div class="flex flex-wrap gap-4">
                        <a href="#projects" class="bg-primary text-white px-8 py-4 rounded-full font-bold hover:bg-accent transition shadow-lg hover:-translate-y-1">
                            Lihat Portfolio
                        </a>
                        <a href="<?php echo $cv_url; ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-8 py-4 rounded-full font-bold hover:bg-gray-50 transition shadow-sm">
                            Unduh CV
                        </a>
                    </div>
                </div>

                <div class="relative group lg:pl-10" data-aos="fade-left" data-aos-delay="200" data-aos-duration="1000">
                    <div class="relative z-10 rounded-[3rem] overflow-hidden shadow-float rotate-2 group-hover:rotate-0 transition duration-700 ease-out border-[6px] border-white bg-white">
                        <img src="<?php echo $foto_profile; ?>" alt="Profile Picture" class="w-full h-auto object-cover aspect-[4/5] scale-105 group-hover:scale-100 transition duration-700">
                    </div>
                    
                    <div class="absolute -bottom-6 -left-6 bg-white p-5 rounded-2xl shadow-xl z-20 animate-bounce hidden md:block" style="animation-duration: 3s;">
                        <div class="flex items-center gap-3">
                            <div class="bg-green-100 p-2 rounded-full text-green-600"><i class="bi bi-check-lg text-xl"></i></div>
                            <div>
                                <span class="block text-2xl font-black text-primary leading-none">15+</span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Projects Done</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="skills" class="py-10 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box">
            <div class="text-center mb-16">
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block">Kompetensi Utama</span>
                <h2 class="text-4xl md:text-5xl font-black text-primary">Technical Arsenal</h2>
            </div>

            <?php
            // FETCH SKILL ICONS
            $skills = ['Analysis' => [], 'Enterprise' => [], 'Development' => []];
            $q_skill = mysqli_query($conn, "SELECT * FROM tech_stacks");
            if(mysqli_num_rows($q_skill) > 0) {
                while($s = mysqli_fetch_assoc($q_skill)) {
                    $cat_db = trim($s['category']); 
                    // Mapping Categories
                    if(in_array($cat_db, ['Analysis', 'Jira', 'Design'])) { $skills['Analysis'][] = $s; } 
                    else if(in_array($cat_db, ['Enterprise', 'System', 'SAP'])) { $skills['Enterprise'][] = $s; } 
                    else { $skills['Development'][] = $s; }
                }
            }
            ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl text-accent mb-6 shadow-sm group-hover:scale-110 transition">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $p['bento_title_1']; ?></h3>
                    <p class="text-sm text-gray-500 group-hover:text-gray-400 mb-6 leading-relaxed flex-grow">
                        <?php echo $p['bento_desc_1']; ?>
                    </p>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Analysis'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-xs font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all">
                            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl text-green-500 mb-6 shadow-sm group-hover:scale-110 transition">
                        <i class="bi bi-kanban"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $p['bento_title_2']; ?></h3>
                    <p class="text-sm text-gray-500 group-hover:text-gray-400 mb-6 leading-relaxed flex-grow">
                        <?php echo $p['bento_desc_2']; ?>
                    </p>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Enterprise'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-xs font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all">
                            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl text-purple-500 mb-6 shadow-sm group-hover:scale-110 transition">
                        <i class="bi bi-code-slash"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $p['bento_title_3']; ?></h3>
                    <p class="text-sm text-gray-500 group-hover:text-gray-400 mb-6 leading-relaxed flex-grow">
                        <?php echo $p['bento_desc_3']; ?>
                    </p>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Development'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-xs font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all">
                            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="about" class="py-24 max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <div class="lg:col-span-5 sticky top-10" data-aos="fade-right">
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-4 block">About Me</span>
                <h2 class="text-5xl md:text-6xl font-black text-primary leading-none mb-8">
                    <?php echo $judul_besar; ?>
                </h2>
                
                <img src="assets/img/<?php echo $p['about_img_1']; ?>" class="w-full rounded-[2rem] shadow-2xl rotate-[-1deg] hover:rotate-0 transition duration-500 border-4 border-white mb-6">
                
                <div class="grid grid-cols-2 gap-4">
                    <img src="assets/img/<?php echo $p['about_img_2']; ?>" class="rounded-2xl shadow-md border-2 border-white hover:scale-105 transition">
                    <img src="assets/img/<?php echo $p['about_img_3']; ?>" class="rounded-2xl shadow-md border-2 border-white hover:scale-105 transition">
                </div>
            </div>

            <div class="lg:col-span-7 lg:pt-4" data-aos="fade-left">
                
                <h3 class="text-2xl md:text-3xl font-bold text-accent leading-relaxed mb-12">
                    <?php echo $subtitle_biru; ?>
                </h3>

                <div class="bg-white p-8 rounded-[2.5rem] shadow-island border border-white/60">
                    <h4 class="text-xl font-black mb-8 flex items-center gap-3">
                        <i class="bi bi-briefcase-fill text-accent"></i> Perjalanan Karir
                    </h4>
                    
                    <div class="timeline-container">
                        <?php 
                        $q_time = mysqli_query($conn, "SELECT * FROM timeline ORDER BY id DESC"); 
                        // Error handling timeline
                        if($q_time):
                            while($row = mysqli_fetch_assoc($q_time)): 
                        ?>
                        <div class="timeline-item group">
                            <div class="timeline-dot group-hover:scale-125 transition duration-300"></div>
                            
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2">
                                <h5 class="text-lg font-bold text-primary group-hover:text-accent transition">
                                    <?php echo $row['role']; ?>
                                </h5>
                                <span class="text-xs font-bold bg-indigo-50 text-accent px-3 py-1 rounded-full mt-1 sm:mt-0 w-fit border border-indigo-100">
                                    <?php echo $row['year']; ?>
                                </span>
                            </div>
                            
                            <div class="text-sm font-bold text-gray-400 mb-3 uppercase tracking-wider">
                                <?php echo $row['company']; ?>
                            </div>
                            
                            <p class="text-sm text-gray-600 leading-relaxed">
                                <?php echo strip_tags($row['description']); ?>
                            </p>
                        </div>
                        <?php 
                            endwhile; 
                        endif;
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section id="projects" class="py-10 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box bg-primary text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-accent/20 blur-[80px] rounded-full pointer-events-none"></div>

            <div class="flex justify-between items-end mb-12 relative z-10">
                <div>
                    <h2 class="text-4xl md:text-5xl font-black mb-2">Selected Works</h2>
                    <p class="text-gray-400">Proyek pilihan yang saya bangun dengan logika & passion.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
                <?php 
                $q_proj = mysqli_query($conn, "SELECT * FROM projects ORDER BY id DESC LIMIT 6"); 
                if($q_proj):
                    while ($d = mysqli_fetch_assoc($q_proj)): 
                ?>
                <div class="group cursor-pointer" onclick="openModal('<?php echo clean($d['title']);?>', '<?php echo clean($d['description']);?>', 'assets/img/<?php echo $d['image'];?>', '<?php echo clean($d['link_demo']);?>')">
                    
                    <div class="overflow-hidden rounded-3xl mb-5 border border-white/10 relative shadow-lg">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition z-10"></div>
                        <img src="assets/img/<?php echo $d['image']; ?>" class="w-full h-72 object-cover transform group-hover:scale-105 transition duration-700 ease-out" onerror="this.src='assets/img/default.jpg'">
                    </div>
                    
                    <div class="flex justify-between items-start px-2">
                        <div>
                            <span class="text-xs font-bold text-accent uppercase tracking-wider mb-2 block"><?php echo $d['category']; ?></span>
                            <h3 class="text-2xl font-bold group-hover:text-accent transition"><?php echo $d['title']; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-black transition">
                            <i class="bi bi-arrow-up-right text-lg"></i>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile; 
                endif;
                ?>
            </div>
        </div>
    </section>

    <section id="contact" class="py-32 text-center" data-aos="zoom-in">
        <div class="max-w-4xl mx-auto px-6">
            <h2 class="text-5xl md:text-7xl font-black text-primary mb-8 tracking-tight">
                Siap Membangun<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-accent to-purple-600">Sesuatu yang Hebat?</span>
            </h2>
            <p class="text-xl text-gray-500 mb-12 max-w-xl mx-auto">
                Saya tersedia untuk diskusi proyek baru, konsultasi sistem, atau sekadar bertegur sapa.
            </p>
            <div class="flex justify-center gap-4">
                <a href="mailto:<?php echo $p['email']; ?>" class="bg-primary text-white px-10 py-5 rounded-full font-bold text-lg hover:bg-accent transition shadow-xl hover:-translate-y-2">
                    Kirim Email
                </a>
                <a href="https://wa.me/<?php echo $p['whatsapp']; ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-10 py-5 rounded-full font-bold text-lg hover:bg-gray-50 transition shadow-md">
                    WhatsApp
                </a>
            </div>
        </div>
    </section>

    <footer class="text-center pb-32 pt-10 text-gray-400 text-sm border-t border-gray-200">
        &copy; <?php echo date('Y'); ?> Ferry Fernando. Built with Logic.
    </footer>

    <div id="projectModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-4xl rounded-[2rem] shadow-2xl overflow-hidden animate-[fadeIn_0.3s_ease-out] relative">
                <button onclick="closeModal()" class="absolute top-4 right-4 bg-white/80 hover:bg-white p-2 rounded-full z-10 transition shadow-sm"><i class="bi bi-x-lg text-xl"></i></button>
                
                <img id="modalImg" src="" class="w-full h-64 md:h-96 object-cover bg-gray-100">
                
                <div class="p-8 md:p-12">
                    <h3 id="modalTitle" class="text-3xl md:text-4xl font-black mb-6 text-primary"></h3>
                    <p id="modalDesc" class="text-lg text-gray-600 leading-relaxed mb-8"></p>
                    
                    <div class="flex gap-4">
                        <a id="modalLink" href="#" target="_blank" class="bg-primary text-white px-8 py-4 rounded-full font-bold hover:bg-accent transition">Lihat Project</a>
                        <button onclick="closeModal()" class="border border-gray-200 px-8 py-4 rounded-full font-bold hover:bg-gray-50 transition">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Init Animation
        AOS.init({ duration: 800, offset: 50, once: true, easing: 'ease-out-cubic' });

        // Modal Functions
        function openModal(title, desc, img, link) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalImg').src = img;
            let btn = document.getElementById('modalLink');
            if(link && link !== '#') btn.classList.remove('hidden'); else btn.classList.add('hidden');
            document.getElementById('projectModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Stop scroll bg
        }
        
        function closeModal() { 
            document.getElementById('projectModal').classList.add('hidden'); 
            document.body.style.overflow = 'auto'; // Resume scroll
        }
    </script>
</body>
</html>