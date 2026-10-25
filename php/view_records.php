<?php
/**
 * StudentHub Portal - Practical 7 Intermediate Extension
 * Web page to display and inspect stored CSV & JSON records.
 */

require_once __DIR__ . '/config.php';

// 1. Read JSON Records
$jsonFile = DATA_DIR . 'submissions.json';
$jsonRecords = [];
if (file_exists($jsonFile)) {
    $raw = file_get_contents($jsonFile);
    $jsonRecords = json_decode($raw, true) ?: [];
}

// 2. Read CSV Records
$csvFile = DATA_DIR . 'submissions.csv';
$csvRecords = [];
if (file_exists($csvFile)) {
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            if ($header && count($header) === count($data)) {
                $csvRecords[] = array_combine($header, $data);
            } else {
                $csvRecords[] = $data;
            }
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stored Form Records - Practical 7</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <!-- Header -->
  <header>
    <div class="header-container">
      <a href="../pages/index.html" class="brand-logo">
        <span style="font-size: 1.8rem;">🎓</span>
        <span>Student<span class="brand-badge">Hub</span></span>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="../pages/index.html">Home</a></li>
          <li><a href="../pages/register.html">Registration</a></li>
          <li><a href="view_records.php" class="active">Stored Records (P7)</a></li>
          <li><a href="admin_dashboard.php">Admin DB (P14)</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <a href="../pages/register.html" class="btn btn-primary btn-sm">+ New Submission</a>
      </div>
    </div>
  </header>

  <main style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <section class="section-title">
      <span class="badge badge-primary">Practical 7: File-Based Records Viewer</span>
      <h1>Stored Form Submissions</h1>
      <p>Server-side persisted data from <code>data/submissions.csv</code> and <code>data/submissions.json</code>.</p>
    </section>

    <!-- JSON Storage Table -->
    <div class="card" style="margin-bottom: 30px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3>📄 JSON Records (<code>data/submissions.json</code>)</h3>
        <span class="badge badge-success"><?= count($jsonRecords) ?> Records Found</span>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Timestamp</th>
              <th>Name</th>
              <th>Email</th>
              <th>Department / Course</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jsonRecords)): ?>
              <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No records in JSON file yet.</td></tr>
            <?php else: ?>
              <?php foreach (array_reverse($jsonRecords) as $row): ?>
                <tr>
                  <td><strong>#<?= htmlspecialchars($row['id'] ?? '') ?></strong></td>
                  <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['course'] ?? ($row['subject'] ?? 'N/A')) ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($row['type'] ?? 'Submission') ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CSV Storage Table -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3>📊 CSV Records (<code>data/submissions.csv</code>)</h3>
        <span class="badge badge-info"><?= count($csvRecords) ?> CSV Rows</span>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Timestamp</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Course / Subject</th>
              <th>Gender</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($csvRecords)): ?>
              <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No rows in CSV file yet.</td></tr>
            <?php else: ?>
              <?php foreach (array_reverse($csvRecords) as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['ID'] ?? $row[0] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Timestamp'] ?? $row[1] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($row['Name'] ?? $row[2] ?? '') ?></strong></td>
                  <td><?= htmlspecialchars($row['Email'] ?? $row[3] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Phone'] ?? $row[4] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Course'] ?? $row[5] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Gender'] ?? $row[7] ?? '') ?></td>
                  <td><span class="badge badge-success"><?= htmlspecialchars($row['Type'] ?? $row[8] ?? 'Record') ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>
