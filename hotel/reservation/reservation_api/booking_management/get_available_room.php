<?php
// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Include DB connection (adjust path if needed)
include '../../db_connect.php';

if(isset($_POST['room_type']) && !empty($_POST['room_type'])) {
    $room_type = trim($_POST['room_type']); // remove extra spaces

    // Prepare SQL: case-insensitive matching for room_type and status
    $sql = "SELECT room_id, room_number
            FROM rooms
            WHERE LOWER(TRIM(room_type)) = LOWER(?)
              AND LOWER(TRIM(status)) = 'available'
            ORDER BY 
                CAST(room_number AS UNSIGNED) ASC,
                room_number ASC";

    $stmt = $conn->prepare($sql);
    if(!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'SQL Prepare Error: ' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("s", $room_type);
    $stmt->execute();
    $result = $stmt->get_result();

    $rooms = [];
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }

    $stmt->close();
    $conn->close();

    if(count($rooms) > 0) {
        echo json_encode(['status'=>'success','rooms'=>$rooms]);
    } else {
        echo json_encode(['status'=>'empty','rooms'=>[]]);
    }

} else {
    echo json_encode(['status'=>'error','message'=>'No room type provided']);
}
?>
