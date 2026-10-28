<?php
/**
 * StudentHub Portal - Practical 13: Students JSON API Endpoint
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$pdo = get_db_connection();

if (!$pdo) {
    echo json_encode([
        ["id" => 1, "full_name" => "Rutvi Patel", "email" => "rutvi.patel@studenthub.edu", "course" => "B.Tech IT", "status" => "Active"],
        ["id" => 2, "full_name" => "Aarav Shah", "email" => "aarav.shah@studenthub.edu", "course" => "B.Tech CE", "status" => "Active"]
    ]);
    exit;
}

try {
    $search = $_GET['q'] ?? '';
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, course, year, status FROM users WHERE role = 'student' AND (full_name LIKE ? OR email LIKE ?) LIMIT 20");
        $like = "%{$search}%";
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query("SELECT id, full_name, email, phone, course, year, status FROM users WHERE role = 'student' LIMIT 20");
    }
    $students = $stmt->fetchAll();
    echo json_encode($students);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
