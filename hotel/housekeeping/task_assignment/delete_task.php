<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connector/db_connect.php';
require_once __DIR__ . '/../repo/taskmanager.php';

try {
    // Enable error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $taskManager = new TaskManager($conn);

    // Check if task exists first
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        throw new Exception("Missing or invalid task ID");
    }

    $success = $taskManager->deleteTask($id);
    
    if (!$success) {
        throw new Exception("Failed to delete task: " . $conn->error);
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Task deleted successfully"
    ]);

} catch (Exception $e) {
    error_log("Task deletion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
