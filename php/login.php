<?php
/**
 * StudentHub Portal - Practical 10: Secure Login with Sessions & RBAC
 * Features: password_verify(), session fixation protection (session_regenerate_id),
 * role-based redirection, inactivity timeout handling, remember-me tokens,
 * CSRF protection, and brute-force rate limiting.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

// Ensure session is initialized
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$msg = $_GET['msg'] ?? '';

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// If already authenticated, redirect to appropriate role dashboard
if (is_logged_in()) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$email_input = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_email = trim($_POST['email'] ?? '');
    $email_input = $raw_email;
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // 1. CSRF Token Verification (enforced when token is provided from form)
    if (isset($_POST['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = "Security validation failed: invalid CSRF token. Please refresh the page and try again.";
    } elseif (empty($raw_email) || empty($password)) {
        $error = "Please provide both your email address and password.";
    } elseif (!filter_var($raw_email, FILTER_VALIDATE_EMAIL)) {
        // Strict email validation (reject invalid email formats)
        $error = "Please enter a valid email address.";
    } else {
        $email = strtolower($raw_email);
        $pdo = get_db_connection();

        if (!$pdo) {
            $error = "Database service is currently unavailable. Please verify MySQL is running in XAMPP.";
        } else {
            // 2. Brute-Force Rate Limiting: Lock after 5 failed attempts per IP or Email in 15 minutes
            $lockoutMinutes = 15;
            $maxAttempts = 5;
            $isLocked = false;

            try {
                $checkAttempts = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM audit_logs 
                    WHERE action = 'LOGIN_FAILED' 
                      AND (ip_address = ? OR details LIKE ?) 
                      AND created_at >= (NOW() - INTERVAL ? MINUTE)
                ");
                $checkAttempts->execute([$client_ip, "%{$email}%", $lockoutMinutes]);
                $failedAttempts = (int)$checkAttempts->fetchColumn();

                if ($failedAttempts >= $maxAttempts) {
                    $isLocked = true;
                    $error = "Too many failed login attempts. This account/IP has been temporarily locked for 15 minutes for security.";
                }
            } catch (Exception $e) {
                error_log("Brute-force check error: " . $e->getMessage());
            }

            if (!$isLocked) {
                try {
                    // Fetch user record by email using prepared statement
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    // Practical 10: password_verify()
                    if ($user && password_verify($password, $user['password_hash'])) {
                        if ($user['status'] !== 'Active') {
                            $error = "Your account has been deactivated. Please contact the administrator.";
                            record_audit_log($user['id'], 'LOGIN_DEACTIVATED', "Login attempt on deactivated account: {$email}", $pdo);
                        } else {
                            // Prevent Session Fixation attack
                            session_regenerate_id(true);

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['last_activity'] = time();

                            // Regenerate fresh CSRF token for the authenticated session
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                            // Practical 10 Advanced Extension: Remember-Me Token
                            if ($remember_me) {
                                $token = bin2hex(random_bytes(32));
                                $token_hash = hash('sha256', $token);
                                $expires_at = date('Y-m-d H:i:s', time() + (86400 * 14)); // 14 days

                                $tokenStmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                                $tokenStmt->execute([$user['id'], $token_hash, $expires_at]);

                                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                                setcookie('studenthub_remember', $token, [
                                    'expires'  => time() + (86400 * 14),
                                    'path'     => '/',
                                    'domain'   => '',
                                    'secure'   => $isHttps,
                                    'httponly' => true,
                                    'samesite' => 'Lax'
                                ]);
                            }

                            // Update last login timestamp
                            $updateLogin = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            $updateLogin->execute([$user['id']]);

                            // Audit Log
                            record_audit_log($user['id'], 'LOGIN_SUCCESS', "User logged in: {$user['email']}", $pdo);

                            // Role-Based Redirection
                            if ($user['role'] === 'admin') {
                                header("Location: admin_dashboard.php");
                            } else {
                                header("Location: dashboard.php");
                            }
                            exit;
                        }
                    } else {
                        // User not found or password mismatch: generic message
                        $error = "Invalid email or password. Please try again.";
                        $userId = $user ? $user['id'] : null;
                        record_audit_log($userId, 'LOGIN_FAILED', "Failed password attempt for {$email}", $pdo);
                    }
                } catch (PDOException $e) {
                    // Do not leak raw database exceptions to user
                    error_log("Login database error: " . $e->getMessage());
                    $error = "Authentication system error. Please try again later.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Login to StudentHub Portal - Secure authentication with session management, RBAC, and remember-me tokens.">
  <title>Portal Login - StudentHub Portal</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Header & Navigation (Unified with login.html) -->
  <header>
    <div class="header-container">
      <a href="../pages/index.html" class="brand-logo" aria-label="StudentHub Home">
        <span style="font-size: 1.8rem;">🎓</span>
        <span>Student<span class="brand-badge">Hub</span></span>
      </a>

      <button class="hamburger-btn" id="hamburger-btn" aria-label="Toggle navigation" aria-expanded="false">
        ☰
      </button>

      <nav id="main-nav" aria-label="Primary Navigation">
        <ul class="nav-links">
          <li><a href="../pages/index.html">Home</a></li>
          <li><a href="../pages/about.html">About</a></li>
          <li><a href="../pages/events.html">Events</a></li>
          <li><a href="../pages/faq.html">FAQ</a></li>
          <li><a href="../pages/contact.html">Contact</a></li>
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="admin_dashboard.php">Admin</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <button class="theme-toggle-btn" id="theme-toggle-btn" aria-label="Toggle dark mode">🌙 Dark</button>
        <a href="login.php" class="btn btn-primary btn-sm active">Login</a>
        <a href="register.php" class="btn btn-outline btn-sm">Register</a>
      </div>
    </div>
  </header>

  <!-- Breadcrumbs -->
  <div class="breadcrumbs" aria-label="Breadcrumb">
    <a href="../pages/index.html">Home</a> &gt; <span>Login</span>
  </div>

  <!-- Main Content Area -->
  <main id="main-content">
    <div class="form-card" style="max-width: 480px; margin: 0 auto;">
      <div class="form-header">
        <span class="badge badge-primary">Practical 10: Secure Auth</span>
        <h2>Welcome Back</h2>
        <p>Sign in to access your student or administrator dashboard.</p>
      </div>

      <!-- Feedback Messages -->
      <?php if ($msg === 'timeout'): ?>
        <div class="alert alert-danger" role="alert">
          ⏱️ <strong>Session Expired:</strong> You were logged out due to 15 minutes of inactivity.
        </div>
      <?php elseif ($msg === 'logout'): ?>
        <div class="alert alert-success" role="alert">
          🔒 <strong>Logged Out:</strong> Your session was securely destroyed.
        </div>
      <?php elseif ($msg === 'login_required'): ?>
        <div class="alert alert-info" role="alert">
          🔐 <strong>Authentication Required:</strong> Please sign in to access that page.
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
          ❌ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Demo Credentials Helper Callout -->
      <div style="background: var(--info-bg); border: 1px solid #bae6fd; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem; color: #0369a1;">
        <strong>🔑 Default Demo Credentials:</strong><br>
        • <strong>Admin:</strong> <code>admin@studenthub.com</code> / <code>Admin@123</code><br>
        • <strong>Student:</strong> <code>student@studenthub.com</code> / <code>Student@123</code>
      </div>

      <!-- Login Form: posts to login.php with CSRF Protection -->
      <form id="login-form" action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="form-group">
          <label for="login-email" class="form-label">Email Address *</label>
          <input type="email" id="login-email" name="email" class="form-control"
                 value="<?= htmlspecialchars($email_input) ?>"
                 placeholder="e.g. admin@studenthub.com" required autocomplete="username">
        </div>

        <div class="form-group">
          <label for="login-password" class="form-label">
            <span>Password *</span>
            <a href="../pages/forgot_password.html" style="color: var(--primary); font-size: 0.8rem; text-decoration: none;">Forgot?</a>
          </label>
          <input type="password" id="login-password" name="password" class="form-control"
                 placeholder="Enter your password" required autocomplete="current-password">
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
            <input type="checkbox" name="remember_me" id="remember-me" value="1">
            <span>Remember me on this computer (Practical 10 Token)</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1.05rem;">
          Sign In
        </button>
      </form>

      <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: var(--text-muted);">
        Don't have an account yet? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register here</a>
      </div>

      <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-around;">
        <a href="../pages/dashboard.html" class="btn btn-outline btn-sm">Static Student Preview &rarr;</a>
        <a href="../pages/admin.html" class="btn btn-secondary btn-sm">Static Admin Preview &rarr;</a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <div class="footer-container">
      <div class="footer-bottom" style="text-align: center; padding: 24px 0; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem;">
        &copy; 2026 StudentHub Portal. Department of Information Technology, FTE, CHARUSAT. All rights reserved.
      </div>
    </div>
  </footer>

  <script src="../js/main.js"></script>
</body>
</html>
