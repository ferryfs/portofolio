<?php
// apps/sales-brief/process_sb.php (PDO VERSION)

session_name("SB_APP_SESSION");
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if (isset($_POST['submit_sb'])) {
    
    // Validasi CSRF
    if (!verifyCSRFToken()) {
        die("Security Alert: Invalid Token.");
    }

    try {
        $pdo->beginTransaction(); // Mulai Transaksi

        $sb_no      = trim($_POST['SalesBriefNo']);
        $ref_no     = trim($_POST['original_salesbrief_no'] ?? '');
        $promo_name = trim($_POST['SalesBriefName']);
        $start      = trim($_POST['fromdate']);
        $end        = trim($_POST['todate']);
        $uom        = trim($_POST['uom'] ?? '');
        $budget     = sanitizeInt(str_replace('.', '', $_POST['budget_amount']));
        $terms      = trim($_POST['syarat'] ?? '');
        $mech       = trim($_POST['mekanisme'] ?? '');
        $creator    = $_SESSION['sb_name'] ?? 'System';

        // Checkbox Logic
        $is_capping     = isset($_POST['capping_promo']) ? 1 : 0;
        $is_consignment = isset($_POST['is_consigment']) ? 1 : 0;
        $is_mix         = isset($_POST['is_mix_item']) ? 1 : 0;
        $is_multiple    = isset($_POST['berlaku_kelipatan']) ? 1 : 0;

        $target_calc    = $_POST['target_by'] ?? null;
        $mechanism      = $_POST['promo_mechanism'] ?? null;
        $promo_type     = $_POST['promo_selection'] ?? null;

        // Consignment Overrides
        if($is_consignment) {
            $is_mix = 1;
            $target_calc = 'qty';
            $mechanism = 'Direct Promo';
            $promo_type = '2';
        }

        // JSON Fields
        $conds   = json_encode($_POST['promo_condition'] ?? []);
        $types   = json_encode($_POST['product_type'] ?? []);
        $cats    = json_encode($_POST['item_category'] ?? []);
        $groups  = json_encode($_POST['product_group'] ?? []);
        $sel     = json_encode($_POST['selected_items'] ?? []);
        $man     = json_encode($_POST['mandatory_items'] ?? []);

        // File Upload
        $banner = null;
        if (isset($_FILES['file_banner']) && $_FILES['file_banner']['error'] == 0) {
            $up = handleFileUpload($_FILES['file_banner'], 'uploads/');
            if ($up['success']) $banner = $up['filename'];
        }

        // 1. INSERT HEADER
        $sql = "INSERT INTO sales_briefs (
            sb_number, ref_number, promo_name, start_date, end_date, 
            is_capping, is_consignment, is_mix_item, is_multiple,
            target_calculation, promo_mechanism, promo_type, promo_conditions,
            product_types, item_categories, product_groups, selected_items, mandatory_items,
            uom, budget_allocation, banner_image, terms_conditions, mechanism, created_by, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', NOW())";

        safeQuery($pdo, $sql, [
            $sb_no, $ref_no, $promo_name, $start, $end,
            $is_capping, $is_consignment, $is_mix, $is_multiple,
            $target_calc, $mechanism, $promo_type, $conds,
            $types, $cats, $groups, $sel, $man,
            $uom, $budget, $banner, $terms, $mech, $creator
        ]);
        
        $sb_id = $pdo->lastInsertId();

        // 2. INSERT TARGETS (TIER)
        if (isset($_POST['jml_min_motif'])) {
            $count = count($_POST['jml_min_motif']);
            for ($i=0; $i < $count; $i++) {
                $motif = sanitizeInt($_POST['jml_min_motif'][$i]);
                $qty   = sanitizeInt($_POST['jml_min_qty'][$i]);
                $amt   = sanitizeInt(str_replace('.', '', $_POST['jml_min_amount'][$i]));
                $disc  = sanitizeInt($_POST['discount_percent'][$i]);
                
                if ($motif > 0 || $qty > 0 || $amt > 0) {
                    safeQuery($pdo, "INSERT INTO sb_targets (sb_id, tier_level, min_motif, min_qty, min_amount, discount_pct) VALUES (?, ?, ?, ?, ?, ?)", 
                    [$sb_id, ($i+1), $motif, $qty, $amt, $disc]);
                }
            }
        }

        // 3. INSERT CUSTOMERS
        if (isset($_POST['cust_code'])) {
            $count = count($_POST['cust_code']);
            for ($i=0; $i < $count; $i++) {
                $c_code = trim($_POST['cust_code'][$i]);
                $c_name = trim($_POST['cust_name'][$i]);
                $c_qty  = sanitizeInt($_POST['cust_target_qty'][$i]);
                $c_amt  = sanitizeInt(str_replace('.', '', $_POST['cust_target_amt'][$i]));
                
                // Get Min Data from hidden inputs
                $c_min_qty = sanitizeInt($_POST['cust_min_qty'][$i] ?? 0);
                $c_min_amt = sanitizeInt(str_replace('.', '', $_POST['cust_min_amt'][$i] ?? 0));
                $c_uom     = trim($_POST['cust_uom'][$i] ?? '');

                safeQuery($pdo, "INSERT INTO sb_customers (sb_id, cust_code, cust_name, target_qty, target_amount, min_qty_applied, min_amount_applied, uom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                [$sb_id, $c_code, $c_name, $c_qty, $c_amt, $c_min_qty, $c_min_amt, $c_uom]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Sales Brief Berhasil Disimpan!'); window.location='index.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>