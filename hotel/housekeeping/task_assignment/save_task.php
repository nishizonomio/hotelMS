<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
require_once __DIR__ . '/../repo/taskmanager.php';

try {
    // Enable error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $taskManager = new TaskManager($conn);
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (!isset($data['room_id']) || !isset($data['task_type']) || !isset($data['staff_id'])) {
        throw new Exception("Missing required fields: room_id, task_type, and staff_id are required");
    }
    
    // Add default values if not provided
    $data['task_date'] = $data['task_date'] ?? date('Y-m-d');
    $data['status'] = $data['status'] ?? 'Pending';
    $data['remarks'] = $data['remarks'] ?? '';
    
    $success = $taskManager->saveTask($data);
    
    if (!$success) {
        throw new Exception("Failed to save task: " . $conn->error);
    }
    
    echo json_encode(["success" => true]);
    
} catch (Exception $e) {
    error_log("Task save error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
