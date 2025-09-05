<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../db/TaskManager.php';

$taskManager = new TaskManager($conn);

$response = [
    "tasks" => $taskManager->getTasks(),
    "stats" => $taskManager->getTaskStats()
];

header('Content-Type: application/json');
echo json_encode($response);
