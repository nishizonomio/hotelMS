<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../db/TaskManager.php';

$taskManager = new TaskManager($conn);

// Validate and normalize id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
	http_response_code(400);
	echo json_encode(["success" => false, "message" => "Missing or invalid id"]);
	exit;
}

$success = $taskManager->deleteTask($id);

if ($success) {
	echo json_encode(["success" => true]);
} else {
	http_response_code(500);
	echo json_encode(["success" => false, "message" => "Failed to delete task"]);
}
