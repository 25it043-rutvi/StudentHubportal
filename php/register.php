<?php
/**
 * StudentHub Portal - Practical 9: Secure User Registration
 * Features: MySQL prepared statements, duplicate email/username check,
 * and bcrypt password hashing via password_hash().
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['fullname'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $course = sanitize_input($_POST['course'] ?? '');
    $year = sanitize_input($_POST['year'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? 'Other');

    // Backend Validation Checks
    if (empty($name) || !preg_match("/^[A-Za-z\s]{3,50}$/", $name)) {
        $errors[] = "Valid full name (alphabets only, min 3 chars) is required.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid student email address is required.";
    }

    if (empty($phone) || !preg_match("/^[6-9]\d{9}$/", $phone)) {
        $errors[] = "Valid 10-digit mobile number starting with 6-9 is required.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Password and confirm password do not match.";
    }

    if (empty($course)) {
        $errors[] = "Please select your department.";
    }

    // Database Insertion with Duplicate Check & Password Hashing
    if (empty($errors)) {
        $pdo = get_db_connection();

        if (!$pdo) {
            // If MySQL is offline, graceful message for evaluation
            $errors[] = "Database connection error. Please ensure MySQL is running in XAMPP.";
        } else {
            try {
                // 1. Duplicate Email Check (Prepared Statement)
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $errors[] = "An account with this email address already exists. Please login instead.";
                } else {
                    // 2. Password Hashing (Practical 9: password_hash())
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                    // 3. Secure Database Insert (Prepared Statement)
                    $insertStmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, phone, password_hash, role, course, year, gender, status)
                        VALUES (?, ?, ?, ?, 'student', ?, ?, ?, 'Active')
                    ");
                    $insertStmt->execute([$name, $email, $phone, $passwordHash, $course, $year, $gender]);
                    $newUserId = $pdo->lastInsertId();

                    // 4. Record Audit Log (Practical 14)
                    record_audit_log($newUserId, 'USER_REGISTERED', "Student registered: {$email}", $pdo);

                    $success = true;
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors[] = "Database operation failed: " . $e->getMessage();
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
  <title>Registration Result - Practical 9</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main style="max-width: 650px; margin: 60px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Practical 9: Secure Registration</span>
        <h2>Registration Status</h2>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <div>
            <h4>🎉 Student Account Created Successfully!</h4>
            <p>Your details have been inserted into MySQL with duplicate validation and bcrypt password hashing.</p>
          </div>
        </div>

        <div style="background: var(--bg-subtle); padding: 18px; border-radius: var(--radius-sm); margin-bottom: 24px;">
          <p><strong>Registered Name:</strong> <?= htmlspecialchars($name) ?></p>
          <p><strong>Student Email:</strong> <?= htmlspecialchars($email) ?></p>
          <p><strong>Department:</strong> <?= htmlspecialchars($course) ?> (<?= htmlspecialchars($year) ?>)</p>
          <p><strong>Security Note:</strong> Password securely hashed via <code>PASSWORD_BCRYPT</code>.</p>
        </div>

        <div style="display: flex; gap: 12px; justify-content: center;">
          <a href="login.php" class="btn btn-primary">Proceed to Login &rarr;</a>
          <a href="../pages/index.html" class="btn btn-secondary">Return Home</a>
        </div>
      <?php else: ?>
        <div class="alert alert-danger">
          <div>
            <h4>❌ Registration Could Not Be Completed</h4>
            <ul style="margin-top: 6px; padding-left: 20px;">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <div style="text-align: center; margin-top: 24px;">
          <a href="../pages/register.html" class="btn btn-primary">&larr; Return to Registration Form</a>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
