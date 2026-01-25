<?php 
// 🛡️ MATIKAN PESAN WARNING (Biar tampilan bersih)
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php'; 

// Security Headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Helper Functions
function clean($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function sanitizeLink($input, $type) {
    if ($type == 'email') return filter_var($input, FILTER_SANITIZE_EMAIL);
    return preg_replace('/[^0-9]/', '', $input);
}

// 🔥 FUNGSI SAKTI (VERSI SIMPLE & KUAT)
// Menerima nilai langsung (bukan array), cek kosong, bersihkan.
function db_val($val, $default = '') {
    return (!empty($val)) ? clean($val) : $default;
}

// Caching
function getCachedData($conn, $key, $sql) {
    $cacheFile = __DIR__ . "/../cache/{$key}.json";
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        $json = json_decode(file_get_contents($cacheFile), true);
        if($json) return $json;
    }
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_dir(__DIR__ . '/../cache')) mkdir(__DIR__ . '/../cache', 0755, true);
        file_put_contents($cacheFile, json_encode($data));
        return $data;
    } catch (Exception $e) { return []; }
}

session_start();
$lang = (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'])) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'id');
$_SESSION['lang'] = $lang;
$is_en = ($lang == 'en');

// =======================
// 📥 DATA FETCHING
// =======================
$timelineData = getCachedData($db, "timeline", "SELECT * FROM timeline ORDER BY sort_date DESC");
$skillsData   = getCachedData($db, "skills", "SELECT * FROM tech_stacks ORDER BY category ASC");

$stmt = $db->prepare("SELECT * FROM projects ORDER BY id DESC");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($projects as &$row) {
    $row['image_url'] = (!empty($row['image']) && file_exists(__DIR__ . '/../assets/img/'.$row['image'])) ? 'assets/img/'.$row['image'] : 'assets/img/default.jpg';
}
unset($row);

// Profile
$stmt = $db->prepare("SELECT * FROM profile WHERE id=1");
$stmt->execute();
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) $p = []; 

// =======================
// 📝 TEXT MAPPING (FIXED)
// =======================
// Menggunakan @ di depan variabel DB ($p) untuk suppress warning "Undefined Index"

// Hero
$hero_pre   = $is_en ? db_val(@$p['hero_pre_en'], 'Hello Everyone 👋') : db_val(@$p['hero_pre'], 'Halo Semuanya 👋');
$hero_greet = $is_en ? db_val(@$p['hero_greeting_en'], 'Hi, my name is Ferry') : db_val(@$p['hero_greeting'], 'Hallo, saya Ferry');
$hero_title_raw = $is_en ? db_val(@$p['hero_title_en'], 'IT Analyst') : db_val(@$p['hero_title'], 'IT Analyst');
$hero_title = str_replace('| ', '| <br class="hidden md:block">', $hero_title_raw);
$hero_desc  = $is_en ? db_val(@$p['hero_desc_en'], 'Welcome.') : db_val(@$p['hero_desc'], 'Selamat datang.');

// Text Array
$txt = [
    'sect_about' => $is_en ? db_val(@$p['tentang_saya_en'], 'About Me') : db_val(@$p['tentang_saya'], 'Tentang Saya'),
    'about_title' => $is_en ? db_val(@$p['about_title_en'], 'Analyst') : db_val(@$p['about_title'], 'Analis'),
    'sect_skills_label' => $is_en ? db_val(@$p['skills_en'], 'Competencies') : db_val(@$p['skills'], 'Kompetensi'),
    'sect_skills' => $is_en ? db_val(@$p['title_skills_en'], 'Tech Arsenal') : db_val(@$p['title_skills'], 'Keahlian Teknis'),
    'sect_proj_title' => $is_en ? db_val(@$p['project_title_en'], 'Projects') : db_val(@$p['project_title'], 'Proyek'),
    'sect_proj_desc' => $is_en ? db_val(@$p['project_desc_en'], 'My Works') : db_val(@$p['project_desc'], 'Karya Saya'),
    'sect_contact_1' => $is_en ? db_val(@$p['title_contact_1_en'], 'Ready?') : db_val(@$p['title_contact_1'], 'Siap?'),
    'sect_contact_2' => $is_en ? db_val(@$p['title_contact_2_en'], 'Lets Talk') : db_val(@$p['title_contact_2'], 'Ayo Bicara'),
    
    // Static
    'status_avail' => $is_en ? "Open for Collaboration" : "Terbuka untuk Kolaborasi",
    'stat_exp' => $is_en ? "Years Exp" : "Tahun Pengalaman",
    'stat_proj' => $is_en ? "Projects" : "Proyek Selesai",
    'stat_hiring' => $is_en ? "Available" : "Siap Kerja",
    'btn_port' => $is_en ? "View Portfolio" : "Lihat Portfolio",
    'btn_cv' => $is_en ? "Download CV" : "Unduh CV",
    'btn_email' => $is_en ? "Email Me" : "Kirim Email",
    'contact_sub' => $is_en ? "Available for freelance or full-time." : "Tersedia untuk freelance atau full-time.",
    'footer' => $is_en ? "Built with Logic & Passion." : "Dibuat dengan Logika & Hati.",
    'chatbot_invite' => $is_en ? "Let's chat with my AI!" : "Ngobrol sama AI-ku yuk!",
    'read_more' => $is_en ? "View Details" : "Lihat Detail",
    'career_title' => $is_en ? "Professional Journey" : "Perjalanan Karir",
    'tab_work' => $is_en ? "Professional Work" : "Proyek Profesional",
    'tab_personal' => $is_en ? "Personal Projects" : "Proyek Pribadi"
];

$foto_profile = !empty($p['profile_pic']) && file_exists(__DIR__ . "/../assets/img/".$p['profile_pic']) ? "assets/img/".$p['profile_pic'] : "assets/img/default.jpg";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased selection:bg-accent selection:text-white relative">