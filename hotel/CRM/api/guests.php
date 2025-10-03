<?php
// guests.php - Flexible version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? null;

if ($method === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid execution context. Must be called via HTTP request.']);
    exit();
}

if ($method === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

require_once __DIR__ . '/../bootstrap.php';

use CRM\Lib\Database as CRMDatabase;
use CRM\Lib\GuestRepository;
use CRM\Lib\GuestService;

$crmDb = new CRMDatabase();
$conn = $crmDb->getConnection();
// instantiate repository and service
$guestRepo = new GuestRepository($conn);
$guestService = new GuestService($guestRepo);

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

// Check table structure dynamically
function getTableColumns($conn, $table) {
    try {
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $columns;
    } catch (Exception $e) {
        return [];
    }
}

// Get guest table structure
$guestColumns = getTableColumns($conn, 'guests');
$hasFirstLastName = in_array('first_name', $guestColumns) && in_array('last_name', $guestColumns);
$hasFullName = in_array('name', $guestColumns);
$primaryKey = in_array('guest_id', $guestColumns) ? 'guest_id' : 'id';

// Phone field detection
$phoneField = 'phone';
if (in_array('first_phone', $guestColumns)) $phoneField = 'first_phone';
elseif (in_array('phone_number', $guestColumns)) $phoneField = 'phone_number';

try {
    switch ($method) {
        case 'GET':
            $search = $_GET['search'] ?? '';
            $query = "SELECT * FROM guests";
            $params = [];

            if (!empty($search)) {
                if ($hasFirstLastName && $hasFullName) {
                    // Both formats exist
                    $query .= " WHERE (CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,'')) LIKE :search 
                               OR name LIKE :search OR email LIKE :search)";
                } elseif ($hasFirstLastName) {
                    // Only first_name, last_name
                    $query .= " WHERE (CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,'')) LIKE :search 
                               OR email LIKE :search)";
                } else {
                    // Only name field
                    $query .= " WHERE (name LIKE :search OR email LIKE :search)";
                }
                $params[':search'] = "%$search%";
            }

            $query .= " ORDER BY created_at DESC";
            $search = $_GET['search'] ?? '';
            $guests = $guestService->listGuests($search);
            respond(['success' => true, 'data' => $guests]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            // Validate required fields
            if (empty($input['name']) || empty($input['email']) || empty($input['phone']) || empty($input['loyalty_tier'])) {
                respond(['success' => false, 'error' => 'Missing required fields: name, email, phone, loyalty_tier'], 400);
            }

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                respond(['success' => false, 'error' => 'Invalid email address'], 400);
            }

            // Check for existing email
            $checkStmt = $conn->prepare("SELECT $primaryKey FROM guests WHERE email = :email");
            $checkStmt->execute([':email' => $input['email']]);
            if ($checkStmt->fetch()) {
                respond(['success' => false, 'error' => 'Guest with this email already exists'], 409);
            }

            // Prepare data based on table structure
            $fields = [];
            $values = [];
            $params = [];

            if ($hasFirstLastName) {
                // Split name into first and last
                $fullName = trim($input['name']);
                $nameParts = explode(' ', $fullName, 2);
                $fields[] = 'first_name';
                $fields[] = 'last_name';
                $values[] = ':first_name';
                $values[] = ':last_name';
                $params[':first_name'] = trim($nameParts[0] ?? '');
                $params[':last_name'] = trim($nameParts[1] ?? '');
            }

            if ($hasFullName) {
                $fields[] = 'name';
                $values[] = ':name';
                $params[':name'] = trim($input['name']);
            }

            // Common fields
            $fields[] = 'email';
            $fields[] = $phoneField;
            $fields[] = 'loyalty_tier';
            $fields[] = 'location';
            $fields[] = 'notes';
            $fields[] = 'created_at';

            $values[] = ':email';
            $values[] = ':phone';
            $values[] = ':loyalty_tier';
            $values[] = ':location';
            $values[] = ':notes';
            $values[] = 'NOW()';

            $params[':email'] = trim($input['email']);
            $params[':phone'] = trim($input['phone']);
            $params[':loyalty_tier'] = trim($input['loyalty_tier']);
            $params[':location'] = $input['location'] ?? 'Unknown';
            $params[':notes'] = $input['notes'] ?? '';

            $sql = "INSERT INTO guests (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            try {
                $newGuest = $guestService->createGuest($input);
                respond(['success' => true, 'data' => $newGuest], 201);
            } catch (Exception $e) {
                respond(['success' => false, 'error' => $e->getMessage()], 400);
            }
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing guest ID'], 400);

            $updateFields = [];
            $params = [":id" => $input['id']];

            // Handle name updates
            if (isset($input['name'])) {
                if ($hasFirstLastName) {
                    $fullName = trim($input['name']);
                    $nameParts = explode(' ', $fullName, 2);
                    $updateFields[] = "first_name = :first_name";
                    $updateFields[] = "last_name = :last_name";
                    $params[':first_name'] = trim($nameParts[0] ?? '');
                    $params[':last_name'] = trim($nameParts[1] ?? '');
                }
                if ($hasFullName) {
                    $updateFields[] = "name = :name";
                    $params[':name'] = trim($input['name']);
                }
            }

            // Handle other fields
            if (isset($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    respond(['success' => false, 'error' => 'Invalid email'], 400);
                }
                $updateFields[] = "email = :email";
                $params[':email'] = trim($input['email']);
            }

            if (isset($input['phone'])) {
                $updateFields[] = "$phoneField = :phone";
                $params[':phone'] = trim($input['phone']);
            }

            if (isset($input['loyalty_tier'])) {
                $updateFields[] = "loyalty_tier = :loyalty_tier";
                $params[':loyalty_tier'] = trim($input['loyalty_tier']);
            }

            if (isset($input['location'])) {
                $updateFields[] = "location = :location";
                $params[':location'] = trim($input['location']);
            }

            if (isset($input['notes'])) {
                $updateFields[] = "notes = :notes";
                $params[':notes'] = trim($input['notes']);
            }

            if (empty($updateFields)) {
                respond(['success' => false, 'error' => 'No fields to update'], 400);
            }

            $sql = "UPDATE guests SET " . implode(', ', $updateFields) . " WHERE $primaryKey = :id";
            $ok = $guestService->updateGuest($input);
            if ($ok) respond(['success' => true, 'message' => 'Guest updated successfully']);
            else respond(['success' => false, 'error' => 'No fields to update or guest not found'], 400);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing guest ID'], 400);

            $guestId = (int)$input['id'];

            // Direct SQL delete using correct primary key
            $sql = "DELETE FROM guests WHERE $primaryKey = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $guestId]);
            $deleted = $stmt->rowCount();

            if ($deleted) {
                respond(['success' => true, 'message' => 'Guest deleted successfully']);
            } else {
                respond(['success' => false, 'error' => 'Guest not found'], 404);
            }
            break;

        default:
            respond(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()], 500);
}
?>