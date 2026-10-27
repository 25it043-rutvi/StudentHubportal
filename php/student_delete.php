<?php
/**
 * StudentHub Portal - Practical 11: Delete Student Record
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$pdo = get_db_connection();
$id = intval($_GET['id'] ?? 0);

if ($id && $pdo) {
    try {
        // Fetch details for audit log
        $check = $pdo->prepare("SELECT email FROM users WHERE id = ? AND role = 'student'");
        $check->execute([$id]);
        $student = $check->fetch();

        if ($student) {
            $del = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $del->execute([$id]);

            record_audit_log($_SESSION['user_id'], 'STUDENT_DELETED', "Admin deleted student #{$id} ({$student['email']})", $pdo);
            header("Location: students.php?msg=deleted");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Student delete error: " . $e->getMessage());
    }
}

header("Location: students.php");
exit;
?>
