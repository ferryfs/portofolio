<?php 
// ==========================================
// 🛡️ 1. SECURITY HEADERS (SATPAM WEBSITE)
// ==========================================

// Matikan error biar path gak bocor
error_reporting(E_ALL); 
ini_set('display_errors', 0);

// Anti-Clickjacking (Biar web lu gak bisa di-iframe web maling)
header("X-Frame-Options: SAMEORIGIN");

// Anti-MIME Sniffing
header("X-Content-Type-Options: nosniff");

// XSS Protection (Blokir script jahat di browser)
header("X-XSS-Protection: 1; mode=block");

// Referrer Policy (Privasi user)
header("Referrer-Policy: strict-origin-when-cross-origin");

// ==========================================
// 2. BACKEND LOGIC & MULTI-LANGUAGE
// ==========================================
require_once 'koneksi.php'; 

function clean($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// A. LOGIC BAHASA (DENGAN SECURITY FILTER)
session_start();

// Validasi input ?lang=... biar gak diisi script aneh
if(isset($_GET['lang'])) {
    $allowed_langs = ['id', 'en'];
    if(in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    } else {
        $_SESSION['lang'] = 'id'; // Default kalau input aneh
    }
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'id';
$is_en = ($lang == 'en');

// B. TEXT MAPPING
$txt = [
    'greeting_pre' => $is_en ? "Hi, I'm" : "Halo, saya",
    'status_avail' => $is_en ? "Open for Collaboration" : "Terbuka untuk Kolaborasi",
    'stat_exp' => $is_en ? "Years Exp" : "Pengalaman",
    'stat_proj' => $is_en ? "Projects" : "Proyek",
    'stat_hiring' => $is_en ? "Available" : "Available",
    'btn_port' => $is_en ? "View Portfolio" : "Lihat Portfolio",
    'btn_cv' => $is_en ? "Download CV" : "Unduh CV",
    'sect_skills' => $is_en ? "Technical Arsenal" : "Keahlian Teknis",
    'sect_about' => $is_en ? "About Me" : "Tentang Saya",
    'sect_proj' => $is_en ? "Selected Works" : "Proyek Pilihan",
    'sect_contact_1' => $is_en ? "Ready to" : "Siap Membangun",
    'sect_contact_2' => $is_en ? "Collaborate?" : "Sesuatu yang Hebat?",
    'contact_sub' => $is_en ? "Available for freelance projects or full-time opportunities." : "Tersedia untuk proyek freelance maupun kesempatan karir full-time.",
    'footer' => $is_en ? "Built with Logic & Passion." : "Dibuat dengan Logika & Hati.",
    'chatbot_invite' => $is_en ? "Let's chat with my AI!" : "Ngobrol sama AI-ku yuk!"
];

// C. AMBIL DATA DB
$query = mysqli_query($conn, "SELECT * FROM profile WHERE id=1");
if ($query && mysqli_num_rows($query) > 0) {
    $p = mysqli_fetch_assoc($query);
} else {
    $p = ['hero_title'=>'System Analyst','hero_title_en'=>'System Analyst','hero_desc'=>'Please configure CMS.','profile_pic'=>'','cv_link'=>'#'];
}

// Variable Switch
$hero_title_raw = $is_en ? $p['hero_title_en'] : $p['hero_title'];
$hero_title = str_replace('| ', '| <br class="hidden md:block">', $hero_title_raw);
$hero_desc  = $is_en ? $p['hero_desc_en'] : $p['hero_desc'];
$about_title = $is_en ? $p['about_title_en'] : $p['about_title'];
$about_text  = $is_en ? $p['about_text_en'] : $p['about_text'];

// Bento Titles
$bt1 = $p['bento_title_1']; $bd1 = $is_en ? $p['bento_desc_1_en'] : $p['bento_desc_1'];
$bt2 = $p['bento_title_2']; $bd2 = $is_en ? $p['bento_desc_2_en'] : $p['bento_desc_2'];
$bt3 = $p['bento_title_3']; $bd3 = $is_en ? $p['bento_desc_3_en'] : $p['bento_desc_3'];

// Assets
$foto_profile = !empty($p['profile_pic']) && file_exists("assets/img/".$p['profile_pic']) ? "assets/img/".$p['profile_pic'] : "https://via.placeholder.com/600x750?text=No+Image";
$cv_url = (!empty($p['cv_link']) && strpos($p['cv_link'], 'http') !== false) ? $p['cv_link'] : "assets/doc/" . ($p['cv_link'] ?? '#');
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo clean(substr($hero_desc, 0, 150)); ?>">
    <title><?php echo clean($hero_title_raw); ?> | Portfolio</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Satoshi', 'sans-serif'] },
                    colors: {
                        bg: '#FAFAFA',       
                        primary: '#111827',  
                        secondary: '#6B7280',
                        accent: '#2563EB',   
                    },
                    boxShadow: { 'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)' },
                    keyframes: { fadeInUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } } },
                    animation: { fadeInUp: 'fadeInUp 0.3s ease-out forwards' }
                }
            }
        }
    </script>
    <style>
        body { background-color: #FAFAFA; color: #111827; }
        .island-box { background: white; border-radius: 2rem; padding: 3rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02); border: 1px solid #F3F4F6; }
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 4px 30px rgba(0,0,0,0.05); }
        .timeline-container { border-left: 2px solid #E5E7EB; margin-left: 8px; padding-left: 28px; }
        .timeline-item { position: relative; padding-bottom: 40px; }
        .timeline-dot { position: absolute; left: -35px; top: 6px; width: 12px; height: 12px; background: #2563EB; border-radius: 50%; box-shadow: 0 0 0 4px #DBEAFE; }
        .lang-switch { position: relative; background: #E5E7EB; border-radius: 99px; padding: 4px; display: inline-flex; cursor: pointer; }
        .lang-btn { position: relative; z-index: 10; padding: 4px 12px; font-size: 11px; font-weight: 800; color: #6B7280; transition: all 0.3s; border-radius: 99px; }
        .lang-btn.active { color: #000; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .lang-btn:hover { color: #000; }
    </style>
</head>

<body class="antialiased selection:bg-accent selection:text-white relative">

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

    <section id="home" class="min-h-screen flex items-center relative py-20 overflow-hidden">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-blue-50 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/3 pointer-events-none opacity-70"></div>
        <div class="max-w-7xl mx-auto px-6 w-full relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <div data-aos="fade-right" data-aos-duration="1000" class="-mt-20 lg:-mt-32">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-gray-200 shadow-sm text-secondary text-[10px] font-bold uppercase tracking-widest mb-6">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <?php echo $txt['status_avail']; ?>
                    </div>
                    <div class="mb-6">
                        <h2 class="text-2xl md:text-3xl font-bold mb-4"> <span class="text-accent"><?php echo $txt['greeting_pre']; ?></span> <span class="text-primary">Ferry Fernando</span>,</h2>
                        <h1 class="text-5xl md:text-7xl font-black leading-[1.1] text-primary tracking-tight"><?php echo $hero_title; ?></h1>
                    </div>
                    <div class="flex gap-6 mb-10 border-l-4 border-accent/20 pl-6">
                        <p class="text-lg text-gray-600 leading-relaxed max-w-lg font-medium"><?php echo $hero_desc; ?></p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <a href="#projects" class="bg-primary text-white px-8 py-3.5 rounded-full font-bold text-sm hover:bg-accent transition shadow-lg hover:shadow-glow hover:-translate-y-1"><?php echo $txt['btn_port']; ?></a>
                        <a href="<?php echo $cv_url; ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-8 py-3.5 rounded-full font-bold text-sm hover:bg-gray-50 transition shadow-sm hover:-translate-y-1"><?php echo $txt['btn_cv']; ?></a>
                    </div>
                </div>
                <div class="relative flex justify-center lg:justify-end" data-aos="fade-left" data-aos-duration="1200">
                    <div class="relative w-full max-w-sm lg:max-w-md z-10">
                        <div class="rounded-[3rem] overflow-hidden shadow-2xl rotate-2 hover:rotate-0 transition duration-700 ease-out border-[8px] border-white bg-white">
                            <img src="<?php echo $foto_profile; ?>" alt="Profile" class="w-full h-auto object-cover grayscale hover:grayscale-0 transition duration-700">
                        </div>
                        <div class="absolute top-10 -right-6 md:-right-12 flex flex-col gap-4 z-20">
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_3s_infinite]">
                                <span class="text-xl md:text-2xl font-black text-primary">5+</span>
                                <span class="text-[8px] md:text-[10px] font-bold text-gray-400 uppercase"><?php echo $txt['stat_exp']; ?></span>
                            </div>
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-primary rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-800 animate-[bounce_3.5s_infinite]">
                                <span class="text-xl md:text-2xl font-black text-white">15+</span>
                                <span class="text-[8px] md:text-[10px] font-bold text-gray-400 uppercase"><?php echo $txt['stat_proj']; ?></span>
                            </div>
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_4s_infinite]">
                                <div class="w-3 h-3 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                <span class="text-[8px] md:text-[10px] font-bold text-primary uppercase text-center leading-tight px-2"><?php echo $txt['stat_hiring']; ?><br>For Hire</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[110%] h-[110%] border border-dashed border-gray-300 rounded-full -z-10 animate-[spin_30s_linear_infinite] opacity-50"></div>
                </div>
            </div>
        </div>
    </section>

    <section id="skills" class="py-20 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box">
            <div class="text-center mb-16">
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block"><?php echo $txt['sect_skills']; ?></span>
                <h2 class="text-4xl md:text-5xl font-black text-primary"><?php echo $txt['sect_skills']; ?></h2>
            </div>
            <?php
            $skills = ['Analysis' => [], 'Enterprise' => [], 'Development' => []];
            $q_skill = mysqli_query($conn, "SELECT * FROM tech_stacks");
            if(mysqli_num_rows($q_skill) > 0) {
                while($s = mysqli_fetch_assoc($q_skill)) {
                    $cat_db = trim($s['category']); 
                    if(in_array($cat_db, ['Analysis', 'Jira', 'Design'])) $skills['Analysis'][] = $s; 
                    else if(in_array($cat_db, ['Enterprise', 'System', 'SAP'])) $skills['Enterprise'][] = $s; 
                    else $skills['Development'][] = $s;
                }
            }
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-accent mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-diagram-3"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt1; ?></h3>
                    <p class="text-xs text-gray-500 group-hover:text-gray-400 mb-6 leading-relaxed flex-grow"><?php echo $bd1; ?></p>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Analysis'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all"><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-green-500 mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-kanban"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt2; ?></h3>
                    <p class="text-xs text-gray-500 group-hover:text-gray-400 mb-6 leading-relaxed flex-grow"><?php echo $bd2; ?></p>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Enterprise'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all"><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-purple-500 mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-code-slash"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt3; ?></h3>
                    <p class="text-xs text-gray-500 group-hover:text-gray-400 mb-6 leading-relaxed flex-grow"><?php echo $bd3; ?></p>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Development'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all"><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-24 max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            <div class="lg:col-span-5 sticky top-10" data-aos="fade-right">
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-4 block"><?php echo $txt['sect_about']; ?></span>
                <h2 class="text-4xl md:text-5xl font-black text-primary leading-tight mb-8"><?php echo $about_title; ?></h2>
                <img src="assets/img/<?php echo $p['about_img_1']; ?>" class="w-full rounded-[2rem] shadow-2xl rotate-[-1deg] hover:rotate-0 transition duration-500 border-4 border-white mb-6 bg-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <img src="assets/img/<?php echo $p['about_img_2']; ?>" class="rounded-2xl shadow-md border-2 border-white hover:scale-105 transition bg-gray-200">
                    <img src="assets/img/<?php echo $p['about_img_3']; ?>" class="rounded-2xl shadow-md border-2 border-white hover:scale-105 transition bg-gray-200">
                </div>
            </div>
            <div class="lg:col-span-7 lg:pt-4" data-aos="fade-left">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-700 leading-relaxed mb-12"><?php echo $about_text; ?></h3>
                <div class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-glass border border-white/60">
                    <h4 class="text-xl font-black mb-10 flex items-center gap-3"><div class="p-2 bg-blue-50 text-accent rounded-lg"><i class="bi bi-briefcase-fill"></i></div> Career Journey</h4>
                    <div class="timeline-container">
                        <?php 
                        $q_time = mysqli_query($conn, "SELECT * FROM timeline ORDER BY id DESC"); 
                        if($q_time): while($row = mysqli_fetch_assoc($q_time)): 
                        ?>
                        <div class="timeline-item group">
                            <div class="timeline-dot group-hover:scale-125 transition duration-300"></div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2">
                                <h5 class="text-lg font-bold text-primary group-hover:text-accent transition"><?php echo $row['role']; ?></h5>
                                <span class="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full mt-1 sm:mt-0 w-fit"><?php echo $row['year']; ?></span>
                            </div>
                            <div class="text-sm font-bold text-accent mb-3 uppercase tracking-wider"><?php echo $row['company']; ?></div>
                            <p class="text-sm text-gray-500 leading-relaxed"><?php echo strip_tags($row['description']); ?></p>
                        </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="projects" class="py-20 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box bg-primary text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-accent/20 blur-[100px] rounded-full pointer-events-none"></div>
            <div class="mb-16 relative z-10">
                <h2 class="text-4xl md:text-5xl font-black mb-4"><?php echo $txt['sect_proj']; ?></h2>
                <div class="w-16 h-1 bg-accent rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
                <?php 
                $q_proj = mysqli_query($conn, "SELECT * FROM projects ORDER BY id DESC LIMIT 6"); 
                if($q_proj): while ($d = mysqli_fetch_assoc($q_proj)): 
                ?>
                <div class="group cursor-pointer" onclick="openModal('<?php echo clean($d['title']);?>', '<?php echo clean($d['description']);?>', 'assets/img/<?php echo $d['image'];?>', '<?php echo clean($d['link_demo']);?>')">
                    <div class="overflow-hidden rounded-3xl mb-5 border border-white/10 relative shadow-lg bg-gray-800">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition z-10"></div>
                        <img src="assets/img/<?php echo $d['image']; ?>" class="w-full h-72 object-cover transform group-hover:scale-105 transition duration-700 ease-out opacity-90 group-hover:opacity-100" onerror="this.src='assets/img/default.jpg'">
                    </div>
                    <div class="flex justify-between items-start px-2">
                        <div>
                            <span class="text-[10px] font-bold text-accent uppercase tracking-widest mb-2 block"><?php echo $d['category']; ?></span>
                            <h3 class="text-2xl font-bold group-hover:text-accent transition"><?php echo $d['title']; ?></h3>
                        </div>
                        <div class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-black transition"><i class="bi bi-arrow-up-right text-sm"></i></div>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </section>

    <section id="contact" class="py-32 text-center relative overflow-hidden" data-aos="zoom-in">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-blue-100 to-purple-100 rounded-full blur-[120px] -z-10 opacity-70"></div>
        <div class="max-w-4xl mx-auto px-6 relative z-10">
            <h2 class="text-5xl md:text-7xl font-black text-primary mb-8 tracking-tight leading-tight">
                <?php echo $txt['sect_contact_1']; ?><br><span class="text-transparent bg-clip-text bg-gradient-to-r from-accent to-purple-600"><?php echo $txt['sect_contact_2']; ?></span>
            </h2>
            <p class="text-xl text-gray-500 mb-12 max-w-xl mx-auto leading-relaxed"><?php echo $txt['contact_sub']; ?></p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="mailto:<?php echo $p['email']; ?>" class="bg-primary text-white px-10 py-5 rounded-full font-bold text-lg hover:bg-accent transition shadow-xl hover:shadow-glow hover:-translate-y-1"><?php echo $txt['btn_email']; ?></a>
                <a href="https://wa.me/<?php echo $p['whatsapp']; ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-10 py-5 rounded-full font-bold text-lg hover:bg-gray-50 transition shadow-md hover:-translate-y-1">WhatsApp</a>
            </div>
        </div>
    </section>

    <footer class="text-center pb-32 pt-10 text-gray-400 text-sm border-t border-gray-200 bg-white">
        &copy; <?php echo date('Y'); ?> Ferry Fernando. <?php echo $txt['footer']; ?>
    </footer>

    <div class="fixed bottom-8 right-8 z-[9999] flex flex-col items-end">
        <div class="bg-white py-2 px-4 rounded-xl shadow-lg mb-2 animate-bounce text-sm font-bold text-accent hidden md:block">
            <?php echo $txt['chatbot_invite']; ?> 👇
        </div>
        <button onclick="toggleChatbot()" class="bg-accent text-white p-4 rounded-full shadow-2xl hover:bg-primary transition hover:scale-110 flex items-center justify-center w-16 h-16 relative z-20">
            <i class="bi bi-robot text-3xl"></i>
        </button>
        
        <div id="chatbotFrame" class="hidden fixed bottom-24 right-4 md:right-8 w-[90%] md:w-[350px] h-[500px] max-h-[80vh] bg-white rounded-3xl shadow-2xl border border-gray-200 overflow-hidden z-[9999] animate-fadeInUp origin-bottom-right flex flex-col">
            <div class="bg-gray-50 p-3 flex justify-between items-center border-b flex-none">
                <span class="font-bold text-sm">Ferry AI Assistant</span>
                <button onclick="toggleChatbot()" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <iframe src="apps/api/chat.php" class="w-full flex-1 border-0"></iframe>
        </div>
    </div>

    <div id="projectModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-primary/90 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out] relative">
                <button onclick="closeModal()" class="absolute top-6 right-6 bg-white/50 hover:bg-white p-2 rounded-full z-10 transition shadow-sm backdrop-blur-md"><i class="bi bi-x-lg text-xl"></i></button>
                <img id="modalImg" src="" class="w-full h-64 md:h-96 object-cover bg-gray-100">
                <div class="p-8 md:p-12">
                    <h3 id="modalTitle" class="text-3xl md:text-5xl font-black mb-6 text-primary leading-tight"></h3>
                    <div class="w-12 h-1 bg-accent rounded-full mb-8"></div>
                    <p id="modalDesc" class="text-lg text-gray-600 leading-relaxed mb-10 font-medium"></p>
                    <div class="flex gap-4">
                        <a id="modalLink" href="#" target="_blank" class="bg-primary text-white px-8 py-4 rounded-full font-bold hover:bg-accent transition shadow-lg">Lihat Project</a>
                        <button onclick="closeModal()" class="border border-gray-200 px-8 py-4 rounded-full font-bold hover:bg-gray-50 transition text-gray-500">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, offset: 50, once: true, easing: 'ease-out-cubic' });
        
        function openModal(title, desc, img, link) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalImg').src = img;
            let btn = document.getElementById('modalLink');
            if(link && link !== '#') btn.classList.remove('hidden'); else btn.classList.add('hidden');
            document.getElementById('projectModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
        }
        function closeModal() { 
            document.getElementById('projectModal').classList.add('hidden'); 
            document.body.style.overflow = 'auto'; 
        }

        function toggleChatbot() {
            const frame = document.getElementById('chatbotFrame');
            frame.classList.toggle('hidden');
        }
    </script>
</body>
</html>