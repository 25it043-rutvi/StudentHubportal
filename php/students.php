<?php
/**
 * StudentHub Portal - Practical 11: Student Management CRUD with Search & Filter
 * Features: SELECT, search by name/email/course, filter by status,
 * confirmation modal for delete, CSV export extension.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$pdo = get_db_connection();
$search = trim($_GET['search'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$msg = $_GET['msg'] ?? '';

// Handle CSV Export (Practical 11 Intermediate Extension)
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $pdo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Status', 'Registered At']);

    $exportStmt = $pdo->query("SELECT id, full_name, email, phone, course, year, gender, status, created_at FROM users WHERE role = 'student' ORDER BY id ASC");
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Build Dynamic Filter Query using Prepared Statements
$query = "SELECT * FROM users WHERE role = 'student'";
$params = [];

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($courseFilter) && $courseFilter !== 'All') {
    $query .= " AND course = ?";
    $params[] = $courseFilter;
}

if (!empty($statusFilter) && $statusFilter !== 'All') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY id DESC";

$students = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Student query error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students - Practical 11</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <!-- Admin Header -->
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
          <li><a href="../pages/ajax-events.html">AJAX Live</a></li>
          <li><a href="view_records.php">Submissions</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <span style="font-size: 0.9rem; font-weight: 600;">🛡️ <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <div class="breadcrumbs">
    <a href="admin_dashboard.php">Admin Console</a> &gt; <span>Student Management (Practical 11)</span>
  </div>

  <main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
      <div>
        <span class="badge badge-primary">Practical 11: CRUD Operations</span>
        <h1 style="margin-top: 4px;">Student Records Management</h1>
        <p style="color: var(--text-muted);">Manage student profiles, search by multiple criteria, update records, and export data.</p>
      </div>

      <div style="display: flex; gap: 10px;">
        <a href="students.php?export=csv" class="btn btn-secondary btn-sm">📥 Export to CSV</a>
        <a href="student_add.php" class="btn btn-primary btn-sm">+ Add New Student</a>
      </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg === 'added'): ?>
      <div class="alert alert-success">✅ Student record added successfully!</div>
    <?php elseif ($msg === 'updated'): ?>
      <div class="alert alert-success">✅ Student details updated successfully!</div>
    <?php elseif ($msg === 'deleted'): ?>
      <div class="alert alert-success">✅ Student record removed from system.</div>
    <?php elseif ($msg === 'status_toggled'): ?>
      <div class="alert alert-info">ℹ️ Student active status updated.</div>
    <?php endif; ?>

    <!-- Search & Filter Bar (Practical 11) -->
    <form method="GET" action="students.php" class="filter-bar">
      <div class="filter-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="search-input" placeholder="🔍 Search name, email, phone...">
        
        <select name="course" class="search-input" style="min-width: 170px;">
          <option value="All">All Departments</option>
          <option value="B.Tech IT" <?= $courseFilter === 'B.Tech IT' ? 'selected' : '' ?>>B.Tech IT</option>
          <option value="B.Tech CE" <?= $courseFilter === 'B.Tech CE' ? 'selected' : '' ?>>B.Tech CE</option>
          <option value="B.Tech AIML" <?= $courseFilter === 'B.Tech AIML' ? 'selected' : '' ?>>B.Tech AIML</option>
          <option value="MCA" <?= $courseFilter === 'MCA' ? 'selected' : '' ?>>MCA</option>
        </select>

        <select name="status" class="search-input" style="min-width: 140px;">
          <option value="All">All Statuses</option>
          <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active Only</option>
          <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive Only</option>
        </select>
      </div>

      <div class="filter-group">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="students.php" class="btn btn-secondary btn-sm">Reset</a>
      </div>
    </form>

    <!-- Students Table -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3>Student List (<?= count($students) ?> Records)</h3>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Student Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Department</th>
              <th>Year</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
                  No student records match your query. <a href="students.php" style="color: var(--primary);">Clear filters</a>.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $stu): ?>
                <tr>
                  <td><strong>#<?= $stu['id'] ?></strong></td>
                  <td><?= htmlspecialchars($stu['full_name']) ?></td>
                  <td><?= htmlspecialchars($stu['email']) ?></td>
                  <td><?= htmlspecialchars($stu['phone']) ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($stu['course']) ?></span></td>
                  <td><?= htmlspecialchars($stu['year']) ?></td>
                  <td>
                    <span class="badge <?= $stu['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>">
                      <?= htmlspecialchars($stu['status']) ?>
                    </span>
                  </td>
                  <td>
                    <a href="student_edit.php?id=<?= $stu['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    <!-- Practical 11 Requirement: Delete confirmed before execution -->
                    <a href="student_delete.php?id=<?= $stu['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete student #<?= $stu['id'] ?> (<?= htmlspecialchars(addslashes($stu['full_name'])) ?>)? This cannot be undone.');">
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
