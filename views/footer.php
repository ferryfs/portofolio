<footer class="text-center pb-32 pt-10 text-gray-400 text-sm border-t border-gray-200 bg-white">
    &copy; <?php echo date('Y'); ?> Ferry Fernando. <?php echo $txt['footer']; ?>
</footer>

<div id="superModal" class="fixed inset-0 z-[99999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    
    <div class="fixed inset-0 bg-gray-900/90 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

    <div class="fixed inset-0 z-10 overflow-hidden flex items-center justify-center p-2 md:p-6">
        
        <div class="relative w-full max-w-7xl h-full max-h-[95vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white z-20 shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="bi bi-journal-text text-xl"></i>
                    </div>
                    <div>
                        <h3 id="guideTitle" class="text-xl font-bold text-gray-900 leading-none mb-1">Project Name</h3>
                        <span id="guideCat" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Documentation</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <a id="guideDemoLinkTop" href="#" target="_blank" class="hidden md:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <i class="bi bi-play-circle"></i> Live Demo
                    </a>
                    <button type="button" onclick="closeModal()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-1 overflow-hidden">
                
                <div class="hidden md:flex w-64 bg-gray-50 border-r border-gray-200 flex-col shrink-0">
                    <div class="p-6 overflow-y-auto">
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Table of Contents</h5>
                        <nav class="space-y-1">
                            <a href="#section-overview" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-md border border-blue-100">
                                <i class="bi bi-eye"></i> Overview
                            </a>
                            <a href="#section-features" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-md transition">
                                <i class="bi bi-stars"></i> Key Features
                            </a>
                            <a href="#section-tech" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-md transition">
                                <i class="bi bi-code-slash"></i> Tech Stack
                            </a>
                        </nav>
                    </div>
                    
                    <div class="mt-auto p-6 border-t border-gray-200 bg-gray-100">
                        <p class="text-xs text-gray-500 mb-2 font-semibold">Project by</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-300 overflow-hidden border border-gray-300">
                                <img src="assets/img/profile.jpg" class="w-full h-full object-cover">
                            </div>
                            <div class="text-sm font-bold text-gray-800">Ferry Fernando</div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto bg-white scroll-smooth relative" id="guideContentArea">
                    <div class="max-w-4xl mx-auto p-6 md:p-10 space-y-12 pb-24">
                        
                        <section id="section-overview" class="scroll-mt-6">
                            
                            <div class="w-full h-auto max-h-[400px] bg-gray-100 rounded-xl border border-gray-200 mb-8 overflow-hidden flex items-center justify-center p-4">
                                <img id="guideMainImage" src="" class="h-full w-full object-contain rounded shadow-sm" alt="App Preview">
                            </div>

                            <div id="guideRoleInfo" class="hidden bg-blue-50 border border-blue-100 rounded-xl p-5 mb-8 shadow-sm">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-blue-600 shadow-sm shrink-0 border border-blue-100">
                                        <i class="bi bi-person-workspace text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xs font-bold text-blue-400 uppercase tracking-widest mb-1">My Role in this Project</h4>
                                        <div id="guideRoleTitle" class="text-lg font-bold text-gray-900 leading-tight">Job Title</div>
                                        <div id="guideRoleCompany" class="text-sm font-semibold text-gray-500 mb-3">at Company Name</div>
                                        
                                        <details class="group bg-white rounded-lg border border-blue-100">
                                            <summary class="flex cursor-pointer items-center justify-between px-4 py-2 text-xs font-bold text-blue-600 hover:bg-blue-50 rounded-lg select-none transition">
                                                <span><i class="bi bi-list-check me-2"></i> View Scope & Responsibilities</span>
                                                <span class="transition group-open:rotate-180 text-blue-400">
                                                    <i class="bi bi-chevron-down"></i>
                                                </span>
                                            </summary>
                                            <div id="guideRoleDesc" class="px-4 pb-4 pt-1 text-sm text-gray-600 prose prose-sm prose-blue max-w-none leading-relaxed border-t border-gray-100 mt-1">
                                                </div>
                                        </details>
                                    </div>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Project Overview</h2>
                            <div id="guideDesc" class="prose prose-blue max-w-none text-gray-600 leading-relaxed text-sm md:text-base"></div>
                        </section>

                        <section id="section-features" class="scroll-mt-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">Key Challenges & Impact</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="p-6 rounded-xl border border-red-100 bg-red-50/50">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="p-2 bg-red-100 text-red-600 rounded-lg"><i class="bi bi-fire"></i></div>
                                        <h4 class="font-bold text-gray-900">The Challenge</h4>
                                    </div>
                                    <p id="guideChallenge" class="text-sm text-gray-600 leading-relaxed"></p>
                                </div>
                                <div class="p-6 rounded-xl border border-green-100 bg-green-50/50">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="p-2 bg-green-100 text-green-600 rounded-lg"><i class="bi bi-graph-up-arrow"></i></div>
                                        <h4 class="font-bold text-gray-900">The Impact</h4>
                                    </div>
                                    <p id="guideImpact" class="text-sm text-gray-600 leading-relaxed"></p>
                                </div>
                            </div>
                        </section>

                        <section id="section-tech" class="scroll-mt-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">Technology Stack</h2>
                            <div id="guideStack" class="flex flex-wrap gap-2"></div>
                        </section>

                        <div class="mt-12 p-8 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl text-center border border-blue-100">
                            <h3 class="font-bold text-blue-900 mb-2 text-lg">Interested in this project?</h3>
                            <p class="text-sm text-blue-700 mb-6">Check out the live demo to see how it works in real-time.</p>
                            <a id="guideBottomLink" href="#" target="_blank" class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 text-white font-bold rounded-full shadow-lg hover:bg-blue-700 hover:-translate-y-1 transition transform">
                                Launch Application 🚀
                            </a>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php
