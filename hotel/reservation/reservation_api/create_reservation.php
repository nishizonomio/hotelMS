<?php
// Include the database connection file, going up one directory
include '../../db_connect.php';
include 'popup_message.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $guest_id = $_POST['guest_id'];
    $room_id = $_POST['room_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    // SQL query to insert a new reservation using a prepared statement
    $sql_reservation = "INSERT INTO reservations (guest_id, room_id, status, remarks, check_in, check_out) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt_reservation = $conn->prepare($sql_reservation);
    $stmt_reservation->bind_param("iissss", $guest_id, $room_id, $status, $remarks, $check_in, $check_out);

    if ($stmt_reservation->execute()) {
        // If the reservation is successfully created, update the room status to 'occupied'
        $sql_update_room = "UPDATE rooms SET status = 'occupied', updated_at = NOW() WHERE room_id = ?";
        $stmt_update_room = $conn->prepare($sql_update_room);
        $stmt_update_room->bind_param("i", $room_id);
        
        if ($stmt_update_room->execute()) {
            showPopupMessage("Reservation created and room status updated successfully!");
        } else {
            showPopupMessage("❌ Error updating room status: " . $conn->error, "error");
        }
        
        $stmt_update_room->close();
    } else {
        showPopupMessage("❌ Error creating reservation: " . $conn->error, "error");
    }
    
    $stmt_reservation->close();
}

// Fetch all guests for the dropdown
$guests_sql = "SELECT guest_id, first_name, last_name FROM guests";
$guests_result = $conn->query($guests_sql);

// Fetch all available rooms for the dropdown
$rooms_sql = "SELECT room_id, room_number FROM rooms WHERE status = 'available'";
$rooms_result = $conn->query($rooms_sql);

// Close the connection after fetching data, but before displaying the form
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Reservation</title>
    <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<a href="booking_management/booking_management.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
    <h2>Create a New Reservation</h2>
    <form method="post" action="create_reservation.php">
        <label for="guest_id">Guest:</label>
        <select id="guest_id" name="guest_id" required>
            <option value="">-- Select a Guest --</option>
            <?php
            if ($guests_result && $guests_result->num_rows > 0) {
                while($row = $guests_result->fetch_assoc()) {
                    echo "<option value='" . $row["guest_id"] . "'>" . $row["first_name"] . " " . $row["last_name"] . "</option>";
                }
            }
            ?>
        </select>

        <label for="room_id">Room:</label>
        <select id="room_id" name="room_id" required>
            <option value="">-- Select a Room --</option>
            <?php
            if ($rooms_result && $rooms_result->num_rows > 0) {
                while($row = $rooms_result->fetch_assoc()) {
                    echo "<option value='" . $row["room_id"] . "'>Room #" . $row["room_number"] . "</option>";
                }
            } else {
                echo "<option disabled>No available rooms.</option>";
            }
            ?>
        </select>

        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
        </select>

        <label for="check_in">Check-in Date/Time:</label>
        <input type="datetime-local" id="check_in" name="check_in" required>

        <label for="check_out">Check-out Date/Time:</label>
        <input type="datetime-local" id="check_out" name="check_out" required>

        <label for="remarks">Remarks:</label>
        <textarea id="remarks" name="remarks" rows="4"></textarea>

        <div class="button-group">
            <input type="submit" value="💾 Save Reservation">
        </div>
    </form>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    // Get the check-in and check-out dates
    const checkIn = new Date(document.getElementById('check_in').value);
    const checkOut = new Date(document.getElementById('check_out').value);

    // Validate dates
    if (checkOut <= checkIn) {
        e.preventDefault();
        alert('Check-out date must be after check-in date');
        return;
    }

    // Confirm submission
    if (!confirm('Are you sure you want to create this reservation?')) {
        e.preventDefault();
    }
});

// Set minimum date-time for check-in and check-out
const now = new Date();
const formatted = now.toISOString().slice(0, 16); // Format: YYYY-MM-DDThh:mm
document.getElementById('check_in').min = formatted;
document.getElementById('check_out').min = formatted;
</script>

</body>
</html>
