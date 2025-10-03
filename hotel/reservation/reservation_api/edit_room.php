<?php
include '../db_connect.php';

// Get room information if ID is provided
if (isset($_GET['id'])) {
    $room_id = $_GET['id'];
    $sql = "SELECT * FROM rooms WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];
    $room_number = $_POST['room_number'];
    $room_types = $_POST['room_types'];
    $max_occupancy = $_POST['max_occupancy'];
    $status = $_POST['status'];

    $sql = "UPDATE rooms SET 
            room_number = ?,
            room_types = ?,
            max_occupancy = ?,
            status = ?,
            updated_at = NOW()
            WHERE room_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisi", $room_number, $room_types, $max_occupancy, $status, $room_id);

    if ($stmt->execute()) {
        echo "<p style='color:#ffd700;'>✅ Room information updated successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error updating room information: " . $conn->error . "</p>";
    }
}

// Fetch all rooms for the selection dropdown
$all_rooms_sql = "SELECT room_id, room_number FROM rooms ORDER BY room_number";
$all_rooms_result = $conn->query($all_rooms_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room Information</title>
        <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
</head>
<body>
<a href="room_management/room_management.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
        <h2>Edit Room Details</h2>
    
    <!-- Room Selection Form -->
    <?php if (!isset($_GET['id'])): ?>
    <form method="get" action="edit_room.php">
        <label for="id">Select Room:</label>
        <select name="id" id="id" required onchange="this.form.submit()">
            <option value="">-- Select a Room --</option>
            <?php while($room_row = $all_rooms_result->fetch_assoc()): ?>
                <option value="<?php echo $room_row['room_id']; ?>">
                    Room #<?php echo htmlspecialchars($room_row['room_number']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
    <?php endif; ?>

    <!-- Edit Form -->
    <?php if (isset($room)): ?>
    <form method="post" action="edit_room.php">
        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
        
        <label for="room_number">Room Number:</label>
        <input type="number" id="room_number" name="room_number" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>

        <label for="room_types">Room Type:</label>
        <input type="text" id="room_types" name="room_types" value="<?php echo htmlspecialchars($room['room_types']); ?>" required>

        <label for="max_occupancy">Max Occupancy:</label>
        <input type="number" id="max_occupancy" name="max_occupancy" value="<?php echo htmlspecialchars($room['max_occupancy']); ?>" required>

        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="available" <?php echo $room['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
            <option value="occupied" <?php echo $room['status'] == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
        </select>

        <input type="submit" value="Update Room Information">
    </form>
    <?php endif; ?>
</div>

</body>
</html>
