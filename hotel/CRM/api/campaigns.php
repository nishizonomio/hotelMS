<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Safe check kung may REQUEST_METHOD
$method = $_SERVER['REQUEST_METHOD'] ?? null;

if ($method === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/database.php';
$db = new Database();
$conn = $db->getConnection();

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

try {
    if ($method === null) {
        respond(['success' => false, 'error' => 'Invalid execution context. Must be called via HTTP request.'], 400);
    }

    // Allowed values
    $validTypes = ['email', 'sms', 'both'];
    $validStatuses = ['draft', 'scheduled', 'active', 'completed'];

    if ($method === 'GET') {
        $stmt = $conn->query("SELECT * FROM campaigns ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['success' => true, 'data' => $data]);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($input['name']) || empty($input['type']) || empty($input['target_audience']) || empty($input['message'])) {
            respond(['success' => false, 'error' => 'Missing required fields'], 400);
        }

        if (!in_array($input['type'], $validTypes)) $input['type'] = 'email';
        if (!in_array($input['status'], $validStatuses)) $input['status'] = 'draft';

        $stmt = $conn->prepare("
            INSERT INTO campaigns 
            (name, description, type, target_audience, message, status, schedule, created_by_user, created_at) 
            VALUES (:name, :description, :type, :target_audience, :message, :status, :schedule, :created_by_user, NOW())
        ");

        $stmt->execute([
            ':name' => $input['name'],
            ':description' => $input['description'] ?? null,
            ':type' => $input['type'],
            ':target_audience' => $input['target_audience'],
            ':message' => $input['message'],
            ':status' => $input['status'],
            ':schedule' => $input['schedule'] ?? null,
            ':created_by_user' => $input['created_by_user'] ?? null
        ]);

        $id = $conn->lastInsertId();
        $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        respond(['success' => true, 'message' => 'Campaign created', 'data' => $campaign], 201);
    }

    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing ID'], 400);

        if (!in_array($input['type'], $validTypes)) $input['type'] = 'email';
        if (!in_array($input['status'], $validStatuses)) $input['status'] = 'draft';

        $stmt = $conn->prepare("
            UPDATE campaigns 
            SET name = :name, description = :description, type = :type, target_audience = :target_audience,
                message = :message, status = :status, schedule = :schedule, updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $input['id'],
            ':name' => $input['name'],
            ':description' => $input['description'] ?? null,
            ':type' => $input['type'],
            ':target_audience' => $input['target_audience'],
            ':message' => $input['message'],
            ':status' => $input['status'],
            ':schedule' => $input['schedule'] ?? null
        ]);

        $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = :id");
        $stmt->execute([':id' => $input['id']]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

        respond(['success' => true, 'message' => 'Campaign updated', 'data' => $updated]);
    }

    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing ID'], 400);

        $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = :id");
        $stmt->execute([':id' => $input['id']]);

        respond(['success' => true, 'message' => 'Campaign deleted']);
    }

    respond(['success' => false, 'error' => 'Invalid method'], 405);

} catch (Exception $e) {
    respond(['success' => false, 'error' => $e->getMessage()], 500);
}
