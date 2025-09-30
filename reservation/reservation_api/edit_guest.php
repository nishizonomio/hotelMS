<?php
include '../../db_connect.php';
include 'popup_message.php';

// Get guest information if ID is provided
if (isset($_GET['id'])) {
    $guest_id = $_GET['id'];
    $sql = "SELECT * FROM guests WHERE guest_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $guest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $guest = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guest_id = $_POST['guest_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $first_phone = $_POST['first_phone'];
    $second_phone = $_POST['second_phone'];
    $status = $_POST['status'];

    $sql = "UPDATE guests SET 
            first_name = ?,
            last_name = ?,
            email = ?,
            first_phone = ?,
            second_phone = ?,
            status = ?,
            updated_at = NOW()
            WHERE guest_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $first_phone, $second_phone, $status, $guest_id);

    if ($stmt->execute()) {
        showPopupMessage("Guest information updated successfully!");
    } else {
        showPopupMessage("❌ Error updating guest information: " . $conn->error, "error");
    }
}

// Fetch all guests for the selection dropdown
$all_guests_sql = "SELECT guest_id, first_name, last_name FROM guests ORDER BY first_name";
$all_guests_result = $conn->query($all_guests_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Guest Information</title>
    <link rel="stylesheet" href="../reservation_css/base.css">
    <link rel="stylesheet" href="../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<a href="guest_profile_management/guest_profile_management.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
    <h2>Edit Guest Information</h2>
    
    <!-- Guest Selection Form -->
    <?php if (!isset($_GET['id'])): ?>
    <form method="get" action="edit_guest.php">
        <label for="id">Select Guest:</label>
        <select name="id" id="id" required onchange="this.form.submit()">
            <option value="">-- Select a Guest --</option>
            <?php while($guest_row = $all_guests_result->fetch_assoc()): ?>
                <option value="<?php echo $guest_row['guest_id']; ?>">
                    <?php echo htmlspecialchars($guest_row['first_name'] . ' ' . $guest_row['last_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
    <?php endif; ?>

    <!-- Edit Form -->
    <?php if (isset($guest)): ?>
    <form method="post" action="edit_guest.php">
        <input type="hidden" name="guest_id" value="<?php echo $guest['guest_id']; ?>">
        
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($guest['first_name']); ?>" required>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($guest['last_name']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($guest['email']); ?>">

        <label for="first_phone">Primary Phone:</label>
        <input type="text" id="first_phone" name="first_phone" value="<?php echo htmlspecialchars($guest['first_phone']); ?>">

        <label for="second_phone">Secondary Phone:</label>
        <input type="text" id="second_phone" name="second_phone" value="<?php echo htmlspecialchars($guest['second_phone']); ?>">

        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="regular" <?php echo $guest['status'] == 'regular' ? 'selected' : ''; ?>>Regular</option>
            <option value="vip" <?php echo $guest['status'] == 'vip' ? 'selected' : ''; ?>>VIP</option>
            <option value="banned" <?php echo $guest['status'] == 'banned' ? 'selected' : ''; ?>>Banned</option>
        </select>

        <input type="submit" value="Update Guest Information">
    </form>
    <?php endif; ?>
</div>

</body>
</html>
