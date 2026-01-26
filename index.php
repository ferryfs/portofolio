<?php
// index.php - MAIN ROUTER (BERSIH)

// 1. HEADER (Wajib Paling Atas - Memuat Config & Data)
include 'views/header.php';

// 2. COMPONENTS
include 'views/navbar.php';
include 'views/hero.php';
include 'views/about.php';
include 'views/skills.php';
include 'views/projects.php'; 
include 'views/contact.php';

// 3. FOOTER
include 'views/modals.php';
include 'views/footer.php';
?>