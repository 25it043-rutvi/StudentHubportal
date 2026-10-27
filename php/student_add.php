<?php
/**
 * StudentHub Portal - Practical 11: Add Student Record
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$errors = [];
$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $course = sanitize_input($_POST['course'] ?? '');
    $year = sanitize_input($_POST['year'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? 'Other');
    $password = $_POST['password'] ?? 'Student@123';

    if (empty($name) || strlen($name) < 3) {
        $errors[] = "Valid full name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid student email address is required.";
    }
    if (empty($phone) || !preg_match("/^[6-9]\d{9}$/", $phone)) {
        $errors[] = "Valid 10-digit phone number is required.";
    }

    if (empty($errors) && $pdo) {
        try {
            // Duplicate Check
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = "A student with this email address already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, course, year, gender, status) VALUES (?, ?, ?, ?, 'student', ?, ?, ?, 'Active')");
                $stmt->execute([$name, $email, $phone, $hash, $course, $year, $gender]);
                $newId = $pdo->lastInsertId();

                record_audit_log($_SESSION['user_id'], 'STUDENT_CREATED', "Admin created student #{$newId} ({$email})", $pdo);
                header("Location: students.php?msg=added");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Student - Practical 11</title>
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
          <li><a href="events.php">Events CRUD</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="admin_dashboard.php">Admin Console</a> &gt; <a href="students.php">Students</a> &gt; <span>Add Student</span>
  </div>

  <main style="max-width: 650px; margin: 30px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Practical 11: INSERT Operation</span>
        <h2>Add New Student Record</h2>
        <p>Register a student account directly using administrative prepared statements.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="student_add.php">
        <div class="form-group">
          <label class="form-label" for="full_name">Full Name *</label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Priyansh Shah" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="email">Student Email *</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="student@charusat.edu" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="phone">Mobile Number *</label>
            <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit mobile" maxlength="10" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="course">Department *</label>
            <select id="course" name="course" class="form-control" required>
              <option value="B.Tech IT">B.Tech IT</option>
              <option value="B.Tech CE">B.Tech CE</option>
              <option value="B.Tech AIML">B.Tech AIML</option>
              <option value="MCA">MCA</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="year">Year *</label>
            <select id="year" name="year" class="form-control" required>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year" selected>3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="gender">Gender</label>
            <select id="gender" name="gender" class="form-control">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="password">Initial Password</label>
            <input type="password" id="password" name="password" class="form-control" value="Student@123" required>
          </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 10px;">
          <button type="submit" class="btn btn-primary btn-block">Create Student Record</button>
          <a href="students.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
