<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../db/TaskManager.php';

$taskManager = new TaskManager($conn);
$id = $_GET['id'] ?? null;

$success = $id ? $taskManager->markComplete($id) : false;

echo json_encode(["success" => $success]);
