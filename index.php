<?php 
// 1. KONEKSI & AMBIL DATA PROFILE
include 'koneksi.php'; 
// Ambil data dari tabel 'profile' (ID=1)
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));

// Helper Function: Bersihin teks dari enter/new line biar gak ngerusak JS
function clean($str) {
    return addslashes(str_replace(array("\r", "\n"), "", $str));
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferry Fernando | Functional Analyst</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* ==========================================
           👉 DESIGN SYSTEM (DARK/LIGHT)
           ========================================== */
        :root {
            --bg-body: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --text-sub: #94a3b8;
            --accent: #3b82f6; --border-color: rgba(255,255,255,0.1); --nav-bg: rgba(15, 23, 42, 0.85);
        }
        [data-theme="light"] {
            --bg-body: #f8fafc; --bg-card: #ffffff; --text-main: #0f172a; --text-sub: #64748b;
            --accent: #2563eb; --border-color: rgba(0,0,0,0.1); --nav-bg: rgba(255, 255, 255, 0.85);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-main); transition: 0.3s; overflow-x: hidden; }
        
        /* NAVBAR (DIBIKIN PUTIH TEKSNYA) */
        .navbar { background: var(--nav-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border-color); padding: 15px 0; transition: 0.3s; }
        
        /* 👉 PERUBAHAN 1: TEXT NAVBAR JADI PUTIH (WHITE) */
        .nav-link { color: #f8fafc !important; font-weight: 500; transition: 0.3s; } 
        /* Kalau light mode, balik jadi gelap biar kebaca */
        [data-theme="light"] .nav-link { color: #0f172a !important; }
        
        .nav-link:hover, .nav-link.active { color: var(--accent) !important; }
        
        /* HERO SECTION */
        .hero-section { padding: 130px 0 80px; }
        .hero-badge { background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 8px 16px; border-radius: 30px; font-weight: 600; font-size: 0.85rem; border: 1px solid rgba(59, 130, 246, 0.2); display: inline-block; margin-bottom: 20px; }
        .hero-title { font-size: 3.5rem; font-weight: 800; line-height: 1.1; letter-spacing: -1px; background: linear-gradient(to right, var(--text-main), var(--text-sub)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* FOTO PROFIL (STYLE LAMA - BULAT & TOP CENTER) */
        .profile-img { width: 320px; height: 320px; object-fit: cover; object-position: top center; border-radius: 50%; border: 4px solid var(--bg-card); box-shadow: 0 20px 40px rgba(0,0,0,0.3); transition: 0.3s; }
        .profile-img:hover { transform: scale(1.05); border-color: var(--accent); }

        /* HORIZONTAL SCROLL PROJECTS (STYLE LAMA - WAJIB ADA) */
        .project-scroll-wrapper { display: flex; overflow-x: auto; gap: 24px; padding-bottom: 20px; scroll-behavior: smooth; scroll-padding-left: 0; }
        .project-scroll-wrapper::-webkit-scrollbar { height: 8px; }
        .project-scroll-wrapper::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .project-scroll-wrapper::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .project-col { min-width: 350px; max-width: 350px; flex: 0 0 auto; } /* INI YG BIKIN SLIDE JALAN */
        
        /* CARD STYLE */
        .project-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; height: 100%; transition: 0.3s; display: flex; flex-direction: column; }
        .project-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .tech-pill { background: rgba(255,255,255,0.05); color: var(--text-sub); font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; margin-right: 5px; display: inline-block; margin-bottom: 5px; border: 1px solid var(--border-color); }

        /* BUTTONS */
        .btn-primary-custom { background-color: var(--accent); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; transition: 0.3s; }
        .btn-primary-custom:hover { background-color: #2563eb; transform: translateY(-2px); color: white; }
        .btn-outline-custom { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); padding: 12px 30px; border-radius: 8px; font-weight: 600; transition: 0.3s; }
        .btn-outline-custom:hover { border-color: var(--accent); color: var(--accent); }

        /* UTILS */
        .lang-switch { cursor: pointer; font-weight: 700; font-size: 0.85rem; color: inherit; transition: 0.3s; }
        .lang-switch.active { color: var(--accent); }
        .theme-toggle { cursor: pointer; color: inherit; transition: 0.3s; padding: 5px; border-radius: 50%; border: 1px solid var(--border-color); }
        .theme-toggle:hover { color: var(--accent); border-color: var(--accent); }
        
        /* Modal Fix */
        .modal-content { background-color: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        [data-theme="light"] .btn-close { filter: none; }

        /* TIMELINE & CONTACT */
        .timeline-item { border-left: 2px solid var(--border-color); padding-left: 20px; margin-bottom: 30px; position: relative; }
        .timeline-item::before { content: ''; width: 10px; height: 10px; background: var(--accent); border-radius: 50%; position: absolute; left: -6px; top: 5px; }
        .contact-card { background: var(--bg-card); border: 1px solid var(--border-color); padding: 25px; border-radius: 16px; text-align: center; transition: 0.3s; height: 100%; }
        .contact-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .contact-icon { font-size: 2rem; color: var(--accent); margin-bottom: 10px; }

        /* 👉 PERUBAHAN 2: TAB NAVIGASI JADI PUTIH */
        .nav-pills .nav-link { 
            color: rgba(255,255,255,0.7); /* Putih agak transparan */
            border-radius: 30px; 
            padding: 8px 25px; 
            font-weight: 600; 
            border: 1px solid transparent; 
        }
        [data-theme="light"] .nav-pills .nav-link { color: #64748b; } /* Kalau light mode, abu */
        
        .nav-pills .nav-link.active { background-color: var(--accent); color: white !important; }
        .nav-pills .nav-link:hover:not(.active) { color: white; border-color: rgba(255,255,255,0.2); }
    </style>
</head>

<body data-bs-spy="scroll" data-bs-target=".navbar" data-bs-offset="100">

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#" onclick="window.scrollTo(0,0)">
                <svg width="40" height="40" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="80" fill="#0a0d18"/>
                    <path d="M180 96 L332 96 L356 420 L256 448 L156 420 Z" fill="#ffffff"/>
                    <path d="M256 140 L256 380" stroke="#0a0d18" stroke-width="14" stroke-linecap="round"/>
                </svg>
            </a>

            <div class="d-flex align-items-center gap-4 order-lg-3">
                <div class="theme-toggle text-white" id="themeToggle"><i class="bi bi-sun-fill" id="themeIcon"></i></div>
                
                <div class="text-white fw-bold">
                    <span class="lang-switch active" onclick="setLanguage('id')" id="btn-id">ID</span> 
                    <span class="mx-1">|</span> 
                    <span class="lang-switch" onclick="setLanguage('en')" id="btn-en">EN</span>
                </div>

                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto me-4 align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home" data-lang="nav_home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects" data-lang="nav_projects">Proyek</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about" data-lang="nav_about">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact" data-lang="nav_contact">Kontak</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center flex-column-reverse flex-lg-row">
                <div class="col-lg-7 mt-5 mt-lg-0">
                    <div class="hero-badge mb-3" data-lang="hero_greeting"><?php echo $p['hero_greeting']; ?></div>
                    <h1 class="display-3 hero-title mb-4" data-lang="hero_title"><?php echo $p['hero_title']; ?></h1>
                    <p class="lead mb-5 w-75" style="color: var(--text-sub);" data-lang="hero_desc"><?php echo $p['hero_desc']; ?></p>
                    <div class="d-flex gap-3">
                        <a href="#projects" class="btn btn-primary-custom" data-lang="btn_work">Lihat Karya</a>
                        <a href="secure-doc.php?token=ax99-secure-access" target = "_blank" class="btn btn-outline-custom" data-lang="btn_cv">
                          <i class="bi bi-file-earmark-person me-2"></i> Unduh CV
                      </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center">
                    <img src="assets/img/<?php echo $p['profile_pic']; ?>" class="profile-img" alt="Ferry Fernando">
                </div>
            </div>
        </div>
    </section>

    <section id="projects" class="py-5">
        <div class="container">
            
            <div class="row mb-4 align-items-end">
                <div class="col-lg-8">
                    <h5 class="text-primary fw-bold text-uppercase fs-6 mb-2">Portfolio</h5>
                    <h2 class="fw-bold display-6" style="color: var(--text-main);" data-lang="proj_title"><?php echo $p['project_title']; ?></h2>
                    <p style="color: var(--text-sub);" data-lang="proj_subtitle"><?php echo $p['project_desc']; ?></p>
                </div>
                <div class="col-lg-4 text-lg-end d-none d-lg-block">
                    <span class="text-secondary small border px-3 py-1 rounded-pill" style="border-color: var(--border-color)!important;">
                        <i class="bi bi-arrow-left"></i> Scroll <i class="bi bi-arrow-right"></i>
                    </span>
                </div>
            </div>

            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="pills-work-tab" data-bs-toggle="pill" data-bs-target="#pills-work" data-lang="tab_work">🏢 Work Projects</button></li>
                <li class="nav-item"><button class="nav-link" id="pills-personal-tab" data-bs-toggle="pill" data-bs-target="#pills-personal" data-lang="tab_personal">🚀 Personal Projects</button></li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-work">
                    <div class="project-scroll-wrapper">
                        <?php
                        $q_work = mysqli_query($conn, "SELECT * FROM projects WHERE category='work' ORDER BY id DESC");
                        while ($row = mysqli_fetch_assoc($q_work)) { include 'component/card_project.php'; }
                        ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-personal">
                    <div class="project-scroll-wrapper">
                        <?php
                        $q_pers = mysqli_query($conn, "SELECT * FROM projects WHERE category='personal' OR category IS NULL ORDER BY id DESC");
                        while ($row = mysqli_fetch_assoc($q_pers)) { include 'component/card_project.php'; }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5" style="background-color: rgba(255,255,255,0.02);">
        <div class="container">
            <div class="row gx-lg-5 align-items-start">
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <h5 class="text-primary fw-bold text-uppercase fs-6 mb-3" data-lang="about_head">TENTANG SAYA</h5>
                    <h2 class="fw-bold display-6 mb-4" style="color: var(--text-main);" data-lang="about_title">Lebih dari sekadar Technical.</h2>
                    <p style="color: var(--text-sub); white-space: pre-line;" data-lang="about_desc"><?php echo $p['about_text']; ?></p>
                    <div class="row g-3 mt-2">
                        <div class="col-4"><h2 class="fw-bold text-primary mb-0"><?php echo $p['years_exp']; ?>+</h2><small class="text-muted" data-lang="stat_exp">Years Exp</small></div>
                        <div class="col-4"><h2 class="fw-bold text-primary mb-0"><?php echo $p['projects_done']; ?>+</h2><small class="text-muted" data-lang="stat_proj">Projects</small></div>
                        <div class="col-4"><h2 class="fw-bold text-primary mb-0"><?php echo $p['client_happy']; ?>%</h2><small class="text-muted">Satisfaction</small></div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <h5 class="text-primary fw-bold text-uppercase fs-6 mb-4" data-lang="career_head">PERJALANAN KARIR</h5>
                    <div class="ps-2">
                        <?php
                        $q_time = mysqli_query($conn, "SELECT * FROM timeline ORDER BY id DESC");
                        if(mysqli_num_rows($q_time) > 0) {
                            while($t = mysqli_fetch_assoc($q_time)):
                        ?>
                        <div class="timeline-item">
                            <div style="font-weight: 700; color: var(--accent);"><?php echo $t['year']; ?></div>
                            <h5 style="color: var(--text-main); font-weight: 700; margin: 5px 0;"><?php echo $t['role']; ?></h5>
                            <div style="color: var(--text-sub); font-size: 0.9rem; margin-bottom: 8px;"><?php echo $t['company']; ?></div>
                            <p class="small mb-0" style="color: var(--text-sub);"><?php echo $t['description']; ?></p>
                        </div>
                        <?php endwhile; } else { echo "<p class='text-muted'>Belum ada data timeline.</p>"; } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5">
        <div class="container text-center">
            <h5 class="text-primary fw-bold text-uppercase fs-6 mb-2" data-lang="contact_head">KONTAK</h5>
            <h2 class="fw-bold display-6 mb-4" style="color: var(--text-main);" data-lang="contact_title">Mari Berkolaborasi</h2>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="mailto:<?php echo $p['email']; ?>" class="btn btn-primary-custom px-4"><i class="bi bi-envelope-fill me-2"></i> Email</a>
                <a href="https://wa.me/<?php echo $p['whatsapp']; ?>" class="btn btn-outline-custom px-4"><i class="bi bi-whatsapp me-2"></i> WhatsApp</a>
                <a href="<?php echo $p['linkedin']; ?>" class="btn btn-outline-custom px-4"><i class="bi bi-linkedin me-2"></i> LinkedIn</a>
            </div>
        </div>
    </section>

    <footer><div class="container text-center"><p class="small text-muted mb-0">&copy; <?php echo date('Y'); ?> Ferry Fernando.</p></div></footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DARK MODE
        const toggleBtn=document.getElementById('themeToggle'), icon=document.getElementById('themeIcon'), html=document.documentElement;
        if(localStorage.getItem('theme')==='light'){ html.setAttribute('data-theme','light'); icon.classList.replace('bi-sun-fill','bi-moon-fill'); }
        toggleBtn.addEventListener('click',()=>{
            if(html.getAttribute('data-theme')==='dark'){ html.setAttribute('data-theme','light'); icon.classList.replace('bi-sun-fill','bi-moon-fill'); localStorage.setItem('theme','light'); }
            else{ html.setAttribute('data-theme','dark'); icon.classList.replace('bi-moon-fill','bi-sun-fill'); localStorage.setItem('theme','dark'); }
        });

        // LANGUAGE SWITCHER
        const translations = {
            id: {
                nav_home:"Beranda", nav_projects:"Proyek", nav_about:"Tentang", nav_contact:"Kontak",
                btn_work:"Lihat Karya", btn_cv:"Unduh CV",
                hero_greeting:`<?php echo clean($p['hero_greeting']); ?>`, hero_title:`<?php echo clean($p['hero_title']); ?>`, hero_desc:`<?php echo clean($p['hero_desc']); ?>`,
                proj_title:`<?php echo clean($p['project_title']); ?>`, proj_subtitle:`<?php echo clean($p['project_desc']); ?>`,
                about_desc:`<?php echo clean($p['about_text']); ?>`,
                tab_work:"🏢 Work Projects", tab_personal:"🚀 Personal Projects",
                about_head:"TENTANG SAYA", about_title:"Lebih dari sekadar Technical.",
                stat_exp:"Tahun Pengalaman", stat_proj:"Proyek Selesai",
                career_head:"PERJALANAN KARIR",
                contact_head:"KONTAK", contact_title:"Mari Berkolaborasi"
            },
            en: {
                nav_home:"Home", nav_projects:"Projects", nav_about:"About", nav_contact:"Contact",
                btn_work:"View Work", btn_cv:"Download CV",
                hero_greeting:`<?php echo clean($p['hero_greeting_en']); ?>`, hero_title:`<?php echo clean($p['hero_title_en']); ?>`, hero_desc:`<?php echo clean($p['hero_desc_en']); ?>`,
                proj_title:`<?php echo clean($p['project_title_en']); ?>`, proj_subtitle:`<?php echo clean($p['project_desc_en']); ?>`,
                about_desc:`<?php echo clean($p['about_text_en']); ?>`,
                tab_work:"🏢 Work Projects", tab_personal:"🚀 Personal Projects",
                about_head:"ABOUT ME", about_title:"More than just Technical.",
                stat_exp:"Years Experience", stat_proj:"Projects Done",
                career_head:"CAREER JOURNEY",
                contact_head:"CONTACT", contact_title:"Let's Collaborate"
            }
        };

        function setLanguage(lang) {
            document.querySelectorAll('[data-lang]').forEach(el => {
                const key = el.getAttribute('data-lang');
                if(translations[lang][key]) el.innerHTML = translations[lang][key];
            });
            document.getElementById('btn-id').classList.remove('active');
            document.getElementById('btn-en').classList.remove('active');
            document.getElementById('btn-'+lang).classList.add('active');
            localStorage.setItem('ferry_lang', lang);
        }
        window.onload = () => { const savedLang = localStorage.getItem('ferry_lang')||'id'; setLanguage(savedLang); };
    </script>
</body>
</html>