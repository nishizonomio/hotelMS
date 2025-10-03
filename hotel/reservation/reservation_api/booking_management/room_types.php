<?php
include '../../db_connect.php';

$sql = "SELECT DISTINCT room_type FROM rooms ORDER BY room_type ASC";
$result = $conn->query($sql);

$room_types = [];
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $room_types[] = $row['room_type'];
    }
}

echo json_encode($room_types);
$conn->close();
?>
