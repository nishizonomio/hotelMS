<?php
// Include the database connection file
include '../db_connect.php';
include 'popup_message.php';

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $first_phone = $_POST['first_phone'];
    $second_phone = $_POST['second_phone'];
    $status = $_POST['status'];

    // SQL query to insert data using a prepared statement
    $sql = "INSERT INTO guests (first_name, last_name, email, first_phone, second_phone, status) VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    
    // Bind parameters to the statement
    $stmt->bind_param("ssssss", $first_name, $last_name, $email, $first_phone, $second_phone, $status);

    // Execute the statement
    if ($stmt->execute()) {
        showPopupMessage("New guest added successfully!");
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
    <title>Add New Guest</title>
    <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dark-theme">
<a href="guest_profile_management/guest_profile_management.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
    <h2>Add a New Guest</h2>
    <form method="post" action="add_guest.php">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email">

        <label for="first_phone">Primary Phone:</label>
        <input type="text" id="first_phone" name="first_phone">

        <label for="second_phone">Secondary Phone:</label>
        <input type="text" id="second_phone" name="second_phone">

        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="regular">Regular</option>
            <option value="vip">VIP</option>
            <option value="banned">Banned</option>
        </select>

        <input type="submit" value="Add Guest">
    </form>
</div>

</body>
</html>