<?php
require_once(__DIR__ . '/../utils/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = $_POST['complaint_id'] ?? null;
    $guest_name = $_POST['guest_name'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $complaint_text = $_POST['complaint_text'] ?? '';
    $status = $_POST['status'] ?? 'Open';
    $recipe_id = $_POST['recipe_id'] ?? null;

    if ($guest_name && $complaint_text && $status) {
        if ($complaint_id) {
            $stmt = $pdo->prepare("
                UPDATE complaints 
                SET guest_name = ?, room_number = ?, complaint_text = ?, status = ?, recipe_id = ?
                WHERE complaint_id = ?
            ");
            $stmt->execute([$guest_name, $room_number, $complaint_text, $status, $recipe_id, $complaint_id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO complaints (guest_name, room_number, complaint_text, status, date_filed, recipe_id)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$guest_name, $room_number, $complaint_text, $status, $recipe_id]);
        }
        header("Location: complaints.php?success=1");
        exit;
    } else {
        header("Location: file_a_complaint.php?message=" . urlencode("All required fields must be filled."));
        exit;
    }
}
