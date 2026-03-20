<?php
session_name("TMS_APP_SESSION");
session_start();
if (!isset($_SESSION['tms_status'])) { http_response_code(403); exit(); }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
header('Content-Type: application/json');
$dn_id = sanitizeInt($_GET['dn_id'] ?? 0);
$items = safeGetAll($pdo,
    "SELECT i.id, i.item_code, i.item_name, i.qty_ordered, i.qty_received
     FROM tms_items i JOIN tms_lpns l ON i.lpn_id=l.id
     WHERE l.dn_id=? ORDER BY l.id, i.id", [$dn_id]);
echo json_encode($items);
