<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ferry Fernando | Product Manager Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      body {
        font-family: 'Poppins', sans-serif;
        background-color: #0f172a; /* Dark theme kekinian */
        color: #e2e8f0;
      }
      .navbar {
        background-color: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
      }
      .hero-section {
        padding: 100px 0;
      }
      .card-custom {
        background-color: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        transition: transform 0.3s;
      }
      .card-custom:hover {
        transform: translateY(-5px);
        border-color: #3b82f6;
      }
      .btn-primary-custom {
        background-color: #3b82f6;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
      }
      .btn-primary-custom:hover {
        background-color: #2563eb;
        color: white;
      }
      .text-highlight {
        color: #3b82f6;
      }
    </style>
  </head>
  <body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
      <div class="container">
        <a class="navbar-brand fw-bold" href="#">Ferry.<span class="text-highlight">Dev</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#projects">Projects</a></li>
            <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
            <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <section id="home" class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h5 class="text-highlight">Hi, I'm Ferry Fernando</h5>
            <h1 class="display-4 fw-bold mb-3">Product Manager & <br>Tech Enthusiast</h1>
            <p class="lead mb-4 text-secondary">Membangun solusi digital yang efisien dan user-friendly. Spesialis dalam manajemen produk dan pengembangan sistem.</p>
            <a href="#projects" class="btn btn-primary-custom">Lihat Project Saya</a>
            <a href="#" class="btn btn-outline-light ms-2">Download CV</a>
          </div>
          <div class="col-lg-6 text-center">
            <img src="https://via.placeholder.com/400" class="img-fluid rounded-circle shadow-lg" alt="Ferry Profile">
          </div>
        </div>
      </div>
    </section>

    <section id="projects" class="py-5">
      <div class="container">
        <h2 class="text-center fw-bold mb-5">Featured Projects</h2>
        <div class="row">
          
          <div class="col-md-4 mb-4">
            <div class="card card-custom h-100 p-3">
              <div class="card-body">
                <h3 class="h5 card-title fw-bold">Aplikasi Manajemen Stok</h3>
                <p class="card-text text-secondary">Aplikasi berbasis web untuk mengatur stok barang masuk dan keluar dengan fitur reporting real-time.</p>
                <div class="mt-3">
                  <span class="badge bg-secondary">PHP</span>
                  <span class="badge bg-secondary">MySQL</span>
                </div>
              </div>
              <div class="card-footer bg-transparent border-0">
                <a href="https://link-ke-aplikasi-lo.com" target="_blank" class="btn btn-primary-custom w-100">Coba Aplikasi <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-4">
            <div class="card card-custom h-100 p-3">
              <div class="card-body">
                <h3 class="h5 card-title fw-bold">Cancel Order System</h3>
                <p class="card-text text-secondary">Sistem integrasi NAV dan Tacommerce untuk handling pembatalan order dan capping otomatis.</p>
                <div class="mt-3">
                  <span class="badge bg-secondary">System Design</span>
                  <span class="badge bg-secondary">BRD</span>
                </div>
              </div>
              <div class="card-footer bg-transparent border-0">
                <a href="#" class="btn btn-outline-light w-100">Lihat Case Study</a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>