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
<title>Finish Assessments</title>
<link rel="stylesheet" href="hr.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<div class="container">
  <h2>Finish Assessments</h2>
  <div class="grid">
    <a href="assessments.php" class="module">
      <i class="fas fa-clipboard-check"></i>
      Finish Assessments
    </a>
  </div>
</div>
</body>
</html>
