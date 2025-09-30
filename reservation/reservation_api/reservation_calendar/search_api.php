<?php
// API: Search for rooms or guests
include '../../../db_connect.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$query = $_GET['query'] ?? '';
$status = $_GET['status'] ?? '';
$response = [];
if ($type === 'room') {
    if ($status) {
        $sql = "SELECT r.room_id, r.room_number FROM rooms r JOIN bookings b ON r.room_id = b.room_id WHERE r.room_number LIKE ? AND b.status = ? GROUP BY r.room_id, r.room_number";
        $like = "%$query%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $like, $status);
    } else {
        $sql = "SELECT room_id, room_number FROM rooms WHERE room_number LIKE ?";
        $like = "%$query%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
} elseif ($type === 'guest') {
    if ($status) {
        $sql = "SELECT g.guest_id, g.first_name, g.last_name FROM guests g JOIN bookings b ON g.guest_id = b.guest_id WHERE (g.first_name LIKE ? OR g.last_name LIKE ?) AND b.status = ? GROUP BY g.guest_id, g.first_name, g.last_name";
        $like = "%$query%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $like, $like, $status);
    } else {
        $sql = "SELECT guest_id, first_name, last_name FROM guests WHERE first_name LIKE ? OR last_name LIKE ?";
        $like = "%$query%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $like, $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
}
echo json_encode($response);
$conn->close();
