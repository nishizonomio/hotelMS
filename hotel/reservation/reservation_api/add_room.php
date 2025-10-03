<?php
// Include the database connection file.
// The path is '../' because this file is inside the 'reservation_api' folder.
include '../db_connect.php';
include 'popup_message.php';

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data

    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $max_occupancy = $_POST['max_occupancy'];
    $price_rate = $_POST['price_rate'];
    $status = $_POST['status'];

    // SQL query to insert data using a prepared statement for security
    $sql = "INSERT INTO rooms (room_number, room_type, max_occupancy, price_rate, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isids", $room_number, $room_type, $max_occupancy, $price_rate, $status);

    // Execute the statement
    if ($stmt->execute()) {
        showPopupMessage("New room added successfully!");
    } else {
        showPopupMessage("❌ Error: " . $conn->error, "error");
    }

    // Close the statement
    $stmt->close();
    
    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Room</title>
    <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <a href="room_management/room_management.php" class="back-button">
        <img src="../reservation_img/back_icon.png" alt="Back">
    </a>

<div class="container">
    <h2>Add a New Room</h2>
    <form method="post" action="add_room.php">
        <label for="room_number">Room Number:</label>
        <input type="number" id="room_number" name="room_number" required>

        <label for="room_type">Room Type:</label>
        <select id="room_type" name="room_type" required>
            <option value="Single Room">Single Room</option>
            <option value="Double Room">Double Room</option>
            <option value="Twin Room">Twin Room</option>
            <option value="Deluxe Room">Deluxe Room</option>
            <option value="Suite">Suite</option>
            <option value="Family Room">Family Room</option>
        </select>

        <label for="max_occupancy">Max Occupancy:</label>
        <input type="number" id="max_occupancy" name="max_occupancy" required>

        <label for="price_rate">Price Rate:</label>
        <input type="number" id="price_rate" name="price_rate" step="0.01" required>

        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="reserved">Reserved</option>
            <option value="maintenance">Maintenance</option>
        </select>
        <input type="submit" value="Add Room">
    </form>
</div>

</body>
</html>