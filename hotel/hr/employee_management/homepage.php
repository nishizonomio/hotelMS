<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Portal - Hotel La Vista</title>
  <link rel="stylesheet" href="../css/homepage.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <div class="header-text">
        <h1>Hotel La Vista</h1>
        <p>Employee Portal</p>
      </div>

      <h2>Check Your Profile</h2>
      <div class="grid">
        <a href="employee_profile.php" class="module">
          <i class="fas fa-id-badge"></i>
          <span>View & Update Your Info</span>
        </a>
        <a href="my_department.php" class="module">
          <i class="fas fa-sitemap"></i>
          <span>Departments & Teams</span>
        </a>
      </div>

      <h2>Manage Your Attendance</h2>
      <div class="grid">
        <a href="your_attendance.php" class="module" style="grid-column: span 2;">
          <i class="fas fa-calendar-check"></i>
          <span>Attendance & Timesheets</span>
        </a>
      </div>

      <h2>Complete Onboarding Tasks</h2>
      <div class="grid">
        <a href="my_documents.php" class="module">
          <i class="fas fa-folder-open"></i>
          <span>Submit Required Documents</span>
        </a>
        <a href="attend_orientation.php" class="module">
          <i class="fas fa-chalkboard-teacher"></i>
          <span>Orientation</span>
        </a>
      </div>

      <h2>Request Leaves or Updates</h2>
      <div class="grid">
        <a href="file_leave.php" class="module">
          <i class="fas fa-plane-departure"></i>
          <span>Submit Leave Request</span>
        </a>
        <a href="update_password.php" class="module">
          <i class="fas fa-key"></i>
          <span>Change Your Password</span>
        </a>
      </div>

      <div class="grid back-module-grid">
  <a href="http://localhost/hotel/hr/employee_login.php" class="module">
    <i class="fas fa-arrow-left"></i> HR Admin
  </a>
  <a href="employee_login.php?logout=1" class="module">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</div>
      </div>
    </div>
  </div>
</body>
</html>
