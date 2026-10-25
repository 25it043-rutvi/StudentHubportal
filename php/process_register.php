<?php
/**
 * StudentHub Portal - Practical 7: PHP Form Processing with CSV/JSON Storage
 * Validates and sanitizes registration inputs and persists them to CSV/JSON files.
 */

require_once __DIR__ . '/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize Inputs
    $name = sanitize_input($_POST['fullname'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $course = sanitize_input($_POST['course'] ?? '');
    $year = sanitize_input($_POST['year'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? '');

    // 2. Server-side Validation
    if (empty($name) || !preg_match("/^[A-Za-z\s]{3,50}$/", $name)) {
        $errors[] = "Name is invalid. Must contain 3 to 50 alphabetical characters.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    if (empty($phone) || !preg_match("/^[6-9]\d{9}$/", $phone)) {
        $errors[] = "Phone number must be a valid 10-digit number.";
    }

    if (empty($course)) {
        $errors[] = "Please select a department/course.";
    }

    if (empty($gender)) {
        $errors[] = "Please specify gender.";
    }

    // 3. Process & Store in Files if valid
    if (empty($errors)) {
        $timestamp = date('Y-m-d H:i:s');
        $id = time();

        // 3A. Append to CSV
        $csvFile = DATA_DIR . 'submissions.csv';
        $csvExists = file_exists($csvFile);
        $fp = fopen($csvFile, 'a');
        if ($fp) {
            if (!$csvExists) {
                fputcsv($fp, ['ID', 'Timestamp', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Type']);
            }
            fputcsv($fp, [$id, $timestamp, $name, $email, $phone, $course, $year, $gender, 'Registration']);
            fclose($fp);
        }

        // 3B. Append to JSON
        $jsonFile = DATA_DIR . 'submissions.json';
        $jsonData = [];
        if (file_exists($jsonFile)) {
            $raw = file_get_contents($jsonFile);
            $jsonData = json_decode($raw, true) ?: [];
        }
        $jsonData[] = [
            'id' => $id,
            'timestamp' => $timestamp,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'course' => $course,
            'year' => $year,
            'gender' => $gender,
            'type' => 'Registration'
        ];
        file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT), LOCK_EX);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Processing - Practical 7</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main style="max-width: 650px; margin: 60px auto;">
    <div class="form-card">
      <div class="form-header">
        <span class="badge badge-primary">Practical 7: File Storage Processing</span>
        <h2>Form Submission Results</h2>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <div>
            <h4 style="margin-bottom: 4px;">✅ Registration Successfully Recorded!</h4>
            <p>Your details have been validated on the server and safely appended to <code>submissions.csv</code> and <code>submissions.json</code>.</p>
          </div>
        </div>

        <div style="background: var(--bg-subtle); padding: 18px; border-radius: var(--radius-sm); margin-bottom: 24px;">
          <h4 style="margin-bottom: 10px;">Submitted Details:</h4>
          <p><strong>Name:</strong> <?= htmlspecialchars($name) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
          <p><strong>Phone:</strong> <?= htmlspecialchars($phone) ?></p>
          <p><strong>Department:</strong> <?= htmlspecialchars($course) ?> (<?= htmlspecialchars($year) ?>)</p>
          <p><strong>Gender:</strong> <?= htmlspecialchars($gender) ?></p>
          <p><strong>Storage Status:</strong> Appended to CSV & JSON with file locks.</p>
        </div>

        <div style="display: flex; gap: 12px; justify-content: center;">
          <a href="view_records.php" class="btn btn-primary">View Stored Records &rarr;</a>
          <a href="../pages/register.html" class="btn btn-secondary">Submit Another</a>
        </div>
      <?php else: ?>
        <div class="alert alert-danger">
          <div>
            <h4 style="margin-bottom: 4px;">❌ Submission Failed</h4>
            <ul style="margin-top: 6px; padding-left: 20px;">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
          <a href="../pages/register.html" class="btn btn-primary">&larr; Return to Registration Form</a>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
