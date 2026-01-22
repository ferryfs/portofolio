<?php 
// ==========================================
// 🛡️ 1. SECURITY HEADERS (AMAN)
// ==========================================
error_reporting(E_ALL); 
ini_set('display_errors', 0);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ==========================================
// 2. BACKEND LOGIC
// ==========================================
require_once 'koneksi.php'; 

function clean($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

session_start();
if(isset($_GET['lang'])) {
    $allowed_langs = ['id', 'en'];
    if(in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    } else {
        $_SESSION['lang'] = 'id'; 
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
    'sect_skills_label' => $is_en ? "Core Competencies" : "Kompetensi Utama", 
    'sect_skills' => $is_en ? "Technical Arsenal" : "Keahlian Teknis",
    'sect_about' => $is_en ? "About Me" : "Tentang Saya",
    'sect_proj' => $is_en ? "Selected Works" : "Proyek Pilihan",
    'sect_contact_1' => $is_en ? "Ready to" : "Siap Membangun",
    'sect_contact_2' => $is_en ? "Collaborate?" : "Sesuatu yang Hebat?",
    'contact_sub' => $is_en ? "Available for freelance projects or full-time opportunities." : "Tersedia untuk proyek freelance maupun kesempatan karir full-time.",
    'footer' => $is_en ? "Built with Logic & Passion." : "Dibuat dengan Logika & Hati.",
    'chatbot_invite' => $is_en ? "Let's chat with my AI!" : "Ngobrol sama AI-ku yuk!",
    'read_more' => $is_en ? "View Details" : "Lihat Detail",
    'career_title' => $is_en ? "Professional Journey" : "Perjalanan Karir"
];

// C. AMBIL DATA DB
$query = mysqli_query($conn, "SELECT * FROM profile WHERE id=1");
if ($query && mysqli_num_rows($query) > 0) {
    $p = mysqli_fetch_assoc($query);
} else {
    $p = [
        'hero_title'=>'System Analyst',
        'hero_title_en'=>'System Analyst',
        'hero_desc'=>'Please configure CMS.',
        'profile_pic'=>'',
        'cv_link'=>'#',
        'about_title' => 'Analyst & Leader.',
        'about_title_en' => 'Analyst & Leader.'
    ];
}

$hero_title_raw = $is_en ? $p['hero_title_en'] : $p['hero_title'];
$hero_title = str_replace('| ', '| <br class="hidden md:block">', $hero_title_raw);
$hero_desc  = $is_en ? $p['hero_desc_en'] : $p['hero_desc'];

// 🔥 FIX: ABOUT TITLE AMBIL DARI DB (INDO/INGGRIS)
$about_title = $is_en ? ($p['about_title_en'] ?? $p['about_title']) : $p['about_title'];

$bt1 = $p['bento_title_1']; $bd1 = $is_en ? $p['bento_desc_1_en'] : $p['bento_desc_1'];
$bt2 = $p['bento_title_2']; $bd2 = $is_en ? $p['bento_desc_2_en'] : $p['bento_desc_2'];
$bt3 = $p['bento_title_3']; $bd3 = $is_en ? $p['bento_desc_3_en'] : $p['bento_desc_3'];

$foto_profile = !empty($p['profile_pic']) && file_exists("assets/img/".$p['profile_pic']) ? "assets/img/".$p['profile_pic'] : "https://via.placeholder.com/600x750?text=No+Image";
$cv_url = (!empty($p['cv_link']) && strpos($p['cv_link'], 'http') !== false) ? $p['cv_link'] : "assets/doc/" . ($p['cv_link'] ?? '#');

// SETUP GAMBAR POPUP DEFAULT (Jika di DB kosong)
$default_popup_img = "https://cdni.iconscout.com/illustration/premium/thumb/businessman-showing-thumbs-up-sign-2761821-2299873.png";
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="scroll-smooth overflow-x-hidden">
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
        body { background-color: #FAFAFA; color: #111827; overflow-x: hidden; width: 100%; position: relative; }
        .island-box { background: white; border-radius: 2rem; padding: 3rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02); border: 1px solid #F3F4F6; }
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 4px 30px rgba(0,0,0,0.05); }
        .timeline-container { border-left: 2px solid #E5E7EB; margin-left: 8px; padding-left: 28px; }
        .timeline-item { position: relative; padding-bottom: 40px; }
        .timeline-dot { position: absolute; left: -35px; top: 6px; width: 12px; height: 12px; background: #2563EB; border-radius: 50%; box-shadow: 0 0 0 4px #DBEAFE; }
        .lang-switch { position: relative; background: #E5E7EB; border-radius: 99px; padding: 4px; display: inline-flex; cursor: pointer; }
        .lang-btn { position: relative; z-index: 10; padding: 4px 12px; font-size: 11px; font-weight: 800; color: #6B7280; transition: all 0.3s; border-radius: 99px; }
        .lang-btn.active { color: #000; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .lang-btn:hover { color: #000; }
        
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #999; }
    </style>
</head>

<body class="antialiased selection:bg-accent selection:text-white relative overflow-x-hidden">

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
                    
                    <div class="relative w-full max-w-sm mx-auto my-10 lg:hidden flex justify-center" data-aos="fade-up">
                        <div class="relative w-full z-10">
                            <div class="rounded-[2.5rem] overflow-hidden shadow-2xl rotate-2 border-[6px] border-white bg-white">
                                <img src="<?php echo $foto_profile; ?>" alt="Profile" class="w-full h-auto object-cover">
                            </div>
                            <div class="absolute top-6 -right-2 flex flex-col gap-3 z-20 scale-90">
                                <div class="w-16 h-16 bg-white rounded-full shadow-lg flex flex-col items-center justify-center border border-gray-100 animate-[bounce_3s_infinite]">
                                    <span class="text-lg font-black text-primary">5+</span>
                                    <span class="text-[8px] font-bold text-gray-400 uppercase"><?php echo $txt['stat_exp']; ?></span>
                                </div>
                                <div class="w-16 h-16 bg-primary rounded-full shadow-lg flex flex-col items-center justify-center border border-gray-800 animate-[bounce_3.5s_infinite]">
                                    <span class="text-lg font-black text-white">15+</span>
                                    <span class="text-[8px] font-bold text-gray-400 uppercase"><?php echo $txt['stat_proj']; ?></span>
                                </div>
                                <div class="w-16 h-16 bg-white rounded-full shadow-lg flex flex-col items-center justify-center border border-gray-100 animate-[bounce_4s_infinite]">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                    <span class="text-[8px] font-bold text-primary uppercase text-center leading-tight px-2"><?php echo $txt['stat_hiring']; ?><br>For Hire</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-6 mb-10 border-l-4 border-accent pl-6">
                        <p class="text-lg text-gray-600 leading-relaxed max-w-lg font-medium"><?php echo $hero_desc; ?></p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <a href="#projects" class="bg-primary text-white px-8 py-3.5 rounded-full font-bold text-sm hover:bg-accent transition shadow-lg hover:shadow-glow hover:-translate-y-1"><?php echo $txt['btn_port']; ?></a>
                        <a href="<?php echo $cv_url; ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-8 py-3.5 rounded-full font-bold text-sm hover:bg-gray-50 transition shadow-sm hover:-translate-y-1"><?php echo $txt['btn_cv']; ?></a>
                    </div>
                </div>
                
                <div class="relative hidden lg:flex justify-end" data-aos="fade-left" data-aos-duration="1200">
                    <div class="relative w-full max-w-md z-10">
                        <div class="rounded-[3rem] overflow-hidden shadow-2xl rotate-2 hover:rotate-0 transition duration-700 ease-out border-[8px] border-white bg-white">
                            <img src="<?php echo $foto_profile; ?>" alt="Profile" class="w-full h-auto object-cover grayscale hover:grayscale-0 transition duration-700">
                        </div>
                        <div class="absolute top-10 -right-12 flex flex-col gap-4 z-20">
                            <div class="w-24 h-24 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_3s_infinite]">
                                <span class="text-2xl font-black text-primary">5+</span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo $txt['stat_exp']; ?></span>
                            </div>
                            <div class="w-24 h-24 bg-primary rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-800 animate-[bounce_3.5s_infinite]">
                                <span class="text-2xl font-black text-white">15+</span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo $txt['stat_proj']; ?></span>
                            </div>
                            <div class="w-24 h-24 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_4s_infinite]">
                                <div class="w-3 h-3 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                <span class="text-[10px] font-bold text-primary uppercase text-center leading-tight px-2"><?php echo $txt['stat_hiring']; ?><br>For Hire</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[110%] h-[110%] border border-dashed border-gray-300 rounded-full -z-10 animate-[spin_30s_linear_infinite] opacity-50"></div>
                </div>
            </div>
        </div>
    </section>

    <section id="skills" class="pt-0 pb-8 md:pt-10 md:pb-16 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box">
            <div class="text-center mb-16">
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block"><?php echo $txt['sect_skills_label']; ?></span>
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
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow">
                        <p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $bd1; ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Analysis'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all"><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-green-500 mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-kanban"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt2; ?></h3>
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow">
                        <p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $bd2; ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Enterprise'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all"><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 cursor-default h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-purple-500 mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-code-slash"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt3; ?></h3>
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow">
                        <p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $bd3; ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Development'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm group-hover:bg-white/10 group-hover:text-white group-hover:border-white/30 transition-all"><i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="pt-8 pb-24 md:pt-16 md:pb-32 max-w-7xl mx-auto px-6">
        <div class="text-center mb-12" data-aos="fade-up">
            <span class="text-accent font-bold tracking-widest text-xs uppercase mb-4 block"><?php echo $txt['sect_about']; ?></span>
            <h2 class="text-4xl md:text-5xl font-black text-primary leading-tight"><?php echo $about_title; ?></h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch lg:h-[600px]">
            
            <div class="flex flex-col gap-4 lg:h-[600px]" data-aos="fade-right">
                <div class="h-[65%] cursor-zoom-in relative group overflow-hidden rounded-[2rem] border-4 border-white bg-gray-200 shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_1']; ?>')">
                    <img src="assets/img/<?php echo $p['about_img_1']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                </div>
                <div class="h-[35%] grid grid-cols-2 gap-4">
                    <div class="cursor-zoom-in rounded-2xl overflow-hidden shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_2']; ?>')">
                        <img src="assets/img/<?php echo $p['about_img_2']; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500 bg-gray-200">
                    </div>
                    <div class="cursor-zoom-in rounded-2xl overflow-hidden shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_3']; ?>')">
                        <img src="assets/img/<?php echo $p['about_img_3']; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500 bg-gray-200">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 md:p-8 rounded-[2.5rem] shadow-glass border border-white/60 relative flex flex-col lg:h-[600px]" data-aos="fade-left">
                
                <div class="flex-none pb-4 border-b border-gray-100">
                    <h4 class="text-xl font-black flex items-center gap-3">
                        <div class="p-2 bg-blue-50 text-accent rounded-lg"><i class="bi bi-briefcase-fill"></i></div> 
                        <?php echo $txt['career_title']; ?>
                    </h4>
                </div>

                <div class="flex-1 overflow-y-auto custom-scroll pr-2 mt-4">
                    <div class="grid grid-cols-1 gap-4">
                        <?php 
                        $careers = [];
                        $q_time = mysqli_query($conn, "SELECT * FROM timeline ORDER BY sort_date DESC"); 
                        while($row = mysqli_fetch_assoc($q_time)) {
                            $careers[$row['company']][] = $row; 
                        }

                        $jsCareerData = [];
                        foreach($careers as $company => $roles) {
                            $jsCareerData[$company] = $roles;
                        ?>
                        
                        <div class="group bg-gray-50 hover:bg-white hover:shadow-xl border border-gray-100 rounded-2xl p-5 cursor-pointer transition-all duration-300 transform hover:-translate-y-1 hover:border-accent/30" 
                             onclick="openCareerModal('<?php echo clean($company); ?>')">
                            
                            <div class="flex justify-between items-center mb-2">
                                <div class="text-xs font-bold bg-accent/10 text-accent px-3 py-1 rounded-full uppercase tracking-wider">
                                    <?php echo $roles[0]['year']; ?>
                                </div>
                                <i class="bi bi-arrow-right-circle-fill text-gray-300 group-hover:text-accent text-2xl transition"></i>
                            </div>
                            
                            <h5 class="text-lg font-black text-primary mb-1"><?php echo $company; ?></h5>
                            <p class="text-sm text-gray-500 font-medium mb-4 line-clamp-1"><?php echo $roles[0]['role']; ?></p>
                            
                            <span class="text-xs font-bold text-accent border-b border-accent/20 group-hover:border-accent pb-0.5 transition">
                                <?php echo $txt['read_more']; ?>
                            </span>
                        </div>
                        <?php } ?>
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

    <div class="fixed bottom-8 right-8 z-40 flex flex-col items-end">
        <div class="bg-white py-2 px-4 rounded-xl shadow-lg mb-2 animate-bounce text-sm font-bold text-accent hidden md:block">
            <?php echo $txt['chatbot_invite']; ?> 👇
        </div>
        <button onclick="toggleChatbot()" class="bg-accent text-white p-4 rounded-full shadow-2xl hover:bg-primary transition hover:scale-110 flex items-center justify-center w-16 h-16 relative z-20">
            <i class="bi bi-robot text-3xl"></i>
        </button>
        <div id="chatbotFrame" class="hidden fixed bottom-24 right-4 md:right-8 w-[90%] md:w-[350px] h-[500px] max-h-[80vh] bg-white rounded-3xl shadow-2xl border border-gray-200 overflow-hidden z-50 animate-fadeInUp origin-bottom-right flex flex-col">
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

    <div id="imageModal" class="fixed inset-0 z-[110] hidden" onclick="closeImageModal()">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <img id="lightboxImg" src="" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl animate-[fadeInUp_0.3s_ease-out]">
            <button class="absolute top-6 right-6 text-white text-4xl hover:text-accent">&times;</button>
        </div>
    </div>

    <div id="careerModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-primary/80 backdrop-blur-sm transition-opacity" onclick="closeCareerModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-5xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out] relative h-[80vh] flex flex-col md:flex-row">
                
                <div class="w-full md:w-1/3 bg-blue-50 flex items-center justify-center p-8 border-b md:border-b-0 md:border-r border-gray-100 relative">
                    <img id="careerCartoon" src="" class="w-48 md:w-full drop-shadow-xl animate-[bounce_4s_infinite]">
                    <div class="absolute bottom-4 text-center w-full text-xs text-gray-400 font-bold uppercase tracking-widest opacity-50">Career Highlights</div>
                </div>

                <div class="w-full md:w-2/3 flex flex-col h-full overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center bg-white sticky top-0 z-10 flex-none">
                        <h3 id="careerCompany" class="text-2xl font-black text-primary"></h3>
                        <button onclick="closeCareerModal()" class="bg-gray-100 hover:bg-red-50 hover:text-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="bi bi-x-lg"></i></button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scroll">
                        <div id="careerContent" class="p-8">
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const careerData = <?php echo json_encode($jsCareerData); ?>;
        // Default image jika di DB kosong
        const defaultCartoon = "<?php echo $default_popup_img; ?>";
    </script>

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

        function openImageModal(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openCareerModal(company) {
            document.getElementById('careerCompany').innerText = company;
            
            const roles = careerData[company];
            
            // 🔥 LOGIKA BARU: LOOPING CARI GAMBAR
            // Kita cari di SEMUA role dalam PT tersebut.
            // Begitu nemu satu gambar, langsung pake itu.
            let foundImg = defaultCartoon;
            
            for (let i = 0; i < roles.length; i++) {
                // Cek apakah kolom image ada isinya dan tidak null
                if (roles[i].image && roles[i].image.trim() !== "") {
                    foundImg = 'assets/img/' + roles[i].image;
                    break; // Berhenti mencari kalau udah nemu
                }
            }
            
            // Pasang gambarnya
            document.getElementById('careerCartoon').src = foundImg;

            // ... (Kode render HTML list jabatan di bawahnya tetep sama) ...
            let html = '';
            if(roles.length > 1) { html += '<div class="grid grid-cols-1 gap-6">'; } 
            else { html += '<div>'; }

            roles.forEach(role => {
                html += `
                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:border-accent/30 transition shadow-sm hover:shadow-md">
                        <div class="flex justify-between items-start mb-4 border-b border-gray-200 pb-3">
                            <div>
                                <h4 class="font-bold text-lg text-primary">${role.role}</h4>
                                <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-1">Full Time</div>
                            </div>
                            <span class="text-xs font-bold bg-primary text-white px-3 py-1 rounded-full text-center h-fit">${role.year}</span>
                        </div>
                        <div class="text-sm text-gray-600 leading-relaxed space-y-2 prose prose-sm max-w-none">
                            <style>.db-desc ul { padding-left: 1.2rem; list-style-type: disc; } .db-desc li { margin-bottom: 0.5rem; } .db-desc b, .db-desc strong { color: #2563EB; font-weight: 800; }</style>
                            <div class="db-desc">${role.description}</div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            document.getElementById('careerContent').innerHTML = html;
            document.getElementById('careerModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeCareerModal() {
            document.getElementById('careerModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function toggleChatbot() {
            const frame = document.getElementById('chatbotFrame');
            frame.classList.toggle('hidden');
        }
    </script>
</body>
</html>