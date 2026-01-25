<footer class="text-center pb-32 pt-10 text-gray-400 text-sm border-t border-gray-200 bg-white">
        &copy; <?php echo date('Y'); ?> Ferry Fernando. <?php echo $txt['footer']; ?>
    </footer>

    <?php
    // Prepare Data for JS
    $jsCareerData = [];
    foreach($careers as $company => $roles) { $jsCareerData[$company] = $roles; }
    ?>
    <script>
        // PASS PHP DATA TO JS
        const careerData = <?php echo json_encode($jsCareerData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const projectData = <?php echo json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const is_en = <?php echo $is_en ? 'true' : 'false'; ?>;
        const defaultCartoon = "<?php echo $default_popup_img; ?>";
    </script>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>