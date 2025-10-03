<?php
session_start();
include '../db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}

$employee_id = $_SESSION['staff_id'];
$message = '';

function validate_password($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) return "Password must include at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) return "Password must include at least one lowercase letter.";
    if (!preg_match('/[0-9]/', $password)) return "Password must include at least one number.";
    if (!preg_match('/[\W]/', $password)) return "Password must include at least one special character.";
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $message = "New password and confirmation do not match.";
    } else {
        $validation = validate_password($new);
        if ($validation) {
            $message = $validation;
        } else {
            $stmt = $conn->prepare("SELECT password FROM staff WHERE staff_id=?");
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result || !password_verify($current, $result['password'])) {
                $message = "Current password is incorrect.";
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE staff SET password=? WHERE staff_id=?");
                $update->bind_param("ss", $hash, $employee_id);
                if ($update->execute()) {
                    $message = "Password updated successfully.";
                } else {
                    $message = "Failed to update password. Try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f0e6;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 50px;
    margin: 0;
}

.back-btn {
    position: fixed;
    top: 20px;
    left: 20px;
    text-decoration: none;
    color: #800000;
    background: #fff8f0;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 600;
    border: 2px solid #800000;
    transition: background 0.3s, color 0.3s;
    z-index: 1000;
}

.back-btn:hover {
    background-color: #800000;
    color: #fff;
}

.container {
    background-color: #fff8f0;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    width: 350px;
    text-align: center;
    border: 2px solid #800000;
}

h1 {
    margin-bottom: 25px;
    color: #800000;
}

label {
    display: block;
    text-align: left;
    margin-bottom: 5px;
    color: #800000;
    font-size: 14px;
}

input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    border: 1px solid #800000;
    font-size: 14px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px;
    background-color: #800000;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s;
}

button:hover {
    background-color: #5a0000;
}

.message {
    text-align: center;
    font-weight: bold;
    color: red;
    margin-bottom: 15px;
}

.requirements {
    font-size: 13px;
    color: #555;
    margin-bottom: 15px;
    text-align: left;
}
</style>
</head>
<body>
<a href="homepage.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
<div class="container">
<h1>Change Password</h1>

<?php if($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post">
    <label>Current Password:</label>
    <input type="password" name="current_password" placeholder="Current Password" required>
    <label>New Password:</label>
    <input type="password" name="new_password" placeholder="New Password" required>
    <div class="requirements">
        Password must be at least 8 characters and include uppercase, lowercase, number, and special character.
    </div>
    <label>Confirm New Password:</label>
    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
    <button type="submit">Update Password</button>
</form>
</div>
</body>
</html>
