<?php
// API: Fetch room and guest bookings for the calendar
include '../../../db_connect.php';
header('Content-Type: application/json');
$type = $_GET['type'] ?? 'room';
$id = $_GET['id'] ?? null;
$response = [];
if ($type === 'room' && $id) {
    $sql = "SELECT * FROM bookings WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
} elseif ($type === 'guest' && $id) {
    $sql = "SELECT * FROM bookings WHERE guest_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
}
echo json_encode($response);
$conn->close();
