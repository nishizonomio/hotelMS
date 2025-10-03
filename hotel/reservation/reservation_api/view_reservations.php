<?php
session_start();
include '../db_connect.php'; // Adjust the path if needed

// Determine which table to show
$show = $_GET['show'] ?? 'reservations'; // default to reservations

// Initialize data
$bookings = [];
$error = '';

// Fetch data based on selected table
if ($show === 'reservations') {
    $sql = "SELECT r.reservation_id, r.guest_id, r.room_id, r.status, r.reservation_date, r.remarks,
                   r.check_in, r.check_out, r.extended_duration, r.actual_checkout
            FROM reservations r
            ORDER BY r.check_in DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $error = "Error fetching reservations: " . $conn->error;
    }
} elseif ($show === 'walk_in') {
    $sql = "SELECT w.walkin_id, w.guest_id, w.room_id, w.status, w.remarks,
                   w.check_in, w.check_out, w.extended_duration, w.actual_checkout
            FROM walk_in w
            ORDER BY w.check_in DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $error = "Error fetching walk-ins: " . $conn->error;
    }
} elseif ($show === 'room_payments') {
    $sql = "SELECT payment_id, guest_id, walkin_id, reservation_id, room_type, room_price, stay,
                   extended_price, extended_duration, created_at, updated_at
            FROM room_payments
            ORDER BY created_at DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $error = "Error fetching room payments: " . $conn->error;
    }
} else {
    $error = "Invalid selection.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Bookings</title>
<link rel="stylesheet" href="../reservation_css/base.css">
<link rel="stylesheet" href="../reservation_css/back_button.css">
<style>
table th, table td { text-align: center; font-size: 0.9rem; }
table th { background-color: rgba(255, 215, 0, 0.2); color: #ffd700; }
table td { color: #fff; }
.select-buttons { margin: 20px 0; display: flex; gap: 15px; flex-wrap: wrap; justify-content: center; }
.select-buttons a { text-decoration: none; padding: 10px 20px; background: rgba(28,28,28,0.95); color: #ffd700; border-radius: 8px; border: 1px solid #ffd700; transition: all 0.3s; }
.select-buttons a:hover { background: #ffd700; color: #1c1c1c; transform: translateY(-2px); }

/* Search Box */
#searchInput {
    width: 100%;
    max-width: 400px;
    padding: 10px 15px;
    margin: 10px auto 20px;
    display: block;
    border-radius: 8px;
    border: 1px solid #ffd700;
    background: rgba(28,28,28,0.9);
    color: #fff;
    font-size: 1rem;
}

#searchInput::placeholder {
    color: #ffd700;
}
</style>
</head>
<body>
<div class="container">
    <h1>View Bookings</h1>

    <div class="select-buttons">
        <a href="?show=reservations">Reservations</a>
        <a href="?show=walk_in">Walk-Ins</a>
        <a href="?show=room_payments">Room Payments</a>
    </div>

    <!-- Search Box -->
    <input type="text" id="searchInput" placeholder="Search by Guest ID, Room ID, Name, or Payment ID">

    <?php if($error): ?>
        <p style="color:red; text-align:center;"><?= $error ?></p>
    <?php else: ?>

        <table id="bookingsTable">
            <thead>
            <tr>
                <?php if($show === 'reservations'): ?>
                    <th>Reservation ID</th>
                    <th>Guest ID</th>
                    <th>Room ID</th>
                    <th>Status</th>
                    <th>Reservation Date</th>
                    <th>Remarks</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Extended Duration</th>
                    <th>Actual Checkout</th>
                <?php elseif($show === 'walk_in'): ?>
                    <th>Walk-In ID</th>
                    <th>Guest ID</th>
                    <th>Room ID</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Extended Duration</th>
                    <th>Actual Checkout</th>
                <?php elseif($show === 'room_payments'): ?>
                    <th>Payment ID</th>
                    <th>Guest ID</th>
                    <th>Walk-In ID</th>
                    <th>Reservation ID</th>
                    <th>Room Type</th>
                    <th>Room Price</th>
                    <th>Stay</th>
                    <th>Extended Price</th>
                    <th>Extended Duration</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if(!empty($bookings)): ?>
                <?php foreach($bookings as $b): ?>
                    <tr>
                        <?php if($show === 'reservations'): ?>
                            <td><?= htmlspecialchars($b['reservation_id']) ?></td>
                            <td><?= htmlspecialchars($b['guest_id']) ?></td>
                            <td><?= htmlspecialchars($b['room_id']) ?></td>
                            <td><?= htmlspecialchars($b['status']) ?></td>
                            <td><?= htmlspecialchars($b['reservation_date']) ?></td>
                            <td><?= htmlspecialchars($b['remarks']) ?></td>
                            <td><?= htmlspecialchars($b['check_in']) ?></td>
                            <td><?= htmlspecialchars($b['check_out']) ?></td>
                            <td><?= htmlspecialchars($b['extended_duration']) ?></td>
                            <td><?= htmlspecialchars($b['actual_checkout']) ?></td>
                        <?php elseif($show === 'walk_in'): ?>
                            <td><?= htmlspecialchars($b['walkin_id']) ?></td>
                            <td><?= htmlspecialchars($b['guest_id']) ?></td>
                            <td><?= htmlspecialchars($b['room_id']) ?></td>
                            <td><?= htmlspecialchars($b['status']) ?></td>
                            <td><?= htmlspecialchars($b['remarks']) ?></td>
                            <td><?= htmlspecialchars($b['check_in']) ?></td>
                            <td><?= htmlspecialchars($b['check_out']) ?></td>
                            <td><?= htmlspecialchars($b['extended_duration']) ?></td>
                            <td><?= htmlspecialchars($b['actual_checkout']) ?></td>
                        <?php elseif($show === 'room_payments'): ?>
                            <td><?= htmlspecialchars($b['payment_id']) ?></td>
                            <td><?= htmlspecialchars($b['guest_id']) ?></td>
                            <td><?= htmlspecialchars($b['walkin_id']) ?></td>
                            <td><?= htmlspecialchars($b['reservation_id']) ?></td>
                            <td><?= htmlspecialchars($b['room_type']) ?></td>
                            <td><?= htmlspecialchars($b['room_price']) ?></td>
                            <td><?= htmlspecialchars($b['stay']) ?></td>
                            <td><?= htmlspecialchars($b['extended_price']) ?></td>
                            <td><?= htmlspecialchars($b['extended_duration']) ?></td>
                            <td><?= htmlspecialchars($b['created_at']) ?></td>
                            <td><?= htmlspecialchars($b['updated_at']) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="15" style="text-align:center;">No records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>

<!-- Back Button -->
<a href="/hotel/reservation/reservation.php" class="back-button" title="Back to Dashboard">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<script>
// Simple client-side search
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#bookingsTable tbody tr');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
