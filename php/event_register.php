<?php
/**
 * StudentHub Portal - Event Registration Action
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_login();

$userId = $_SESSION['user_id'];
$eventId = intval($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
$pdo = get_db_connection();

if (!$eventId || !$pdo) {
    header("Location: dashboard.php");
    exit;
}

try {
    // Check if already registered
    $chk = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
    $chk->execute([$userId, $eventId]);
    if ($chk->fetch()) {
        header("Location: dashboard.php?msg=already_registered");
        exit;
    }

    // Insert registration
    $ins = $pdo->prepare("INSERT INTO registrations (user_id, event_id, status) VALUES (?, ?, 'Confirmed')");
    $ins->execute([$userId, $eventId]);

    record_audit_log($userId, 'EVENT_REGISTER', "Student enrolled in event #{$eventId}", $pdo);
    header("Location: dashboard.php?msg=reg_success");
    exit;
} catch (PDOException $e) {
    error_log("Event register error: " . $e->getMessage());
    header("Location: dashboard.php?msg=error");
    exit;
}
?>
