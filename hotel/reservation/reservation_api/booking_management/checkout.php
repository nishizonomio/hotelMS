<?php
session_start();
include '../../db_connect.php';
date_default_timezone_set('Asia/Manila'); // Manila timezone for all PHP time functions

// Initialize session messages
if (!isset($_SESSION['success_message'])) $_SESSION['success_message'] = "";
if (!isset($_SESSION['error_message'])) $_SESSION['error_message'] = "";
if (!isset($_SESSION['checkout_time'])) $_SESSION['checkout_time'] = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_type = trim($_POST['booking_type'] ?? '');
    $booking_id   = trim($_POST['booking_id'] ?? '');

    if ($booking_type === '' || $booking_id === '') {
        $_SESSION['error_message'] = "⚠️ Please select booking type and enter booking ID.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Choose the proper select query depending on booking type
    if ($booking_type === "reservation") {
        $stmt = $conn->prepare("SELECT reservation_id AS id, room_id FROM reservations WHERE reservation_id = ? AND status != 'checked_out'");
    } elseif ($booking_type === "walkin") {
        $stmt = $conn->prepare("SELECT walkin_id AS id, room_id FROM walk_in WHERE walkin_id = ? AND status != 'checked_out'");
    } else {
        $stmt = null;
    }

    if (!$stmt) {
        $_SESSION['error_message'] = "❌ Invalid booking type!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "❌ Booking ID does not exist in $booking_type table or is already checked out!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $booking = $result->fetch_assoc();
    $room_id = (int)$booking['room_id'];

    // ✅ Correct check-out time (Manila timezone)
    $now_db   = date('Y-m-d H:i:s');  // database storage format
    $now_disp = date('Y-m-d h:i A');  // human-readable format (12-hr with AM/PM)

    // Update booking table (status + actual_checkout)
    if ($booking_type === "reservation") {
        $update = $conn->prepare("UPDATE reservations SET status = 'checked_out', actual_checkout = ? WHERE reservation_id = ?");
    } else {
        $update = $conn->prepare("UPDATE walk_in SET status = 'checked_out', actual_checkout = ? WHERE walkin_id = ?");
    }
    $booking_id_int = (int)$booking_id; // ensure integer type
    $update->bind_param("si", $now_db, $booking_id_int);
    $update->execute();

    // Update room status to dirty
    $updateRoom = $conn->prepare("UPDATE rooms SET status = 'dirty' WHERE room_id = ?");
    $updateRoom->bind_param("i", $room_id);
    $updateRoom->execute();

    // Set messages and store display time in session
    $_SESSION['success_message'] = "✅ Guest checked out successfully!";
    $_SESSION['checkout_time']   = $now_disp;

    // Clean up
    $stmt->close();
    $update->close();
    $updateRoom->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Out Guest</title>
    <link rel="stylesheet" href="../../reservation_css/base.css">
    <link rel="stylesheet" href="../../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .message {
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            opacity: 1;
            transition: opacity 1s ease-out, transform 1s ease-out;
        }
        .message.success { color: #4CAF50; }
        .message.error { color: #ff4d4d; }
        .fade-out {
            opacity: 0 !important;
            transform: translateY(-20px);
        }
        .checkout-time {
            text-align: center;
            margin-top: 10px;
            font-size: 1.05rem;
            color: #00c3ff;
        }
    </style>
</head>
<body class="dark-theme">
<div class="container">
    <h1>Check Out Guest</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="fadeable">
            <p class="message success"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
            <?php if (!empty($_SESSION['checkout_time'])): ?>
                <p class="checkout-time">🕒 Actual Check-out: <b><?= htmlspecialchars($_SESSION['checkout_time']) ?></b></p>
            <?php endif; ?>
        </div>
        <?php
            $_SESSION['success_message'] = "";
            $_SESSION['checkout_time']   = "";
        ?>
    <?php elseif (!empty($_SESSION['error_message'])): ?>
        <div class="fadeable">
            <p class="message error"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        </div>
        <?php $_SESSION['error_message'] = ""; ?>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="booking_type">Booking Type</label>
        <select name="booking_type" id="booking_type" required onchange="updatePlaceholder()">
            <option value="">-- Select --</option>
            <option value="reservation">Reservation</option>
            <option value="walkin">Walk-in</option>
        </select>

        <label for="booking_id">Booking ID</label>
        <input type="number" name="booking_id" id="booking_id" placeholder="Enter Booking ID" required>

        <input type="submit" value="Check Out">
    </form>
</div>

<a href="booking_management.php" class="back-button" title="Back to Dashboard">
    <img src="../../reservation_img/back_icon.png" alt="Back">
</a>

<script>
    // Auto fade and remove ALL messages (success, error, checkout time) after 5s
    window.addEventListener('load', function() {
        const fadeables = document.querySelectorAll('.fadeable');
        if (fadeables.length > 0) {
            setTimeout(() => {
                fadeables.forEach(el => el.classList.add('fade-out'));
                setTimeout(() => {
                    fadeables.forEach(el => { if (el.parentNode) el.parentNode.removeChild(el); });
                }, 1000);
            }, 5000);
        }
    });

    // Change placeholder text based on booking type
    function updatePlaceholder() {
        const type = document.getElementById('booking_type').value;
        const input = document.getElementById('booking_id');
        if (type === "reservation") {
            input.placeholder = "Enter Reservation ID";
        } else if (type === "walkin") {
            input.placeholder = "Enter Walk-in ID";
        } else {
            input.placeholder = "Enter Booking ID";
        }
    }
</script>
</body>
</html>
