<?php
// apps/sales-brief/change_status.php (PDO SECURE)
session_name("SB_APP_SESSION");
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

if(!isset($_SESSION['sb_user'])) { header("Location: index.php"); exit(); }

// Hanya terima POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Security Alert: Invalid Request (CSRF).');
}

if(isset($_POST['id']) && isset($_POST['action'])) {
    $id = sanitizeInt($_POST['id']);
    $action = sanitizeInput($_POST['action']);
    
    if ($id === false) die('Invalid ID');

    if($action == 'approve') {
        safeQuery($pdo, "UPDATE sales_briefs SET status = 'Approved' WHERE id = ?", [$id]);
        logSecurityEvent('SB Approved: ' . $id . ' by ' . $_SESSION['sb_user'], 'INFO');
        header("Location: view_sb.php?id=" . $id . "&source=approval");
    } 
    elseif($action == 'reopen') {
        // Increment Reopen Count
        safeQuery($pdo, "UPDATE sales_briefs SET status = 'Reopened', reopen_count = reopen_count + 1 WHERE id = ?", [$id]);
        logSecurityEvent('SB Reopened: ' . $id . ' by ' . $_SESSION['sb_user'], 'INFO');
        header("Location: informasi_promo.php");
    }
} else {
    header("Location: index.php");
}
?>