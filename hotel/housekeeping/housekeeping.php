  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Housekeeping | Hotel La Vista</title>
    <link rel="stylesheet" href="/hotel/homepage/index.css">
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
          <a href="room_status/room_status.php" class="module">
            <i class="fas fa-bed"></i>
            <span>Room Status Dashboard</span>
          </a>
          <a href="task_assignment/tasks.php" class="module">
            <i class="fas fa-clipboard-list"></i>
            <span>Room Cleaning Task</span>
          </a>
          <a href="supplies_inventory/housekeeping_inventory.php" class="module">
            <i class="fas fa-boxes"></i>
            <span>Inventory of Supplies</span>
          </a>
          <a href="maintenance_requests/maintenance_requests.php" class="module">
            <i class="fas fa-tools"></i>
            <span>Maintenance Requests</span>
          </a>
          <a href="staff_performance/hp_performance.php" class="module">
            <i class="fas fa-user-check"></i>
            <span>Staff Performance Tracking</span>
          </a>
        </div>

        <footer>
          <br><br>
        <a href="/hotel/homepage/index.php" class="module" style="display:inline-block; background:rgba(255,255,255,0.15);">
          <i class="fas fa-arrow-left"></i>
          <span>Back to Main Dashboard</span>
        </a>

          </a>
        </footer>
      </div>
    </div>
  </body>
  </html>
