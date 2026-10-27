<?php
/**
 * StudentHub Portal - Authentication & Role Access Guard
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Check if the user is currently authenticated
 */
function is_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        // Attempt remember-me cookie verification (Practical 10 Advanced)
        if (isset($_COOKIE['studenthub_remember'])) {
            return verify_remember_cookie();
        }
        return false;
    }

    // Check Inactivity Timeout (Practical 10: 15 minutes = 900 seconds)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
        // Inactivity timeout expired
        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? 'unknown';
        if ($userId) {
            record_audit_log($userId, 'TIMEOUT_LOGOUT', "Session expired due to inactivity for {$userEmail}");
        }

        session_unset();
        session_destroy();

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        if (isset($_COOKIE['studenthub_remember'])) {
            setcookie('studenthub_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        header("Location: login.php?msg=timeout");
        exit;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Require authentication or redirect to login
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php?msg=login_required");
        exit;
    }
}

/**
 * Require specific role ('admin' or 'student')
 */
function require_role($required_role) {
    require_login();
    if ($_SESSION['user_role'] !== $required_role) {
        // Unauthorized access blocked (Practical 10 requirement)
        http_response_code(403);
        die("
            <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
                <h1 style='color: #ef4444;'>403 - Access Denied</h1>
                <p>You do not have administrative privileges to view this page.</p>
                <a href='dashboard.php' style='color: #2563eb;'>Return to Student Dashboard</a> | 
                <a href='logout.php' style='color: #ef4444;'>Logout</a>
            </div>
        ");
    }
}

/**
 * Verify Remember-Me Cookie against DB token
 * Reads cookie, validates hash against remember_tokens, checks expiry and active status, and logs user in.
 * Also cleans up expired tokens from the database.
 */
function verify_remember_cookie() {
    $token = $_COOKIE['studenthub_remember'] ?? '';
    if (empty($token)) return false;

    $pdo = get_db_connection();
    if (!$pdo) return false;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Automatically delete expired tokens (maintenance)
    try {
        $pdo->exec("DELETE FROM remember_tokens WHERE expires_at <= NOW()");
    } catch (Exception $e) {
        error_log("Pruning expired tokens failed: " . $e->getMessage());
    }

    $token_hash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare("
            SELECT u.* 
            FROM users u 
            JOIN remember_tokens rt ON u.id = rt.user_id 
            WHERE rt.token_hash = ? AND rt.expires_at > NOW() AND u.status = 'Active' 
            LIMIT 1
        ");
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch();

        if ($user) {
            // Restore secure session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            record_audit_log($user['id'], 'REMEMBER_LOGIN', "Auto-login via remember-me token for {$user['email']}", $pdo);
            return true;
        } else {
            // Token is invalid, expired, or user is inactive: clear invalid cookie
            setcookie('studenthub_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    } catch (Exception $e) {
        error_log("Remember token error: " . $e->getMessage());
    }
    return false;
}
?>
