<?php $modalID = "proj".$row['id']; ?>

<div class="project-col">
    <div class="project-card">
        <div style="height: 180px; overflow: hidden; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#<?php echo $modalID; ?>">
            <img src="assets/img/<?php echo $row['image']; ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo $row['title']; ?>">
        </div>
        
        <div class="p-4 d-flex flex-column h-100">
            <h5 class="fw-bold mb-2 text-truncate" style="color: var(--text-main);"><?php echo $row['title']; ?></h5>
            
            <div class="mb-3" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php 
                $stacks = explode(',', $row['tech_stack']);
                $limit = 0;
                foreach($stacks as $s){
                    if($limit<3){ echo '<span class="tech-pill">'.trim($s).'</span>'; $limit++; }
                }
                if(count($stacks)>3) echo '<small style="color:var(--text-sub)">+'.(count($stacks)-3).'</small>';
                ?>
            </div>

            <p class="small mb-4 flex-grow-1" style="color: var(--text-sub); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                <?php echo strip_tags($row['description']); ?>
            </p>

            <button class="btn btn-sm btn-outline-custom w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#<?php echo $modalID; ?>">
                Detail Project
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="<?php echo $modalID; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary border-opacity-10">
                <h5 class="modal-title fw-bold"><?php echo $row['title']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <img src="assets/img/<?php echo $row['image']; ?>" class="img-fluid rounded border w-100">
                    </div>
                    <div class="col-md-6">
                        <p style="color: var(--text-sub); white-space: pre-line;"><?php echo $row['description']; ?></p>
                        
                        <?php if(!empty($row['credentials'])): ?>
                        <div class="alert alert-secondary border-0 small mb-3">
                            <i class="bi bi-key-fill me-2"></i><strong>Credentials:</strong><br>
                            <?php echo nl2br($row['credentials']); ?>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2">
                            <?php if(!empty($row['link_case']) && $row['link_case']!='#'){
                                $is_url = (strpos($row['link_case'],'http')!==false);
                                $lk = $is_url ? $row['link_case'] : 'assets/docs/'.$row['link_case'];
                                echo '<a href="'.$lk.'" target="_blank" class="btn btn-outline-custom flex-fill"><i class="bi bi-file-earmark-text me-2"></i>Studi Kasus</a>';
                            } ?>
                            
                            <?php if(!empty($row['link_demo']) && $row['link_demo']!='#'){
                                echo '<a href="'.$row['link_demo'].'" target="_blank" class="btn btn-primary-custom flex-fill"><i class="bi bi-box-arrow-up-right me-2"></i>Live Demo</a>';
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>