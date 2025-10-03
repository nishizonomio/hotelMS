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
  <title>HR Dashboard - Hotel La Vista</title>
  <link rel="stylesheet" href="../css/hr_employee_management.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <div class="header-text">
      <h1>Hotel La Vista</h1>
      <p>HR Dashboard</p>
    </div>

    <h2>Employee Profiles</h2>
    <div class="grid">
      <a href="employee_management.php" class="module">
        <i class="fas fa-user-tie"></i>
        <span>Employee Management</span>
      </a>
      <a href="employee_login.php" class="module">
        <i class="fas fa-sign-in-alt"></i>
        <span>Employee Portal</span>
      </a>
    </div>

    <h2>Attendance Tracking</h2>
    <div class="grid">
      <a href="attendance.php" class="module">
        <i class="fas fa-calendar-check"></i>
        <span>Attendance & Timesheets</span>
      </a>
      <a href="overtime.php" class="module">
        <i class="fas fa-clock"></i>
        <span>Overtime & Shifts</span>
      </a>
    </div>

    <h2>Employee Onboarding</h2>
    <div class="grid">
      <a href="orientation.php" class="module">
        <i class="fas fa-chalkboard-teacher"></i>
        <span>Orientation</span>
      </a>
      <a href="document_submission.php" class="module">
        <i class="fas fa-folder-open"></i>
        <span>Document Submission</span>
      </a>
    </div>

    <h2>Job & Department Management</h2>
    <div class="grid">
      <a href="departments.php" class="module">
        <i class="fas fa-sitemap"></i>
        <span>Departments & Teams</span>
      </a>
      <a href="leave_request.php" class="module">
        <i class="fas fa-plane-departure"></i>
        <span>Staff Leave Requests</span>
      </a>
    </div>

    <div class="grid">
      <a href="../hr_dashboard.php" class="module back-module">
        <i class="fas fa-arrow-left"></i>
        <span>Back to HR Dashboard</span>
      </a>
    </div>
  </div>
</div>
</body>
</html>
