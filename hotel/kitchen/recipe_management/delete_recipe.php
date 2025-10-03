<?php
require_once(__DIR__ . '/../utils/db.php');

$id = $_POST['id'] ?? 0;
$id = (int)$id;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: recipes.php");
exit;
