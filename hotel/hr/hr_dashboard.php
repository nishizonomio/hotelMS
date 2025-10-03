<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../employee_management/employee_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hotel La Vista</title>
  <link rel="stylesheet" href="css/hr_dashboard.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Welcome to Hotel La Vista!</h1>
        <p>Your one-stop dashboard for complete Hotel Management</p>
      </header>

      <div class="grid">
        <a href="recruitment/recruitment.php" class="module">
          <i class="fas fa-clipboard-list"></i>
          <span>HR Recruitment</span>
        </a>
         <a href="http://localhost/hotel/hr/employee_management/hr_employee_management.php" class="module">
          <i class="fas fa-users-cog"></i>
          <span>Employee Management</span>
        </a>
        <a href="http://localhost/hotel/hr/payroll/payroll.php" class="module">
          <i class="fas fa-money-bill-wave"></i>
          <span>Payroll</span>
        </a>
      </div>
      <div class="back-btn-container">
        <a href="/hotel/homepage/index.php" class="back-btn">
          <i class="fas fa-arrow-left"></i>
          Back to Main Dashboard
        </a>
      </div>
    </div>
  </div>
</body>
</html>
