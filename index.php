<?php 
// 1. KONEKSI DATABASE
include 'koneksi.php'; 

// 2. AMBIL DATA PROFILE (Untuk About, Stats, Kontak, CV)
// Pastikan tabel 'profile' sudah dibuat dan ada isinya (id=1)
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferry Fernando | Functional Analyst</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* [CSS SAMA SEPERTI SEBELUMNYA - TIDAK ADA PERUBAHAN VISUAL] */
        :root { --bg-dark: #0f172a; --card-bg: #1e293b; --accent: #3b82f6; --text-gray: #94a3b8; --text-white: #f8fafc; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-dark); color: var(--text-white); line-height: 1.6; overflow-x: hidden; }
        .navbar { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 0; }
        .navbar-brand { font-weight: 800; letter-spacing: -0.5px; }
        .nav-link { color: var(--text-gray) !important; font-weight: 500; transition: 0.3s; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { color: var(--accent) !important; }
        .hero-section { padding: 130px 0 80px; }
        .hero-badge { background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 8px 16px; border-radius: 30px; font-weight: 600; font-size: 0.85rem; border: 1px solid rgba(59, 130, 246, 0.2); display: inline-block; margin-bottom: 20px; }
        .hero-title { font-size: 3.5rem; font-weight: 800; line-height: 1.1; letter-spacing: -1px; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .profile-img { width: 320px; height: 320px; object-fit: cover; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); transform: rotate(-3deg); transition: 0.3s; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .profile-img:hover { transform: rotate(0deg) scale(1.02); }
        .project-scroll-wrapper { display: flex; overflow-x: auto; gap: 24px; padding-bottom: 20px; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; scroll-padding-left: 0; }
        .project-scroll-wrapper::-webkit-scrollbar { height: 8px; }
        .project-scroll-wrapper::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .project-scroll-wrapper::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .project-scroll-wrapper::-webkit-scrollbar-thumb:hover { background: var(--accent); }
        .project-col { min-width: 350px; max-width: 350px; flex: 0 0 auto; }
        @media (max-width: 768px) { .project-col { min-width: 280px; max-width: 280px; } }
        .project-card { background-color: var(--card-bg); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; overflow: hidden; height: 100%; transition: transform 0.3s ease, border-color 0.3s ease; display: flex; flex-direction: column; }
        .project-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .tech-pill { background: rgba(255,255,255,0.05); color: var(--text-gray); font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; margin-right: 5px; border: 1px solid rgba(255,255,255,0.05); display: inline-block; margin-bottom: 5px; }
        .btn-primary-custom { background-color: var(--accent); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; transition: 0.3s; }
        .btn-primary-custom:hover { background-color: #2563eb; transform: translateY(-2px); color: white; }
        .btn-outline-custom { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; padding: 12px 30px; border-radius: 8px; font-weight: 600; transition: 0.3s; }
        .btn-outline-custom:hover { border-color: white; background: rgba(255,255,255,0.05); color: white; }
        .lang-switch { cursor: pointer; font-weight: 700; font-size: 0.85rem; color: var(--text-gray); transition: 0.3s; }
        .lang-switch:hover, .lang-switch.active { color: var(--accent); }
        .lang-divider { color: rgba(255,255,255,0.1); margin: 0 8px; }
        footer { border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 80px; }
    </style>
</head>

<body data-bs-spy="scroll" data-bs-target=".navbar" data-bs-offset="100">

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand text-white" href="#" onclick="window.scrollTo(0,0)">
                <i class="fas fa-code text-primary me-2"></i>Ferry<span class="text-primary">Fernando</span>.
            </a>
            <button class="navbar-toggler navbar-dark border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home" data-lang="nav_home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects" data-lang="nav_projects">Proyek</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about" data-lang="nav_about">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact" data-lang="nav_contact">Kontak</a></li>
                    
                    <li class="nav-item ms-lg-4 mt-3 mt-lg-0">
                        <span class="lang-switch active" onclick="setLanguage('id')" id="btn-id">ID</span>
                        <span class="lang-divider">|</span>
                        <span class="lang-switch" onclick="setLanguage('en')" id="btn-en">EN</span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 order-2 order-lg-1">
                    <div class="hero-badge" data-lang="hero_greeting">👋 Halo, saya Ferry Fernando</div>
                    <h1 class="hero-title mb-4" data-lang="hero_title">Functional Analyst <br> & Product Enthusiast</h1>
                    <p class="lead text-secondary mb-5 w-75" data-lang="hero_desc">
                        Menjembatani kebutuhan bisnis dengan solusi teknis untuk menciptakan produk digital yang efisien dan berdampak nyata.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#projects" class="btn btn-primary-custom" data-lang="btn_work">Lihat Karya</a>
                        
                        <a href="<?php echo $p['cv_link']; ?>" target="_blank" class="btn btn-outline-custom" data-lang="btn_cv">
                            <i class="bi bi-download me-2"></i> Unduh CV
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-5 order-1 order-lg-2 text-center mb-5 mb-lg-0">
                    <img src="assets/img/profile.jpg" class="profile-img" alt="Ferry Fernando">
                </div>
            </div>
        </div>
    </section>

    <section id="projects" class="py-5">
        <div class="container">
            <div class="row mb-4 align-items-end">
                <div class="col-lg-8">
                    <h5 class="text-primary fw-bold text-uppercase fs-6 mb-2">Portfolio</h5>
                    <h2 class="fw-bold display-6 text-white" data-lang="proj_title">Proyek Unggulan</h2>
                    <p class="text-secondary mb-0" data-lang="proj_subtitle">Geser ke samping untuk melihat lebih banyak.</p>
                </div>
                <div class="col-lg-4 text-lg-end d-none d-lg-block">
                    <div class="text-secondary small border border-secondary border-opacity-25 rounded-pill px-3 py-1 d-inline-block">
                        <i class="bi bi-arrow-left me-2"></i> Scroll / Swipe <i class="bi bi-arrow-right ms-2"></i>
                    </div>
                </div>
            </div>

            <div class="project-scroll-wrapper">
                
                <?php
                // Query Projects
                $query = mysqli_query($conn, "SELECT * FROM projects ORDER BY id DESC");
                while ($row = mysqli_fetch_assoc($query)) {
                    $modalID = "projectModal" . $row['id'];
                ?>
                
                <div class="project-col">
                    <div class="project-card h-100">
                        <div style="height: 180px; overflow: hidden; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#<?php echo $modalID; ?>">
                            <img src="assets/img/<?php echo $row['image']; ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo $row['title']; ?>">
                        </div>
                        
                        <div class="p-4 d-flex flex-column h-100">
                            <h3 class="h5 fw-bold text-white mb-2 text-truncate" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#<?php echo $modalID; ?>">
                                <?php echo $row['title']; ?>
                            </h3>
                            
                            <div class="mb-3" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php 
                                $stacks = explode(',', $row['tech_stack']);
                                $limit_stack = 0;
                                foreach($stacks as $stack) {
                                    if($limit_stack < 3) {
                                        echo '<span class="tech-pill">'.trim($stack).'</span>';
                                        $limit_stack++;
                                    }
                                }
                                if(count($stacks) > 3) echo '<small class="text-secondary ms-1">+'.(count($stacks)-3).'</small>';
                                ?>
                            </div>

                            <p class="text-secondary small mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo strip_tags($row['description']); ?>
                            </p>

                            <div class="d-grid gap-2 mt-auto">
                                <button type="button" class="btn btn-sm btn-primary-custom" data-bs-toggle="modal" data-bs-target="#<?php echo $modalID; ?>">
                                    <i class="bi bi-info-circle me-1"></i> Info & Cara Pakai
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="<?php echo $modalID; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content" style="background-color: #1e293b; border: 1px solid rgba(255,255,255,0.1); color: white;">
                            
                            <div class="modal-header border-bottom border-secondary border-opacity-25">
                                <h5 class="modal-title fw-bold text-white"><?php echo $row['title']; ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body p-4">
                                <div class="row">
                                    <div class="col-md-5 mb-3 mb-md-0">
                                        <img src="assets/img/<?php echo $row['image']; ?>" class="img-fluid rounded-3 border border-secondary border-opacity-25 w-100" alt="Detail">
                                        <div class="mt-3">
                                            <small class="text-secondary d-block mb-2 fw-bold text-uppercase">Tech Stack:</small>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php 
                                                foreach($stacks as $stack) { echo '<span class="badge bg-dark border border-secondary text-secondary fw-normal">'.trim($stack).'</span>'; }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-7">
                                        <h6 class="text-primary fw-bold text-uppercase fs-7 mb-2">Deskripsi Lengkap</h6>
                                        <p class="text-secondary small" style="white-space: pre-line;">
                                            <?php echo $row['description']; ?>
                                        </p>

                                        <?php if(!empty($row['credentials'])): ?>
                                        <div class="alert alert-dark border border-secondary border-opacity-25 mt-3 p-3 bg-opacity-50" style="background: #0f172a;">
                                            <div class="d-flex gap-3">
                                                <i class="bi bi-key-fill text-warning fs-4"></i>
                                                <div>
                                                    <h6 class="fw-bold text-white mb-1 fs-6">Akses Demo / Credentials</h6>
                                                    <p class="mb-0 small text-secondary">
                                                        <?php echo nl2br($row['credentials']); // Tampilkan sesuai format di admin ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer border-top border-secondary border-opacity-25 justify-content-between">
                                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                                
                                <div class="d-flex gap-2">
                                    <?php if(!empty($row['link_case']) && $row['link_case'] != '#') { 
                                        $is_url = (strpos($row['link_case'], 'http') !== false);
                                        $link_target = $is_url ? $row['link_case'] : 'assets/docs/' . $row['link_case'];
                                        $btn_icon = $is_url ? 'bi-link-45deg' : 'bi-file-pdf';
                                        $btn_text = $is_url ? 'Baca di Website' : 'Download PDF';
                                    ?>
                                        <a href="<?php echo $link_target; ?>" target="_blank" class="btn btn-outline-light">
                                            <i class="bi <?php echo $btn_icon; ?> me-2"></i> <?php echo $btn_text; ?>
                                        </a>
                                    <?php } ?>

                                    <?php if(!empty($row['link_demo']) && $row['link_demo'] != '#') { ?>
                                        <a href="<?php echo $row['link_demo']; ?>" target="_blank" class="btn btn-primary-custom">
                                            <i class="fas fa-external-link-alt me-2"></i> Buka Live Demo
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <?php } ?>

            </div> </div>
    </section>

    <section id="about" class="py-5 bg-opacity-10" style="background-color: rgba(255,255,255,0.02);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="p-4 border border-secondary border-opacity-25 rounded-4">
                        <div class="d-flex justify-content-between text-center">
                            <div>
                                <h2 class="fw-bold text-white mb-0 display-4"><?php echo $p['years_exp']; ?>+</h2>
                                <p class="text-secondary small text-uppercase" data-lang="stat_exp">Tahun<br>Pengalaman</p>
                            </div>
                            <div class="border-end border-secondary border-opacity-25"></div>
                            <div>
                                <h2 class="fw-bold text-white mb-0 display-4"><?php echo $p['projects_done']; ?>+</h2>
                                <p class="text-secondary small text-uppercase" data-lang="stat_proj">Proyek<br>Selesai</p>
                            </div>
                            <div class="border-end border-secondary border-opacity-25"></div>
                            <div>
                                <h2 class="fw-bold text-white mb-0 display-4"><?php echo $p['client_happy']; ?>%</h2>
                                <p class="text-secondary small text-uppercase">Client<br>Satisfaction</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h5 class="text-primary fw-bold text-uppercase fs-6 mb-2" data-lang="about_head">TENTANG SAYA</h5>
                    <h2 class="fw-bold text-white mb-4" data-lang="about_title">Lebih dari sekadar Technical.</h2>
                    <p class="text-secondary mb-4" data-lang="about_lead">
                        Saya memiliki latar belakang teknis yang kuat, memungkinkan saya untuk berkomunikasi secara efektif dengan developer.
                    </p>
                    <p class="text-secondary" data-lang="about_desc">
                        <?php echo nl2br($p['about_text']); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h5 class="text-primary fw-bold text-uppercase fs-6 mb-2" data-lang="contact_head">KONTAK</h5>
                    <h2 class="fw-bold text-white mb-4" data-lang="contact_title">Mari Berkolaborasi</h2>
                    <p class="text-secondary mb-5" data-lang="contact_sub">Punya ide menarik? Jangan ragu untuk menghubungi saya.</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="mailto:<?php echo $p['email']; ?>" class="btn btn-primary-custom px-4">
                            <i class="bi bi-envelope-fill me-2"></i> <span data-lang="btn_email">Kirim Email</span>
                        </a>
                        <a href="https://wa.me/<?php echo $p['whatsapp']; ?>" target="_blank" class="btn btn-outline-custom px-4">
                            <i class="bi bi-whatsapp me-2"></i> WhatsApp
                        </a>
                        <a href="<?php echo $p['linkedin']; ?>" target="_blank" class="btn btn-outline-custom px-4">
                            <i class="bi bi-linkedin me-2"></i> LinkedIn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container text-center">
            <p class="text-secondary mb-0 small">&copy; 2025 Ferry Fernando. Built with PHP & Passion.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // SCRIPT TRANSLATE TETAP AMAN
        const translations = {
            id: {
                nav_home: "Beranda", nav_projects: "Proyek", nav_about: "Tentang", nav_contact: "Kontak",
                hero_greeting: "👋 Halo, saya Ferry Fernando",
                hero_title: "Functional Analyst <br> & Product Enthusiast",
                hero_desc: "Menjembatani kebutuhan bisnis dengan solusi teknis untuk menciptakan produk digital yang efisien dan berdampak nyata.",
                btn_work: "Lihat Karya", btn_cv: "Unduh CV",
                proj_title: "Proyek Unggulan", proj_subtitle: "Geser ke samping untuk melihat lebih banyak.",
                btn_demo: "Live Demo", btn_case: "Studi Kasus",
                about_head: "TENTANG SAYA", about_title: "Lebih dari sekadar Technical.",
                about_lead: "Saya memiliki latar belakang teknis yang kuat, memungkinkan saya untuk berkomunikasi secara efektif dengan developer.",
                about_desc: "Fokus saya adalah menciptakan produk yang user-centric dan memenuhi kebutuhan bisnis.",
                stat_exp: "Tahun<br>Pengalaman", stat_proj: "Proyek<br>Selesai",
                contact_head: "KONTAK", contact_title: "Mari Berkolaborasi", contact_sub: "Punya ide menarik? Hubungi saya.",
                btn_email: "Kirim Email"
            },
            en: {
                nav_home: "Home", nav_projects: "Projects", nav_about: "About", nav_contact: "Contact",
                hero_greeting: "👋 Hi, I'm Ferry Fernando",
                hero_title: "Functional Analyst <br> & Product Enthusiast",
                hero_desc: "Bridging business needs with technical solutions to create efficient and impactful digital products.",
                btn_work: "View Work", btn_cv: "Download CV",
                proj_title: "Featured Projects", proj_subtitle: "Scroll horizontally to see more.",
                btn_demo: "Live Demo", btn_case: "Case Study",
                about_head: "ABOUT ME", about_title: "More than just Technical.",
                about_lead: "I have a strong technical background, allowing me to communicate effectively with developers.",
                about_desc: "My focus is creating user-centric products that meet business requirements.",
                stat_exp: "Years<br>Experience", stat_proj: "Projects<br>Done",
                contact_head: "CONTACT", contact_title: "Let's Collaborate", contact_sub: "Have an idea? Let's talk.",
                btn_email: "Send Email"
            }
        };

        function setLanguage(lang) {
            document.querySelectorAll('[data-lang]').forEach(el => {
                const key = el.getAttribute('data-lang');
                if(translations[lang][key]) el.innerHTML = translations[lang][key];
            });
            document.getElementById('btn-id').classList.remove('active');
            document.getElementById('btn-en').classList.remove('active');
            document.getElementById('btn-' + lang).classList.add('active');
            localStorage.setItem('ferry_lang', lang);
        }

        window.onload = () => {
            const savedLang = localStorage.getItem('ferry_lang') || 'id';
            setLanguage(savedLang);
        };
    </script>
</body>
</html>