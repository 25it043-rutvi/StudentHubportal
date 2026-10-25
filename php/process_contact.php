<?php
/**
 * StudentHub Portal - Practical 7: Contact Form Processing
 */

require_once __DIR__ . '/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = sanitize_input($_POST['subject'] ?? 'General Inquiry');
    $message = sanitize_input($_POST['message'] ?? '');

    if (empty($name) || strlen($name) < 3) {
        $errors[] = "Please provide your full name (minimum 3 characters).";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($message) || strlen($message) < 5) {
        $errors[] = "Message is required (minimum 5 characters).";
    }

    if (empty($errors)) {
        $timestamp = date('Y-m-d H:i:s');
        $id = time();

        // Append to CSV
        $csvFile = DATA_DIR . 'submissions.csv';
        $fp = fopen($csvFile, 'a');
        if ($fp) {
            fputcsv($fp, [$id, $timestamp, $name, $email, 'N/A', $subject, 'N/A', 'N/A', 'Contact Message']);
            fclose($fp);
        }

        // Append to JSON
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
            'subject' => $subject,
            'message' => $message,
            'type' => 'Contact Inquiry'
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
  <title>Contact Processing - Practical 7</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main style="max-width: 600px; margin: 60px auto;">
    <div class="form-card">
      <?php if ($success): ?>
        <div class="alert alert-success">
          <div>
            <h4>✅ Inquiry Sent & Saved!</h4>
            <p>Your message has been processed on the server and stored in <code>submissions.csv</code> and <code>submissions.json</code>.</p>
          </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
          <a href="../pages/contact.html" class="btn btn-secondary">&larr; Back to Contact</a>
          <a href="view_records.php" class="btn btn-primary">View All Records &rarr;</a>
        </div>
      <?php else: ?>
        <div class="alert alert-danger">
          <div>
            <h4>❌ Inquiry Failed</h4>
            <ul>
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
          <a href="../pages/contact.html" class="btn btn-primary">&larr; Return to Contact Page</a>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
