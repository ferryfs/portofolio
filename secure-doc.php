<?php
// KODE RAHASIA: Kalau link gak pake token, tendang ke depan
if (!isset($_GET['token']) || $_GET['token'] !== 'ax99-secure-access') {
    header("Location: index.php");
    exit();
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Curriculum Vitae | Ferry Fernando</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      body {
        font-family: 'Poppins', sans-serif;
        background-color: #f3f4f6;
        color: #1f2937;
      }
      .a4-page {
        background: white;
        max-width: 900px;
        margin: 50px auto;
        padding: 40px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        border-radius: 8px;
      }
      .cv-header {
        border-bottom: 2px solid #3b82f6;
        padding-bottom: 20px;
        margin-bottom: 30px;
      }
      .cv-name { color: #111827; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
      .cv-role { color: #3b82f6; font-weight: 600; font-size: 1.2rem; }
      .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 15px;
        text-transform: uppercase;
        border-left: 4px solid #3b82f6;
        padding-left: 10px;
      }
      .timeline-item {
        position: relative;
        padding-left: 20px;
        border-left: 2px solid #e5e7eb;
        margin-bottom: 30px;
      }
      .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px; top: 5px;
        width: 10px; height: 10px;
        background: #3b82f6;
        border-radius: 50%;
      }
      .job-title { font-weight: 700; font-size: 1rem; color: #111827; }
      .job-company { font-weight: 600; color: #4b5563; }
      .job-date { font-size: 0.9rem; color: #6b7280; font-style: italic; margin-bottom: 8px; }
      .job-desc { font-size: 0.95rem; color: #374151; }
      .job-desc li { margin-bottom: 5px; }

      .skill-section h6 { font-weight: 700; color: #374151; margin-top: 15px; margin-bottom: 8px; }
      .skill-tag {
        background: #eff6ff;
        color: #1d4ed8;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 4px 4px 0;
        text-decoration: none;
        transition: 0.2s;
      }
      .skill-tag:hover { background: #dbeafe; color: #1e40af; }
      
      .contact-link { color: #4b5563; text-decoration: none; transition: 0.2s; }
      .contact-link:hover { color: #3b82f6; }

      /* Floating Download Button */
      .fab-container { position: fixed; bottom: 30px; right: 30px; z-index: 999; }
      .btn-download {
        background-color: #3b82f6; color: white; border-radius: 50px; padding: 15px 30px;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4); font-weight: 600; transition: all 0.3s;
        text-decoration: none; display: flex; align-items: center; gap: 10px;
      }
      .btn-download:hover { background-color: #2563eb; transform: translateY(-5px); color: white; }

      @media (max-width: 768px) {
        .a4-page { margin: 0; padding: 20px; border-radius: 0; }
        .btn-download { padding: 12px 20px; font-size: 0.9rem; }
      }
    </style>
  </head>
  <body>

    <div class="fab-container">
        <a href="assets/doc/cv-ferry.pdf" download class="btn-download">
            <i class="bi bi-file-earmark-pdf-fill fs-4"></i>
            <span>Download PDF</span>
        </a>
    </div>

    <div class="container">
        <div class="py-4 no-print">
            <a href="index.php" class="text-decoration-none text-secondary fw-bold">
                <i class="bi bi-arrow-left"></i> Kembali ke Portfolio
            </a>
        </div>

        <div class="a4-page">
            <div class="cv-header">
                <div class="row">
                    <div class="col-md-9">
                        <h1 class="display-5 cv-name">Ferry Fernando</h1>
                        <p class="cv-role mb-3">IT Supervisor Functional Analyst</p>
                        
                        <p class="text-secondary small mb-3" style="line-height: 1.6;">
                            Saat ini menjabat sebagai IT Functional Analyst Supervisor di perusahaan manufaktur interior. Berpengalaman dalam pengembangan sistem end-to-end (Web & Mobile), analisis kebutuhan, dan konseptualisasi solusi. Kreatif dan adaptif dalam mengoptimalkan sistem serta kuat dalam penyelesaian masalah teknis. Terbuka untuk peluang remote/freelance.
                        </p>

                        <div class="d-flex flex-wrap gap-3 text-secondary text-sm fw-medium">
                            <a href="mailto:ferryfernandosiahaan46@gmail.com" class="contact-link"><i class="bi bi-envelope-fill me-1"></i> ferryfernandosiahaan46@gmail.com</a>
                            <span><i class="bi bi-whatsapp me-1"></i> +62 821 4495 7275</span>
                            <a href="https://www.linkedin.com/in/ferry-fernando-/" target="_blank" class="contact-link"><i class="bi bi-linkedin me-1"></i> LinkedIn Profile</a>
                            <span><i class="bi bi-geo-alt-fill me-1"></i> Bekasi Timur, Jawa Barat</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mt-3 mt-md-0">
                        <img src="assets/img/profile.jpg" class="rounded border shadow-sm" width="130" height="130" style="object-fit: cover;" alt="Foto Ferry">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 pe-lg-4 border-end-lg">
                    
                    <h3 class="section-title">Pengalaman Kerja</h3>

                    <div class="timeline-item">
                        <div class="job-title">IT Functional Analyst Supervisor</div>
                        <div class="job-company">PT Tangkas Cipta Optimal (TACO Group)</div>
                        <div class="job-date">Mar 2024 - Sekarang</div>
                        <div class="job-desc">
                            <ul class="ps-3 mb-2">
                                <li>Menerjemahkan kebutuhan user menjadi Business Requirement Design (BRD) yang jelas.</li>
                                <li>Meninjau BRD tim (2 orang) untuk memastikan keselarasan dengan standar bisnis.</li>
                                <li>Menentukan Product Vision & Roadmap aplikasi internal (Tacommerce, Sales Brief).</li>
                                <li>Mengelola pengembangan aplikasi (Scrum & Agile) sebagai jembatan User & Developer.</li>
                                <li>Melakukan SIT, UAT, dan validasi kualitas sistem sebelum rilis.</li>
                                <li>Melakukan training bulanan kepada tim Sales dan memecahkan masalah user.</li>
                            </ul>
                            <strong>Key Projects:</strong> Sales Brief, Tacommerce (Web/Mobile), Tacollect, Consignment System, Customer Portal, Website TACO, Chatbot, AGLiS (Warehouse Automation).
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="job-title">Senior Business Analyst & Business Analyst</div>
                        <div class="job-company">PT Astra International, Tbk</div>
                        <div class="job-date">Okt 2022 - Mar 2024</div>
                        <div class="job-desc">
                            <strong>Senior Role:</strong>
                            <ul class="ps-3 mb-2">
                                <li>Memimpin tim BA Junior (2 orang) dalam requirement gathering.</li>
                                <li>Analisis gap & implementasi solusi di divisi Sparepart (Hemat 35% waktu & biaya).</li>
                                <li>Memimpin simulasi Pra-SIT modul SAP S/4HANA Finance.</li>
                            </ul>
                            <strong>BA Role:</strong>
                            <ul class="ps-3">
                                <li>Menyusun user story, RDM, BPS, dan UI Mockup (Figma).</li>
                                <li>Simulasi end-to-end SAP S/4HANA (FI/CO & WM/EWM).</li>
                                <li>Melakukan SIT & UAT fitur baru.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="job-title">Business Analyst & Back End Developer</div>
                        <div class="job-company">PT Star Karlo Indonesia</div>
                        <div class="job-date">Mei 2022 - Okt 2022</div>
                        <div class="job-desc">
                            <ul class="ps-3">
                                <li><strong>BE Dev:</strong> Implementasi Product Disbursement, fixing isu database, merancang endpoint API, integrasi sistem logistik (FMS, Odoo, SAP).</li>
                                <li><strong>Analyst:</strong> Strategi optimalisasi proses bisnis logistik & migrasi backend ke AWS.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="job-title">Quality Management Analyst & Field Engineer</div>
                        <div class="job-company">PT Smartfren Telecom Tbk</div>
                        <div class="job-date">Sep 2019 - Mei 2022</div>
                        <div class="job-desc">
                            <ul class="ps-3">
                                <li>Memeriksa dokumen serah terima BTS & analisis masalah material.</li>
                                <li>Merancang aplikasi Warehouse Management System (WMS).</li>
                                <li>Koordinasi jadwal pemeliharaan BTS (Preventive/Corrective) & pengawasan subkontraktor.</li>
                            </ul>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">
                    
                    <div class="mb-5">
                        <h3 class="section-title">Pendidikan</h3>
                        <div class="timeline-item border-0 ps-0 mb-0">
                            <div class="job-title">S1 Teknik Informatika</div>
                            <div class="job-company">Univ. Bhayangkara Jakarta Raya</div>
                            <div class="job-date">2015 - 2019 | IPK: 3.35</div>
                            <a href="https://drive.google.com/file/d/1zzuecoqx9jm-HmDjrgh2Sx6KcldWYmxA/view" target="_blank" class="skill-tag mt-2">
                                <i class="bi bi-file-earmark-check me-1"></i> Lihat Ijazah
                            </a>
                        </div>
                    </div>

                    <div class="mb-5">
                        <h3 class="section-title">Keahlian & Tools</h3>
                        
                        <div class="skill-section">
                            <h6>Core Competencies</h6>
                            <a href="https://drive.google.com/file/d/1Z_kgzqgsFHnXRwsh1uM-lYwxEmhH52Rd/view" target="_blank" class="skill-tag">Business Analyst 🔗</a>
                            <span class="skill-tag">Problem Solving</span>
                            <span class="skill-tag">Communication</span>
                            <span class="skill-tag">Leadership</span>
                            <a href="https://drive.google.com/file/d/1WO_STU4uwJ--QBjAt7XAgB72127TC-BF/view" target="_blank" class="skill-tag">Training & Knowledge 🔗</a>
                        </div>

                        <div class="skill-section">
                            <h6>Methodology</h6>
                            <span class="skill-tag">Functional Analysis</span>
                            <span class="skill-tag">Scrum / Agile</span>
                            <span class="skill-tag">PDCA</span>
                            <span class="skill-tag">Product Dev</span>
                        </div>

                        <div class="skill-section">
                            <h6>Tools</h6>
                            <span class="skill-tag">JIRA / Confluence</span>
                            <span class="skill-tag">Figma / Miro / Draw.io</span>
                            <span class="skill-tag">SAP S4 Hana</span>
                            <span class="skill-tag">AWS / NAV</span>
                            <span class="skill-tag">DBeaver</span>
                        </div>

                        <div class="skill-section">
                            <h6>Tech Stack</h6>
                            <span class="skill-tag">SQL (MySQL/SQL Server)</span>
                            <span class="skill-tag">HTML</span>
                            <span class="skill-tag">NodeJs</span>
                            <span class="skill-tag">NoSQL</span>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>