<?php include '../../../db_connect.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management</title>
    <link rel="stylesheet" href="../../reservation_css/base.css">
    <link rel="stylesheet" href="../../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1>Room Management</h1>
    <div class="dashboard-grid">
        <div class="dashboard-item">
            <a href="../add_room.php">Add a New Room</a>
        </div>
        <div class="dashboard-item">
            <a href="../edit_room.php">Edit Room Details</a>
        </div>
    </div>
</div>
<a href="../../reservation.php" class="back-button" title="Back to Dashboard">
    <img src="../../reservation_img/back_icon.png" alt="Back">
</a>
</body>
</html>
