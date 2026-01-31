<?php
// 1. LOGIC: AMBIL PERUSAHAAN UNIK DARI TIMELINE (Career Journey)
// Kita group berdasarkan nama Company, dan ambil Role TERAKHIR (ID tertinggi/Date terbaru)
$company_tabs = [];
foreach ($timelineData as $t) {
    $compName = $t['company'];
    // Jika perusahaan belum ada di list, atau data ini lebih baru (ID lebih besar), simpan/update
    // Asumsi: Data di JSON timeline lu sudah urut atau ID makin gede makin baru
    if (!isset($company_tabs[$compName]) || $t['id'] > $company_tabs[$compName]['id']) {
        $company_tabs[$compName] = [
            'id' => $t['id'],
            'company' => $t['company'],
            'role' => $t['role'], // Ambil jabatan terakhir
        ];
    }
}
?>

<section id="projects" class="py-12 md:py-20 mt-0 md:mt-12 max-w-7xl mx-auto px-4 relative z-10" data-aos="fade-up">
    
    <div class="island-box bg-primary text-white relative overflow-hidden shadow-2xl rounded-[2.5rem]">
        <div class="absolute top-0 right-0 w-96 h-96 bg-accent/20 blur-[100px] rounded-full pointer-events-none"></div>
        
        <div class="p-6 md:p-12 pb-0 relative z-20">
            <div class="mb-8 text-center md:text-left">
                <h2 class="text-3xl md:text-5xl font-black mb-3 text-white tracking-tight"><?php echo $txt['sect_proj_title']; ?></h2>
                <p class="text-gray-400 max-w-xl text-sm md:text-base leading-relaxed"><?php echo $txt['sect_proj_desc']; ?></p>
            </div>

            <div class="flex gap-3 mb-8 overflow-x-auto pb-2 scrollbar-hide border-b border-white/10">
                <button onclick="switchMainTab('work')" id="main-tab-work" class="main-tab-btn active px-6 py-3 text-sm md:text-base font-bold text-white border-b-2 border-accent transition-all">
                    <i class="bi bi-briefcase-fill me-2"></i> <?php echo $txt['tab_work']; ?>
                </button>
                <button onclick="switchMainTab('personal')" id="main-tab-personal" class="main-tab-btn px-6 py-3 text-sm md:text-base font-bold text-gray-400 hover:text-white border-b-2 border-transparent transition-all">
                    <i class="bi bi-code-square me-2"></i> <?php echo $txt['tab_personal']; ?>
                </button>
            </div>
        </div>

        <div class="relative w-full z-20 p-6 md:p-12 pt-0 min-h-[400px]">
            
            <div id="content-work" class="tab-content-area">
                
                <div class="flex gap-3 mb-8 overflow-x-auto pb-4 scrollbar-hide snap-x">
                    <button onclick="switchSubTab('all-work')" id="sub-btn-all-work" class="sub-tab-btn active shrink-0 px-5 py-2 rounded-full text-xs font-bold bg-white text-primary border border-transparent shadow-lg hover:scale-105 transition-all">
                        All Works
                    </button>

                    <?php 
                    $i = 0;
                    foreach($company_tabs as $comp): 
                        $slug = md5($comp['company']); // Bikin ID unik dari nama PT
                    ?>
                    <button onclick="switchSubTab('<?php echo $slug; ?>')" id="sub-btn-<?php echo $slug; ?>" class="sub-tab-btn shrink-0 px-5 py-2 rounded-full text-xs font-bold bg-white/5 text-gray-300 border border-white/10 hover:bg-white/10 hover:text-white transition-all hover:scale-105">
                        <?php echo $comp['company']; ?>
                    </button>
                    <?php $i++; endforeach; ?>
                </div>

                <div id="sub-content-all-work" class="sub-content-area flex flex-nowrap gap-6 overflow-x-auto pb-10 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php 
                    $hasWork = false;
                    foreach($projects as $d): 
                        if(strtolower($d['category']) == 'work'): 
                            $hasWork = true;
                            include 'component/card_project.php'; 
                        endif; 
                    endforeach; 
                    if(!$hasWork) echo '<div class="text-white/30 italic">No work projects yet.</div>';
                    ?>
                </div>

                <?php foreach($company_tabs as $comp): $slug = md5($comp['company']); ?>
                <div id="sub-content-<?php echo $slug; ?>" class="sub-content-area hidden flex flex-nowrap gap-6 overflow-x-auto pb-10 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php 
                    $found = false;
                    foreach($projects as $d): 
                        // Logic Matching: Cek apakah nama PT ada di deskripsi project atau field 'tech_stack' (Kalo DB project belum ada kolom company)
                        // IDEALNYA: Di DB project ada kolom 'company_name'. 
                        // SEMENTARA: Kita anggap field 'tech_stack' atau title mengandung nama PT, atau tampilin semua work (User nanti update DB).
                        // Note: Karena lu bilang "nanti aja isi popupnya", asumsi matching project ke PT juga manual dulu atau show all work filtered.
                        
                        // FILTER: Project Category WORK && (Cocokkan nama PT manual/string match)
                        // Di sini gue filter category 'work' dulu. Nanti lu harus tambah field 'company' di table project biar akurat.
                        if(strtolower($d['category']) == 'work'): 
                            $found = true;
                            // Inject Company Name ke array project biar bisa dipake di popup
                            $d['company_ref'] = $comp['company']; 
                            include 'component/card_project.php'; 
                        endif; 
                    endforeach; 
                    if(!$found) echo '<div class="text-white/30 italic">No projects for this company.</div>';
                    ?>
                </div>
                <?php endforeach; ?>

            </div>

            <div id="content-personal" class="tab-content-area hidden">
                <div class="flex flex-nowrap gap-6 overflow-x-auto pb-10 snap-x scrollbar-hide cursor-grab active:cursor-grabbing">
                    <?php 
                    $hasPersonal = false;
                    foreach($projects as $d): 
                        if(strtolower($d['category']) == 'personal'): 
                            $hasPersonal = true;
                            include 'component/card_project.php'; 
                        endif; 
                    endforeach; 
                    
                    if(!$hasPersonal): ?>
                        <div class="w-full text-center py-12 text-white/30 italic border border-dashed border-white/10 rounded-2xl">
                            Belum ada project kategori Personal.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
function switchMainTab(tab) {
    // 1. Ganti Style Tombol Utama
    document.querySelectorAll('.main-tab-btn').forEach(btn => {
        btn.classList.remove('text-white', 'border-accent');
        btn.classList.add('text-gray-400', 'border-transparent');
    });
    const activeBtn = document.getElementById('main-tab-' + tab);
    activeBtn.classList.remove('text-gray-400', 'border-transparent');
    activeBtn.classList.add('text-white', 'border-accent');

    // 2. Hide/Show Content Area
    document.querySelectorAll('.tab-content-area').forEach(el => el.classList.add('hidden'));
    document.getElementById('content-' + tab).classList.remove('hidden');
}

function switchSubTab(slug) {
    // 1. Ganti Style Tombol Sub
    document.querySelectorAll('.sub-tab-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'text-primary');
        btn.classList.add('bg-white/5', 'text-gray-300');
    });
    const activeBtn = document.getElementById('sub-btn-' + slug);
    activeBtn.classList.remove('bg-white/5', 'text-gray-300');
    activeBtn.classList.add('bg-white', 'text-primary');

    // 2. Hide/Show Sub Content
    document.querySelectorAll('.sub-content-area').forEach(el => el.classList.add('hidden'));
    document.getElementById('sub-content-' + slug).classList.remove('hidden');
}
</script>