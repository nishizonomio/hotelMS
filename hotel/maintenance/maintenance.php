<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title> Hotel La Vista </title>
  <link rel="stylesheet" href="index.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .header-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      margin-bottom: 24px;
    }
    .back-btn-header {
      position: fixed;
      top: 24px;
      right: 32px;
      background: #fff;
      color: #374151;
      border: 1px solid #e5e7eb;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 2px 8px rgba(59,130,246,0.08);
      transition: background 0.2s, color 0.2s, box-shadow 0.2s;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
      z-index: 1000;
    }
    .back-btn-header:hover {
      background: #f3f4f6;
      color: #2563eb;
      box-shadow: 0 4px 16px rgba(59,130,246,0.12);
    }
     .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 15px;
      background: #111827;
      color: #f9fafb;
      font-size: 10px;
      border-top: 1px solid #374151;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      z-index: 100;
    }
    @media (max-width: 600px) {
      .back-btn-header { right: 8px; top: 8px; padding: 8px 12px; font-size: 14px; }
      .header-bar { flex-direction: column; gap: 10px; }
    }
  </style>
</head>
<body>
  <a href="index.php" class="back-btn-header">
    <i class="fas fa-arrow-left"></i> Back to Home
  </a>
  <div class="overlay">
    <div class="container">
      <div class="header-bar">
        <header style="flex:1;">
          <a href="index.php" style="font-weight: bold; font-size: 60px; color: inherit; text-decoration: none;">
            Maintenance and Engineering
          </a>
          <p>Responsible for upkeep and functionality of all hotel Facilities and Safety Systems</p>
        </header>
      </div>
      <div class="grid">
         <a href="requests.php" class="module">
         <i class="fa-regular fa-circle-user"></i>
          <span>Maintenance Request Logging</span>
        </a>
        <a href="equipment.php" class="module">
         <i class="fa-solid fa-wrench"></i>
          <span>Equipment Assets and Register</span>
        </a>
        <a href="prevention.php" class="module">
          <i class="fa-regular fa-chart-bar"></i>
          <span>Prevention Maintenance Request</span>
        </a>
       <a href="breakdown.php" class="module">
          <i class="fa-solid fa-file"></i>
          <span>Breakdown History and Reporting</span>
        </a>
        <a href="technician.php" class="module">
          <i class="fa-solid fa-user"></i>
          <span>Technician Assignment</span>
        </a>
        <footer class="footer">
  <p> © 2025 Hotel La Vista  | All Rights Reserved</p>
</footer>
      </div>  