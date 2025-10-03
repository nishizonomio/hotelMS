<?php
session_start();
include '../db.php';

$error = '';
$step = 1;

if(isset($_POST['send_otp'])) {
    $staff_id = trim($_POST['staff_id']);
    $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ? LIMIT 1");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $_SESSION['staff_id'] = $staff_id;
        $_SESSION['otp'] = rand(100000, 999999);
        $step = 2;
    } else {
        $error = "Employee ID not found.";
    }
} elseif(isset($_POST['verify_otp'])) {
    $otp_input = trim($_POST['otp']);
    if(isset($_SESSION['otp']) && $otp_input == $_SESSION['otp']){
        $stmt = $conn->prepare("UPDATE staff SET password = 'temp123' WHERE staff_id = ?");
        $stmt->bind_param("s", $_SESSION['staff_id']);
        $stmt->execute();

        $_SESSION['force_change'] = true;
        unset($_SESSION['otp']);
        header("Location: employee_login.php");
        exit;
    } else {
        $error = "Invalid OTP. Please try again.";
        $step = 2;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/forgot_password.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <h1>Forgot Password</h1>
      <?php if($error): ?>
          <div class="message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if($step === 1): ?>
          <form method="POST" action="">
              <label for="staff_id">Employee ID:</label>
              <input type="text" id="staff_id" name="staff_id" required>
              <button type="submit" name="send_otp">Send OTP</button>
          </form>
          <a href="employee_login.php" class="back-link">&larr; Back to Login</a>
      <?php else: ?>
          <p>A verification code has been sent to your registered email/phone.</p>
          <p style="color:#800000;font-weight:bold;">(Dummy OTP: <?= $_SESSION['otp'] ?>)</p>
          <form method="POST" action="">
              <label for="otp">Enter OTP:</label>
              <input type="text" id="otp" name="otp" required>
              <button type="submit" name="verify_otp">Verify & Reset Password</button>
          </form>
          <a href="employee_login.php" class="back-link">&larr; Back to Login</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
