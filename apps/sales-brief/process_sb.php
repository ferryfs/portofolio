<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db"); 
if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

if (isset($_POST['submit_sb'])) {

    $sb_no          = $_POST['SalesBriefNo'];
    $ref_no         = $_POST['original_salesbrief_no'] ?? '';
    $promo_name     = mysqli_real_escape_string($conn, $_POST['SalesBriefName']);
    $start_date     = $_POST['fromdate'];
    $end_date       = $_POST['todate'];
    
    // Values from Disabled fields might not be sent, handle them if needed via hidden input or logic
    // Here we assume standard submit works. 
    // NOTE: Disabled select fields don't send value. If Consignment is ON, we hardcode values here or rely on JS to remove disabled before submit.
    // Hack: JS submission via form.submit() sends what is enabled. 
    // Best practice: remove 'disabled' attribute just before submit via JS. 
    // But since we control the backend, we can set default if empty and consignment is ON.
    
    $is_capping     = isset($_POST['capping_promo']) ? 1 : 0;
    $is_consignment = isset($_POST['is_consigment']) ? 1 : 0;
    $is_mix         = isset($_POST['is_mix_item']) ? 1 : 0;
    $is_multiple    = isset($_POST['berlaku_kelipatan']) ? 1 : 0;

    $target_calc    = $_POST['target_by'] ?? null;
    $mechanism      = $_POST['promo_mechanism'] ?? null;
    $promo_type     = $_POST['promo_selection'] ?? null;
    
    // Auto-fill logic at Backend just in case disabled fields were empty
    if($is_consignment) {
        $is_mix = 1;
        $target_calc = 'qty';
        $mechanism = 'Direct Promo';
        $promo_type = '2'; // Cashback
    }

    $uom            = $_POST['uom'] ?? null;
    $conditions     = isset($_POST['promo_condition']) ? json_encode($_POST['promo_condition']) : '[]';
    $prod_types     = isset($_POST['product_type']) ? json_encode($_POST['product_type']) : '[]';
    $item_cats      = isset($_POST['item_category']) ? json_encode($_POST['item_category']) : '[]';
    $prod_groups    = isset($_POST['product_group']) ? json_encode($_POST['product_group']) : '[]';
    $sel_items      = isset($_POST['selected_items']) ? json_encode($_POST['selected_items']) : '[]';
    $man_items      = isset($_POST['mandatory_items']) ? json_encode($_POST['mandatory_items']) : '[]';
    $budget_clean   = str_replace('.', '', $_POST['budget_amount']); 
    $terms          = mysqli_real_escape_string($conn, $_POST['syarat'] ?? '');
    $mech           = mysqli_real_escape_string($conn, $_POST['mekanisme'] ?? '');
    $creator        = $_SESSION['sb_name'] ?? 'System';

    $banner_filename = null;
    if (isset($_FILES['file_banner']) && $_FILES['file_banner']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $banner_filename = time() . "_" . basename($_FILES["file_banner"]["name"]);
        move_uploaded_file($_FILES["file_banner"]["tmp_name"], $target_dir . $banner_filename);
    }

    mysqli_begin_transaction($conn);

    try {
        $sql_header = "INSERT INTO sales_briefs (
            sb_number, ref_number, promo_name, start_date, end_date, 
            is_capping, is_consignment, is_mix_item, is_multiple,
            target_calculation, promo_mechanism, promo_type, promo_conditions,
            product_types, item_categories, product_groups, selected_items, mandatory_items,
            uom, budget_allocation, banner_image, terms_conditions, mechanism, created_by
        ) VALUES (
            '$sb_no', '$ref_no', '$promo_name', '$start_date', '$end_date',
            '$is_capping', '$is_consignment', '$is_mix', '$is_multiple',
            '$target_calc', '$mechanism', '$promo_type', '$conditions',
            '$prod_types', '$item_cats', '$prod_groups', '$sel_items', '$man_items',
            '$uom', '$budget_clean', '$banner_filename', '$terms', '$mech', '$creator'
        )";

        if (!mysqli_query($conn, $sql_header)) { throw new Exception("Error Header: " . mysqli_error($conn)); }
        $sb_id = mysqli_insert_id($conn); 

        if(isset($_POST['jml_min_motif'])) {
            $count_tier = count($_POST['jml_min_motif']);
            for ($i = 0; $i < $count_tier; $i++) {
                $t_motif  = $_POST['jml_min_motif'][$i] ?? 0;
                $t_qty    = $_POST['jml_min_qty'][$i] ?? 0;
                $t_amt    = str_replace('.', '', $_POST['jml_min_amount'][$i] ?? 0); 
                $t_disc   = $_POST['discount_percent'][$i] ?? 0;
                $tier_lvl = $i + 1;

                if($t_motif > 0 || $t_qty > 0 || $t_amt > 0) {
                    $sql_target = "INSERT INTO sb_targets (sb_id, tier_level, min_motif, min_qty, min_amount, discount_pct)
                                   VALUES ('$sb_id', '$tier_lvl', '$t_motif', '$t_qty', '$t_amt', '$t_disc')";
                    if (!mysqli_query($conn, $sql_target)) { throw new Exception("Error Target Tier: " . mysqli_error($conn)); }
                }
            }
        }

        if(isset($_POST['cust_code'])) {
            $count_cust = count($_POST['cust_code']);
            for ($i = 0; $i < $count_cust; $i++) {
                $c_code    = $_POST['cust_code'][$i];
                $c_name    = mysqli_real_escape_string($conn, $_POST['cust_name'][$i]);
                $c_tgt_qty = $_POST['cust_target_qty'][$i] ?? 0; 
                $c_tgt_amt = str_replace('.', '', $_POST['cust_target_amt'][$i] ?? 0);
                $c_min_qty = $_POST['cust_min_qty'][$i] ?? 0;
                $c_min_amt = str_replace('.', '', $_POST['cust_min_amt'][$i] ?? 0);
                $c_uom     = $_POST['cust_uom'][$i] ?? '';

                $sql_cust = "INSERT INTO sb_customers (sb_id, cust_code, cust_name, target_qty, target_amount, min_qty_applied, min_amount_applied, uom)
                             VALUES ('$sb_id', '$c_code', '$c_name', '$c_tgt_qty', '$c_tgt_amt', '$c_min_qty', '$c_min_amt', '$c_uom')";
                
                if (!mysqli_query($conn, $sql_cust)) { throw new Exception("Error Customer: " . mysqli_error($conn)); }
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Sales Brief Berhasil Dibuat! 🔥'); window.location='index.php';</script>";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal Menyimpan: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>