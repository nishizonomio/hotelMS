<?php
$conn = new mysqli("localhost","root","","hotel");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch rooms with guest info for reserved or occupied rooms
$sql = "
SELECT 
    r.room_id,
    r.room_number,
    r.room_type,
    r.max_occupancy,
    r.price_rate,
    r.status,
    r.created_at,
    r.updated_at,
    res.guest_id,
    CONCAT(g.first_name, ' ', g.last_name) AS guest_name
FROM rooms r
LEFT JOIN reservations res 
    ON r.room_id = res.room_id
LEFT JOIN guests g 
    ON res.guest_id = g.guest_id
";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$rooms = [];
$statusCounts = [
    'available' => 0,
    'reserved' => 0,
    'under maintenance' => 0,
    'dirty' => 0,
    'occupied' => 0
];

while ($row = $result->fetch_assoc()) {
    $status = strtolower($row['status']);
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }

    // Only show guest info if room is reserved or occupied and has a guest
    if (!in_array($status, ['reserved', 'occupied']) || !$row['guest_id']) {
        $row['guest_id'] = '-';
        $row['guest_name'] = '-';
    }

    $rooms[] = $row;
}

$response = [
    'rooms' => $rooms,
    'statusCounts' => $statusCounts,
    'totalRooms' => count($rooms)
];

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
?>
