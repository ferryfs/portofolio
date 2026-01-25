<?php
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) { die('Akses Ditolak!'); }

class Database {
    private $host; private $db_name; private $username; private $password; public $conn;

    public function __construct() { $this->loadEnv(); }

    private function loadEnv() {
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
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'portfolio_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';

        try {
            // 🔥 FIX EMOJI: Tambahkan charset=utf8mb4
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 🔥 FIX EMOJI: Paksa command set names
            $this->conn->exec("set names utf8mb4");
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            // Die diam-diam biar gak bocor path error
            die("Database Connection Error.");
        }
        return $this->conn;
    }
}

$database = new Database();
$db = $database->connect();
?>