// ==========================================
// 3. PHP DATA PREPARATION (Wajib ada biar JS gak error)
// ==========================================
$js_projects = $projects ?? [];
$js_timeline = $timelineData ?? []; // Data Mentah Timeline
$temp_careers = [];
foreach($js_timeline as $row) { $temp_careers[$row['company']][] = $row; }
?>

<script>
    // PASS DATA PHP KE JS
    const projectData = <?php echo json_encode($js_projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const careerData = <?php echo json_encode($temp_careers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    // Data Timeline Flat buat pencarian
    const rawTimeline = <?php echo json_encode($js_timeline, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const is_en = <?php echo ($is_en ?? false) ? 'true' : 'false'; ?>;
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/main.js"></script>

<script>
    // ===============================================
    // LOGIC MODAL "USER GUIDE" (FIXED)
    // ===============================================
    const modal = document.getElementById('superModal');

    function showProjectDetail(id) {
        // Cari data project
        const project = projectData.find(p => p.id == id);
        if (!project) {
            console.error("Project ID not found:", id);
            return;
        }

        // 1. ISI HEADER
        document.getElementById('guideTitle').innerText = project.title;
        document.getElementById('guideCat').innerText = project.category + " DOCUMENTATION";
        
        // 2. ISI GAMBAR UTAMA
        let imgSrc = 'assets/img/default.jpg';
        if(project.image_url) imgSrc = project.image_url;
        else if (project.image) imgSrc = `assets/img/${project.image}`;
        document.getElementById('guideMainImage').src = imgSrc;

        // 3. ISI DESKRIPSI
        const desc = is_en ? project.description_en : project.description;
        document.getElementById('guideDesc').innerHTML = desc || '<p class="text-gray-400 italic">No overview available.</p>';

        // 4. ISI CHALLENGE & IMPACT
        document.getElementById('guideChallenge').innerHTML = project.challenge || "No specific challenge details.";
        document.getElementById('guideImpact').innerHTML = project.impact || "No specific impact details.";

        // 5. ISI TECH STACK
        const stackContainer = document.getElementById('guideStack');
        stackContainer.innerHTML = '';
        if (project.tech_stack) {
            project.tech_stack.split(',').forEach(tech => {
                if(tech.trim() !== '') {
                    stackContainer.innerHTML += `
                        <div class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-sm font-medium text-gray-700">${tech.trim()}</span>
                        </div>
                    `;
                }
            });
        }

        // ===============================================
        // 🔥 LOGIC BARU: MATCHING ROLE via COMPANY_REF
        // ===============================================
        const roleInfoBox = document.getElementById('guideRoleInfo');
        let matchedCareer = null;

        if (roleInfoBox) { // Cek biar gak error kalo elemen gak ada
            
            // CARA 1: Cek Jalur VIP (Database Admin)
            if (project.company_ref && project.company_ref.trim() !== "") {
                matchedCareer = rawTimeline.find(job => job.company === project.company_ref);
            }

            // CARA 2: Kalau Jalur VIP kosong, coba TEBAK (Fallback)
            if (!matchedCareer) {
                 rawTimeline.forEach(job => {
                    const keyword = job.company.split(' ')[0].length > 2 ? job.company.split(' ')[0] : job.company.split(' ')[1];
                    if (keyword && project.title.toLowerCase().includes(keyword.toLowerCase())) {
                        matchedCareer = job;
                    }
                });
            }

            // TAMPILKAN HASILNYA
            if (matchedCareer) {
                roleInfoBox.classList.remove('hidden');
                document.getElementById('guideRoleTitle').innerText = matchedCareer.role;
                document.getElementById('guideRoleCompany').innerText = "at " + matchedCareer.company;
                document.getElementById('guideRoleDesc').innerHTML = matchedCareer.description; 
            } else {
                roleInfoBox.classList.add('hidden');
            }
        }
        // ===============================================

        // 6. LINK DEMO
        const btnTop = document.getElementById('guideDemoLinkTop');
        const btnBottom = document.getElementById('guideBottomLink');
        
        if (project.link_demo && project.link_demo !== '#' && project.link_demo !== '') {
            const link = project.link_demo;
            btnTop.href = link;
            btnTop.classList.remove('hidden');
            btnTop.classList.add('inline-flex');
            
            btnBottom.href = link;
            btnBottom.classList.remove('opacity-50', 'pointer-events-none', 'bg-gray-400');
            btnBottom.classList.add('bg-blue-600', 'hover:bg-blue-700', 'hover:-translate-y-1');
            btnBottom.innerText = "Launch Application 🚀";
        } else {
            btnTop.href = '#';
            btnTop.classList.add('hidden');
            
            btnBottom.href = '#';
            btnBottom.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'hover:-translate-y-1');
            btnBottom.classList.add('opacity-50', 'pointer-events-none', 'bg-gray-400');
            btnBottom.innerText = "Private / No Demo Available 🔒";
        }

        // BUKA MODAL
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // SECURITY & UX
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") closeModal();
        if (event.ctrlKey && event.shiftKey && (event.key === 'X' || event.key === 'x')) window.location.href = 'admin'; 
    });

    document.querySelectorAll('a').forEach(function(link) {
        if(link.getAttribute('href') && !link.getAttribute('href').includes('logout') && link.getAttribute('target') !== '_blank') {
            let urlTujuan = link.getAttribute('href');
            link.setAttribute('href', 'javascript:void(0);');
            link.addEventListener('click', function() { window.location.href = urlTujuan; });
        }
    });
</script>

</body>
</html>