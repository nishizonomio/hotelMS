<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../db/TaskManager.php';

$taskManager = new TaskManager($conn);
$data = json_decode(file_get_contents("php://input"), true);

$success = $taskManager->saveTask($data);

echo json_encode(["success" => $success]);
