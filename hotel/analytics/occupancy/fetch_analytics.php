<?php
include '../../db_connect.php';

// Fetch all rooms
$sql = "SELECT room_id, room_number, room_type, max_occupancy, price_rate, status, created_at, updated_at FROM rooms";
$result = $conn->query($sql);

$rooms = [];
$statusCounts = [
    "available" => 0,
    "reserved" => 0,
    "under maintenance" => 0,
    "dirty" => 0,
    "occupied" => 0
];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;

        $status = strtolower($row["status"]);
        if (array_key_exists($status, $statusCounts)) {
            $statusCounts[$status]++;
        }
    }
}

$response = [
    "rooms" => $rooms,
    "statusCounts" => $statusCounts,
    "totalRooms" => count($rooms)
];

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
