<?php
session_start();
include 'db_connect.php';

// Get total number of rooms
$total_rooms_sql = "SELECT COUNT(*) AS total FROM rooms";
$total_rooms_result = $conn->query($total_rooms_sql);
$total_rooms = $total_rooms_result->fetch_assoc()['total'];

// Get number of occupied rooms
$occupied_rooms_sql = "SELECT COUNT(*) AS occupied FROM rooms WHERE status = 'occupied'";
$occupied_rooms_result = $conn->query($occupied_rooms_sql);
$occupied_rooms = $occupied_rooms_result->fetch_assoc()['occupied'];

// Get number of currently checked-in guests
$checked_in_guests_sql = "SELECT COUNT(*) AS checked_in FROM reservations WHERE status = 'checked_in'";
$checked_in_guests_result = $conn->query($checked_in_guests_sql);
$checked_in_guests = $checked_in_guests_result->fetch_assoc()['checked_in'];

// Calculate available rooms
$available_rooms = $total_rooms - $occupied_rooms;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Front Desk Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background-image: url('reservation_img/reservation_background.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 0;
        }

        .main-container {
            position: relative;
            z-index: 1;
            width: 95%;
            max-width: 1400px;
            background: rgba(0, 0, 0, 0.75);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin: 20px;
        }

        h1 {
            text-align: center;
            color: #FFD700;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .dashboard-layout {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }

        .stats-container {
            flex: 1;
            background: rgba(255, 215, 0, 0.1);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .stat-box {
            background: rgba(0, 0, 0, 0.5);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-label {
            font-size: 1.1em;
            margin-bottom: 10px;
            color: #FFD700;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            color: white;
        }

        .buttons-container {
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .nav-button {
            display: block;
            padding: 20px;
            background: rgba(0, 0, 0, 0.5);
            color: #FFD700;
            text-decoration: none;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 215, 0, 0.2);
            font-weight: 500;
        }

        .nav-button:hover {
            background: rgba(255, 215, 0, 0.15);
            transform: translateY(-3px);
            border-color: #FFD700;
        }

        @media (max-width: 1200px) {
            body {
                align-items: flex-start;
            }
            .main-container {
                margin: 20px auto;
            }
        }

        @media (max-width: 992px) {
            .dashboard-layout {
                flex-direction: column;
            }

            .buttons-container {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .buttons-container {
                grid-template-columns: 1fr;
            }

            .main-container {
                padding: 20px;
                margin: 10px;
                width: calc(100% - 20px);
            }
        }
    </style>
    <link rel="stylesheet" href="reservation_css/back_button.css">
</head>
<body>

<div class="main-container">
    <h1>Hotel Front Desk Dashboard</h1>
    
    <div class="dashboard-layout">
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Total Rooms</div>
                    <div class="stat-value"><?php echo $total_rooms; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Occupied Rooms</div>
                    <div class="stat-value"><?php echo $occupied_rooms; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Available Rooms</div>
                    <div class="stat-value"><?php echo $available_rooms; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Guests Checked-In</div>
                    <div class="stat-value"><?php echo $checked_in_guests; ?></div>
                </div>
            </div>
        </div>

        <div class="buttons-container">
            <a href="reservation_api/guest_profile_management/guest_profile_management.php" class="nav-button">
                Guest Profile Management
            </a>
            <a href="reservation_api/booking_management/booking_management.php" class="nav-button">
                Booking Management
            </a>
            <a href="reservation_api/room_management/room_management.php" class="nav-button">
                Room Management
            </a>
            <a href="reservation_api/reservation_calendar/reservation_calendar.php" class="nav-button">
                Reservation Calendar
            </a>
            <a href="reservation_api/view_reservations.php" class="nav-button">
                View All Bookings
            </a>
        </div>
    </div>
</div>


</body>
</html>

<!-- Back Button -->
<a href="/hotel/homepage/index.php" class="back-button" title="Back to Home">
    <img src="reservation_img/back_icon.png" alt="Back">
</a>