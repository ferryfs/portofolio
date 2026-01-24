<?php 
// ==========================================
// 🛡️ 1. SECURITY & CONFIG
// ==========================================
error_reporting(E_ALL); 
ini_set('display_errors', 0);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once 'koneksi.php'; 

function clean($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function sanitizeLink($input, $type) {
    if ($type == 'email') return filter_var($input, FILTER_SANITIZE_EMAIL);
    return preg_replace('/[^0-9]/', '', $input);
}

session_start();
$lang = (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'])) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'id');
$_SESSION['lang'] = $lang;
$is_en = ($lang == 'en');

// ==========================================
// ⚡ 2. SMART CACHING
// ==========================================
function getCachedData($conn, $key, $sql) {
    $cacheFile = "cache/{$key}.json";
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) $data[] = $row;
    if (!is_dir('cache')) mkdir('cache', 0755, true);
    file_put_contents($cacheFile, json_encode($data));
    return $data;
}

$timelineData = getCachedData($conn, "timeline", "SELECT * FROM timeline ORDER BY sort_date DESC");
$skillsData   = getCachedData($conn, "skills", "SELECT * FROM tech_stacks ORDER BY category ASC");

// Fetch Projects
$q_proj = $conn->query("SELECT * FROM projects ORDER BY id DESC");
$projects = [];
while($row = $q_proj->fetch_assoc()) {
    $row['image_url'] = (!empty($row['image']) && file_exists('assets/img/'.$row['image'])) ? 'assets/img/'.$row['image'] : 'assets/img/default.jpg';
    $projects[] = $row;
}

// Fetch Profile Data
$stmt = $conn->prepare("SELECT * FROM profile WHERE id=1"); $stmt->execute(); 
$p = $stmt->get_result()->fetch_assoc();
if (!$p) $p = []; 

// ==========================================
// 📝 3. DYNAMIC TEXT MAPPING (FIXED SESUAI DB PROFILE)
// ==========================================

// Helper: Ambil data, kalau kosong pakai default
function db_val($data, $default) { return !empty($data) ? clean($data) : $default; }

// 1. HERO SECTION
// Di DB: hero_pre ("Halo Semuanya"), hero_greeting ("Hallo, saya Ferry...")
$hero_pre   = $is_en ? db_val($p['hero_pre_en'], 'Hello Everyone 👋') : db_val($p['hero_pre'], 'Halo Semuanya 👋');
$hero_title = str_replace('| ', '| <br class="hidden md:block">', $is_en ? db_val($p['hero_title_en'], 'IT Analyst') : db_val($p['hero_title'], 'IT Analyst'));
$hero_desc  = $is_en ? db_val($p['hero_desc_en'], 'Welcome.') : db_val($p['hero_desc'], 'Selamat datang.');

// Note: Di DB 'hero_greeting' isinya full kalimat ("Hallo, saya Ferry..."), jadi langsung panggil aja
$hero_greet_full = $is_en ? db_val($p['hero_greeting_en'], 'Hi, my name is Ferry') : db_val($p['hero_greeting'], 'Hallo, saya Ferry');

// 2. ABOUT SECTION
// Di DB: tentang_saya, about_title
$label_about = $is_en ? db_val($p['tentang_saya_en'], 'About Me') : db_val($p['tentang_saya'], 'Tentang Saya'); 
$title_about = $is_en ? db_val($p['about_title_en'], 'Analyst & Leader') : db_val($p['about_title'], 'Analyst & Leader');

// 3. SKILLS SECTION
// Di DB: skills, title_skills
$label_skills = $is_en ? db_val($p['skills_en'], 'Core Competencies') : db_val($p['skills'], 'Kompetensi Utama');
$title_skills = $is_en ? db_val($p['title_skills_en'], 'Technical Arsenal') : db_val($p['title_skills'], 'Keahlian Teknis');

// 4. PROJECTS SECTION
$proj_title = $is_en ? db_val($p['project_title_en'], 'Projects') : db_val($p['project_title'], 'Projects');
$proj_desc  = $is_en ? db_val($p['project_desc_en'], 'Collection of projects...') : db_val($p['project_desc'], 'Koleksi proyek...');

// 5. CONTACT SECTION
$contact_1 = $is_en ? db_val($p['title_contact_1_en'], 'Ready to') : db_val($p['title_contact_1'], 'Siap Membangun');
$contact_2 = $is_en ? db_val($p['title_contact_2_en'], 'Collaborate?') : db_val($p['title_contact_2'], 'Sesuatu yang Hebat?');

// Bento Grid (Tetap sama)
$bd1 = clean($is_en ? $p['bento_desc_1_en'] : $p['bento_desc_1']);
$bd2 = clean($is_en ? $p['bento_desc_2_en'] : $p['bento_desc_2']);
$bd3 = clean($is_en ? $p['bento_desc_3_en'] : $p['bento_desc_3']);

// ARRAY TXT FINAL
$txt = [
    'sect_about' => $label_about, // Panggil variabel yg udah dibenerin di atas
    'about_title' => $title_about,
    'sect_skills_label' => $label_skills,
    'sect_skills' => $title_skills,
    'sect_proj_title' => $proj_title,
    'sect_proj_desc' => $proj_desc,
    'sect_contact_1' => $contact_1,
    'sect_contact_2' => $contact_2,
    
    // Static Text
    'status_avail' => $is_en ? "Open for Collaboration" : "Terbuka untuk Kolaborasi",
    'stat_exp' => $is_en ? "Years Exp" : "Tahun Pengalaman",
    'stat_proj' => $is_en ? "Projects" : "Proyek Selesai",
    'stat_hiring' => $is_en ? "Available" : "Siap Kerja",
    'btn_port' => $is_en ? "View Portfolio" : "Lihat Portfolio",
    'btn_cv' => $is_en ? "Download CV" : "Unduh CV",
    'btn_email' => $is_en ? "Email Me" : "Kirim Email",
    'contact_sub' => $is_en ? "Available for freelance projects or full-time opportunities." : "Tersedia untuk proyek freelance maupun kesempatan karir full-time.",
    'footer' => $is_en ? "Built with Logic & Passion." : "Dibuat dengan Logika & Hati.",
    'chatbot_invite' => $is_en ? "Let's chat with my AI!" : "Ngobrol sama AI-ku yuk!",
    'read_more' => $is_en ? "View Details" : "Lihat Detail",
    'career_title' => $is_en ? "Professional Journey" : "Perjalanan Karir",
    'tab_work' => $is_en ? "Professional Work" : "Proyek Profesional",
    'tab_personal' => $is_en ? "Personal Projects" : "Proyek Pribadi"
];

$foto_profile = !empty($p['profile_pic']) && file_exists("assets/img/".$p['profile_pic']) ? "assets/img/".$p['profile_pic'] : "https://via.placeholder.com/600x750?text=No+Image";
$cv_url = (!empty($p['cv_link']) && strpos($p['cv_link'], 'http') !== false) ? $p['cv_link'] : "assets/doc/" . ($p['cv_link'] ?? '#');
$default_popup_img = "https://cdni.iconscout.com/illustration/premium/thumb/businessman-showing-thumbs-up-sign-2761821-2299873.png";
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($hero_title_raw); ?> | Portfolio</title>
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Satoshi', 'sans-serif'] },
                    colors: { bg: '#FAFAFA', primary: '#111827', secondary: '#6B7280', accent: '#2563EB' },
                    boxShadow: { 'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)' },
                    keyframes: { fadeInUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } } },
                    animation: { fadeInUp: 'fadeInUp 0.3s ease-out forwards' }
                }
            }
        }
    </script>
    <style>
        body { background-color: #FAFAFA; color: #111827; overflow-x: hidden; width: 100%; position: relative; }
        .island-box { background: white; border-radius: 2rem; padding: 3rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #F3F4F6; }
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.6); }
        .lang-btn { padding: 4px 12px; font-size: 11px; font-weight: 800; color: #6B7280; transition: all 0.3s; border-radius: 99px; }
        .lang-btn.active { color: #000; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; } 
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .db-desc ul { padding-left: 1.2rem; list-style-type: disc; } 
        .db-desc li { margin-bottom: 0.5rem; } 
        .db-desc b, .db-desc strong { color: #2563EB; font-weight: 800; }
        .tab-btn.active { background-color: #ffffff; color: #2563EB; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
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
                
                <div data-aos="fade-right" data-aos-duration="1000" class="-mt-20 lg:-mt-32 order-2 lg:order-1">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-gray-200 shadow-sm text-secondary text-[10px] font-bold uppercase tracking-widest mb-6">
                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span>
                        <?php echo $txt['status_avail']; ?>
                    </div>
                    
                    <div class="mb-6">
                        <h2 class="text-2xl md:text-3xl font-bold mb-4"> 
                            <span class="text-accent"><?php echo $hero_pre; ?></span> <br class="md:hidden">
                            <span class="text-primary"><?php echo $hero_greet_full; ?></span>,
                        </h2>
                        <h1 class="text-5xl md:text-7xl font-black leading-[1.1] text-primary tracking-tight"><?php echo $hero_title; ?></h1>
                    </div>

                    <div class="relative w-full max-w-xs mx-auto my-10 lg:hidden flex justify-center order-1 lg:order-2" data-aos="fade-up">
                        <div class="relative w-full z-10">
                            <div class="rounded-[2.5rem] overflow-hidden shadow-2xl rotate-2 border-[6px] border-white bg-white relative z-10">
                                <img src="<?php echo $foto_profile; ?>" alt="Profile" class="w-full h-auto object-cover" loading="lazy">
                            </div>
                            
                            <div class="absolute top-4 -right-2 flex flex-col gap-3 z-20 scale-90">
                                
                                <div class="w-16 h-16 bg-white rounded-full shadow-lg flex flex-col items-center justify-center border border-gray-100 animate-[bounce_3s_infinite] p-1">
                                    <span class="text-lg font-black text-primary leading-none mb-0.5"><?php echo $p['years_exp']; ?>+</span>
                                    <span class="text-[7.9px] font-bold text-gray-400 uppercase text-center leading-tight">
                                        <?php echo $txt['stat_exp']; ?>
                                    </span>
                                </div>

                                <div class="w-16 h-16 bg-primary rounded-full shadow-lg flex flex-col items-center justify-center border border-gray-800 animate-[bounce_3.5s_infinite] p-1">
                                    <span class="text-lg font-black text-white leading-none mb-0.5"><?php echo $p['projects_done']; ?>+</span>
                                    <span class="text-[7.9px] font-bold text-gray-400 uppercase text-center leading-tight">
                                        <?php echo $txt['stat_proj']; ?>
                                    </span>
                                </div>

                                <div class="w-16 h-16 bg-white rounded-full shadow-lg flex flex-col items-center justify-center border border-gray-100 animate-[bounce_4s_infinite] p-1">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                    <span class="text-[7.9px] font-bold text-primary uppercase text-center leading-tight">
                                        Available<br>For Hire
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="flex gap-6 mb-10 border-l-4 border-accent pl-6 mt-4 lg:mt-0">
                        <p class="text-lg text-gray-600 leading-relaxed max-w-lg font-medium"><?php echo $hero_desc; ?></p>
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
                        <div class="absolute top-10 right-4 flex flex-col gap-4 z-20">
                            <div class="w-20 h-20 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_3s_infinite]">
                                <span class="text-2xl font-black text-primary"><?php echo $p['years_exp']; ?>+</span>
                                <span class="text-[8.8px] font-bold text-gray-600 uppercase text-center"><?php echo $txt['stat_exp']; ?></span>
                            </div>
                            <div class="w-20 h-20 bg-primary rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-800 animate-[bounce_3.5s_infinite]">
                                <span class="text-2xl font-black text-white"><?php echo $p['projects_done']; ?>+</span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase text-center"><?php echo $txt['stat_proj']; ?></span>
                            </div>
                            <div class="w-20 h-20 bg-white rounded-full shadow-xl flex flex-col items-center justify-center border border-gray-100 animate-[bounce_4s_infinite]">
                                <div class="w-3 h-3 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                <span class="text-[10px] font-bold text-primary uppercase text-center leading-tight px-2">Available<br>For Hire</span>
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
                <span class="text-accent font-bold tracking-widest text-xs uppercase mb-2 block"><?php echo $label_skills; ?></span>
                <h2 class="text-4xl md:text-5xl font-black text-primary"><?php echo $title_skills; ?></h2>
            </div>
            <?php
            $skills = ['Analysis' => [], 'Enterprise' => [], 'Development' => []];
            foreach($skillsData as $s) {
                $cat_db = trim($s['category']); 
                if(in_array($cat_db, ['Analysis', 'Jira', 'Design'])) $skills['Analysis'][] = $s; 
                else if(in_array($cat_db, ['Enterprise', 'System', 'SAP'])) $skills['Enterprise'][] = $s; 
                else $skills['Development'][] = $s;
            }
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-accent mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-diagram-3"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt1; ?></h3>
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow"><p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $bd1; ?></p></div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Analysis'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm transition-all"><i class="<?php echo clean($item['icon']); ?>"></i> <?php echo clean($item['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-green-500 mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-kanban"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt2; ?></h3>
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow"><p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $bd2; ?></p></div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Enterprise'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm transition-all"><i class="<?php echo clean($item['icon']); ?>"></i> <?php echo clean($item['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="group p-8 rounded-3xl bg-gray-50 hover:bg-primary hover:text-white transition duration-500 h-full flex flex-col">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-xl text-purple-500 mb-6 shadow-sm group-hover:scale-110 transition"><i class="bi bi-code-slash"></i></div>
                    <h3 class="text-lg font-bold mb-3"><?php echo $bt3; ?></h3>
                    <div class="border-l-2 border-accent pl-4 mb-6 flex-grow"><p class="text-xs text-gray-500 group-hover:text-gray-400 leading-relaxed"><?php echo $bd3; ?></p></div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <?php foreach($skills['Development'] as $item): ?>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white border border-gray-200 text-[10px] font-bold text-gray-700 shadow-sm transition-all"><i class="<?php echo clean($item['icon']); ?>"></i> <?php echo clean($item['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="pt-8 pb-24 md:pt-16 md:pb-32 max-w-7xl mx-auto px-6">
        <div class="text-center mb-12" data-aos="fade-up">
            <span class="text-accent font-bold tracking-widest text-xs uppercase mb-4 block"><?php echo $label_about; ?></span>
            <h2 class="text-4xl md:text-5xl font-black text-primary leading-tight"><?php echo $title_about; ?></h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch lg:h-[600px]">
            <div class="flex flex-col gap-4 lg:h-[600px]" data-aos="fade-right">
                <div class="h-[65%] cursor-zoom-in relative group overflow-hidden rounded-[2rem] border-4 border-white bg-gray-200 shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_1']; ?>')">
                    <img src="assets/img/<?php echo $p['about_img_1']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700" loading="lazy">
                </div>
                <div class="h-[35%] grid grid-cols-2 gap-4">
                    <div class="cursor-zoom-in rounded-2xl overflow-hidden shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_2']; ?>')">
                        <img src="assets/img/<?php echo $p['about_img_2']; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500 bg-gray-200" loading="lazy">
                    </div>
                    <div class="cursor-zoom-in rounded-2xl overflow-hidden shadow-sm" onclick="openImageModal('assets/img/<?php echo $p['about_img_3']; ?>')">
                        <img src="assets/img/<?php echo $p['about_img_3']; ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500 bg-gray-200" loading="lazy">
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
                    <?php 
                    $careers = [];
                    foreach($timelineData as $row) { $careers[$row['company']][] = $row; }
                    $jsCareerData = [];
                    foreach($careers as $company => $roles) { $jsCareerData[$company] = $roles; }
                    foreach($careers as $company => $roles): 
                    ?>
                    <div class="group bg-gray-50 hover:bg-white hover:shadow-xl border border-gray-100 rounded-2xl p-5 cursor-pointer transition-all duration-300 transform hover:-translate-y-1 hover:border-accent/30" onclick="openCareerModal('<?php echo clean($company); ?>')">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold bg-accent/10 text-accent px-3 py-1 rounded-full"><?php echo clean($roles[0]['year']); ?></span>
                            <i class="bi bi-arrow-right-circle-fill text-gray-300 group-hover:text-accent text-2xl transition"></i>
                        </div>
                        <h5 class="text-lg font-black text-primary mb-1"><?php echo clean($company); ?></h5>
                        <p class="text-sm text-gray-500 font-medium mb-4 line-clamp-1"><?php echo clean($roles[0]['role']); ?></p>
                        <span class="text-xs font-bold text-accent border-b border-accent/20 group-hover:border-accent pb-0.5 transition"><?php echo $txt['read_more']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="projects" class="py-20 max-w-7xl mx-auto px-4" data-aos="fade-up">
        <div class="island-box bg-primary text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-accent/20 blur-[100px] rounded-full pointer-events-none"></div>
            
            <div class="mb-10 relative z-10 text-center md:text-left">
                <h2 class="text-4xl md:text-5xl font-black mb-2"><?php echo $proj_title; ?></h2>
                <p class="text-gray-400 max-w-xl"><?php echo $proj_desc; ?></p>
            </div>

            <div class="flex gap-2 mb-8 relative z-10 overflow-x-auto pb-2 scrollbar-hide">
                <button onclick="switchTab('work')" id="btn-work" class="tab-btn active px-6 py-2.5 rounded-full text-sm font-bold bg-white text-primary transition-all duration-300 border border-transparent hover:border-white/50">
                    <i class="bi bi-briefcase-fill me-2"></i> <?php echo $txt['tab_work']; ?>
                </button>
                <button onclick="switchTab('personal')" id="btn-personal" class="tab-btn px-6 py-2.5 rounded-full text-sm font-bold bg-white/10 text-white hover:bg-white/20 transition-all duration-300 border border-transparent border-white/10">
                    <i class="bi bi-code-square me-2"></i> <?php echo $txt['tab_personal']; ?>
                </button>
            </div>

            <div class="relative w-full">
                <div id="list-work" class="flex gap-6 overflow-x-auto pb-8 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php 
                    $hasWork = false;
                    foreach($projects as $idx => $d): 
                        if(strtolower($d['category']) == 'work'): 
                            $hasWork = true;
                    ?>
                    <div class="min-w-[320px] md:min-w-[400px] snap-center group cursor-pointer" onclick="openProjectModal(<?php echo $idx; ?>)">
                        <div class="bg-gray-800 rounded-[2rem] border border-white/10 overflow-hidden relative shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 h-full flex flex-col">
                            <div class="relative h-56 overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent z-10 opacity-60"></div>
                                <img src="<?php echo $d['image_url']; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                                <div class="absolute top-4 right-4 z-20">
                                    <span class="bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">WORK</span>
                                </div>
                            </div>
                            <div class="p-6 flex flex-col flex-1">
                                <h3 class="text-xl font-bold mb-2 group-hover:text-accent transition line-clamp-2"><?php echo clean($d['title']); ?></h3>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if(!empty($d['tech_stack'])) { $techs = explode(',', $d['tech_stack']); foreach(array_slice($techs, 0, 3) as $t): ?>
                                    <span class="text-[10px] font-bold bg-black/30 text-gray-300 px-2 py-1 rounded-md border border-white/5"><?php echo clean(trim($t)); ?></span>
                                    <?php endforeach; } ?>
                                </div>
                                <div class="mt-auto flex items-center gap-2 text-accent text-xs font-bold uppercase tracking-wider group-hover:translate-x-2 transition">
                                    <?php echo $txt['read_more']; ?> <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; if(!$hasWork): ?>
                        <div class="w-full text-center text-gray-500 py-10">No work projects yet.</div>
                    <?php endif; ?>
                </div>

                <div id="list-personal" class="hidden flex gap-6 overflow-x-auto pb-8 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php 
                    $hasPersonal = false;
                    foreach($projects as $idx => $d): 
                        if(strtolower($d['category']) == 'personal'): 
                            $hasPersonal = true;
                    ?>
                    <div class="min-w-[320px] md:min-w-[400px] snap-center group cursor-pointer" onclick="openProjectModal(<?php echo $idx; ?>)">
                        <div class="bg-gray-800 rounded-[2rem] border border-white/10 overflow-hidden relative shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 h-full flex flex-col">
                            <div class="relative h-56 overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent z-10 opacity-60"></div>
                                <img src="<?php echo $d['image_url']; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                                <div class="absolute top-4 right-4 z-20">
                                    <span class="bg-accent/20 backdrop-blur-md border border-accent/30 text-accent text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">PERSONAL</span>
                                </div>
                            </div>
                            <div class="p-6 flex flex-col flex-1">
                                <h3 class="text-xl font-bold mb-2 group-hover:text-accent transition line-clamp-2"><?php echo clean($d['title']); ?></h3>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if(!empty($d['tech_stack'])) { $techs = explode(',', $d['tech_stack']); foreach(array_slice($techs, 0, 3) as $t): ?>
                                    <span class="text-[10px] font-bold bg-black/30 text-gray-300 px-2 py-1 rounded-md border border-white/5"><?php echo clean(trim($t)); ?></span>
                                    <?php endforeach; } ?>
                                </div>
                                <div class="mt-auto flex items-center gap-2 text-accent text-xs font-bold uppercase tracking-wider group-hover:translate-x-2 transition">
                                    <?php echo $txt['read_more']; ?> <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; if(!$hasPersonal): ?>
                        <div class="w-full text-center text-gray-500 py-10">No personal projects yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-32 text-center relative overflow-hidden" data-aos="zoom-in">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-blue-100 to-purple-100 rounded-full blur-[120px] -z-10 opacity-70"></div>
        <div class="max-w-4xl mx-auto px-6 relative z-10">
            <h2 class="text-5xl md:text-7xl font-black text-primary mb-8 tracking-tight leading-tight">
                <?php echo $contact_1; ?><br><span class="text-transparent bg-clip-text bg-gradient-to-r from-accent to-purple-600"><?php echo $contact_2; ?></span>
            </h2>
            <p class="text-xl text-gray-500 mb-12 max-w-xl mx-auto leading-relaxed"><?php echo $txt['contact_sub']; ?></p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="mailto:<?php echo sanitizeLink($p['email'], 'email'); ?>" class="bg-primary text-white px-10 py-5 rounded-full font-bold text-lg hover:bg-accent transition shadow-xl hover:shadow-glow hover:-translate-y-1"><?php echo $txt['btn_email']; ?></a>
                <a href="https://wa.me/<?php echo sanitizeLink($p['whatsapp'], 'number'); ?>" target="_blank" class="bg-white text-primary border border-gray-200 px-10 py-5 rounded-full font-bold text-lg hover:bg-gray-50 transition shadow-md hover:-translate-y-1">WhatsApp</a>
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
            <iframe src="apps/api/chat.php" class="w-full flex-1 border-0" loading="lazy"></iframe>
        </div>
    </div>

    <div id="projectModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-primary/95 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-5xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out] relative h-auto max-h-[90vh] flex flex-col md:flex-row">
                <div class="w-full md:w-5/12 bg-gray-100 h-64 md:h-auto relative">
                    <img id="modalImg" src="" class="w-full h-full object-cover">
                    <button onclick="closeModal()" class="absolute top-4 left-4 bg-white/50 p-2 rounded-full md:hidden"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="w-full md:w-7/12 flex flex-col h-full overflow-hidden">
                    <div class="p-8 md:p-10 flex-1 overflow-y-auto custom-scroll">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span id="modalCat" class="text-[10px] font-bold bg-accent/10 text-accent px-3 py-1 rounded-full uppercase tracking-widest mb-3 inline-block">Category</span>
                                <h3 id="modalTitle" class="text-3xl md:text-4xl font-black text-primary leading-tight"></h3>
                            </div>
                            <button onclick="closeModal()" class="hidden md:block bg-gray-100 hover:bg-red-50 hover:text-red-500 w-10 h-10 rounded-full flex-none flex items-center justify-center transition"><i class="bi bi-x-lg"></i></button>
                        </div>

                        <div class="mb-8">
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Tech Stack</h5>
                            <div id="modalTech" class="flex flex-wrap gap-2"></div>
                        </div>

                        <div class="space-y-8">
                            <div>
                                <h5 class="font-bold text-primary text-lg mb-2 flex items-center gap-2"><i class="bi bi-info-circle text-accent"></i> Overview</h5>
                                <div id="modalDesc" class="text-gray-600 leading-relaxed db-desc prose prose-sm max-w-none"></div>
                            </div>
                            
                            <div id="boxChallenge" class="bg-red-50 p-6 rounded-2xl border border-red-100">
                                <h5 class="font-bold text-red-600 text-sm uppercase tracking-wider mb-2">The Challenge</h5>
                                <div id="modalChallenge" class="text-gray-700 text-sm leading-relaxed db-desc"></div>
                            </div>
                            
                            <div id="boxImpact" class="bg-green-50 p-6 rounded-2xl border border-green-100">
                                <h5 class="font-bold text-green-600 text-sm uppercase tracking-wider mb-2">The Impact</h5>
                                <div id="modalImpact" class="text-gray-700 text-sm leading-relaxed db-desc"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 border-t border-gray-100 bg-white sticky bottom-0 z-10 flex gap-4">
                        <a id="modalLink" href="#" target="_blank" class="flex-1 bg-primary text-white py-4 rounded-xl font-bold text-center hover:bg-accent transition shadow-lg">Visit Project <i class="bi bi-arrow-up-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 z-[110] hidden" onclick="closeImageModal()">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <img id="lightboxImg" src="" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl animate-[fadeInUp_0.3s_ease-out]" loading="lazy">
            <button class="absolute top-6 right-6 text-white text-4xl hover:text-accent">&times;</button>
        </div>
    </div>

    <div id="careerModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-primary/80 backdrop-blur-sm transition-opacity" onclick="closeCareerModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-5xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out] relative h-auto md:h-[80vh] flex flex-col md:flex-row max-h-[85vh]">
                <div class="w-full md:w-1/3 bg-blue-50 flex items-center justify-center p-4 md:p-8 border-b md:border-b-0 md:border-r border-gray-100 relative min-h-[60px] md:min-h-0">
                    <img id="careerCartoon" src="" class="hidden md:block w-48 md:w-full drop-shadow-xl animate-[bounce_4s_infinite]" onerror="this.style.display='none'">
                    <div class="relative md:absolute md:bottom-4 text-center w-full text-xs font-bold uppercase tracking-widest text-black md:text-gray-400 opacity-100 md:opacity-50">Career Highlights</div>
                </div>
                <div class="w-full md:w-2/3 flex flex-col h-full overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center bg-white sticky top-0 z-10 flex-none">
                        <h3 id="careerCompany" class="text-xl md:text-2xl font-black text-primary"></h3>
                        <button onclick="closeCareerModal()" class="bg-gray-100 hover:bg-red-50 hover:text-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scroll"><div id="careerContent" class="p-6 md:p-8"></div></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DATA AMAN (JSON)
        const careerData = <?php echo json_encode($jsCareerData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const projectData = <?php echo json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const is_en = <?php echo $is_en ? 'true' : 'false'; ?>;
        const defaultCartoon = "<?php echo $default_popup_img; ?>";
    </script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, offset: 50, once: true, easing: 'ease-out-cubic' });
        
        // TAB SWITCHER LOGIC
        function switchTab(tab) {
            const listWork = document.getElementById('list-work');
            const listPersonal = document.getElementById('list-personal');
            const btnWork = document.getElementById('btn-work');
            const btnPersonal = document.getElementById('btn-personal');

            if(tab === 'work') {
                listWork.classList.remove('hidden'); listPersonal.classList.add('hidden');
                btnWork.className = "tab-btn active px-6 py-2.5 rounded-full text-sm font-bold bg-white text-primary transition-all duration-300 border border-transparent shadow-md";
                btnPersonal.className = "tab-btn px-6 py-2.5 rounded-full text-sm font-bold bg-white/10 text-white hover:bg-white/20 transition-all duration-300 border border-transparent border-white/10";
            } else {
                listWork.classList.add('hidden'); listPersonal.classList.remove('hidden');
                btnPersonal.className = "tab-btn active px-6 py-2.5 rounded-full text-sm font-bold bg-white text-primary transition-all duration-300 border border-transparent shadow-md";
                btnWork.className = "tab-btn px-6 py-2.5 rounded-full text-sm font-bold bg-white/10 text-white hover:bg-white/20 transition-all duration-300 border border-transparent border-white/10";
            }
        }

        // PROJECT MODAL LOGIC (PRO)
        function openProjectModal(index) {
            const p = projectData[index];
            const desc = is_en && p.description_en ? p.description_en : p.description;
            
            document.getElementById('modalTitle').innerText = p.title;
            document.getElementById('modalCat').innerText = p.category;
            document.getElementById('modalImg').src = p.image_url;
            document.getElementById('modalDesc').innerHTML = desc;
            
            // Tech Stack
            let techHtml = '';
            if(p.tech_stack) {
                p.tech_stack.split(',').forEach(t => {
                    techHtml += `<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-lg text-xs font-bold border border-gray-200">${t.trim()}</span>`;
                });
            }
            document.getElementById('modalTech').innerHTML = techHtml;

            // Challenge & Impact (Hide if empty)
            const chalBox = document.getElementById('boxChallenge');
            const impBox = document.getElementById('boxImpact');
            
            if(p.challenge && p.challenge.trim() !== '') {
                document.getElementById('modalChallenge').innerHTML = p.challenge;
                chalBox.classList.remove('hidden');
            } else { chalBox.classList.add('hidden'); }

            if(p.impact && p.impact.trim() !== '') {
                document.getElementById('modalImpact').innerHTML = p.impact;
                impBox.classList.remove('hidden');
            } else { impBox.classList.add('hidden'); }

            // Link
            const btn = document.getElementById('modalLink');
            if(p.link_demo && p.link_demo !== '#' && p.link_demo !== '') {
                btn.href = p.link_demo;
                btn.classList.remove('hidden'); 
            } else { btn.classList.add('hidden'); }

            document.getElementById('projectModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
        }

        function closeModal() { 
            document.getElementById('projectModal').classList.add('hidden'); 
            document.body.style.overflow = 'auto'; 
        }

        // ... (Sisa fungsi ImageModal, CareerModal, Chatbot sama seperti sebelumnya) ...
        function openImageModal(src) { document.getElementById('lightboxImg').src = src; document.getElementById('imageModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        function closeImageModal() { document.getElementById('imageModal').classList.add('hidden'); document.body.style.overflow = 'auto'; }
        
        function openCareerModal(company) {
            document.getElementById('careerModal').classList.remove('hidden'); document.body.style.overflow = 'hidden';
            document.getElementById('careerCompany').innerText = company;
            const roles = careerData[company];
            let foundImg = defaultCartoon;
            if(roles) { for (let i = 0; i < roles.length; i++) { if (roles[i].image && roles[i].image.trim() !== "") { foundImg = 'assets/img/' + roles[i].image; break; } } }
            const imgEl = document.getElementById('careerCartoon'); imgEl.src = foundImg; imgEl.style.display = ''; imgEl.classList.remove('hidden'); 
            if(window.innerWidth < 768) imgEl.classList.add('hidden'); else imgEl.classList.add('md:block');
            let html = roles ? (roles.length > 1 ? '<div class="grid grid-cols-1 gap-6">' : '<div>') : '';
            if(roles) { roles.forEach(role => { html += `<div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:border-accent/30 transition shadow-sm hover:shadow-md"><div class="flex justify-between items-start mb-4 border-b border-gray-200 pb-3"><div><h4 class="font-bold text-lg text-primary">${role.role}</h4><div class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-1">Full Time</div></div><span class="text-xs font-bold bg-primary text-white px-3 py-1 rounded-full text-center h-fit">${role.year}</span></div><div class="text-sm text-gray-600 leading-relaxed space-y-2 prose prose-sm max-w-none"><div class="db-desc">${role.description}</div></div></div>`; }); html += '</div>'; }
            document.getElementById('careerContent').innerHTML = html;
        }
        function closeCareerModal() { document.getElementById('careerModal').classList.add('hidden'); document.body.style.overflow = 'auto'; }
        function toggleChatbot() { document.getElementById('chatbotFrame').classList.toggle('hidden'); }
    </script>
</body>
</html>