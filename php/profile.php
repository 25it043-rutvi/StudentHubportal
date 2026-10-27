<?php
/**
 * StudentHub Portal - Practical 10: Authenticated Student Profile
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_login();

$userId = $_SESSION['user_id'];
$pdo = get_db_connection();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['full_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $course = sanitize_input($_POST['course'] ?? '');
    $year = sanitize_input($_POST['year'] ?? '');

    if (empty($name) || strlen($name) < 3) {
        $error = "Valid name is required.";
    } elseif (empty($phone) || !preg_match("/^[6-9]\d{9}$/", $phone)) {
        $error = "Valid 10-digit mobile number is required.";
    } else {
        if ($pdo) {
            try {
                $upStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, course = ?, year = ? WHERE id = ?");
                $upStmt->execute([$name, $phone, $course, $year, $userId]);
                $_SESSION['user_name'] = $name;
                record_audit_log($userId, 'PROFILE_UPDATE', "User updated profile", $pdo);
                $msg = "Profile updated successfully!";
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        }
    }
}

$user = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Profile - StudentHub Portal</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <header>
    <div class="header-container">
      <a href="../pages/index.html" class="brand-logo">
        <span style="font-size: 1.8rem;">🎓</span>
        <span>Student<span class="brand-badge">Hub</span></span>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="../pages/index.html">Home</a></li>
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="../pages/events.html">Events</a></li>
          <li><a href="profile.php" class="active">My Profile</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <span style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="dashboard.php">Dashboard</a> &gt; <span>My Profile</span>
  </div>

  <main style="max-width: 700px; margin: 30px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Account Management</span>
        <h2>My Student Profile</h2>
        <p>Update your personal information in MySQL.</p>
      </div>

      <?php if (!empty($msg)): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="profile.php">
        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? $_SESSION['user_name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address (Read-Only)</label>
          <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? $_SESSION['user_email']) ?>" readonly style="background: var(--bg-subtle);">
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Mobile Number</label>
          <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="course">Department / Course</label>
            <select id="course" name="course" class="form-control">
              <option value="B.Tech IT" <?= ($user['course'] ?? '') === 'B.Tech IT' ? 'selected' : '' ?>>B.Tech IT</option>
              <option value="B.Tech CE" <?= ($user['course'] ?? '') === 'B.Tech CE' ? 'selected' : '' ?>>B.Tech CE</option>
              <option value="B.Tech AIML" <?= ($user['course'] ?? '') === 'B.Tech AIML' ? 'selected' : '' ?>>B.Tech AIML</option>
              <option value="MCA" <?= ($user['course'] ?? '') === 'MCA' ? 'selected' : '' ?>>MCA</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="year">Year</label>
            <select id="year" name="year" class="form-control">
              <option value="1st Year" <?= ($user['year'] ?? '') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
              <option value="2nd Year" <?= ($user['year'] ?? '') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
              <option value="3rd Year" <?= ($user['year'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
              <option value="4th Year" <?= ($user['year'] ?? '') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
            </select>
          </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 10px;">
          <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
          <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
