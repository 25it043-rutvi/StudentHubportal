<?php
/**
 * StudentHub Portal - Practical 13: PHP JSON API for Events CRUD
 * Supports GET, POST, PUT, and DELETE operations returning JSON.
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

// Helper to read raw JSON input
function get_json_input() {
    $raw = file_get_contents("php://input");
    return json_decode($raw, true) ?: [];
}

$input = get_json_input();
// Support action override via POST (e.g. action=create, action=update, action=delete)
$action = $input['action'] ?? $_GET['action'] ?? '';

// If DB is offline, provide JSON file fallback
if (!$pdo) {
    handle_json_file_fallback($method, $action, $input);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([intval($_GET['id'])]);
                $event = $stmt->fetch();
                if ($event) {
                    echo json_encode(["status" => "success", "data" => $event]);
                } else {
                    http_response_code(404);
                    echo json_encode(["status" => "error", "message" => "Event not found"]);
                }
            } else {
                $category = $_GET['category'] ?? '';
                if (!empty($category) && $category !== 'All') {
                    $stmt = $pdo->prepare("SELECT * FROM events WHERE category = ? ORDER BY event_date ASC");
                    $stmt->execute([$category]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
                }
                $events = $stmt->fetchAll();
                echo json_encode($events);
            }
            break;

        case 'POST':
            if ($action === 'delete' || isset($input['id']) && $action === 'delete') {
                // Delete Event via POST action
                $id = intval($input['id']);
                $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(["status" => "success", "message" => "Event #{$id} deleted successfully."]);
            } elseif ($action === 'update' || (isset($input['id']) && !empty($input['id']))) {
                // Update Event
                $id = intval($input['id']);
                $stmt = $pdo->prepare("
                    UPDATE events SET title = ?, category = ?, event_date = ?, venue = ?, max_seats = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $input['title'] ?? 'Updated Event',
                    $input['category'] ?? 'Technical',
                    $input['date'] ?? date('Y-m-d'),
                    $input['venue'] ?? 'Campus',
                    intval($input['seats'] ?? 100),
                    $input['description'] ?? '',
                    $id
                ]);
                echo json_encode(["status" => "success", "message" => "Event #{$id} updated successfully."]);
            } else {
                // Create New Event
                $stmt = $pdo->prepare("
                    INSERT INTO events (title, category, event_date, event_time, venue, max_seats, description, status)
                    VALUES (?, ?, ?, '10:00 AM', ?, ?, ?, 'Open')
                ");
                $stmt->execute([
                    $input['title'] ?? 'New Event',
                    $input['category'] ?? 'Technical',
                    $input['date'] ?? date('Y-m-d'),
                    $input['venue'] ?? 'Auditorium',
                    intval($input['seats'] ?? 100),
                    $input['description'] ?? ''
                ]);
                $newId = $pdo->lastInsertId();
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Event created", "id" => $newId]);
            }
            break;

        case 'PUT':
            $id = intval($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Event ID is required for update"]);
                exit;
            }
            $stmt = $pdo->prepare("
                UPDATE events SET title = ?, category = ?, event_date = ?, venue = ?, max_seats = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $input['title'] ?? 'Updated Event',
                $input['category'] ?? 'Technical',
                $input['date'] ?? date('Y-m-d'),
                $input['venue'] ?? 'Campus',
                intval($input['seats'] ?? 100),
                $input['description'] ?? '',
                $id
            ]);
            echo json_encode(["status" => "success", "message" => "Event #{$id} updated."]);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Event ID is required for delete"]);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["status" => "success", "message" => "Event #{$id} deleted."]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["status" => "error", "message" => "Method not allowed"]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

/**
 * Fallback handler using data/events.json when MySQL is offline
 */
function handle_json_file_fallback($method, $action, $input) {
    $file = DATA_DIR . 'events.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    if ($method === 'GET') {
        echo json_encode($data);
    } elseif ($method === 'POST') {
        if ($action === 'delete') {
            $id = intval($input['id']);
            $data = array_values(array_filter($data, fn($x) => $x['id'] != $id));
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            echo json_encode(["status" => "success", "message" => "Event #{$id} deleted."]);
        } elseif ($action === 'update') {
            $id = intval($input['id']);
            foreach ($data as &$it) {
                if ($it['id'] == $id) {
                    $it['title'] = $input['title'] ?? $it['title'];
                    $it['category'] = $input['category'] ?? $it['category'];
                    $it['date'] = $input['date'] ?? $it['date'];
                    $it['venue'] = $input['venue'] ?? $it['venue'];
                    $it['seats'] = intval($input['seats'] ?? $it['seats']);
                    $it['description'] = $input['description'] ?? $it['description'];
                }
            }
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            echo json_encode(["status" => "success", "message" => "Event #{$id} updated."]);
        } else {
            $newId = count($data) > 0 ? max(array_column($data, 'id')) + 1 : 1;
            $newItem = [
                "id" => $newId,
                "title" => $input['title'] ?? 'New Event',
                "category" => $input['category'] ?? 'Technical',
                "date" => $input['date'] ?? date('Y-m-d'),
                "time" => "10:00 AM",
                "venue" => $input['venue'] ?? 'Campus Auditorium',
                "description" => $input['description'] ?? '',
                "seats" => intval($input['seats'] ?? 100),
                "registered" => 0,
                "status" => "Open"
            ];
            $data[] = $newItem;
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Event created", "id" => $newId]);
        }
    }
}
?>
