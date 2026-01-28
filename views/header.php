<?php
// views/header.php - VISUAL FIXED (Satoshi Font + Original Tailwind Config)

// 1. KONEKSI DATABASE
require_once __DIR__ . '/../config/database.php';

if (!isset($pdo)) {
    die("<h3>Fatal Error:</h3> <p>Koneksi Database Gagal. Cek config/database.php</p>");
}

// 2. LOGIKA BAHASA
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en']) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'id');
$_SESSION['lang'] = $lang;
$is_en = ($lang == 'en');

// 3. FETCH DATA
try {
    $stmt = $pdo->query("SELECT * FROM profile LIMIT 1");
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) $p = [];

    $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM tech_stacks ORDER BY category ASC");
    $skillsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM timeline ORDER BY sort_date DESC");
    $timelineData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM certifications ORDER BY id DESC");
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// 4. PREPARE VARIABLES
$profile_pic_name = $p['profile_pic'] ?? 'default.jpg';
$foto_profile = file_exists(__DIR__ . "/../assets/img/" . $profile_pic_name) ? "assets/img/" . $profile_pic_name : "assets/img/default.jpg";

$cv_link_db = $p['cv_link'] ?? '#';
$cv_url = (strpos($cv_link_db, 'http') !== false) ? $cv_link_db : "assets/doc/" . $cv_link_db;

// Image URL Logic
foreach ($projects as &$row) {
    $img_name = $row['image'] ?? 'default.jpg';
    $row['image_url'] = file_exists(__DIR__ . '/../assets/img/' . $img_name) ? 'assets/img/' . $img_name : 'assets/img/default.jpg';
}
unset($row);

// Default Cartoon
$default_popup_img = "https://cdni.iconscout.com/illustration/premium/thumb/businessman-showing-thumbs-up-sign-2761821-2299873.png";

// 5. TEXT HELPER & DICTIONARY
function txt($key_en, $key_id, $default_val = '') {
    global $is_en, $p;
    if ($is_en) return !empty($p[$key_en]) ? $p[$key_en] : $default_val;
    return !empty($p[$key_id]) ? $p[$key_id] : $default_val;
}

$txt = [
    'nav_home' => $is_en ? 'Home' : 'Beranda',
    'nav_about' => $is_en ? 'About' : 'Tentang',
    'nav_skills' => $is_en ? 'Skills' : 'Keahlian',
    'nav_projects' => $is_en ? 'Projects' : 'Proyek',
    'nav_contact' => $is_en ? 'Contact' : 'Kontak',

    'hero_pre' => txt('hero_pre_en', 'hero_pre', 'Hello Everyone 👋'),
    'hero_greeting' => txt('hero_greeting_en', 'hero_greeting', "Hi, I'm Ferry"),
    'hero_title_raw' => txt('hero_title_en', 'hero_title', 'IT Analyst'),
    'hero_desc' => txt('hero_desc_en', 'hero_desc', 'Welcome to my portfolio.'),
    'btn_cv' => $is_en ? 'Download CV' : 'Unduh CV',
    'btn_connect' => $is_en ? "Let's Talk" : 'Ayo Ngobrol',

    'status_avail' => $is_en ? "Open for Work" : "Siap Kerja",
    'stat_exp' => $is_en ? "Years Exp" : "Tahun Pengalaman",
    'stat_proj' => $is_en ? "Projects" : "Proyek Selesai",
    'btn_port' => $is_en ? "View Portfolio" : "Lihat Portfolio",

    'sect_about' => txt('tentang_saya_en', 'tentang_saya', 'About Me'),
    'about_title' => txt('about_title_en', 'about_title', 'Analyst & Leader'),
    'career_title' => $is_en ? 'Career Journey' : 'Perjalanan Karir',
    'read_more' => $is_en ? 'View Details' : 'Lihat Detail',

    'bento_desc_1' => txt('bento_desc_1_en', 'bento_desc_1', 'Analyzing...'),
    'bento_desc_2' => txt('bento_desc_2_en', 'bento_desc_2', 'Managing...'),
    'bento_desc_3' => txt('bento_desc_3_en', 'bento_desc_3', 'Developing...'),

    'sect_skills_label' => txt('skills_en', 'skills', 'Core Competencies'),
    'sect_skills' => txt('title_skills_en', 'title_skills', 'Technical Arsenal'),
    'cert_title' => $is_en ? 'Certifications' : 'Sertifikasi',

    'sect_proj_title' => txt('project_title_en', 'project_title', 'Projects'),
    'sect_proj_desc' => txt('project_desc_en', 'project_desc', 'Collection of projects.'),
    'tab_work' => $is_en ? 'Work Projects' : 'Proyek Kerja',
    'tab_personal' => $is_en ? 'Personal Projects' : 'Proyek Pribadi',

    'sect_contact_1' => txt('title_contact_1_en', 'title_contact_1', 'Ready to'),
    'sect_contact_2' => txt('title_contact_2_en', 'title_contact_2', 'Collaborate?'),
    'contact_sub' => $is_en ? "Available for freelance or full-time." : "Tersedia untuk freelance atau full-time.",
    'btn_email' => $is_en ? "Email Me" : "Kirim Email",
    'footer' => $is_en ? "All Rights Reserved." : "Hak Cipta Dilindungi.",
    'chatbot_invite' => $is_en ? "Chat with AI" : "Tanya AI",
];

// Helper Functions
function clean($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function sanitizeLink($input, $type) {
    if ($type == 'email') return filter_var($input, FILTER_SANITIZE_EMAIL);
    return preg_replace('/[^0-9]/', '', $input);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($txt['hero_greeting']); ?> - Portfolio</title>
    
    <?php if(!empty($p['profile_pic'])): ?>
    <link rel="icon" type="image/png" href="assets/img/<?php echo $p['profile_pic']; ?>">
    <?php endif; ?>

    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,400&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Satoshi', 'sans-serif'] }, // Balik ke Satoshi
                    colors: { bg: '#FAFAFA', primary: '#111827', secondary: '#6B7280', accent: '#2563EB' },
                    boxShadow: { 'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)' },
                    keyframes: { fadeInUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } } },
                    animation: { fadeInUp: 'fadeInUp 0.3s ease-out forwards' }
                }
            }
        }
    </script>
    
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* CSS Tambahan Biar Aman */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .glass-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.3); }
        .island-box { background: #0f172a; border-radius: 2rem; padding: 3rem; position: relative; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="antialiased selection:bg-accent selection:text-white relative">