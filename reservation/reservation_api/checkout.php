<?php
// Include the database connection file.
include '../../db_connect.php';
include 'popup_message.php';

// Check if the form was submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve the reservation ID from the form.
    $reservation_id = $_POST['reservation_id'];

    // Start a transaction to ensure both updates happen successfully or none do.
    $conn->begin_transaction();
    $success = true;

    try {
        // First, get the room_id associated with the reservation.
        $room_id_sql = "SELECT room_id FROM reservations WHERE reservation_id = ?";
        $room_id_stmt = $conn->prepare($room_id_sql);
        $room_id_stmt->bind_param("i", $reservation_id);
        $room_id_stmt->execute();
        $room_id_result = $room_id_stmt->get_result();
        $room_id_row = $room_id_result->fetch_assoc();
        $room_id = $room_id_row['room_id'];
        $room_id_stmt->close();
        
        // Second, update the reservation status to 'checked_out'.
        $update_reservation_sql = "UPDATE reservations SET status = 'checked_out', updated_at = NOW() WHERE reservation_id = ?";
        $stmt = $conn->prepare($update_reservation_sql);
        $stmt->bind_param("i", $reservation_id);
        if (!$stmt->execute()) {
            $success = false;
        }
        $stmt->close();

        // Third, update the room status back to 'available'.
        $update_room_sql = "UPDATE rooms SET status = 'available', updated_at = NOW() WHERE room_id = ?";
        $update_stmt = $conn->prepare($update_room_sql);
        $update_stmt->bind_param("i", $room_id);
        if (!$update_stmt->execute()) {
            $success = false;
        }
        $update_stmt->close();
        
        // If all queries were successful, commit the transaction.
        if ($success) {
            $conn->commit();
            showPopupMessage("Guest checked out successfully, and room is now available!");
        } else {
            $conn->rollback();
            showPopupMessage("❌ An error occurred. Changes have been rolled back.", "error");
        }

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        showPopupMessage("❌ Transaction failed: " . $e->getMessage(), "error");
    }
}

// Fetch all reservations with a 'checked_in' status to populate the form.
$reservations_sql = "SELECT r.reservation_id, g.first_name, g.last_name, ro.room_number 
                     FROM reservations r 
                     JOIN guests g ON r.guest_id = g.guest_id
                     JOIN rooms ro ON r.room_id = ro.room_id
                     WHERE r.status = 'checked_in'";
$reservations_result = $conn->query($reservations_sql);

// It's good practice to close the connection after fetching data, but before HTML.
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Out Guest</title>
    <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<a href="booking_management/booking_management.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
    <h2>Check Out a Guest</h2>
    <form method="post" action="checkout.php">
        <label for="reservation_id">Select Reservation to Check Out:</label>
        <select id="reservation_id" name="reservation_id" required>
            <option value="">-- Select a Checked-In Guest --</option>
            <?php
            if ($reservations_result && $reservations_result->num_rows > 0) {
                while($row = $reservations_result->fetch_assoc()) {
                    echo "<option value='" . $row["reservation_id"] . "'>" . $row["first_name"] . " " . $row["last_name"] . " in Room #" . $row["room_number"] . "</option>";
                }
            } else {
                echo "<option disabled>No guests currently checked in.</option>";
            }
            ?>
        </select>
        <input type="submit" value="Check Out Guest">
    </form>
</div>

</body>
</html>