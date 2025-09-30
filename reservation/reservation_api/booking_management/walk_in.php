<?php
session_start();
r    <link rel="stylesheet" href="../../reservation_css/base_updated.css">
    <link rel="stylesheet" href="../../reservation_css/back_button.css">
    <link rel="stylesheet" href="../../reservation_css/container.css">
    <link rel="stylesheet" href="../../reservation_css/walk_in.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>_once('../../db_connect.php');

// Fetch available rooms
$available_rooms_sql = "SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number";
$available_rooms_result = $conn->query($available_rooms_sql);

// Fetch recent walk-ins
$recent_walkins_sql = "
    SELECT w.*, g.first_name, g.last_name, r.room_number
    FROM walk_ins w
    JOIN guests g ON w.guest_id = g.guest_id
    JOIN rooms r ON w.room_id = r.room_id
    ORDER BY w.check_in_time DESC
    LIMIT 10
";
$recent_walkins_result = $conn->query($recent_walkins_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Registration</title>
    <link rel="stylesheet" href="../../reservation_css/base_updated.css">
    <link rel="stylesheet" href="../../reservation_css/back_button.css">
    <link rel="stylesheet" href="../../reservation_css/dark_theme.css">
    <link rel="stylesheet" href="../../reservation_css/walk_in.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="container">
        <h1>Walk-in Registration</h1>
        
        <div class="walk-in-layout">
            <div class="left-section">
                <div class="form-section">
                    <h2>Guest Information</h2>
                    <form id="walkInForm" action="process_walkin.php" method="POST">
                        <div class="form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="firstName" required>
                            
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="lastName" required>
                            
                            <label for="phone">Phone Number:</label>
                            <input type="tel" id="phone" name="phone" required>
                            
                            <label for="email">Email (Optional):</label>
                            <input type="email" id="email" name="email">

                            <label for="remarks">Remarks:</label>
                            <textarea id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                </div>
            </div>

            <div class="right-section">
                <div class="form-section">
                    <h2>Room Selection</h2>
                    <div class="form-group">
                        <label for="room">Available Rooms:</label>
                        <select id="room" name="room_id" required>
                            <option value="">Select a room</option>
                            <?php while($room = $available_rooms_result->fetch_assoc()): ?>
                                <option value="<?php echo $room['room_id']; ?>">
                                    Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <label for="checkOutDate">Expected Check-out Date:</label>
                        <input type="datetime-local" id="checkOutDate" name="expected_check_out" required>

                        <div class="submit-section">
                            <button type="submit" class="submit-button">Register Walk-in</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="recent-section">
            <h2>Recent Walk-ins</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Check-in Time</th>
                            <th>Expected Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($walkin = $recent_walkins_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($walkin['first_name'] . ' ' . $walkin['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($walkin['room_number']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($walkin['check_in_time'])); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($walkin['expected_check_out'])); ?></td>
                                <td><?php echo ucfirst($walkin['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <a href="../../reservation.php" class="back-button" title="Back to Dashboard">
        <img src="../../reservation_img/back_icon.png" alt="Back">
    </a>
</body>
</html>
<?php $conn->close(); ?>
