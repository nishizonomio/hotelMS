<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Housekeeping | Hotel La Vista</title>
  <link rel="stylesheet" href="../homepage/index.css"> 
  <link rel="stylesheet" href="css/housekeeping.css"> 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Housekeeping Management</h1>
        <p>Manage room status, tasks, supplies, and staff performance.</p>
      </header>

      <div class="grid">
        <a href="rooms_status/room_status.php" class="module card">
          <i class="fas fa-bed fa-2x"></i>
          <span>Room Status</span>
        </a>
        <a href="task_assignment/tasks.php" class="module card">
          <i class="fas fa-clipboard-list fa-2x"></i>
          <span>Task Assignment</span>
        </a>
        <a href="supplies_inventory/hp_inventory.php" class="module card">
          <i class="fas fa-soap fa-2x"></i>
          <span>Supplies Inventory</span>
        </a>
        <a href="maintenance_request/hp_maintenance.php" class="module card">
          <i class="fas fa-tools fa-2x"></i>
          <span>Maintenance Requests</span>
        </a>
        <a href="staff_performance/hp_performance.php" class="module card">
          <i class="fas fa-user-check fa-2x"></i>
          <span>Staff Performance</span>
        </a>
      </div>

      <footer>
        <a href="../homepage/index.php" class="back-btn">
          <i class="fas fa-arrow-left"></i> Back to Home
        </a>
      </footer>
    </div>
  </div>
</body>
</html>
