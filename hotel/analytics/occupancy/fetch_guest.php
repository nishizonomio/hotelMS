<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_GET['guest_id'])) {
    echo json_encode(null);
    exit;
}

$guestId = intval($_GET['guest_id']);
$sql = "SELECT guest_id, CONCAT(first_name, ' ', last_name) AS full_name FROM guests WHERE guest_id = $guestId";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(null);
}
