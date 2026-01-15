<?php 
include 'koneksi.php'; 
$p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile WHERE id=1"));

function clean($str) { return addslashes(str_replace(array("\r", "\n"), "", $str)); }

// Logic URL CV & Foto
$cv_url = (!empty($p['cv_link']) && strpos($p['cv_link'], 'http') !== false) ? $p['cv_link'] : "assets/doc/" . $p['cv_link'];
$foto_profile = "assets/img/" . $p['profile_pic'];

// Default Images
$about_img1 = !empty($p['about_img_1']) ? "assets/img/".$p['about_img_1'] : "assets/img/default.jpg";
$about_img2 = !empty($p['about_img_2']) ? "assets/img/".$p['about_img_2'] : "assets/img/default.jpg";
$about_img3 = !empty($p['about_img_3']) ? "assets/img/".$p['about_img_3'] : "assets/img/default.jpg";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    
    <style>
        :root {
            --bg-darker: #020617; 
            --text-main: #f8fafc;
            --text-muted: #cbd5e1; /* LEBIH TERANG BIAR KEBACA */
            --accent: #38bdf8; 
            --accent-glow: rgba(56, 189, 248, 0.4);
            --gold: #fbbf24;
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(15, 23, 42, 0.8); 
            --gradient-text: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
        }

        body { 
            font-family: 'Outfit', sans-serif; background-color: var(--bg-darker); 
            color: var(--text-main); overflow-x: hidden; position: relative;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-darker); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; }

        /* NAVBAR */
        .navbar { background: rgba(2, 6, 23, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border); padding: 15px 0; transition: 0.3s; }
        .nav-link { color: #94a3b8 !important; font-weight: 500; margin: 0 10px; transition: 0.3s; position: relative; }
        .nav-link:hover { color: var(--accent) !important; }
        .nav-link.active { color: var(--accent) !important; font-weight: 700; text-shadow: 0 0 10px var(--accent-glow); }
        .nav-link.active::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 100%; height: 2px; background: var(--accent); }

        /* LANG SWITCHER */
        .lang-toggle { cursor: pointer; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #64748b; transition: 0.3s; }
        .lang-toggle.active { background: var(--accent); color: #000; box-shadow: 0 0 10px var(--accent-glow); }

        /* HERO SECTION */
        .hero-section { padding-top: 180px; min-height: 100vh; display: flex; align-items: center; }
        .hero-greeting { color: #38bdf8; font-size: 1.3rem; font-weight: 700; margin-bottom: 10px; display: block; letter-spacing: 1px; }
        .hero-title { font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-desc { color: #cbd5e1; font-size: 1.1rem; line-height: 1.8; text-align: justify; margin-bottom: 10px; }
        .desc-truncate { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .read-more-btn { background: none; border: none; color: #38bdf8; font-size: 0.95rem; font-weight: 600; cursor: pointer; padding: 0; margin-bottom: 30px; display: inline-block; text-decoration: none; }
        .read-more-btn:hover { text-decoration: underline; }
        .mobile-hero-img { width: 180px; height: 180px; object-fit: cover; object-position: top; border-radius: 20px; border: 2px solid var(--accent); box-shadow: 0 0 20px rgba(56, 189, 248, 0.3); margin: 15px auto 25px auto; display: block; }
        .profile-img-desktop { width: 100%; max-width: 380px; aspect-ratio: 4/5; object-fit: cover; border-radius: 30px; border: 1px solid var(--glass-border); box-shadow: 0 0 40px rgba(56, 189, 248, 0.15); }
        .profile-wrapper-desktop { animation: float 6s ease-in-out infinite; }

        /* BUTTONS */
        .btn-gradient { background: var(--gradient-text); color: white; padding: 12px 35px; border-radius: 50px; font-weight: 600; border: none; box-shadow: 0 0 20px var(--accent-glow); transition: 0.3s; }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 0 40px var(--accent-glow); color: white; }
        .btn-glass { background: rgba(255,255,255,0.05); color: white; border: 1px solid var(--glass-border); padding: 12px 35px; border-radius: 50px; transition: 0.3s; }
        .btn-glass:hover { border-color: var(--accent); color: var(--accent); background: rgba(56, 189, 248, 0.1); }

        /* --- ABOUT & JOURNEY UPDATED --- */
        .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .gallery-item { 
            width: 100%; height: 100%; object-fit: cover; /* Biar tajem */
            border-radius: 12px; border: 1px solid var(--glass-border); cursor: pointer; transition: 0.3s; 
        }
        .gallery-item:hover { transform: scale(1.02); border-color: var(--accent); }
        .gallery-tall { height: 320px; grid-row: span 2; }
        .gallery-short { height: 150px; }

        .timeline-box { position: relative; border-left: 2px dashed rgba(56, 189, 248, 0.3); margin-left: 15px; padding-left: 35px; padding-bottom: 20px; }
        /* Group Perusahaan */
        .company-group { margin-bottom: 40px; position: relative; }
        /* Dot Perusahaan */
        .company-dot { 
            position: absolute; left: -44px; top: 0; width: 20px; height: 20px; 
            background: var(--bg-darker); border: 2px solid var(--accent); border-radius: 50%; z-index: 2;
        }
        .role-item { margin-bottom: 25px; position: relative; }
        /* Deskripsi Job */
        .role-desc { 
            color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; 
            text-align: justify; /* Rata Kanan Kiri */
            margin-top: 8px;
        }
        .role-desc ul { padding-left: 20px; margin-bottom: 0; } /* Support bullet points */
        .role-summary {
            font-size: 0.8rem; color: var(--accent); font-weight: 600; margin-top: 2px; display: block;
        }

        /* CARD PROJECT */
        .project-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; overflow: hidden; height: 100%; display: flex; flex-direction: column; transition: 0.3s; }
        .project-card:hover { transform: translateY(-10px); border-color: var(--accent); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
        .project-img-box { height: 200px; width: 100%; position: relative; overflow: hidden; }
        .project-img-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .project-card:hover .project-img-box img { transform: scale(1.1); }
        .btn-card-action { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--accent); padding: 10px; border-radius: 10px; font-weight: 600; transition: 0.3s; text-align: center; margin-top: auto; cursor: pointer; }
        .btn-card-action:hover { background: var(--accent); color: #000; }

        /* CERTIFICATES */
        .cert-card { background: linear-gradient(145deg, rgba(20, 20, 35, 0.9), rgba(10, 10, 20, 1)); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 15px; padding: 25px; height: 100%; transition: 0.3s; position: relative; overflow: hidden; }
        .cert-card::before { content:''; position: absolute; top:0; left:0; width:4px; height:100%; background: var(--gold); }
        .cert-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(251, 191, 36, 0.15); }

        /* CONTACT */
        .contact-card { background: var(--glass-bg); border: 1px solid var(--glass-border); padding: 30px; border-radius: 20px; text-align: center; transition: 0.3s; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .contact-card:hover { border-color: var(--accent); transform: translateY(-5px); }

        /* MOBILE */
        @media (max-width: 991px) {
            .hero-section { padding-top: 140px; text-align: left; padding-bottom: 50px; }
            .hero-desc { text-align: left; font-size: 1rem; }
            .hero-title { font-size: 2.5rem; }
            .container { padding-left: 25px; padding-right: 25px; }
            .navbar-collapse { background: var(--bg-darker); padding: 20px; border-radius: 15px; margin-top: 10px; border: 1px solid var(--glass-border); }
        }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-15px); } 100% { transform: translateY(0px); } }
    </style>
</head>

<body data-bs-spy="scroll" data-bs-target="#navbar-main" data-bs-offset="100">

    <nav class="navbar navbar-expand-lg fixed-top" id="navbar-main">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <img src="assets/img/logo.png" alt="Logo" style="height: 45px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="fw-bold text-white fs-4" style="display:none;">FF.</span>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><i class="bi bi-list fs-1"></i></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home" data-lang="nav_home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about" data-lang="nav_about">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#skills" data-lang="nav_skills">Keahlian</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects" data-lang="nav_projects">Proyek</a></li>
                    <li class="nav-item"><a class="nav-link" href="#certifications" data-lang="nav_cert">Sertifikat</a></li>
                </ul>
                <div class="d-flex align-items-center gap-4 mt-3 mt-lg-0 justify-content-center">
                    <div class="d-flex bg-dark border border-secondary rounded-pill p-1">
                        <span class="lang-toggle active" onclick="setLanguage('id')" id="btn-id">ID</span>
                        <span class="lang-toggle" onclick="setLanguage('en')" id="btn-en">EN</span>
                    </div>
                    <a href="#contact" class="btn btn-sm btn-outline-info rounded-pill px-4 fw-bold">Hire Me</a>
                </div>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7" data-aos="fade-right">
                    <span class="hero-greeting" data-lang="hero_greeting">👋 Halo, saya <?php echo $p['hero_greeting']; ?></span>
                    <div class="d-lg-none text-center">
                        <img src="<?php echo $foto_profile; ?>" class="mobile-hero-img" alt="Profile" onerror="this.src='assets/img/default.jpg';">
                    </div>
                    <h1 class="hero-title" data-lang="hero_title"><?php echo $p['hero_title']; ?></h1>
                    <div id="desc-container" class="hero-desc desc-truncate" data-lang="hero_desc"><?php echo $p['hero_desc']; ?></div>
                    <button onclick="toggleReadMore()" id="btn-read-more" class="read-more-btn">Lihat Selengkapnya <i class="bi bi-chevron-down"></i></button>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#projects" class="btn btn-gradient" data-lang="btn_work">Lihat Karya Saya <i class="bi bi-arrow-right ms-2"></i></a>
                        <a href="<?php echo $cv_url; ?>" target="_blank" class="btn btn-glass" data-lang="btn_cv">Download CV <i class="bi bi-download ms-2"></i></a>
                    </div>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block" data-aos="zoom-in">
                    <div class="profile-wrapper-desktop">
                        <img src="<?php echo $foto_profile; ?>" class="profile-img-desktop" alt="Profile" onerror="this.src='assets/img/default.jpg';"> 
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5" style="margin-top: 50px;"> <div class="container">
            <div class="row gx-lg-5">
                <div class="col-lg-5 mb-5" data-aos="fade-right">
                    <span class="text-info fw-bold small border-bottom border-info pb-1 mb-3 d-inline-block" data-lang="about_head">TENTANG SAYA</span>
                    <h2 class="text-white fw-bold mb-4 display-6" data-lang="about_title">Analyst & Leader.</h2>
                    
                    <div class="gallery-grid mb-4">
                        <div class="gallery-tall">
                            <a href="assets/img/<?php echo $p['about_img_1']; ?>" class="glightbox">
                                <img src="assets/img/<?php echo $p['about_img_1']; ?>" class="gallery-item" onerror="this.src='assets/img/default.jpg'">
                            </a>
                        </div>
                        <div class="gallery-short">
                            <a href="assets/img/<?php echo $p['about_img_2']; ?>" class="glightbox">
                                <img src="assets/img/<?php echo $p['about_img_2']; ?>" class="gallery-item" onerror="this.src='assets/img/default.jpg'">
                            </a>
                        </div>
                        <div class="gallery-short">
                            <a href="assets/img/<?php echo $p['about_img_3']; ?>" class="glightbox">
                                <img src="assets/img/<?php echo $p['about_img_3']; ?>" class="gallery-item" onerror="this.src='assets/img/default.jpg'">
                            </a>
                        </div>
                    </div>

                    <div class="p-4 rounded-4 border border-secondary bg-dark bg-opacity-25">
                        <h6 class="text-white fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Core Competencies</h6>
                        <div class="row g-2">
                            <?php $qc = mysqli_query($conn, "SELECT * FROM competencies"); while($c = mysqli_fetch_assoc($qc)): ?>
                            <div class="col-6 text-muted small"><i class="<?php echo $c['icon']; ?> text-info me-2"></i><?php echo $c['title']; ?></div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7" data-aos="fade-left">
                    <p class="text-muted fs-5 mb-5" style="line-height: 1.8; text-align: justify;" data-lang="about_desc"><?php echo $p['about_text']; ?></p>
                    
                    <h4 class="text-white fw-bold mb-4 border-bottom border-secondary pb-2" data-lang="career_head">Career Journey</h4>
                    
                    <div class="timeline-box">
                        <?php 
                        $q_time = mysqli_query($conn, "SELECT * FROM timeline ORDER BY id DESC"); 
                        $current_company = "";
                        while($t = mysqli_fetch_assoc($q_time)): 
                            // LOGIC GROUPING PERUSAHAAN (BIAR GAK PISAH KALO SAMA)
                            if($current_company != $t['company']) {
                                if($current_company != "") echo '</div>'; // Tutup div company sebelumnya
                                $current_company = $t['company'];
                                echo '<div class="company-group">';
                                echo '<div class="company-dot"></div>'; // Dot Timeline
                                echo '<div class="mb-3 d-flex align-items-center"><h5 class="text-white fw-bold m-0 fs-4">'.$current_company.'</h5></div>';
                            }
                        ?>
                        <div class="role-item border-start border-secondary ps-4 ms-2">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <h6 class="text-info fw-bold mb-0 fs-5"><?php echo $t['role']; ?></h6>
                                    <span class="role-summary">Fulltime Role</span> 
                                </div>
                                <span class="badge bg-secondary rounded-pill"><?php echo $t['year']; ?></span>
                            </div>
                            
                            <div class="role-desc">
                                <?php echo $t['description']; ?>
                            </div>
                        </div>
                        <?php endwhile; echo '</div>'; // Tutup div terakhir ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="skills" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <span class="text-info fw-bold small border-bottom border-info pb-1">KEAHLIAN</span>
                <h2 class="text-white fw-bold mt-2">Tech Arsenal</h2>
            </div>
            <div class="row g-4 justify-content-center">
                <?php 
                $cats = ['Analysis', 'Development', 'Enterprise'];
                $icons = ['Analysis'=>'bi-kanban', 'Development'=>'bi-code-slash', 'Enterprise'=>'bi-buildings'];
                foreach($cats as $cat) {
                    $q_tech = mysqli_query($conn, "SELECT * FROM tech_stacks WHERE category='$cat'");
                    if(mysqli_num_rows($q_tech) > 0) {
                        echo '<div class="col-md-6 col-lg-4" data-aos="fade-up"><div class="p-4 rounded-4 border border-secondary" style="background:var(--glass-bg);">';
                        echo '<div class="d-flex align-items-center mb-3"><i class="'.$icons[$cat].' fs-3 text-info me-3"></i><h5 class="text-white fw-bold m-0">'.$cat.'</h5></div>';
                        echo '<div class="d-flex flex-wrap gap-2">';
                        while($s = mysqli_fetch_assoc($q_tech)) {
                            echo '<span class="badge bg-dark border border-secondary p-2 fw-normal">'.$s['name'].'</span>';
                        }
                        echo '</div></div></div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <section id="projects" class="py-5">
        <div class="container">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-end mb-5" data-aos="fade-up">
                <div>
                    <span class="text-info fw-bold small border-bottom border-info pb-1" data-lang="nav_projects">PORTFOLIO</span>
                    <h2 class="text-white fw-bold mt-2 mb-0"><?php echo $p['project_title']; ?></h2>
                    <p class="text-muted mt-2 mb-0"><?php echo $p['project_desc']; ?></p>
                </div>
                <ul class="nav nav-pills mt-3 mt-lg-0 gap-2" id="pills-tab" role="tablist">
                    <li class="nav-item"><button class="nav-link active rounded-pill px-4 bg-dark border border-secondary text-white" id="pills-work-tab" data-bs-toggle="pill" data-bs-target="#pills-work">🏢 Work</button></li>
                    <li class="nav-item"><button class="nav-link rounded-pill px-4 bg-dark border border-secondary text-white" id="pills-personal-tab" data-bs-toggle="pill" data-bs-target="#pills-personal">🚀 Personal</button></li>
                </ul>
            </div>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-work">
                    <div class="row g-4">
                        <?php 
                        $q_work = mysqli_query($conn, "SELECT * FROM projects WHERE category='work' ORDER BY id DESC"); 
                        while ($d = mysqli_fetch_assoc($q_work)): 
                        ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up">
                            <div class="project-card h-100">
                                <div class="project-img-box">
                                    <img src="assets/img/<?php echo $d['image']; ?>" onerror="this.src='assets/img/default.jpg'">
                                </div>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <h5 class="text-white fw-bold"><?php echo $d['title']; ?></h5>
                                    <p class="text-muted small mb-3 flex-grow-1"><?php echo substr($d['description'], 0, 90); ?>...</p>
                                    
                                    <div class="border-top border-secondary pt-3 mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-info fw-bold">CHALLENGE</small>
                                            <small class="text-muted text-end text-truncate w-50"><?php echo !empty($d['challenge']) ? $d['challenge'] : '-'; ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-warning fw-bold">IMPACT</small>
                                            <small class="text-muted text-end text-truncate w-50"><?php echo !empty($d['impact']) ? $d['impact'] : '-'; ?></small>
                                        </div>
                                    </div>

                                    <button class="btn-card-action" onclick="openProjectModal('<?php echo clean($d['title']);?>', '<?php echo clean($d['description']);?>', 'assets/img/<?php echo $d['image'];?>', '<?php echo clean($d['challenge'] ?? '');?>', '<?php echo clean($d['impact'] ?? '');?>', '<?php echo clean($d['link_demo']);?>')">
                                        Lihat Detail <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-personal">
                    <div class="row g-4">
                        <?php 
                        $q_pers = mysqli_query($conn, "SELECT * FROM projects WHERE category='personal' ORDER BY id DESC"); 
                        while ($d = mysqli_fetch_assoc($q_pers)): 
                        ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up">
                            <div class="project-card h-100">
                                <div class="project-img-box">
                                    <img src="assets/img/<?php echo $d['image']; ?>" onerror="this.src='assets/img/default.jpg'">
                                </div>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <h5 class="text-white fw-bold"><?php echo $d['title']; ?></h5>
                                    <p class="text-muted small mb-3 flex-grow-1"><?php echo substr($d['description'], 0, 90); ?>...</p>
                                    
                                    <div class="border-top border-secondary pt-3 mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-info fw-bold">CHALLENGE</small>
                                            <small class="text-muted text-end text-truncate w-50"><?php echo !empty($d['challenge']) ? $d['challenge'] : '-'; ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-warning fw-bold">IMPACT</small>
                                            <small class="text-muted text-end text-truncate w-50"><?php echo !empty($d['impact']) ? $d['impact'] : '-'; ?></small>
                                        </div>
                                    </div>

                                    <button class="btn-card-action" onclick="openProjectModal('<?php echo clean($d['title']);?>', '<?php echo clean($d['description']);?>', 'assets/img/<?php echo $d['image'];?>', '<?php echo clean($d['challenge'] ?? '');?>', '<?php echo clean($d['impact'] ?? '');?>', '<?php echo clean($d['link_demo']);?>')">
                                        Lihat Detail <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="certifications" class="py-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="text-info fw-bold small border-bottom border-info pb-1" data-lang="nav_cert">SERTIFIKASI</span>
                <h2 class="text-white fw-bold mt-2" data-lang="cert_title">Bukti Kompetensi.</h2>
            </div>
            <div class="row g-4">
                <?php $qc = mysqli_query($conn, "SELECT * FROM certifications ORDER BY id DESC"); while($c = mysqli_fetch_assoc($qc)): ?>
                <div class="col-md-6 col-lg-3" data-aos="zoom-in">
                    <div class="cert-card h-100">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="bg-white p-2 rounded-3" style="width:55px; height:55px; display:flex; align-items:center; justify-content:center;">
                                <img src="assets/img/<?php echo $c['image']; ?>" style="max-width:100%; max-height:100%; object-fit:contain;">
                            </div>
                            <a href="<?php echo $c['credential_link']; ?>" target="_blank" class="text-warning"><i class="bi bi-box-arrow-up-right fs-5"></i></a>
                        </div>
                        <h6 class="text-white fw-bold mb-1"><?php echo $c['name']; ?></h6>
                        <div class="text-info small mb-2"><?php echo $c['issuer']; ?></div>
                        <div class="border-top border-secondary pt-2 mt-auto">
                            <small class="text-muted" style="font-size:0.75rem;">Issued: <?php echo $c['date_issued']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5 mb-5 border-top border-secondary border-opacity-25">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="text-info fw-bold small border-bottom border-info pb-1" data-lang="nav_contact">KONTAK</span>
                <h2 class="text-white fw-bold mt-2" data-lang="contact_title">Mari Berkolaborasi</h2>
            </div>
            <div class="row justify-content-center g-4">
                <div class="col-md-4 col-lg-3">
                    <a href="mailto:<?php echo $p['email']; ?>" class="contact-card text-decoration-none">
                        <i class="bi bi-envelope-at-fill fs-1 text-info mb-3"></i>
                        <h6 class="text-white fw-bold">Email Me</h6>
                        <span class="text-muted small"><?php echo $p['email']; ?></span>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="https://wa.me/<?php echo $p['whatsapp']; ?>" target="_blank" class="contact-card text-decoration-none">
                        <i class="bi bi-whatsapp fs-1 text-success mb-3"></i>
                        <h6 class="text-white fw-bold">WhatsApp</h6>
                        <span class="text-muted small">Chat Now</span>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="<?php echo $p['linkedin']; ?>" target="_blank" class="contact-card text-decoration-none">
                        <i class="bi bi-linkedin fs-1 text-primary mb-3"></i>
                        <h6 class="text-white fw-bold">LinkedIn</h6>
                        <span class="text-muted small">Connect</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-4 text-center border-top border-secondary bg-darker">
        <p class="small text-muted mb-0">&copy; <?php echo date('Y'); ?> Ferry Fernando.</p>
    </footer>

    <div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark border border-secondary text-white shadow-lg">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold" id="modalTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <img id="modalImg" src="" class="w-100 rounded-3 mb-4 shadow" style="max-height:400px; object-fit:cover;">
                    <h5 class="text-white fw-bold mb-2">Deskripsi</h5>
                    <p id="modalDesc" class="text-muted mb-4"></p>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 border border-info border-opacity-25 rounded bg-info bg-opacity-10 h-100">
                                <strong class="text-info d-block mb-2"><i class="bi bi-exclamation-circle me-2"></i>CHALLENGE</strong>
                                <span id="modalChal" class="text-white-50 small"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border border-warning border-opacity-25 rounded bg-warning bg-opacity-10 h-100">
                                <strong class="text-warning d-block mb-2"><i class="bi bi-check-circle me-2"></i>IMPACT</strong>
                                <span id="modalImp" class="text-white-50 small"></span>
                            </div>
                        </div>
                    </div>

                    <a id="modalLink" href="#" target="_blank" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-rocket-takeoff me-2"></i> Buka Aplikasi / Live Demo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="chat-widget" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999;">
        <div id="chat-bubble" style="position: absolute; top: -45px; right: 0; background: white; color: black; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.2); white-space: nowrap; animation: bounce 2s infinite;">
            Tanya Gue Bro! 🤖
            <div style="position: absolute; bottom: -5px; right: 20px; width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 5px solid white;"></div>
        </div>
        
        <button onclick="toggleChat()" style="width: 60px; height: 60px; border-radius: 50%; background: #f59e0b; color: white; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.3s;">
            <i class="fa fa-robot fa-lg"></i>
        </button>

        <div id="chat-box" class="d-none" style="position: fixed; bottom: 100px; right: 30px; width: 350px; height: 500px; background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.3); display: flex; flex-direction: column; overflow: hidden;">
            <div style="background: #0f172a; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
                <div><i class="fa fa-robot me-2"></i> <b>Tech Assistant</b></div>
                <button onclick="toggleChat()" class="btn btn-sm btn-outline-light border-0"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
                <div style="align-self: flex-start; background: #e2e8f0; color: #334155; padding: 10px 15px; border-radius: 15px 15px 15px 0; max-width: 80%; font-size: 0.9rem;">Halo! Gue AI Assistant di sini. Tanya gue apa aja soal Coding atau Teknologi! 🤖</div>
            </div>
            <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 10px; background: white;">
                <input type="text" id="user-input" placeholder="Tanya soal tech..." onkeypress="handleEnter(event)" style="flex: 1; border: 1px solid #ddd; padding: 8px 15px; border-radius: 20px; outline: none;">
                <button onclick="sendMessage()" style="background: #0f172a; color: white; border: none; width: 40px; height: 40px; border-radius: 50%;"><i class="fa fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    
    <script>
        AOS.init({ duration: 800, once: true });
        const lightbox = GLightbox({ selector: '.glightbox' });

        // Navbar Active Scroll
        const sections = document.querySelectorAll("section");
        const navLi = document.querySelectorAll(".nav-link");
        window.addEventListener("scroll", () => {
            let current = "";
            sections.forEach((section) => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - sectionHeight / 3)) {
                    current = section.getAttribute("id");
                }
            });
            navLi.forEach((li) => {
                li.classList.remove("active");
                if (li.getAttribute("href").includes(current)) {
                    li.classList.add("active");
                }
            });
        });

        // Read More Function & Lang Switch
        function toggleReadMore() {
            var desc = document.getElementById("desc-container");
            var btn = document.getElementById("btn-read-more");
            var currentLang = localStorage.getItem('ferry_lang') || 'id'; // Cek bahasa saat ini
            
            if (desc.classList.contains("desc-truncate")) {
                desc.classList.remove("desc-truncate");
                // Ganti teks tombol berdasarkan bahasa
                btn.innerHTML = translations[currentLang]['btn_close'] + ' <i class="bi bi-chevron-up"></i>';
            } else {
                desc.classList.add("desc-truncate");
                // Ganti teks tombol berdasarkan bahasa
                btn.innerHTML = translations[currentLang]['btn_readmore'] + ' <i class="bi bi-chevron-down"></i>';
            }
        }

        // Chatbot
        function toggleChat() { 
            const box = document.getElementById('chat-box');
            const bubble = document.getElementById('chat-bubble');
            box.classList.toggle('d-none');
            if(!box.classList.contains('d-none')) bubble.style.display = 'none';
        }
        function handleEnter(e) { if (e.key === 'Enter') sendMessage(); }
        
        function sendMessage() {
            let input = document.getElementById('user-input');
            let msg = input.value.trim();
            if (!msg) return;
            appendMessage(msg, 'user');
            input.value = '';
            
            let loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = "align-self: flex-start; font-style: italic; color: #94a3b8; font-size: 0.8rem;";
            loadingDiv.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i> Mikir bentar...';
            loadingDiv.id = 'loading-bubble';
            document.getElementById('chat-messages').appendChild(loadingDiv);

            fetch('apps/api/chat_brain.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading-bubble').remove();
                let formattedReply = data.reply.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>').replace(/\n/g, '<br>');
                appendMessage(formattedReply, 'bot');
            })
            .catch(error => {
                document.getElementById('loading-bubble').remove();
                appendMessage("Maaf bro, error koneksi nih.", 'bot');
            });
        }

        function appendMessage(text, sender) {
            let div = document.createElement('div');
            if(sender === 'user') {
                div.style.cssText = "align-self: flex-end; background: #f59e0b; color: white; padding: 10px 15px; border-radius: 15px 15px 0 15px; max-width: 80%; font-size: 0.9rem;";
            } else {
                div.style.cssText = "align-self: flex-start; background: #e2e8f0; color: #334155; padding: 10px 15px; border-radius: 15px 15px 15px 0; max-width: 80%; font-size: 0.9rem;";
            }
            div.innerHTML = text;
            let container = document.getElementById('chat-messages');
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // Modal Logic
        function openProjectModal(title, desc, img, chal, imp, link) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalImg').src = img;
            document.getElementById('modalChal').innerText = chal || "-";
            document.getElementById('modalImp').innerText = imp || "-";
            
            const btnLink = document.getElementById('modalLink');
            if(link && link !== '#') {
                btnLink.href = link;
                btnLink.classList.remove('d-none');
            } else {
                btnLink.classList.add('d-none');
            }
            
            new bootstrap.Modal(document.getElementById('projectModal')).show();
        }

        // Language
        const translations = {
            id: {
                nav_home:"Beranda", nav_projects:"Proyek", nav_cert:"Sertifikat", nav_about:"Tentang", 
                hero_greeting:`👋 Halo, saya <?php echo clean($p['hero_greeting']); ?>`, hero_title:`<?php echo clean($p['hero_title']); ?>`, hero_desc:`<?php echo clean($p['hero_desc']); ?>`,
                btn_work:"Lihat Karya", btn_cv:"Unduh CV", btn_readmore:"Lihat Selengkapnya", btn_close:"Tutup",
                about_head:"TENTANG SAYA", about_title:"Analyst & Leader.", about_desc:`<?php echo clean($p['about_text']); ?>`,
                career_head:"Perjalanan Karir", contact_title:"Siap Memberikan Dampak?", cert_title:"Bukti Kompetensi."
            },
            en: {
                nav_home:"Home", nav_projects:"Projects", nav_cert:"Certifications", nav_about:"About", 
                hero_greeting:`👋 Hello, I'm <?php echo clean($p['hero_greeting_en']); ?>`, hero_title:`<?php echo clean($p['hero_title_en']); ?>`, hero_desc:`<?php echo clean($p['hero_desc_en']); ?>`,
                btn_work:"View Work", btn_cv:"Download CV", btn_readmore:"Read More", btn_close:"Close",
                about_head:"ABOUT ME", about_title:"Analyst & Leader.", about_desc:`<?php echo clean($p['about_text_en']); ?>`,
                career_head:"Career Journey", contact_title:"Ready to Make Impact?", cert_title:"Competency Proof."
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
            
            // FIX: Reset Read More Button Text saat ganti bahasa
            document.getElementById("desc-container").classList.add("desc-truncate"); // Reset ke closed
            document.getElementById("btn-read-more").innerHTML = translations[lang]['btn_readmore'] + ' <i class="bi bi-chevron-down"></i>';
        }
        window.onload = () => { const savedLang = localStorage.getItem('ferry_lang')||'id'; setLanguage(savedLang); };
    </script>
</body>
</html>