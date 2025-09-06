<?php
include '../../db_connect.php';

// Get reservation information if ID is provided
if (isset($_GET['id'])) {
    $reservation_id = $_GET['id'];
    $sql = "SELECT r.*, g.first_name, g.last_name, rm.room_number 
            FROM reservations r
            JOIN guests g ON r.guest_id = g.guest_id
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE r.reservation_id = ? AND r.status = 'checked_in'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_id = $_POST['reservation_id'];
    $new_checkout = $_POST['new_checkout'];
    $remarks = $_POST['remarks'];

    // Start transaction
    $conn->begin_transaction();
    try {
        // Update checkout date
        $update_sql = "UPDATE reservations SET 
                      check_out = ?,
                      remarks = CONCAT(remarks, '\nStay extended to: ', ?, '\nReason: ', ?),
                      updated_at = NOW()
                      WHERE reservation_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $new_checkout, $new_checkout, $remarks, $reservation_id);
        $stmt->execute();

        $conn->commit();
                showPopupMessage("Stay duration extended successfully!");
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>❌ Error extending stay: " . $e->getMessage() . "</p>";
    }
}

// Fetch all active check-ins for the selection dropdown
$active_checkins_sql = "SELECT r.reservation_id, g.first_name, g.last_name, rm.room_number, r.check_in, r.check_out 
                        FROM reservations r
                        JOIN guests g ON r.guest_id = g.guest_id
                        JOIN rooms rm ON r.room_id = rm.room_id
                        WHERE r.status = 'checked_in'
                        ORDER BY r.check_out";
$active_checkins_result = $conn->query($active_checkins_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extend Stay Duration</title>
        <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
</head>
<body>
<a href="booking_management/booking_management.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
    <h2>Extend Stay Duration</h2>
    
    <!-- Reservation Selection Form -->
    <?php if (!isset($_GET['id'])): ?>
    <form method="get" action="extend_stay.php">
        <label for="id">Select Active Check-in:</label>
        <select name="id" id="id" required onchange="this.form.submit()">
            <option value="">-- Select a Reservation --</option>
            <?php while($checkin = $active_checkins_result->fetch_assoc()): ?>
                <option value="<?php echo $checkin['reservation_id']; ?>">
                    Room #<?php echo htmlspecialchars($checkin['room_number']); ?> - 
                    <?php echo htmlspecialchars($checkin['first_name'] . ' ' . $checkin['last_name']); ?> 
                    (Until: <?php echo date('Y-m-d H:i', strtotime($checkin['check_out'])); ?>)
                </option>
            <?php endwhile; ?>
        </select>
    </form>
    <?php endif; ?>

    <!-- Extension Form -->
    <?php if (isset($reservation)): ?>
    <div class="current-info">
        <p><strong>Guest:</strong> <?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></p>
        <p><strong>Room:</strong> #<?php echo htmlspecialchars($reservation['room_number']); ?></p>
        <p><strong>Current Check-out:</strong> <?php echo date('Y-m-d H:i', strtotime($reservation['check_out'])); ?></p>
    </div>

    <form method="post" action="extend_stay.php">
        <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
        
        <label for="new_checkout">New Check-out Date/Time:</label>
        <input type="datetime-local" id="new_checkout" name="new_checkout" 
               min="<?php echo date('Y-m-d\TH:i', strtotime($reservation['check_out'])); ?>" required>

        <label for="remarks">Reason for Extension:</label>
        <textarea id="remarks" name="remarks" rows="3" required></textarea>

        <input type="submit" value="Extend Stay">
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newCheckoutInput = document.getElementById('new_checkout');
    if (newCheckoutInput) {
        const now = new Date();
        newCheckoutInput.min = now.toISOString().slice(0, 16);
    }
});
</script>

</body>
</html>
