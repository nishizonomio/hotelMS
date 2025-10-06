<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hotel La Vista - POS</title>
  <link rel="stylesheet" href="pos.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Hotel La Vista</h1>
        <p>POINT OF SALES DASHBOARD</p>
      </header>

      <div class="grid top-row">
        <a href="restaurant/restaurant_pos.php" class="module">
          <i class="fas fa-utensils"></i>
          <span>Restaurant</span>
        </a>
        <a href="giftstore/giftstore_pos.php" class="module">
          <i class="fas fa-gift"></i>
          <span>Gift Store</span>
        </a>
        <a href="minibar/minibar_pos.php" class="module">
          <i class="fas fa-wine-bottle"></i>
          <span>Mini Bar</span>
        </a>
      </div>

      <div class="grid bottom-row">
        <a href="loungebar/loungebar_pos.php" class="module">
          <i class="fas fa-cocktail"></i>
          <span>Lounge Bar</span>
        </a>
        <a href="settings/settings.php" class="module">
          <i class="fas fa-cog"></i>
          <span>Settings</span>
        </a>
        <a href="salesreport/daily_sales_report.php" class="module">
          <i class="fas fa-chart-line"></i>
          <span>Daily Sales Report</span>
        </a>
        <a href="menu/menu_dashboard.php" class="module">
          <i class="fas fa-book-open"></i>
          <span>Items & Menu</span>
        </a>
      </div>

      <a href="../homepage/index.php" class="module back">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Main Dashboard</span>
      </a>
    </div>
  </div>
</body>
</html>
