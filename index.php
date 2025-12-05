<?php include 'koneksi.php'; ?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ferry Fernando | Functional Analyst</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      /* --- CUSTOM CSS PREMIUM --- */
      :root {
        --primary-color: #3b82f6;
        --dark-bg: #0f172a;
        --card-bg: #1e293b;
        --text-color: #e2e8f0;
      }
      body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--dark-bg);
        color: var(--text-color);
        line-height: 1.6;
        overflow-x: hidden; /* Mencegah scroll samping */
      }
      .navbar {
        background-color: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
        padding: 15px 0;
      }
      .text-highlight { color: var(--primary-color); font-weight: 700; }
      
      /* Toggle Bahasa */
      .lang-switch {
        cursor: pointer;
        font-weight: 600;
        border: 1px solid var(--primary-color);
        padding: 5px 15px;
        border-radius: 20px;
        transition: 0.3s;
        color: white;
      }
      .lang-switch:hover, .lang-switch.active {
        background-color: var(--primary-color);
        color: white;
      }

      /* Hero Section */
      .hero-section { padding-top: 160px; padding-bottom: 100px; }
      .hero-title { font-weight: 800; letter-spacing: -1px; }
      
      /* Foto Profil */
      .profile-container { position: relative; display: inline-block; }
      .profile-container::before {
        content: '';
        position: absolute;
        top: 20px; left: 20px; right: -20px; bottom: -20px;
        border: 2px solid var(--primary-color);
        border-radius: 50%;
        z-index: -1;
        transition: 0.3s;
      }
      .profile-container:hover::before { top: 10px; left: 10px; right: -10px; bottom: -10px; }
      .profile-img {
        width: 350px; height: 350px;
        object-fit: cover;
        border-radius: 50%;
        border: 8px solid var(--card-bg);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      }

      /* Cards */
      .card-custom {
        background-color: var(--card-bg);
        border: 1px solid #334155;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
      }
      .card-custom:hover {
        transform: translateY(-10px);
        border-color: var(--primary-color);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
      }
      .project-img { height: 200px; object-fit: cover; width: 100%; border-bottom: 1px solid #334155; }
      
      .btn-primary-custom {
        background-color: var(--primary-color);
        border: none; padding: 12px 30px; border-radius: 8px; color: white; font-weight: 600;
        transition: all 0.3s;
      }
      .btn-primary-custom:hover {
        background-color: #2563eb; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
      }
      
      section { padding: 100px 0; }
    </style>
  </head>
  <body data-bs-spy="scroll" data-bs-target=".navbar" data-bs-offset="100">

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
      <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="#">My<span class="text-highlight">Portfolio</span></a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item"><a class="nav-link" href="#home" data-lang="nav_home">Beranda</a></li>
            <li class="nav-item"><a class="nav-link" href="#projects" data-lang="nav_projects">Proyek</a></li>
            <li class="nav-item"><a class="nav-link" href="#about" data-lang="nav_about">Tentang</a></li>
            <li class="nav-item"><a class="nav-link" href="#contact" data-lang="nav_contact">Kontak</a></li>
            
            <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                <span class="lang-switch active" onclick="setLanguage('id')" id="btn-id">ID</span>
                <span class="text-secondary mx-1">|</span>
                <span class="lang-switch" onclick="setLanguage('en')" id="btn-en">EN</span>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <section id="home" class="hero-section">
      <div class="container">
        <div class="row align-items-center flex-column-reverse flex-lg-row">
          <div class="col-lg-6 mt-5 mt-lg-0">
            <h5 class="text-highlight mb-3" data-lang="hero_greeting">Halo, saya Ferry Fernando 👋</h5>
            <h1 class="display-3 hero-title mb-4" data-lang="hero_title">Functional Analyst & <br>Product Development Enthusiast</h1>
            <p class="lead mb-5 text-secondary w-75" data-lang="hero_desc">
              Saya menjembatani kebutuhan bisnis dengan solusi teknis untuk menciptakan produk digital yang efisien, user-friendly, dan berdampak nyata.
            </p>
            <div class="d-flex gap-3">
                <a href="#projects" class="btn btn-primary-custom" data-lang="btn_work">Lihat Karya Saya</a>
                <a href="secure-doc.php?token=ax99-secure-access" target="_blank" class="btn btn-outline-light px-4 py-3 fw-semibold rounded-3" data-lang="btn_cv">
                    Unduh CV <i class="bi bi-download ms-2"></i>
                </a>
            </div>
          </div>
          <div class="col-lg-6 text-center">
                    <img src="assets/img/profile.jpg" class="profile-img" alt="Ferry Fernando">
          </div>
        </div>
      </div>
    </section>

    <section id="projects">
      <div class="container">
        <div class="row mb-5">
            <div class="col-lg-6">
                <h5 class="text-highlight">PORTFOLIO</h5>
                <h2 class="fw-bold display-6" data-lang="proj_title">Proyek Unggulan</h2>
            </div>
            <div class="col-lg-6 text-lg-end align-self-end">
                <p class="text-secondary" data-lang="proj_subtitle">Beberapa aplikasi dan sistem yang telah saya kembangkan.</p>
            </div>
        </div>
        
        <div class="row">
    <?php
    // Ambil data dari database
    $query = mysqli_query($conn, "SELECT * FROM projects ORDER BY id DESC");
    
    // Looping data
    while ($row = mysqli_fetch_assoc($query)) {
    ?>
    
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <img src="assets/img/<?php echo $row['image']; ?>" class="card-img-top project-img" alt="Project Image">
            
            <div class="card-body p-4">
                <h3 class="h4 card-title fw-bold mb-3"><?php echo $row['title']; ?></h3>
                <p class="card-text text-secondary mb-4"><?php echo $row['description']; ?></p>
                
                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <span class="badge bg-dark border border-secondary"><?php echo $row['tech_stack']; ?></span>
                </div>
                
                <?php if($row['link_demo']) { ?>
                    <a href="<?php echo $row['link_demo']; ?>" target="_blank" class="btn btn-primary-custom w-100">Lihat Project</a>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php } ?>
</div>

    <section id="about" class="bg-dark bg-opacity-50">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0 text-center">
                    <img src="assets/img/profile.jpg" class="img-fluid rounded-4 shadow" style="max-width: 80%;" alt="Tentang Ferry">
                </div>
                <div class="col-lg-1"></div>
                <div class="col-lg-6">
                    <h5 class="text-highlight" data-lang="about_head">TENTANG SAYA</h5>
                    <h2 class="fw-bold display-6 mb-4" data-lang="about_title">Lebih dari sekadar Product Manager.</h2>
                    <p class="lead text-secondary mb-4" data-lang="about_lead">
                        Saya memiliki latar belakang teknis yang kuat, memungkinkan saya untuk berkomunikasi secara efektif dengan developer.
                    </p>
                    <p class="text-secondary mb-4" data-lang="about_desc">
                        Fokus saya adalah menciptakan produk yang tidak hanya memenuhi kebutuhan bisnis, tetapi juga memberikan pengalaman pengguna yang luar biasa (user-centric).
                    </p>
                    
                    <div class="row mt-5 g-4">
                        <div class="col-6">
                            <h3 class="fw-bold display-5 text-highlight mb-0">3+</h3>
                            <p class="text-secondary" data-lang="stat_exp">Tahun Pengalaman</p>
                        </div>
                        <div class="col-6">
                            <h3 class="fw-bold display-5 text-highlight mb-0">15+</h3>
                            <p class="text-secondary" data-lang="stat_proj">Proyek Selesai</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-6">
                    <h5 class="text-highlight" data-lang="contact_head">KONTAK</h5>
                    <h2 class="fw-bold display-6" data-lang="contact_title">Mari Berkolaborasi</h2>
                    <p class="text-secondary" data-lang="contact_sub">Punya ide menarik? Jangan ragu untuk menghubungi saya.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card card-custom p-5 text-center">
                        <h3 class="fw-bold mb-4 text-white" data-lang="email_title">Hubungi Saya via Email</h3>
                          <a href="mailto:ferryfernandosiahaan46@gmail.com?subject=Halo%20Ferry,%20Saya%20tertarik%20kolaborasi" class="btn btn-primary-custom btn-lg fs-5 px-5 mb-3">
                            <i class="bi bi-envelope-fill me-2"></i> <span data-lang="btn_email">Kirim Email</span>
                            </a>
                              <p class="text-secondary fw-bold">
                                  Atau email ke: <br> 
                                <span class="text-white select-all">ferryfernandosiahaan46@gmail.com</span>
                             </p>
                        <div class="d-flex justify-content-center gap-4 fs-3 mt-3">
                            <a href="https://www.linkedin.com/in/ferry-fernando-/" target="_blank" class="text-secondary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-github"></i></a>
                            <a href="https://wa.me/6282144957275" target="_blank" class="text-secondary"><i class="bi bi-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-4 text-center text-secondary border-top border-secondary mt-5" style="background-color: rgba(15, 23, 42, 1);">
        <small>&copy; 2025 Ferry Fernando.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Data Bahasa
        const translations = {
            id: {
                nav_home: "Beranda", nav_projects: "Proyek", nav_about: "Tentang", nav_contact: "Kontak",
                hero_greeting: "Halo, saya Ferry Fernando 👋",
                hero_title: "Functional Analyst & <br>Product Development Enthusiast",
                hero_desc: "Saya menjembatani kebutuhan bisnis dengan solusi teknis untuk menciptakan produk digital yang efisien, user-friendly, dan berdampak nyata.",
                btn_work: "Lihat Karya Saya", btn_cv: "Unduh CV",
                proj_title: "Proyek Unggulan", proj_subtitle: "Beberapa aplikasi dan sistem yang telah saya kembangkan.",
                p1_title: "Sistem Manajemen Stok", p1_desc: "Aplikasi berbasis web untuk memantau arus keluar-masuk barang secara real-time. Mengurangi selisih stok hingga 40%.",
                p2_title: "Integrasi Pembatalan Order (ERP)", p2_desc: "Merancang alur sistem (BRD) untuk sinkronisasi status pembatalan order antara NAV ERP dan e-commerce portal.",
                btn_demo: "Coba Demo Aplikasi", btn_case: "Lihat Studi Kasus",
                about_head: "TENTANG SAYA", about_title: "Lebih dari sekadar Product Manager.",
                about_lead: "Saya memiliki latar belakang teknis yang kuat, memungkinkan saya untuk berkomunikasi secara efektif dengan developer.",
                about_desc: "Fokus saya adalah menciptakan produk yang tidak hanya memenuhi kebutuhan bisnis, tetapi juga memberikan pengalaman pengguna yang luar biasa.",
                stat_exp: "Tahun Pengalaman", stat_proj: "Proyek Selesai",
                contact_head: "KONTAK", contact_title: "Mari Berkolaborasi", contact_sub: "Punya ide menarik? Jangan ragu untuk menghubungi saya.",
                email_title: "Hubungi Saya via Email", btn_email: "Kirim Email"
            },
            en: {
                nav_home: "Home", nav_projects: "Projects", nav_about: "About", nav_contact: "Contact",
                hero_greeting: "Hi, I'm Ferry Fernando 👋",
                hero_title: "Product Manager & <br>Tech Enthusiast.",
                hero_desc: "Bridging business needs with technical solutions to create efficient, user-friendly, and impactful digital products.",
                btn_work: "View My Work", btn_cv: "Download CV",
                proj_title: "Featured Projects", proj_subtitle: "Some applications and systems I have developed.",
                p1_title: "Inventory Management System", p1_desc: "Web-based application to monitor stock flow in real-time. Reduced stock discrepancies by 40%.",
                p2_title: "Order Cancellation Integration (ERP)", p2_desc: "Designing system flow (BRD) to synchronize order cancellation status between NAV ERP and e-commerce portal.",
                btn_demo: "Try App Demo", btn_case: "View Case Study",
                about_head: "ABOUT ME", about_title: "More than just a Product Manager.",
                about_lead: "I have a strong technical background, allowing me to communicate effectively with developers and understand system complexity.",
                about_desc: "My focus is creating products that not only meet business needs but also deliver exceptional user experiences (user-centric).",
                stat_exp: "Years Experience", stat_proj: "Projects Done",
                contact_head: "CONTACT", contact_title: "Let's Collaborate", contact_sub: "Have an interesting idea? Don't hesitate to contact me.",
                email_title: "Contact Me via Email", btn_email: "Send Email"
            }
        };

        function setLanguage(lang) {
            // Update Text
            document.querySelectorAll('[data-lang]').forEach(el => {
                const key = el.getAttribute('data-lang');
                if(translations[lang][key]) {
                    el.innerHTML = translations[lang][key];
                }
            });

            // Update Tombol Active
            document.getElementById('btn-id').classList.remove('active');
            document.getElementById('btn-en').classList.remove('active');
            document.getElementById('btn-' + lang).classList.add('active');
            
            // Simpan Pilihan Bahasa (Optional: LocalStorage)
            localStorage.setItem('ferry_lang', lang);
        }

        // Cek bahasa terakhir user saat loading
        window.onload = () => {
            const savedLang = localStorage.getItem('ferry_lang') || 'id';
            setLanguage(savedLang);
        };
    </script>
  </body>
</html>