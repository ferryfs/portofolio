<?php
// config/database.php
// Mencegah akses langsung ke file ini
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) { die('Akses Ditolak!'); }

class Database {
    private $host; 
    private $db_name; 
    private $username; 
    private $password; 
    public $conn;

    public function __construct() { 
        $this->loadEnv(); 
    }

    private function loadEnv() {
        // Load file .env dari folder root (naik satu level)
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                putenv(sprintf('%s=%s', trim($name), trim($value)));
            }
        }
    }

    public function connect() {
        $this->conn = null;
        // Ambil credential dari environment variable
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'portofolio_db'; 
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';

        try {
            // 🔥 FIX EMOJI & SECURITY: Tambahkan charset=utf8mb4
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // 🔥 FIX EMOJI: Paksa command set names
            $this->conn->exec("set names utf8mb4");
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Gagal Konek: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// ===========================================
// 🔥 INSTANSIASI GLOBAL ($pdo)
// ===========================================
$database = new Database();
$pdo = $database->connect();

// ============================================================
// 🔥 HELPER FUNCTIONS (INI YANG TADI HILANG BOS!)
// ============================================================

// 1. safeQuery: Eksekusi query dengan aman (Prepared Statement)
if (!function_exists('safeQuery')) {
    function safeQuery($pdo, $sql, $params = []) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
            } catch (PDOException $e) {
            // Tampilkan error lengkap ke layar biar ketahuan salahnya dimana
            die("🔥 SQL ERROR: " . $e->getMessage()); 
        }
    }
}

// 2. safeGetOne: Ambil 1 baris data (fetch assoc)
if (!function_exists('safeGetOne')) {
    function safeGetOne($pdo, $sql, $params = []) {
        $stmt = safeQuery($pdo, $sql, $params);
        return $stmt->fetch();
    }
}

// 3. safeGetAll: Ambil semua data (fetchAll)
if (!function_exists('safeGetAll')) {
    function safeGetAll($pdo, $sql, $params = []) {
        $stmt = safeQuery($pdo, $sql, $params);
        return $stmt->fetchAll();
    }
}
?>