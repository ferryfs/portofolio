<?php
// config/security.php (FINAL VERSION)

// 1. Fungsi Sanitasi String Biasa (XSS Protection)
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// 2. Fungsi Sanitasi Integer (Pastikan Angka) - INI YANG TADI HILANG
function sanitizeInt($data) {
    // Hapus semua karakter kecuali angka dan tanda minus
    $clean = preg_replace('/[^0-9-]/', '', $data);
    return (int) $clean;
}

// 3. Fungsi Sanitasi Float (Angka Desimal)
function sanitizeFloat($data) {
    $clean = preg_replace('/[^0-9.-]/', '', $data);
    return (float) $clean;
}

// 4. Fungsi CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    // Session start manual kalau belum aktif
    // (Biar gak conflict kalau file lain udah session_start)
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token = null) {
    // Jika token tidak dikirim via parameter, cek $_POST default
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// 5. Rate Limiting (Cegah Spam/Brute Force)
function checkRateLimit($key, $limit = 5, $seconds = 60) {
    $timestamp = time();
    $sessionKey = 'rate_limit_' . $key;
    
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [];
    }
    
    // Filter request lama
    $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function($time) use ($timestamp, $seconds) {
        return ($timestamp - $time) < $seconds;
    });
    
    // Cek jumlah request
    if (count($_SESSION[$sessionKey]) >= $limit) {
        return false; // Limit exceeded
    }
    
    // Catat request baru
    $_SESSION[$sessionKey][] = $timestamp;
    return true;
}

// 6. Password Hashing (Bcrypt Wrapper)
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 7. Security Logger (Audit Trail Sederhana)
function logSecurityEvent($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $logMessage = "[$date] [$level] [$ip] $message" . PHP_EOL;
    
    file_put_contents($logDir . '/security.log', $logMessage, FILE_APPEND);
}

// 8. File Upload Handler (Aman)
function handleFileUpload($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validasi Tipe
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Format file tidak diizinkan.'];
    }
    
    // Validasi Ukuran (Max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File terlalu besar (Max 2MB).'];
    }
    
    // Rename Unik (Anti Overwrite & Shell Upload)
    $newFileName = uniqid() . '.' . $fileType;
    $targetPath = $targetDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $newFileName];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file.'];
}
?>