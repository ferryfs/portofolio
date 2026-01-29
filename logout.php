<?php
// Harus sama persis dengan yang di login.php & admin.php
session_name("PORTFOLIO_CMS_SESSION");
session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Redirect
header("Location: login.php");
exit();
?>