<?php
// feedback.php - Flexible version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
$feedbackColumns = getTableColumns($conn, 'feedback');
$guestColumns = getTableColumns($conn, 'guests');

// Detect field variations
$messageField = in_array('message', $feedbackColumns) ? 'message' : 'comment';
$commentField = in_array('comment', $feedbackColumns) ? 'comment' : 'message';
$guestPrimaryKey = in_array('guest_id', $guestColumns) ? 'guest_id' : 'id';
$guestNameField = 'name';
if (in_array('first_name', $guestColumns) && in_array('last_name', $guestColumns)) {
    $guestNameField = "CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,''))";
}

try {
    switch ($method) {
        case 'GET':
            $query = "SELECT * FROM feedback WHERE 1=1";
            $params = [];

            if (isset($_GET['type']) && $_GET['type'] !== 'all') {
                $query .= " AND type = :type";
                $params[':type'] = $_GET['type'];
            }

            if (isset($_GET['status']) && $_GET['status'] !== 'all') {
                $query .= " AND status = :status";
                $params[':status'] = $_GET['status'];
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
                if (isset($item['message']) && !isset($item['comment'])) {
                    $item['comment'] = $item['message'];
                } elseif (isset($item['comment']) && !isset($item['message'])) {
                    $item['message'] = $item['comment'];
                }

                // Set defaults
                $item['type'] = $item['type'] ?? 'review';
                $item['status'] = $item['status'] ?? 'pending';
            }

            respond(['success' => true, 'data' => $items]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            if (empty($input['guest_name']) || empty($input['comment'])) {
                respond(['success' => false, 'error' => 'Missing required fields: guest_name and comment'], 400);
            }

            $type = $input['type'] ?? 'review';
            $validTypes = ['review', 'suggestion', 'compliment', 'service_feedback'];
            if (!in_array($type, $validTypes)) $type = 'review';

            $rating = isset($input['rating']) ? intval($input['rating']) : null;
            if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

            // Validate guest_id if provided
            $guest_id = null;
            if (!empty($input['guest_id'])) {
                $stmt = $conn->prepare("SELECT $guestPrimaryKey FROM guests WHERE $guestPrimaryKey = :guest_id");
                $stmt->execute([':guest_id' => $input['guest_id']]);
                if ($stmt->fetch()) {
                    $guest_id = $input['guest_id'];
                }
            }

            // Prepare insert fields based on table structure
            $fields = ['guest_name', 'type', 'status', 'created_at'];
            $values = [':guest_name', ':type', ':status', 'NOW()'];
            $params = [
                ':guest_name' => trim($input['guest_name']),
                ':type' => $type,
                ':status' => $input['status'] ?? 'pending'
            ];

            // Add guest_id if column exists and value provided
            if (in_array('guest_id', $feedbackColumns) && $guest_id) {
                $fields[] = 'guest_id';
                $values[] = ':guest_id';
                $params[':guest_id'] = $guest_id;
            }

            // Add message/comment field
            $fields[] = $messageField;
            $values[] = ':message';
            $params[':message'] = trim($input['comment']);

            // Add rating if provided
            if ($rating !== null) {
                $fields[] = 'rating';
                $values[] = ':rating';
                $params[':rating'] = $rating;
            }

            $sql = "INSERT INTO feedback (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $id = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM feedback WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $new = $stmt->fetch(PDO::FETCH_ASSOC);

            // Normalize for frontend
            if (isset($new['message']) && !isset($new['comment'])) {
                $new['comment'] = $new['message'];
            }

            respond(['success' => true, 'data' => $new], 201);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing feedback ID'], 400);

            $updateFields = [];
            $params = [':id' => $input['id']];

            if (isset($input['guest_name'])) {
                $updateFields[] = "guest_name = :guest_name";
                $params[':guest_name'] = trim($input['guest_name']);
            }

            if (isset($input['comment'])) {
                $updateFields[] = "$messageField = :message";
                $params[':message'] = trim($input['comment']);
            }

            if (isset($input['rating'])) {
                $updateFields[] = "rating = :rating";
                $params[':rating'] = intval($input['rating']);
            }

            if (isset($input['status'])) {
                $updateFields[] = "status = :status";
                $params[':status'] = $input['status'];
            }

            if (isset($input['reply'])) {
                $updateFields[] = "reply = :reply";
                $params[':reply'] = trim($input['reply']);
                
                // Auto-approve when replying
                if (!isset($input['status'])) {
                    $updateFields[] = "status = 'approved'";
                }
            }

            if (isset($input['type'])) {
                $updateFields[] = "type = :type";
                $params[':type'] = $input['type'];
            }

            if (empty($updateFields)) {
                respond(['success' => false, 'error' => 'No fields to update'], 400);
            }

            $sql = "UPDATE feedback SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            respond(['success' => true, 'message' => 'Feedback updated successfully']);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing feedback ID'], 400);

            $stmt = $conn->prepare("DELETE FROM feedback WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);

            if ($stmt->rowCount() > 0) {
                respond(['success' => true, 'message' => 'Feedback deleted successfully']);
            } else {
                respond(['success' => false, 'error' => 'Feedback not found'], 404);
            }
            break;

        default:
            respond(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()], 500);
}
?>