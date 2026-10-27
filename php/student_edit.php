<?php
/**
 * StudentHub Portal - Practical 11: Edit Student Record
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$pdo = get_db_connection();
$id = intval($_GET['id'] ?? 0);
$errors = [];

if (!$id || !$pdo) {
    header("Location: students.php");
    exit;
}

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['full_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $course = sanitize_input($_POST['course'] ?? '');
    $year = sanitize_input($_POST['year'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Active');

    if (empty($name) || strlen($name) < 3) {
        $errors[] = "Valid full name is required.";
    }
    if (empty($phone) || !preg_match("/^[6-9]\d{9}$/", $phone)) {
        $errors[] = "Valid 10-digit mobile is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, course = ?, year = ?, status = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$name, $phone, $course, $year, $status, $id]);

            record_audit_log($_SESSION['user_id'], 'STUDENT_UPDATED', "Admin updated student #{$id}", $pdo);
            header("Location: students.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}

// Fetch existing record
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: students.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Student #<?= $id ?> - Practical 11</title>
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
          <li><a href="students.php" class="active">Students CRUD</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="admin_dashboard.php">Admin Console</a> &gt; <a href="students.php">Students</a> &gt; <span>Edit Student #<?= $id ?></span>
  </div>

  <main style="max-width: 650px; margin: 30px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Practical 11: UPDATE Operation</span>
        <h2>Edit Student Record #<?= $id ?></h2>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="student_edit.php?id=<?= $id ?>">
        <div class="form-group">
          <label class="form-label" for="full_name">Full Name *</label>
          <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="email">Email (Immutable)</label>
            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" readonly style="background: var(--bg-subtle);">
          </div>
          <div class="form-group">
            <label class="form-label" for="phone">Mobile Number *</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="course">Department</label>
            <select id="course" name="course" class="form-control">
              <option value="B.Tech IT" <?= $student['course'] === 'B.Tech IT' ? 'selected' : '' ?>>B.Tech IT</option>
              <option value="B.Tech CE" <?= $student['course'] === 'B.Tech CE' ? 'selected' : '' ?>>B.Tech CE</option>
              <option value="B.Tech AIML" <?= $student['course'] === 'B.Tech AIML' ? 'selected' : '' ?>>B.Tech AIML</option>
              <option value="MCA" <?= $student['course'] === 'MCA' ? 'selected' : '' ?>>MCA</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="year">Year</label>
            <select id="year" name="year" class="form-control">
              <option value="1st Year" <?= $student['year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
              <option value="2nd Year" <?= $student['year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
              <option value="3rd Year" <?= $student['year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
              <option value="4th Year" <?= $student['year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Account Status</label>
          <select id="status" name="status" class="form-control">
            <option value="Active" <?= $student['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $student['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 10px;">
          <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
          <a href="students.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
