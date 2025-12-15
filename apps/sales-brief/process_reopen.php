<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if (!$conn) { die("Koneksi Gagal: " . mysqli_connect_error()); }

// Cek apakah ada post data dari form edit_reopen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sb_id'])) {

    $id = $_POST['sb_id'];
    $ref_number = $_POST['ref_number'];
    $promo_name = mysqli_real_escape_string($conn, $_POST['promo_name']);
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    
    // 1. HANDLE SELECTED ITEMS (Array -> JSON)
    $selected_items = isset($_POST['selected_items']) ? json_encode($_POST['selected_items']) : '[]';

    // 2. UPDATE HEADER
    // Status kita balikin jadi 'Approved' supaya terkunci lagi.
    // (reopen_count tidak kita ubah disini, karena sudah ditambah saat klik tombol Reopen di awal)
    $sql_update = "UPDATE sales_briefs SET 
                   ref_number = '$ref_number',
                   promo_name = '$promo_name',
                   start_date = '$start_date',
                   end_date   = '$end_date',
                   selected_items = '$selected_items',
                   status = 'Approved' 
                   WHERE id = '$id'";
    
    if(!mysqli_query($conn, $sql_update)) {
        die("Error update header: " . mysqli_error($conn));
    }

    // 3. AMBIL DATA PENDUKUNG (TIER 1 & UOM)
    // Karena form edit reopen tidak menyertakan input UOM/Min Qty, kita ambil dari data header/target existing
    // supaya data customer tetap lengkap/konsisten.
    
    // Ambil UOM dari Header
    $q_header = mysqli_query($conn, "SELECT uom FROM sales_briefs WHERE id='$id'");
    $d_header = mysqli_fetch_assoc($q_header);
    $uom_db   = $d_header['uom'] ?? 'PCS';

    // Ambil Target Tier 1 (untuk min_qty_applied)
    $q_tier = mysqli_query($conn, "SELECT min_qty, min_amount FROM sb_targets WHERE sb_id='$id' AND tier_level=1 LIMIT 1");
    $tier1  = mysqli_fetch_assoc($q_tier);
    $min_qty = $tier1['min_qty'] ?? 0;
    $min_amt = $tier1['min_amount'] ?? 0;

    // 4. UPDATE CUSTOMERS (RESET & RE-INSERT)
    // Hapus semua customer lama untuk ID ini
    mysqli_query($conn, "DELETE FROM sb_customers WHERE sb_id = '$id'");

    // Insert ulang customer dari Form
    if(isset($_POST['cust_code'])) {
        $count = count($_POST['cust_code']);
        for($i = 0; $i < $count; $i++) {
            $c_code = $_POST['cust_code'][$i];
            $c_name = mysqli_real_escape_string($conn, $_POST['cust_name'][$i]);
            
            // Ambil qty dan amount
            $c_qty  = $_POST['cust_target_qty'][$i];
            // Bersihkan format currency (hapus titik) sebelum simpan ke DB
            $c_amt  = str_replace('.', '', $_POST['cust_target_amt'][$i]);

            $sql_cust = "INSERT INTO sb_customers (sb_id, cust_code, cust_name, target_qty, target_amount, min_qty_applied, min_amount_applied, uom)
                         VALUES ('$id', '$c_code', '$c_name', '$c_qty', '$c_amt', '$min_qty', '$min_amt', '$uom_db')";
            
            if(!mysqli_query($conn, $sql_cust)) {
                die("Error insert customer: " . mysqli_error($conn));
            }
        }
    }

    // 5. SELESAI & REDIRECT
    // Arahkan user ke Monitoring untuk melihat hasilnya (Status sudah Approved)
    echo "<script>
            alert('Reopen berhasil disubmit! Data telah diperbarui dan status kembali menjadi Approved.'); 
            window.location='monitoring.php';
          </script>";

} else {
    // Kalau coba akses langsung tanpa post
    header("Location: informasi_promo.php");
}
?>