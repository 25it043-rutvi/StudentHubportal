<?php
/**
 * StudentHub Portal - Practical 12: Delete Event & Unlink Poster
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_check.php';

require_role('admin');

$pdo = get_db_connection();
$id = intval($_GET['id'] ?? 0);

if ($id && $pdo) {
    try {
        $check = $pdo->prepare("SELECT title, poster_image FROM events WHERE id = ?");
        $check->execute([$id]);
        $ev = $check->fetch();

        if ($ev) {
            // Delete record
            $del = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $del->execute([$id]);

            // Unlink poster file if not default
            if (!empty($ev['poster_image']) && $ev['poster_image'] !== 'default_event.jpg') {
                $filePath = UPLOAD_DIR . $ev['poster_image'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            record_audit_log($_SESSION['user_id'], 'EVENT_DELETED', "Admin deleted event #{$id} ({$ev['title']})", $pdo);
            header("Location: events.php?msg=deleted");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Event delete error: " . $e->getMessage());
    }
}

header("Location: events.php");
exit;
?>
