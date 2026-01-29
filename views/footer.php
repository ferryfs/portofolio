<footer class="text-center pb-32 pt-10 text-gray-400 text-sm border-t border-gray-200 bg-white">
    &copy; <?php echo date('Y'); ?> Ferry Fernando. <?php echo $txt['footer']; ?>
</footer>

<?php
// Prepare Data for JS
$jsCareerData = [];
// Group careers by company again for JS logic if needed
$temp_careers = [];
foreach($timelineData as $row) { $temp_careers[$row['company']][] = $row; }

?>
<script>
    // PASS PHP DATA TO JS
    // Ensure using JSON_HEX tags to prevent XSS in JS context
    const careerData = <?php echo json_encode($temp_careers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const projectData = <?php echo json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const is_en = <?php echo $is_en ? 'true' : 'false'; ?>;
    const defaultCartoon = "<?php echo $default_popup_img; ?>";
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/main.js"></script>
<script>
    // Cari semua elemen <a> di halaman ini
    document.querySelectorAll('a').forEach(function(link) {
        // Cek dulu, jangan ubah link logout atau yang punya target="_blank"
        if(link.getAttribute('href') && !link.getAttribute('href').includes('logout') && link.getAttribute('target') !== '_blank') {
            
            let urlTujuan = link.getAttribute('href');
            
            // Hapus href asli biar gak muncul di pojok
            link.setAttribute('href', 'javascript:void(0);');
            
            // Tambahin fungsi klik manual
            link.addEventListener('click', function() {
                window.location.href = urlTujuan;
            });
        }
    });
</script>
</body>
</html>