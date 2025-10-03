<?php
session_start();
include '../db.php';

$showChangePassword = false;
$error = null;
$success = null;

if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: employee_login.php");
    exit;
}

function isValidPassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $password);
}

if (isset($_POST['change_password']) && isset($_SESSION['staff_id'])) {
    $staff_id = $_SESSION['staff_id'];
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if ($new !== $confirm) {
        $error = "New password and confirm password do not match.";
        $showChangePassword = true;
    } elseif (!isValidPassword($new)) {
        $error = "Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
        $showChangePassword = true;
    } else {
        $stmt = $conn->prepare("UPDATE staff SET password = ? WHERE staff_id = ?");
        $stmt->bind_param("ss", $new, $staff_id);
        $stmt->execute();
        $success = "Password successfully changed.";
        $showChangePassword = false;
        header("Location: homepage.php");
        exit;
    }
}

if (isset($_POST['login'])) {
    $staff_id = trim($_POST['staff_id']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ? LIMIT 1");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error = "Invalid Employee ID or Password.";
    } elseif ($password === $user['password'] || ($user['password'] === 'temp123' && $password === 'temp123')) {
        $_SESSION['staff_id'] = $user['staff_id'];
        if ($user['password'] === 'temp123') {
            $showChangePassword = true;
        } else {
            header("Location: homepage.php");
            exit;
        }
    } else {
        $error = "Invalid Employee ID or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hotel La Vista Login</title>
    <link rel="stylesheet" href="../css/employee_login.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Quicksand', sans-serif;
        }
    </style>
</head>
<body>
<div class="overlay">
    <div class="container">
        <?php if(!empty($success)): ?>
            <div class="popup"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($showChangePassword && isset($_SESSION['staff_id'])): ?>
            <h2>Change Password</h2>
            <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <label>New Password:</label>
                <input type="password" name="new_password" required>
                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password" required>
                <button type="submit" name="change_password">Change Password</button>
            </form>
        <?php else: ?>
        <img src="logo.png" alt="Hotel La Vista Logo" class="logo">
            <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <label>Staff ID:</label>
                <input type="text" name="staff_id" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit" name="login">Login</button>
            </form>
            <div class="back-btn-container">
                <a href="employee_management/forgot_password.php">Forgot Password?</a>
            </div>
            <p style="margin-top:15px; color:#800000;">
                <a href="http://localhost/hotel/homepage/index.php" style="color:#800000; text-decoration:none;">&larr; Back to Homepage</a>
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
