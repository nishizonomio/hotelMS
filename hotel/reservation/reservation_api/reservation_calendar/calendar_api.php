<?php
include '../../db_connect.php';
header('Content-Type: application/json');

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n')-1;
$bookings = [];

// Fetch reservations
$res_sql = "SELECT r.reservation_id, r.room_id, r.status, r.check_in, r.check_out, r.extended_duration, g.first_name, g.last_name
            FROM reservations r
            JOIN guests g ON r.guest_id = g.guest_id";
$res_result = $conn->query($res_sql);
while($row = $res_result->fetch_assoc()){
	$bookings[] = [
		'room_id' => $row['room_id'],
		'guest_name' => $row['first_name'].' '.$row['last_name'],
		'status' => $row['status'] ?? 'reserved',
		'check_in' => $row['check_in'],
		'check_out' => $row['check_out'],
		'start' => date('Y-m-d', strtotime($row['check_in'])),
		'end' => date('Y-m-d', strtotime($row['check_out']))
	];
}

// Fetch walk-ins
$walk_sql = "SELECT w.walkin_id, w.room_id, w.status, w.check_in, w.check_out, w.extended_duration, g.first_name, g.last_name
             FROM walk_in w
             JOIN guests g ON w.guest_id = g.guest_id";
$walk_result = $conn->query($walk_sql);
while($row = $walk_result->fetch_assoc()){
	$bookings[] = [
		'room_id' => $row['room_id'],
		'guest_name' => $row['first_name'].' '.$row['last_name'],
		'status' => $row['status'] ?? 'reserved',
		'check_in' => $row['check_in'],
		'check_out' => $row['check_out'],
		'start' => date('Y-m-d', strtotime($row['check_in'])),
		'end' => date('Y-m-d', strtotime($row['check_out']))
	];
}

echo json_encode($bookings);
$conn->close();
