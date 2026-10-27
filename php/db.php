<?php
/**
 * StudentHub Portal - Database Connection Handler
 * Practical 8: MySQL Schema Design, PDO Connectivity, and Prepared Statements
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;
    private $mysqli = null;
    private $error = null;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database PDO Connection Error: " . $this->error);
        }

        // Also initialize MySQLi for hybrid/curriculum demonstration
        try {
            $this->mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($this->mysqli->connect_errno) {
                // MySQLi error
                $this->mysqli = null;
            } else {
                $this->mysqli->set_charset(DB_CHARSET);
            }
        } catch (Exception $e) {
            $this->mysqli = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function getMySQLi() {
        return $this->mysqli;
    }

    public function getError() {
        return $this->error;
    }
}

// Helper global accessor functions
function get_db_connection() {
    return Database::getInstance()->getConnection();
}

function get_mysqli_connection() {
    return Database::getInstance()->getMySQLi();
}

/**
 * Record an entry into Audit Logs (Practical 14 & Security Logging)
 * Accessible across all backend scripts via db.php
 */
if (!function_exists('record_audit_log')) {
    function record_audit_log($userId, $action, $details, $pdo = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!$pdo) {
            $pdo = get_db_connection();
        }
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $action, $details, $ip]);
            } catch (Exception $e) {
                error_log("Audit log failed: " . $e->getMessage());
            }
        }
    }
}
?>
