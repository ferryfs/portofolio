<div class="project-col" data-aos="fade-up">
    <div class="project-card h-100 d-flex flex-column">
        
        <div class="project-img-box" style="height: 200px; overflow: hidden; position: relative;">
            <img src="assets/img/<?php echo $row['image']; ?>" alt="Project Image" 
                 style="width: 100%; height: 100%; object-fit: cover;">
            
            <div style="position: absolute; top: 10px; right: 10px;">
                <?php if($row['category'] == 'work'): ?>
                    <span class="badge bg-primary shadow">🏢 Work</span>
                <?php else: ?>
                    <span class="badge bg-success shadow">🚀 Personal</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-4 d-flex flex-column flex-grow-1">
            
            <h5 class="fw-bold text-white mb-2" style="font-size: 1.1rem;">
                <?php echo $row['title']; ?>
            </h5>
            
            <div class="mb-3">
                <?php 
                $techs = explode(',', $row['tech_stack']);
                foreach($techs as $t) {
                    // Pakai style inline biar warnanya PASTI keluar
                    echo '<span class="badge bg-dark border border-secondary me-1 mb-1 fw-normal" 
                          style="font-size: 0.7rem; color: #cbd5e1;">'.trim($t).'</span>';
                }
                ?>
            </div>
            
            <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.5; color: #94a3b8 !important;">
                <?php 
                // Potong teks kalau kepanjangan
                $desc = $row['description'];
                echo (strlen($desc) > 90) ? substr($desc, 0, 90) . '...' : $desc; 
                ?>
            </p>
            
            <div class="d-flex gap-2 mt-auto pt-3 border-top border-secondary">
                
                <?php if(!empty($row['link_demo']) && $row['link_demo'] != '#'): ?>
                    <a href="<?php echo $row['link_demo']; ?>" target="_blank" 
                       class="btn btn-sm w-100 fw-bold text-white shadow-sm"
                       style="background: var(--accent); border: none; padding: 8px 0;">
                        <i class="bi bi-play-circle-fill me-1"></i> Demo
                    </a>
                <?php else: ?>
                    <button class="btn btn-sm w-100 text-muted border-secondary" disabled 
                            style="background: rgba(255,255,255,0.05); cursor: not-allowed;">
                        <i class="bi bi-lock-fill me-1"></i> Private
                    </button>
                <?php endif; ?>

                <?php 
                // Cek apakah ada file/link case study
                $has_case = ($row['link_case'] != '#' && !empty($row['link_case']));
                
                if($has_case): 
                    // Cek apakah itu Link URL atau File Upload
                    $case_url = (strpos($row['link_case'], 'http') !== false) 
                                ? $row['link_case'] 
                                : 'assets/docs/'.$row['link_case'];
                ?>
                    <a href="<?php echo $case_url; ?>" target="_blank" 
                       class="btn btn-sm w-100 fw-bold"
                       style="background: transparent; border: 1px solid var(--accent); color: var(--accent); padding: 8px 0;">
                        <i class="bi bi-file-earmark-richtext me-1"></i> Detail
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </div>
</div>