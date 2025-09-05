<?php
require_once __DIR__ . '/../db_connector/db_connect.php';

if (isset($_GET['taskId'])) {
    $taskId = intval($_GET['taskId']);
    
    $query = "SELECT * FROM housekeeping_tasks WHERE task_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Task not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'No task ID provided']);
}

$conn->close();
?>
