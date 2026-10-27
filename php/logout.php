<?php
/**
 * StudentHub Portal - Practical 10: Secure Logout
 * Destroys session, deletes remember-me database token, clears cookies.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

// Ensure session is started before accessing or modifying it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_db_connection();
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// Log audit trail while session is still intact
if (isset($_SESSION['user_id'])) {
    $userEmail = $_SESSION['user_email'] ?? 'unknown';
    record_audit_log($_SESSION['user_id'], 'LOGOUT', "User logged out: {$userEmail}", $pdo);
}

// Remove DB remember token if present
if (isset($_COOKIE['studenthub_remember'])) {
    $token = $_COOKIE['studenthub_remember'];
    $token_hash = hash('sha256', $token);
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([$token_hash]);
        } catch (Exception $e) {
            error_log("Logout token delete error: " . $e->getMessage());
        }
    }
    setcookie('studenthub_remember', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params["path"],
        'domain' => $params["domain"],
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Destroy session
session_destroy();

header("Location: login.php?msg=logout");
exit;
?>
