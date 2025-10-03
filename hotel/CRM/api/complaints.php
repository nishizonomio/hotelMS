<?php
// complaints.php - Flexible version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? null;

if ($method === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

require_once __DIR__ . '/database.php';
$db = new Database();
$conn = $db->getConnection();

function respond($data, $code = 200) { 
    http_response_code($code); 
    echo json_encode($data); 
    exit(); 
}

// Dynamic table structure detection
function getTableColumns($conn, $table) {
    try {
        $stmt = $conn->query("DESCRIBE $table");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

// Get table structures
$complaintsColumns = getTableColumns($conn, 'complaints');
$guestColumns = getTableColumns($conn, 'guests');

// Detect field variations
$messageField = in_array('comment', $complaintsColumns) ? 'comment' : 'message';
$guestPrimaryKey = in_array('guest_id', $guestColumns) ? 'guest_id' : 'id';

// Guest name field detection
$guestNameQuery = 'name';
if (in_array('first_name', $guestColumns) && in_array('last_name', $guestColumns)) {
    $guestNameQuery = "CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,''))";
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? null;
    if ($method === null) {
        respond(['success' => false, 'error' => 'Invalid execution context. Must be run via HTTP request.'], 400);
    }

    switch ($method) {
        case 'GET':
            // Stats mode
            if (isset($_GET['stats']) && $_GET['stats'] == '1') {
                $stmt = $conn->prepare("
                    SELECT 
                      COUNT(*) AS total_complaints,
                      SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_complaints,
                      SUM(CASE WHEN status IN ('pending','in-progress') THEN 1 ELSE 0 END) AS active_complaints,
                      SUM(CASE WHEN type = 'suggestion' THEN 1 ELSE 0 END) AS total_suggestions,
                      SUM(CASE WHEN type = 'compliment' THEN 1 ELSE 0 END) AS total_compliments,
                      ROUND(
                        CASE 
                          WHEN COUNT(*) > 0 THEN (SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) / COUNT(*)) * 100
                          ELSE 0 
                        END,
                        1
                      ) AS resolution_rate
                    FROM complaints
                ");
                $stmt->execute();
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);

                respond(['success' => true, 'data' => $summary]);
            }

            // Normal listing
            $query = "SELECT * FROM complaints WHERE 1=1";
            $params = [];

            if (isset($_GET['status']) && $_GET['status'] !== 'all') {
                $query .= " AND status = :status";
                $params[':status'] = $_GET['status'];
            }

            if (isset($_GET['type']) && $_GET['type'] !== 'all') {
                $query .= " AND type = :type";
                $params[':type'] = $_GET['type'];
            }

            if (isset($_GET['search'])) {
                $query .= " AND (guest_name LIKE :search OR $messageField LIKE :search)";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            $query .= " ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize data for frontend compatibility
            foreach ($items as &$item) {
                // Handle message/comment field mapping
                if (in_array('comment', $complaintsColumns) && !isset($item['message'])) {
                    $item['message'] = $item['comment'];
                } elseif (in_array('message', $complaintsColumns) && !isset($item['comment'])) {
                    $item['comment'] = $item['message'];
                }

                // Set defaults
                $item['type'] = $item['type'] ?? 'complaint';
                $item['status'] = $item['status'] ?? 'pending';
            }

            respond(['success' => true, 'data' => $items]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            if ((empty($input['guest_id']) && empty($input['guest_name'])) || empty($input['comment'])) {
                respond(['success' => false, 'error' => 'Missing required fields: guest_id/guest_name and comment'], 400);
            }

            // Resolve guest_name if guest_id is provided
            $guestName = $input['guest_name'] ?? '';
            $guest_id = null;

            if (!empty($input['guest_id'])) {
                $stmt = $conn->prepare("SELECT $guestNameQuery as name FROM guests WHERE $guestPrimaryKey = :guest_id");
                $stmt->execute([':guest_id' => $input['guest_id']]);
                $guest = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($guest) {
                    $guestName = $guest['name'];
                    $guest_id = $input['guest_id'];
                }
            }

            $type = $input['type'] ?? 'complaint';
            $validTypes = ['complaint', 'suggestion', 'compliment'];
            if (!in_array($type, $validTypes)) $type = 'complaint';

            $rating = isset($input['rating']) ? intval($input['rating']) : null;
            if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

            // Prepare insert fields
            $fields = ['guest_name', 'status', 'type', 'created_at'];
            $values = [':guest_name', ':status', ':type', 'NOW()'];
            $params = [
                ':guest_name' => $guestName,
                ':status' => $input['status'] ?? 'pending',
                ':type' => $type
            ];

            // Add guest_id if column exists and value provided
            if (in_array('guest_id', $complaintsColumns) && $guest_id) {
                $fields[] = 'guest_id';
                $values[] = ':guest_id';
                $params[':guest_id'] = $guest_id;
            }

            // Add comment/message field
            $fields[] = $messageField;
            $values[] = ':comment';
            $params[':comment'] = trim($input['comment']);

            // Add rating if provided
            if ($rating !== null && in_array('rating', $complaintsColumns)) {
                $fields[] = 'rating';
                $values[] = ':rating';
                $params[':rating'] = $rating;
            }

            // Add reply if provided
            if (!empty($input['reply']) && in_array('reply', $complaintsColumns)) {
                $fields[] = 'reply';
                $values[] = ':reply';
                $params[':reply'] = $input['reply'];
            }

            $sql = "INSERT INTO complaints (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $id = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM complaints WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $new = $stmt->fetch(PDO::FETCH_ASSOC);

            // Normalize for frontend
            if (in_array('comment', $complaintsColumns) && !isset($new['message'])) {
                $new['message'] = $new['comment'];
            }

            respond(['success' => true, 'data' => $new], 201);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing complaint ID'], 400);

            $updateFields = [];
            $params = [':id' => $input['id']];

            if (isset($input['guest_name'])) {
                $updateFields[] = "guest_name = :guest_name";
                $params[':guest_name'] = trim($input['guest_name']);
            }

            if (isset($input['comment'])) {
                $updateFields[] = "$messageField = :comment";
                $params[':comment'] = trim($input['comment']);
            }

            if (isset($input['status'])) {
                $updateFields[] = "status = :status";
                $params[':status'] = $input['status'];
            }

            if (isset($input['reply']) && in_array('reply', $complaintsColumns)) {
                $updateFields[] = "reply = :reply";
                $params[':reply'] = trim($input['reply']);
            }

            if (isset($input['type'])) {
                $updateFields[] = "type = :type";
                $params[':type'] = $input['type'];
            }

            if (isset($input['rating']) && in_array('rating', $complaintsColumns)) {
                $updateFields[] = "rating = :rating";
                $params[':rating'] = intval($input['rating']);
            }

            if (empty($updateFields)) {
                respond(['success' => false, 'error' => 'No fields to update'], 400);
            }

            $sql = "UPDATE complaints SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            respond(['success' => true, 'message' => 'Complaint updated successfully']);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing complaint ID'], 400);

            $stmt = $conn->prepare("DELETE FROM complaints WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);

            if ($stmt->rowCount() > 0) {
                respond(['success' => true, 'message' => 'Complaint deleted successfully']);
            } else {
                respond(['success' => false, 'error' => 'Complaint not found'], 404);
            }
            break;

        default:
            respond(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()], 500);
}
?>