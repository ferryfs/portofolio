<?php
// config/security.php - PDO VERSION (FINAL)

if (basename($_SERVER['PHP_SELF']) === 'security.php') {
    header("Location: index.php");
    exit();
}

// 1. PASSWORD & HASHING
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 2. DATABASE WRAPPER (PDO)
// Menggantikan fungsi mysqli yang bikin error
function safeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return false;
    }
}

// Helper: Ambil 1 baris data (Cocok buat Login)
function safeGetOne($pdo, $sql, $params = []) {
    $stmt = safeQuery($pdo, $sql, $params);
    return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
}

// 3. SANITIZATION
function sanitizeInput($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 4. CSRF PROTECTION
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfTokenField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function verifyCSRFToken($token = null) {
    $token = $token ?? $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// 5. RATE LIMITING
function checkRateLimit($key, $max = 5, $seconds = 300) {
    $file = sys_get_temp_dir() . '/rl_' . md5($key) . '.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['attempts' => []];
    $now = time();
    $data['attempts'] = array_filter($data['attempts'], fn($t) => ($now - $t) < $seconds);
    
    if (count($data['attempts']) >= $max) return false;
    
    $data['attempts'][] = $now;
    file_put_contents($file, json_encode($data));
    return true;
}

// 6. LOGGING
function logSecurityEvent($event, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/security.log';
    if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);
    $msg = "[" . date('Y-m-d H:i:s') . "][$level][{$_SERVER['REMOTE_ADDR']}] $event\n";
    file_put_contents($logFile, $msg, FILE_APPEND);
}
?>