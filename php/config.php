<?php
/**
 * StudentHub Portal - Application Configuration & Session Setup
 * Subject: Web Development Frameworks (ITUE203) - FTE CHARUSAT
 * Covers Practicals 7, 8, 9, 10, 11, 12, 13, 14
 */

// Error Reporting Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // In production/display, handle gracefully
ini_set('log_errors', 1);

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'studenthub_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Paths & Constants
define('APP_NAME', 'StudentHub Portal');
define('SESSION_TIMEOUT_SECONDS', 900); // 15 Minutes Inactivity Timeout (Practical 10)
define('UPLOAD_DIR', __DIR__ . '/../uploads/posters/');
define('DATA_DIR', __DIR__ . '/../data/');

// Secure Session Initialization (Practical 10)
if (session_status() === PHP_SESSION_NONE) {
    // Session Cookie Hardening
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Sanitize User Input against XSS
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if (!function_exists('record_audit_log')) {
    function record_audit_log($userId, $action, $details, $pdo = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $action, $details, $ip]);
            } catch (Exception $e) {
                // Silently log error without disrupting user flow
                error_log("Audit log failed: " . $e->getMessage());
            }
        }
    }
}
?>
