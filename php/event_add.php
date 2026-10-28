<?php
/**
 * StudentHub Portal - Practical 12: Add Event with Poster Upload & File Validation
 * Features: File size check (max 2MB), MIME type check (jpg, png, webp),
 * unique filename generation, audit log.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$errors = [];
$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $category = sanitize_input($_POST['category'] ?? 'Technical');
    $event_date = sanitize_input($_POST['event_date'] ?? '');
    $event_time = sanitize_input($_POST['event_time'] ?? '');
    $venue = sanitize_input($_POST['venue'] ?? '');
    $max_seats = intval($_POST['max_seats'] ?? 100);
    $description = sanitize_input($_POST['description'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Open');

    if (empty($title)) $errors[] = "Event title is required.";
    if (empty($event_date)) $errors[] = "Event date is required.";
    if (empty($venue)) $errors[] = "Event venue is required.";

    // Poster Upload & File Validation (Practical 12 Core Requirement)
    $posterFileName = 'default_event.jpg';

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload encountered an error (Code: {$file['error']}).";
        } else {
            // 1. File Size Validation (Max 2MB = 2,097,152 bytes)
            $maxFileSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxFileSize) {
                $errors[] = "Poster file size exceeds maximum limit of 2MB.";
            }

            // 2. MIME Type & Extension Validation
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($mimeType, $allowedMimes) || !in_array($ext, $allowedExts)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and WEBP image files are allowed.";
            } else {
                // 3. Advanced Extension: Secure Image Renaming & Sanitization
                $uniqueName = 'poster_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = UPLOAD_DIR . $uniqueName;

                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $posterFileName = $uniqueName;
                } else {
                    $errors[] = "Failed to save uploaded poster file.";
                }
            }
        }
    }

    // Insert into MySQL with Prepared Statement
    if (empty($errors) && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, category, event_date, event_time, venue, description, poster_image, max_seats, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $category, $event_date, $event_time, $venue, $description, $posterFileName, $max_seats, $status, $_SESSION['user_id']]);
            $eventId = $pdo->lastInsertId();

            record_audit_log($_SESSION['user_id'], 'EVENT_CREATED', "Admin created event #{$eventId} with poster ({$posterFileName})", $pdo);
            header("Location: events.php?msg=added");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database insert failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Event with Poster - Practical 12</title>
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
    <a href="admin_dashboard.php">Admin Console</a> &gt; <a href="events.php">Events</a> &gt; <span>Create Event</span>
  </div>

  <main style="max-width: 680px; margin: 30px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Practical 12: Poster Upload</span>
        <h2>Create Campus Event</h2>
        <p>Upload event banner with strict file type and size validation.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <!-- Multipart Form for File Upload -->
      <form method="POST" action="event_add.php" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label" for="title">Event Title *</label>
          <input type="text" id="title" name="title" class="form-control" placeholder="e.g. AI & Web Dev Bootcamp" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="category">Category *</label>
            <select id="category" name="category" class="form-control" required>
              <option value="Technical">Technical</option>
              <option value="Workshop">Workshop</option>
              <option value="Cultural">Cultural</option>
              <option value="Sports">Sports</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="status">Event Status *</label>
            <select id="status" name="status" class="form-control" required>
              <option value="Open">Open for Registration</option>
              <option value="Closed">Closed</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="event_date">Event Date *</label>
            <input type="date" id="event_date" name="event_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="event_time">Event Time</label>
            <input type="text" id="event_time" name="event_time" class="form-control" placeholder="10:00 AM - 02:00 PM">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="venue">Venue *</label>
            <input type="text" id="venue" name="venue" class="form-control" placeholder="Auditorium / Lab 3" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="max_seats">Maximum Capacity *</label>
            <input type="number" id="max_seats" name="max_seats" class="form-control" value="100" min="10" max="1000" required>
          </div>
        </div>

        <!-- File Upload Field (Practical 12) -->
        <div class="form-group">
          <label class="form-label" for="poster">
            <span>Event Poster Image *</span>
            <small style="color: var(--text-muted);">Max 2MB (JPG, PNG, WEBP)</small>
          </label>
          <input type="file" id="poster" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Event Description</label>
          <textarea id="description" name="description" class="form-control" rows="3" placeholder="Provide complete event details and guidelines..."></textarea>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 10px;">
          <button type="submit" class="btn btn-primary btn-block">Publish Event</button>
          <a href="events.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
