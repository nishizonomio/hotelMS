<?php
// loyalty.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? null;
if ($method === 'OPTIONS') { http_response_code(200); exit(); }
if ($method === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid execution context']);
    exit();
}

if (!file_exists(__DIR__ . '/database.php')) {
    respond(['success' => false, 'error' => 'Database configuration file not found'], 500);
}

try {
    require_once __DIR__ . '/database.php';
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()], 500);
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['stats'])) {
                $current = [
                    'members' => (int)$conn->query("SELECT SUM(members_count) FROM loyalty_programs")->fetchColumn(),
                    'points_redeemed' => (int)$conn->query("SELECT SUM(points_redeemed) FROM loyalty_programs")->fetchColumn(),
                    'rewards_given' => (int)$conn->query("SELECT SUM(rewards_given) FROM loyalty_programs")->fetchColumn(),
                    'revenue_impact' => (float)$conn->query("SELECT SUM(revenue_impact) FROM loyalty_programs")->fetchColumn(),
                ];
                $previous = [
                    'members' => max(0, $current['members'] - 2),
                    'points_redeemed' => max(0, $current['points_redeemed'] - 5),
                    'rewards_given' => max(0, $current['rewards_given'] - 1),
                    'revenue_impact' => max(0, $current['revenue_impact'] - 50),
                ];
                respond(['success' => true, 'data' => [
                    'current' => $current,
                    'previous' => $previous
                ]]);
            }

            $stmt = $conn->query("SELECT * FROM loyalty_programs");
            $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(['success' => true, 'data' => $programs]);
            break;

case 'POST':
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input['name']) || empty($input['tier']) || !isset($input['points_rate'])) {
        respond(['success' => false, 'error' => 'Missing required fields: name, tier, points_rate'], 400);
    }

    // ✅ Check unique name
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM loyalty_programs WHERE name = :name");
    $checkStmt->execute([':name' => trim($input['name'])]);
    if ($checkStmt->fetchColumn() > 0) {
        respond(['success' => false, 'error' => 'A loyalty program with this name already exists.'], 400);
    }

    $benefits = !empty($input['benefits'])
        ? (is_array($input['benefits']) ? implode(',', $input['benefits']) : $input['benefits'])
        : '';

    $stmt = $conn->prepare("INSERT INTO loyalty_programs 
        (name, tier, points_rate, benefits, members_count, description, status, created_at) 
        VALUES (:name, :tier, :points_rate, :benefits, :members_count, :description, 'active', NOW())");

    $stmt->execute([
        ':name' => trim($input['name']),
        ':tier' => strtolower(trim($input['tier'])),
        ':points_rate' => floatval($input['points_rate']),
        ':benefits' => $benefits,
        ':members_count' => intval($input['members_count'] ?? 0),
        ':description' => $input['description'] ?? ''
    ]);

$stmt = $conn->prepare("INSERT INTO loyalty_programs 
  (name, tier, points_rate, benefits, description, members_count) 
  VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdssi", $name, $tier, $points_rate, $benefits, $description, $members_count);
    break;


        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing program ID'], 400);

            $fields = [];
            $params = [':id' => $input['id']];

            if (isset($input['name'])) {
                $fields[] = "name = :name";
                $params[':name'] = $input['name'];
            }
            if (isset($input['tier'])) {
                $fields[] = "tier = :tier"; 
                $params[':tier'] = strtolower(trim($input['tier']));
            }
            if (isset($input['points_rate'])) {
                $fields[] = "points_rate = :points_rate"; 
                $params[':points_rate'] = $input['points_rate'];
            }
            if (isset($input['benefits'])) {
                $fields[] = "benefits = :benefits"; 
                $params[':benefits'] = is_array($input['benefits']) ? implode(',', $input['benefits']) : $input['benefits'];
            }
            if (isset($input['status'])) {
                $fields[] = "status = :status"; 
                $params[':status'] = $input['status'];
            }

            if (!$fields) respond(['success' => false, 'error' => 'No fields to update'], 400);
            $sql = "UPDATE loyalty_programs SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            respond(['success' => true, 'message' => 'Program updated successfully']);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['id'])) respond(['success' => false, 'error' => 'Missing program ID'], 400);

            $stmt = $conn->prepare("DELETE FROM loyalty_programs WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);
            if ($stmt->rowCount()) respond(['success' => true, 'message' => 'Program deleted successfully']);
            respond(['success' => false, 'error' => 'Program not found'], 404);
            break;

        default:
            respond(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    respond(['success' => false, 'error' => $e->getMessage()], 500);
}
