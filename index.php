<?php
// Portfolio - Clean Architecture
// 2025 Ferry Fernando

// 1. HEADER (Logic & Config ada di sini)
include 'views/header.php';

// 2. COMPONENTS
include 'views/navbar.php';
include 'views/hero.php';
include 'views/skills.php';
include 'views/about.php';
include 'views/projects.php'; // PASTIKAN CUMA SATU BARIS INI
include 'views/contact.php';

// 3. MODALS
include 'views/modals.php';

// 4. FOOTER (Scripts)
include 'views/footer.php';
?>