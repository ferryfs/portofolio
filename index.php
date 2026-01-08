<?php 
include 'koneksi.php'; 
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));

function clean($str) {
    return addslashes(str_replace(array("\r", "\n"), "", $str));
}

// 👉 LOGIC LINK DOWNLOAD CV (FIXED)
$cv_url = "#";
if (!empty($p['cv_link'])) {
    // Kalau isinya link http (Google Drive, dll) biarin aja
    if (strpos($p['cv_link'], 'http') !== false) {
        $cv_url = $p['cv_link'];
    } 
    // Kalau isinya nama file lokal, tambahin path folder assets/doc/
    else {
        $cv_url = "assets/doc/" . $p['cv_link']; 
    }
}
?>

<!DOCTYPE html>
<html lang="id"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferry Fernando | Functional Analyst</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* ============================
           1. CORE THEME: DEEP FRESH
           ============================ */
        :root {
            --bg-deep: #020617; 
            --bg-card: rgba(30, 41, 59, 0.7);
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --accent: #38bdf8; 
            --accent-hover: #0ea5e9;
            --gradient-main: linear-gradient(135deg, #38bdf8 0%, #3b82f6 100%);
            --border-glass: rgba(255, 255, 255, 0.08);
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background-color: var(--bg-deep); 
            color: var(--text-main); 
            overflow-x: hidden; 
            position: relative;
        }

        /* AMBIENT LIGHT */
        .ambient-light {
            position: fixed; width: 500px; height: 500px; 
            background: var(--accent); filter: blur(180px); opacity: 0.15; 
            border-radius: 50%; z-index: -1; pointer-events: none;
        }
        .light-1 { top: -100px; left: -100px; }
        .light-2 { bottom: -100px; right: -100px; background: #6366f1; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-deep); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* ============================
           2. NAVBAR PROPER
           ============================ */
        .navbar { 
            background: rgba(2, 6, 23, 0.9); 
            backdrop-filter: blur(15px); 
            border-bottom: 1px solid var(--border-glass); 
            padding: 15px 0; 
            transition: 0.3s; 
        }
        
        .navbar-brand svg rect { fill: var(--text-main); }
        .navbar-brand svg path { fill: var(--bg-deep); }
        .navbar-brand svg path[stroke] { stroke: var(--bg-deep); }

        .nav-link { 
            color: #cbd5e1 !important; 
            font-weight: 500; 
            font-size: 0.95rem;
            margin: 0 10px; 
            position: relative;
            transition: 0.3s;
        }
        
        .nav-link:hover, .nav-link.active { 
            color: var(--accent) !important; 
        }
        
        .nav-link::after {
            content: ''; position: absolute; bottom: -5px; left: 0; 
            width: 0%; height: 2px; background: var(--accent); 
            transition: 0.3s ease-in-out;
        }
        .nav-link.active::after { width: 100%; }

        .lang-switch { 
            cursor: pointer; font-weight: 700; font-size: 0.85rem; 
            color: var(--text-muted); transition: 0.3s; padding: 2px 5px;
        }
        .lang-switch.active { color: var(--accent); border-bottom: 1px solid var(--accent); }

        /* ============================
           3. HERO SECTION
           ============================ */
        .hero-section { padding: 180px 0 100px; }
        
        .hero-greeting {
            font-size: 1.2rem; font-weight: 600; color: var(--accent);
            margin-bottom: 10px; display: block; letter-spacing: 1px;
        }
        .hero-title { 
            font-size: 4.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 25px;
            background: linear-gradient(to bottom right, #ffffff, #94a3b8);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .btn-gradient {
            background: var(--gradient-main); color: white; border: none;
            padding: 14px 35px; border-radius: 50px; font-weight: 600;
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.25); transition: 0.3s;
        }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(56, 189, 248, 0.4); color: white; }
        
        .btn-glass {
            background: rgba(255,255,255,0.05); color: white; border: 1px solid var(--border-glass);
            padding: 14px 35px; border-radius: 50px; font-weight: 600; transition: 0.3s;
        }
        .btn-glass:hover { border-color: var(--accent); color: var(--accent); background: rgba(56, 189, 248, 0.05); }

        .profile-wrapper { position: relative; padding: 20px; display: inline-block; }
        .profile-img { 
            width: 380px; height: 450px; object-fit: cover; border-radius: 20px; 
            filter: grayscale(0%); /* Foto normal */
            border: 1px solid var(--border-glass);
            box-shadow: 20px 20px 0px rgba(56, 189, 248, 0.1);
        }

        /* ============================
           4. PROJECTS
           ============================ */
        .section-header { margin-bottom: 50px; }
        .section-tag { color: var(--accent); font-weight: 700; letter-spacing: 2px; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; display: block; }
        .section-title { font-size: 2.5rem; font-weight: 700; color: var(--text-main); }

        .nav-pills { 
            background: rgba(255,255,255,0.03); padding: 5px; border-radius: 50px; 
            display: inline-flex; border: 1px solid var(--border-glass);
        }
        .nav-pills .nav-link { 
            border-radius: 40px; padding: 10px 30px; color: var(--text-muted) !important; margin: 0;
        }
        .nav-pills .nav-link.active { 
            background: var(--bg-deep); color: var(--accent) !important; 
            border: 1px solid var(--accent);
        }
        .nav-pills .nav-link::after { display: none; }

        .project-scroll-wrapper { display: flex; overflow-x: auto; gap: 30px; padding: 20px 5px 40px 5px; scroll-behavior: smooth; }
        .project-col { min-width: 400px; max-width: 400px; flex: 0 0 auto; }
        
        .project-card { 
            background-color: var(--bg-card); 
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-glass); 
            border-radius: 20px; overflow: hidden; height: 100%; 
            transition: 0.4s; display: flex; flex-direction: column; position: relative;
        }
        .project-img-box { overflow: hidden; height: 220px; width: 100%; position: relative; }
        .project-img-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .project-card:hover .project-img-box img { transform: scale(1.1); }
        .project-card:hover { transform: translateY(-10px); border-color: var(--accent); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }

        /* ============================
           5. ABOUT & TIMELINE
           ============================ */
        .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .gallery-item { width: 100%; border-radius: 15px; object-fit: cover; transition: 0.3s; border: 1px solid var(--border-glass); filter: grayscale(30%); }
        .gallery-item:hover { transform: scale(1.03); filter: grayscale(0%); border-color: var(--accent); }
        .gallery-item.tall { height: 300px; grid-row: span 2; }
        .gallery-item.short { height: 140px; }

        .timeline-box { position: relative; padding-left: 30px; border-left: 2px solid rgba(255,255,255,0.1); }
        .timeline-item { position: relative; margin-bottom: 40px; }
        .timeline-item::before { content: ''; position: absolute; left: -36px; top: 5px; width: 14px; height: 14px; background: var(--bg-deep); border: 3px solid var(--accent); border-radius: 50%; }
        .year-badge { background: rgba(56, 189, 248, 0.1); color: var(--accent); padding: 3px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 700; margin-bottom: 5px; display: inline-block; }

        /* ============================
           6. CERTIFICATIONS
           ============================ */
        .cert-card { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: 16px; padding: 25px; transition: 0.3s; display: flex; flex-direction: column; height: 100%; }
        .cert-card:hover { border-color: var(--accent); background: rgba(56, 189, 248, 0.05); transform: translateY(-5px); }
        .cert-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
        .cert-icon { width: 50px; height: 50px; background: #fff; border-radius: 10px; padding: 5px; display: flex; align-items: center; justify-content: center; }
        .cert-icon img { max-width: 100%; max-height: 100%; object-fit: contain; }

        /* ============================
           7. CONTACT
           ============================ */
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .contact-item { background: var(--bg-card); border: 1px solid var(--border-glass); padding: 30px; border-radius: 20px; text-align: center; transition: 0.3s; text-decoration: none; display: block; }
        .contact-item:hover { border-color: var(--accent); transform: translateY(-5px); background: rgba(56, 189, 248, 0.05); }
        .contact-icon { font-size: 2.5rem; color: var(--accent); margin-bottom: 15px; display: block; }
        .contact-label { color: var(--text-muted); font-size: 0.9rem; display: block; margin-bottom: 5px; }
        .contact-val { color: var(--text-main); font-weight: 600; font-size: 1.1rem; }

        @media (max-width: 768px) {
            .navbar-collapse { background: var(--bg-deep); padding: 20px; border-radius: 0 0 20px 20px; border: 1px solid var(--border-glass); border-top: none; }
            .hero-title { font-size: 3rem; }
            .profile-wrapper { display: none; }
            .project-col { min-width: 300px; max-width: 300px; }
            .nav-pills { width: 100%; }
            .nav-pills .nav-item { flex: 1; text-align: center; }
            .nav-pills .nav-link { width: 100%; padding: 10px; font-size: 0.9rem; }
            .mobile-cta { display: block !important; margin-top: 20px; text-align: center; }
        }
    </style>
</head>

<body data-bs-spy="scroll" data-bs-target="#navbar-main" data-bs-offset="100">

    <div class="ambient-light light-1"></div>
    <div class="ambient-light light-2"></div>

    <nav class="navbar navbar-expand-lg fixed-top" id="navbar-main">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <svg width="45" height="45" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="80" fill="#f8fafc"/>
                    <path d="M180 96 L332 96 L356 420 L256 448 L156 420 Z" fill="#0f172a"/>
                    <path d="M256 140 L256 380" stroke="#f8fafc" stroke-width="14" stroke-linecap="round"/>
                </svg>
            </a>

            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home" data-lang="nav_home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about" data-lang="nav_about">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects" data-lang="nav_projects">Proyek</a></li>
                    <li class="nav-item"><a class="nav-link" href="#certifications" data-lang="nav_cert">Sertifikat</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact" data-lang="nav_contact">Kontak</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-4 mt-3 mt-lg-0 justify-content-center">
                    <div class="d-flex align-items-center bg-transparent border border-secondary rounded-pill px-2 py-1">
                        <span class="lang-switch active" onclick="setLanguage('id')" id="btn-id">ID</span>
                        <span class="text-secondary small mx-1">|</span>
                        <span class="lang-switch" onclick="setLanguage('en')" id="btn-en">EN</span>
                    </div>
                </div>

                <div class="mobile-cta d-none">
                    </div>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7" data-aos="fade-up">
                    <span class="hero-greeting" data-lang="hero_greeting">👋 <?php echo $p['hero_greeting']; ?></span>
                    <h1 class="hero-title" data-lang="hero_title"><?php echo $p['hero_title']; ?></h1>
                    <p class="lead mb-5" style="color: var(--text-muted); max-width: 600px; line-height: 1.8;" data-lang="hero_desc">
                        <?php echo $p['hero_desc']; ?>
                    </p>
                    
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#projects" class="btn btn-gradient" data-lang="btn_work">
                            Lihat Karya Saya <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        
                        <a href="<?php echo $cv_url; ?>" target="_blank" class="btn btn-glass" data-lang="btn_cv">
                            Download CV <i class="bi bi-download ms-2"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-5 text-center d-none d-lg-block" data-aos="fade-left" data-aos-delay="200">
                    <div class="profile-wrapper">
                        <img src="assets/img/<?php echo $p['profile_pic']; ?>" class="profile-img" alt="Profile">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5">
        <div class="container">
            <div class="row gx-lg-5">
                <div class="col-lg-5 mb-5 mb-lg-0" data-aos="fade-right">
                    <span class="section-tag" data-lang="about_head">TENTANG SAYA</span>
                    <h2 class="section-title mb-4" data-lang="about_title">yuk kepoin lebih jauh...</h2>
                    <div class="gallery-grid">
                        <img src="assets/img/profile.jpg" class="gallery-item tall" alt="Activity">
                        <img src="assets/img/profile.jpg" class="gallery-item short" alt="Activity">
                        <img src="assets/img/profile.jpg" class="gallery-item short" alt="Activity">
                    </div>
                </div>

                <div class="col-lg-7" data-aos="fade-left">
                    <p class="text-white mb-5 " style="font-size: 1.1rem; line-height: 1.8; white-space: pre-line;" data-lang="about_desc">
                        <?php echo $p['about_text']; ?>
                    </p>

                    <h4 class="fw-bold text-white mb-4" data-lang="career_head">Career Journey</h4>
                    <div class="timeline-box">
                        <?php $q_time = mysqli_query($conn, "SELECT * FROM timeline ORDER BY id DESC"); 
                        while($t = mysqli_fetch_assoc($q_time)): ?>
                        <div class="timeline-item">
                            <span class="year-badge"><?php echo $t['year']; ?></span>
                            <h5 class="fw-bold text-white mb-1"><?php echo $t['role']; ?></h5>
                            <div class="text-muted mb-2 small"><i class="bi bi-building me-1"></i> <?php echo $t['company']; ?></div>
                            <p class="text-white small mb-0"><?php echo $t['description']; ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="p-5 rounded-5 border border-secondary" style="background: rgba(255,255,255,0.02);" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-lg-4">
                        <h3 class="fw-bold text-white mb-2">Tech Stack</h3>
                        <p class="text-muted">Tools yang saya gunakan untuk membangun solusi.</p>
                    </div>
                    <div class="col-lg-8">
                        <div class="d-flex flex-wrap gap-3">
                            <span class="badge bg-dark border border-secondary px-3 py-2">PHP / Laravel</span>
                            <span class="badge bg-dark border-secondary px-3 py-2">React / Vue</span>
                            <span class="badge bg-dark border-secondary px-3 py-2">MySQL</span>
                            <span class="badge bg-dark border-secondary px-3 py-2">SAP S/4HANA</span>
                            <span class="badge bg-dark border-secondary px-3 py-2">AWS Cloud</span>
                            <span class="badge bg-dark border-secondary px-3 py-2">Docker</span>
                            <span class="badge bg-dark border-secondary px-3 py-2">Figma</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="projects" class="py-5">
        <div class="container">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-end mb-5" data-aos="fade-up">
                <div>
                    <span class="section-tag" data-lang="nav_projects">PORTFOLIO</span>
                    <h2 class="section-title mb-0"><?php echo $p['project_title']; ?></h2>
                </div>
                
                <ul class="nav nav-pills mt-3 mt-lg-0" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-work-tab" data-bs-toggle="pill" data-bs-target="#pills-work" data-lang="tab_work">🏢 Work</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-personal-tab" data-bs-toggle="pill" data-bs-target="#pills-personal" data-lang="tab_personal">🚀 Personal</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="pills-tabContent" data-aos="fade-up" data-aos-delay="100">
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
                        $q_pers = mysqli_query($conn, "SELECT * FROM projects WHERE category='personal' ORDER BY id DESC"); 
                        while ($row = mysqli_fetch_assoc($q_pers)) { include 'component/card_project.php'; } 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="certifications" class="py-5">
        <div class="container">
            <div class="row mb-5" data-aos="fade-up">
                <div class="col-12">
                    <span class="section-tag" data-lang="nav_cert">SERTIFIKASI</span>
                    <h2 class="section-title" data-lang="cert_title">Bukti Kompetensi.</h2>
                </div>
            </div>

            <div class="row g-4">
                <?php
                $q_cert = mysqli_query($conn, "SELECT * FROM certifications ORDER BY id DESC");
                if(mysqli_num_rows($q_cert) > 0) {
                    while($c = mysqli_fetch_assoc($q_cert)):
                ?>
                <div class="col-md-6 col-lg-4" data-aos="zoom-in">
                    <div class="cert-card">
                        <div class="cert-header">
                            <div class="cert-icon">
                                <?php if(!empty($c['image'])): ?>
                                    <img src="assets/img/<?php echo $c['image']; ?>" 
                                         alt="Logo"
                                         style="display: block;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <i class="bi bi-trophy-fill text-warning fs-4" style="display: none;"></i>
                                <?php else: ?>
                                    <i class="bi bi-trophy-fill text-warning fs-4"></i>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $c['credential_link']; ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3">
                                Verify <i class="bi bi-patch-check-fill ms-1"></i>
                            </a>
                        </div>
                        <h5 class="fw-bold text-white mb-1"><?php echo $c['name']; ?></h5>
                        <p class="text-muted small mb-0"><?php echo $c['issuer']; ?></p>
                        <hr class="border-secondary my-3 opacity-25">
                        <small class="text-accent fw-bold text-uppercase" style="font-size: 0.7rem;">ISSUED: <?php echo $c['date_issued']; ?></small>
                    </div>
                </div>
                <?php endwhile; } else { ?>
                    <div class="col-12 text-center text-white py-5">
                        <i class="bi bi-award display-4 mb-3 d-block opacity-25"></i>
                        Belum ada sertifikat yang diupload.
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5 mb-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="section-tag" data-lang="nav_contact">KONTAK</span>
                <h2 class="section-title" data-lang="contact_title">Mari Berkolaborasi</h2>
            </div>

            <div class="contact-grid" data-aos="fade-up" data-aos-delay="100">
                <a href="mailto:<?php echo $p['email']; ?>" class="contact-item">
                    <i class="bi bi-envelope-at-fill contact-icon"></i>
                    <span class="contact-label">Email Me</span>
                    <div class="contact-val"><?php echo $p['email']; ?></div>
                </a>
                <a href="https://wa.me/<?php echo $p['whatsapp']; ?>" target="_blank" class="contact-item">
                    <i class="bi bi-whatsapp contact-icon"></i>
                    <span class="contact-label">Chat WhatsApp</span>
                    <div class="contact-val">+62 Hubungi Saya</div>
                </a>
                <a href="<?php echo $p['linkedin']; ?>" target="_blank" class="contact-item">
                    <i class="bi bi-linkedin contact-icon"></i>
                    <span class="contact-label">Connect LinkedIn</span>
                    <div class="contact-val">Ferry Fernando</div>
                </a>
            </div>
        </div>
    </section>

    <footer class="py-4 text-center border-top border-secondary">
        <div class="container">
            <p class="small text-white mb-0">&copy; <?php echo date('Y'); ?> Ferry Fernando. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // 1. INIT ANIMATION
        AOS.init({ duration: 800, once: true, offset: 100 });

        // 2. JS BUAT NUTUP MENU PAS DIKLIK (MOBILE)
        const navLinks = document.querySelectorAll('.nav-link');
        const menuToggle = document.getElementById('navbarNav');
        const bsCollapse = new bootstrap.Collapse(menuToggle, {toggle: false});

        navLinks.forEach((l) => {
            l.addEventListener('click', () => {
                if (menuToggle.classList.contains('show')) {
                    bsCollapse.hide();
                }
            })
        })

        // 3. LANGUAGE SWITCHER
        const translations = {
            id: {
                nav_home:"Beranda", nav_projects:"Proyek", nav_cert:"Sertifikat", nav_about:"Tentang", nav_contact:"Kontak",
                btn_work:"Lihat Karya", btn_cv:"Download CV",
                hero_greeting:` <?php echo clean($p['hero_greeting']); ?>`, hero_title:`<?php echo clean($p['hero_title']); ?>`, hero_desc:`<?php echo clean($p['hero_desc']); ?>`,
                proj_title:`<?php echo clean($p['project_title']); ?>`, proj_subtitle:`<?php echo clean($p['project_desc']); ?>`,
                about_desc:`<?php echo clean($p['about_text']); ?>`,
                tab_work:"🏢 Work", tab_personal:"🚀 Personal",
                cert_title:"Bukti Kompetensi.",
                about_head:"TENTANG SAYA", about_title:"yuk kepoin lebih jauh...",
                career_head:"Perjalanan Karir",
                contact_title:"Mari Berkolaborasi"
            },
            en: {
                nav_home:"Home", nav_projects:"Projects", nav_cert:"Certifications", nav_about:"About", nav_contact:"Contact",
                btn_work:"View Work", btn_cv:"Download CV",
                hero_greeting:` <?php echo clean($p['hero_greeting_en']); ?>`, hero_title:`<?php echo clean($p['hero_title_en']); ?>`, hero_desc:`<?php echo clean($p['hero_desc_en']); ?>`,
                proj_title:`<?php echo clean($p['project_title_en']); ?>`, proj_subtitle:`<?php echo clean($p['project_desc_en']); ?>`,
                about_desc:`<?php echo clean($p['about_text_en']); ?>`,
                tab_work:"🏢 Work", tab_personal:"🚀 Personal",
                cert_title:"Competency Proof.",
                about_head:"ABOUT ME", about_title:"More than just coding.",
                career_head:"Career Journey",
                contact_title:"Let's Collaborate"
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