<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Password</title>
<link rel="stylesheet" href="hr.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<div class="container">
  <h2>Update Account</h2>
  <div class="grid">
    <a href="update_password.php" class="module">
      <i class="fas fa-key"></i>
      Change Your Password
    </a>
  </div>
</div>
</body>
</html>
