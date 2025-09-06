<?php
include '../db_connect.php';

$sql = "SELECT room_id, room_number, room_type, max_occupancy, status, price_rate, created_at, updated_at FROM rooms";
$result = $conn->query($sql);

$rooms = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// Full list of statuses
$allStatuses = [
    'available' => 0,
    'reserved' => 0,
    'under maintenance' => 0,
    'dirty' => 0,
    'occupied' => 0
];

// Count rooms per status
foreach ($rooms as $r) {
    $status = strtolower($r['status']);
    if (isset($allStatuses[$status])) {
        $allStatuses[$status]++;
    }
}

// Keep only statuses with > 0 count
$filteredCounts = [];
foreach ($allStatuses as $status => $count) {
    if ($count > 0) {
        $filteredCounts[$status] = $count;
    }
}

echo json_encode([
    "rooms" => $rooms,
    "statusCounts" => $filteredCounts,
    "totalRooms" => count($rooms)
]);
