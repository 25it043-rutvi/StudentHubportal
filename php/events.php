<?php
/**
 * StudentHub Portal - Practical 12: Event Management CRUD with Poster Uploads
 * Features: Admin event catalog, status filter, registration counts, image preview.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$pdo = get_db_connection();
$category = trim($_GET['category'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');
$msg = $_GET['msg'] ?? '';

// Build Query
$query = "
    SELECT e.*, COUNT(r.id) AS registered_count 
    FROM events e 
    LEFT JOIN registrations r ON e.id = r.event_id 
    WHERE 1=1
";
$params = [];

if ($category !== 'All') {
    $query .= " AND e.category = ?";
    $params[] = $category;
}

if ($status !== 'All') {
    $query .= " AND e.status = ?";
    $params[] = $status;
}

$query .= " GROUP BY e.id ORDER BY e.event_date ASC";

$events = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Event query error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Events - Practical 12</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <!-- Header -->
  <header>
    <div class="header-container">
      <a href="../pages/index.html" class="brand-logo">
        <span style="font-size: 1.8rem;">🎓</span>
        <span>Student<span class="brand-badge">Hub</span> <span class="badge badge-warning">ADMIN</span></span>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="admin_dashboard.php">Dashboard</a></li>
          <li><a href="students.php">Students CRUD</a></li>
          <li><a href="events.php" class="active">Events CRUD</a></li>
          <li><a href="../pages/ajax-events.html">AJAX Live</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <span style="font-size: 0.9rem; font-weight: 600;">🛡️ <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="admin_dashboard.php">Admin Console</a> &gt; <span>Event Management (Practical 12)</span>
  </div>

  <main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
      <div>
        <span class="badge badge-primary">Practical 12: File Validation & CRUD</span>
        <h1 style="margin-top: 4px;">Campus Events Management</h1>
        <p style="color: var(--text-muted);">Create events, upload posters with MIME validation, update schedules, and monitor registrations.</p>
      </div>

      <a href="event_add.php" class="btn btn-primary btn-sm">+ Add Event with Poster</a>
    </div>

    <?php if ($msg === 'added'): ?>
      <div class="alert alert-success">✅ New event created and poster uploaded successfully!</div>
    <?php elseif ($msg === 'updated'): ?>
      <div class="alert alert-success">✅ Event details and poster updated!</div>
    <?php elseif ($msg === 'deleted'): ?>
      <div class="alert alert-success">✅ Event record and associated poster removed.</div>
    <?php endif; ?>

    <!-- Filter Bar (Practical 12 Intermediate Extension) -->
    <form method="GET" action="events.php" class="filter-bar">
      <div class="filter-group">
        <label for="category-select" style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Category:</label>
        <select id="category-select" name="category" class="search-input" style="min-width: 170px;">
          <option value="All">All Categories</option>
          <option value="Technical" <?= $category === 'Technical' ? 'selected' : '' ?>>Technical</option>
          <option value="Workshop" <?= $category === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
          <option value="Cultural" <?= $category === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
          <option value="Sports" <?= $category === 'Sports' ? 'selected' : '' ?>>Sports</option>
        </select>

        <label for="status-select" style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Status:</label>
        <select id="status-select" name="status" class="search-input" style="min-width: 140px;">
          <option value="All">All Statuses</option>
          <option value="Open" <?= $status === 'Open' ? 'selected' : '' ?>>Open</option>
          <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Closed</option>
        </select>
      </div>

      <div class="filter-group">
        <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
        <a href="events.php" class="btn btn-secondary btn-sm">Reset</a>
      </div>
    </form>

    <!-- Events Table -->
    <div class="card">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Poster</th>
              <th>Title & Venue</th>
              <th>Category</th>
              <th>Date & Time</th>
              <th>Capacity</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($events)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
                  No events found. <a href="event_add.php" style="color: var(--primary);">Add your first event</a>.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($events as $ev): ?>
                <tr>
                  <td><strong>#<?= $ev['id'] ?></strong></td>
                  <td>
                    <?php if (!empty($ev['poster_image']) && file_exists(UPLOAD_DIR . $ev['poster_image'])): ?>
                      <img src="../uploads/posters/<?= htmlspecialchars($ev['poster_image']) ?>" alt="Poster" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                    <?php else: ?>
                      <div style="width: 50px; height: 50px; background: var(--primary-light); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">🎪</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($ev['title']) ?></strong><br>
                    <small style="color: var(--text-muted);">📍 <?= htmlspecialchars($ev['venue']) ?></small>
                  </td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($ev['category']) ?></span></td>
                  <td><?= htmlspecialchars($ev['event_date']) ?><br><small style="color: var(--text-muted);"><?= htmlspecialchars($ev['event_time']) ?></small></td>
                  <td><?= $ev['registered_count'] ?> / <?= $ev['max_seats'] ?> Booked</td>
                  <td>
                    <span class="badge <?= $ev['status'] === 'Open' ? 'badge-success' : 'badge-danger' ?>">
                      <?= htmlspecialchars($ev['status']) ?>
                    </span>
                  </td>
                  <td>
                    <a href="event_edit.php?id=<?= $ev['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="event_delete.php?id=<?= $ev['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete event #<?= $ev['id'] ?> and its uploaded poster image?');">
                      Delete
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      &copy; 2026-27 StudentHub Portal. All rights reserved.
    </div>
  </footer>

  <script src="../js/main.js"></script>
</body>
</html>
