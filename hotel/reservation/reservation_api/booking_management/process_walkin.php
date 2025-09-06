<?php
session_start();
require_once('../../../db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $lastName = $conn->real_escape_string($_POST['lastName']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $roomId = (int)$_POST['room_id'];
    $expectedCheckOut = $conn->real_escape_string($_POST['expected_check_out']);
    $remarks = $conn->real_escape_string($_POST['remarks']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert guest
        $guest_sql = "INSERT INTO guests (first_name, last_name, first_phone, email, status) 
                      VALUES (?, ?, ?, ?, 'checked_in')";
        $stmt = $conn->prepare($guest_sql);
        $stmt->bind_param("ssss", $firstName, $lastName, $phone, $email);
        $stmt->execute();
        $guestId = $conn->insert_id;

        // Update room status
        $room_sql = "UPDATE rooms SET status = 'occupied' WHERE room_id = ?";
        $stmt = $conn->prepare($room_sql);
        $stmt->bind_param("i", $roomId);
        $stmt->execute();

        // Create walk-in record
        $walkin_sql = "INSERT INTO walk_ins (guest_id, room_id, expected_check_out, remarks) 
                       VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($walkin_sql);
        $stmt->bind_param("iiss", $guestId, $roomId, $expectedCheckOut, $remarks);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        
        // Redirect back with success message
        header("Location: walk_in.php?success=1");
        exit();

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        header("Location: walk_in.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

$conn->close();
?>
