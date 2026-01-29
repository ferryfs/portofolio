<?php
session_name("SB_APP_SESSION");
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

if(isset($_GET['id']) && isset($_GET['action'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    // --- SKENARIO 1: APPROVE ---
    if($action == 'approve') {
        // Update status jadi Approved
        mysqli_query($conn, "UPDATE sales_briefs SET status = 'Approved' WHERE id = '$id'");
        
        // Balik ke halaman View (mode approval)
        header("Location: view_sb.php?id=$id&source=approval");
    } 
    
    // --- SKENARIO 2: REOPEN ---
    elseif($action == 'reopen') {
        // 1. Update Status jadi 'Reopened'
        // 2. Tambah counter reopen_count + 1 (Supaya limit 1x bekerja)
        mysqli_query($conn, "UPDATE sales_briefs SET status = 'Reopened', reopen_count = reopen_count + 1 WHERE id = '$id'");
        
        // Redirect ke menu Informasi Promo (Sesuai Request)
        header("Location: informasi_promo.php");
    }
}
?>