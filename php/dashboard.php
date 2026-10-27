<?php
/**
 * StudentHub Portal - Practical 10: Authenticated Student Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_login();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];

$pdo = get_db_connection();
$enrolledEvents = [];
$userDetails = null;

if ($pdo) {
    try {
        // Fetch User Info
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $userDetails = $uStmt->fetch();

        // Fetch Enrolled Events
        $eStmt = $pdo->prepare("
            SELECT e.*, r.registered_at, r.status AS reg_status 
            FROM registrations r 
            JOIN events e ON r.event_id = e.id 
            WHERE r.user_id = ? 
            ORDER BY r.registered_at DESC
        ");
        $eStmt->execute([$userId]);
        $enrolledEvents = $eStmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Dashboard query error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - StudentHub Portal</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <!-- Header with Dynamic Role-Based Menu (Practical 14) -->
  <header>
    <div class="header-container">
      <a href="../pages/index.html" class="brand-logo">
        <span style="font-size: 1.8rem;">🎓</span>
        <span>Student<span class="brand-badge">Hub</span></span>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="../pages/index.html">Home</a></li>
          <li><a href="dashboard.php" class="active">Dashboard</a></li>
          <li><a href="../pages/events.html">Events Catalog</a></li>
          <li><a href="profile.php">My Profile</a></li>
          <?php if ($userRole === 'admin'): ?>
            <li><a href="admin_dashboard.php" style="color: var(--warning); font-weight: 700;">Admin Console</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <div class="header-actions">
        <span style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">👤 <?= htmlspecialchars($userName) ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="../pages/index.html">Home</a> &gt; <span>Student Dashboard</span>
  </div>

  <main>
    <!-- Welcome Header -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 28px 32px; border-radius: var(--radius-lg); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
      <div>
        <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">PHP SESSION ACTIVE</span>
        <h1 style="font-size: 2.1rem; margin-top: 6px; color: white;">Welcome, <?= htmlspecialchars($userName) ?>!</h1>
        <p style="opacity: 0.9; font-size: 1.05rem;">
          <?= htmlspecialchars($userDetails['course'] ?? 'B.Tech IT') ?> &bull; 
          <?= htmlspecialchars($userDetails['year'] ?? '3rd Year') ?> &bull; 
          <?= htmlspecialchars($userEmail) ?>
        </p>
      </div>
      <div>
        <a href="../pages/events.html" class="btn btn-secondary" style="background: white; color: var(--primary); font-weight: 700;">Browse All Events &rarr;</a>
      </div>
    </div>

    <!-- Quick Metrics -->
    <section class="stats-grid">
      <div class="stat-card">
        <div class="stat-info">
          <h4>Enrolled Events</h4>
          <div class="stat-number"><?= count($enrolledEvents) ?></div>
        </div>
        <div class="stat-icon">🎟️</div>
      </div>

      <div class="stat-card">
        <div class="stat-info">
          <h4>Account Status</h4>
          <div class="stat-number" style="font-size: 1.4rem; color: var(--success);"><?= htmlspecialchars($userDetails['status'] ?? 'Active') ?></div>
        </div>
        <div class="stat-icon">🛡️</div>
      </div>

      <div class="stat-card">
        <div class="stat-info">
          <h4>Session Timeout</h4>
          <div class="stat-number" style="font-size: 1.4rem;">15 Mins</div>
        </div>
        <div class="stat-icon">⏱️</div>
      </div>

      <div class="stat-card">
        <div class="stat-info">
          <h4>Current Role</h4>
          <div class="stat-number" style="font-size: 1.4rem; text-transform: capitalize;"><?= htmlspecialchars($userRole) ?></div>
        </div>
        <div class="stat-icon">🎓</div>
      </div>
    </section>

    <!-- Enrolled Events Table -->
    <section class="card" style="margin-bottom: 30px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3>My Enrolled Events (MySQL)</h3>
        <a href="../pages/events.html" class="btn btn-outline btn-sm">Find More Events</a>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Event Title</th>
              <th>Category</th>
              <th>Event Date</th>
              <th>Venue</th>
              <th>Status</th>
              <th>Registered On</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($enrolledEvents)): ?>
              <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                  You haven't enrolled in any events yet. <a href="../pages/events.html" style="color: var(--primary);">Explore open events!</a>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($enrolledEvents as $ev): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($ev['category']) ?></span></td>
                  <td><?= htmlspecialchars($ev['event_date']) ?></td>
                  <td><?= htmlspecialchars($ev['venue']) ?></td>
                  <td><span class="badge badge-success"><?= htmlspecialchars($ev['reg_status']) ?></span></td>
                  <td><?= htmlspecialchars($ev['registered_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <footer>
    <div class="footer-bottom">
      &copy; 2026-27 StudentHub Portal. All rights reserved.
    </div>
  </footer>

  <script src="../js/main.js"></script>
</body>
</html>
