<?php
/**
 * StudentHub Portal - Practical 12: Edit Event with Optional Poster Update
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$pdo = get_db_connection();
$id = intval($_GET['id'] ?? 0);
$errors = [];

if (!$id || !$pdo) {
    header("Location: events.php");
    exit;
}

// Fetch existing event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: events.php");
    exit;
}

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $category = sanitize_input($_POST['category'] ?? 'Technical');
    $event_date = sanitize_input($_POST['event_date'] ?? '');
    $event_time = sanitize_input($_POST['event_time'] ?? '');
    $venue = sanitize_input($_POST['venue'] ?? '');
    $max_seats = intval($_POST['max_seats'] ?? 100);
    $description = sanitize_input($_POST['description'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Open');
    $posterFileName = $event['poster_image'];

    // Handle Poster Replacement if new file provided
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $maxFileSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxFileSize) {
                $errors[] = "New poster file exceeds 2MB limit.";
            } else {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($mimeType, $allowedMimes)) {
                    $errors[] = "Invalid file type. Only JPG, PNG, WEBP allowed.";
                } else {
                    $uniqueName = 'poster_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $uniqueName)) {
                        // Unlink old poster if not default
                        if ($posterFileName !== 'default_event.jpg' && file_exists(UPLOAD_DIR . $posterFileName)) {
                            @unlink(UPLOAD_DIR . $posterFileName);
                        }
                        $posterFileName = $uniqueName;
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $up = $pdo->prepare("
                UPDATE events SET title = ?, category = ?, event_date = ?, event_time = ?, venue = ?, description = ?, poster_image = ?, max_seats = ?, status = ?
                WHERE id = ?
            ");
            $up->execute([$title, $category, $event_date, $event_time, $venue, $description, $posterFileName, $max_seats, $status, $id]);

            record_audit_log($_SESSION['user_id'], 'EVENT_UPDATED', "Admin updated event #{$id}", $pdo);
            header("Location: events.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Event #<?= $id ?> - Practical 12</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <header>
    <div class="header-container">
      <a href="../pages/index.html" class="brand-logo">
        <span style="font-size: 1.8rem;">🎓</span>
        <span>Student<span class="brand-badge">Hub</span> <span class="badge badge-warning">ADMIN</span></span>
      </a>
      <nav>
        <ul class="nav-links">
          <li><a href="admin_dashboard.php">Dashboard</a></li>
          <li><a href="events.php" class="active">Events CRUD</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="admin_dashboard.php">Admin Console</a> &gt; <a href="events.php">Events</a> &gt; <span>Edit Event #<?= $id ?></span>
  </div>

  <main style="max-width: 680px; margin: 30px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Practical 12: Event Update</span>
        <h2>Edit Event #<?= $id ?></h2>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="event_edit.php?id=<?= $id ?>" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label" for="title">Event Title *</label>
          <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="category">Category</label>
            <select id="category" name="category" class="form-control">
              <option value="Technical" <?= $event['category'] === 'Technical' ? 'selected' : '' ?>>Technical</option>
              <option value="Workshop" <?= $event['category'] === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
              <option value="Cultural" <?= $event['category'] === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
              <option value="Sports" <?= $event['category'] === 'Sports' ? 'selected' : '' ?>>Sports</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-control">
              <option value="Open" <?= $event['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
              <option value="Closed" <?= $event['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="event_date">Date</label>
            <input type="date" id="event_date" name="event_date" class="form-control" value="<?= htmlspecialchars($event['event_date']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="event_time">Time</label>
            <input type="text" id="event_time" name="event_time" class="form-control" value="<?= htmlspecialchars($event['event_time']) ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="venue">Venue</label>
            <input type="text" id="venue" name="venue" class="form-control" value="<?= htmlspecialchars($event['venue']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="max_seats">Capacity</label>
            <input type="number" id="max_seats" name="max_seats" class="form-control" value="<?= htmlspecialchars($event['max_seats']) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="poster">Replace Poster Image (Optional, Max 2MB)</label>
          <input type="file" id="poster" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
          <small style="color: var(--text-muted); margin-top: 4px;">Current file: <?= htmlspecialchars($event['poster_image']) ?></small>
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Description</label>
          <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($event['description']) ?></textarea>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 10px;">
          <button type="submit" class="btn btn-primary btn-block">Update Event</button>
          <a href="events.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
