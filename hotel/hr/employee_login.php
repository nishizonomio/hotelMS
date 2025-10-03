<?php
session_start();
include 'db.php';

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
        header("Location: http://localhost/hotel/hr/hr_dashboard.php");
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
            header("Location: http://localhost/hotel/hr/hr_dashboard.php");
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
    <link rel="stylesheet" href="hr.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<div class="overlay">
    <div class="container">
        <?php if(!empty($success)): ?>
            <div class="popup"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($showChangePassword && isset($_SESSION['staff_id'])): ?>
            <h2>Change Password</h2>
            <?php if($error) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <label>New Password:</label>
                <div class="password-container">
                    <input type="password" name="new_password" required>
                    <i class="fa-solid fa-eye"></i>
                </div>
                <label>Confirm New Password:</label>
                <div class="password-container">
                    <input type="password" name="confirm_password" required>
                    <i class="fa-solid fa-eye"></i>
                </div>
                <button type="submit" name="change_password">Change Password</button>
            </form>
        <?php else: ?>
            <img src="logo.png" alt="Hotel La Vista Logo" class="logo">
            <?php if($error) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <label>Staff ID:</label>
                <input type="text" name="staff_id" required>
                <label>Password:</label>
                <div class="password-container">
                    <input type="password" name="password" required>
                    <i class="fa-solid fa-eye"></i>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
            <div class="back-btn-container">
                <a href="employee_management/forgot_password.php">Forgot Password?</a>
            </div>
            <p class="back-home">
                <a href="http://localhost/hotel/hr/hr_dashboard.php">&larr; Back to Homepage</a>
            </p>
        <?php endif; ?>
    </div>
</div>
<script>
document.querySelectorAll('.password-container').forEach(container => {
    const input = container.querySelector('input');
    const toggle = container.querySelector('i');
    toggle.addEventListener('click', () => {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        toggle.classList.toggle('fa-eye-slash');
    });
});
</script>
</body>
</html>
