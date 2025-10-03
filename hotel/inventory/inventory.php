<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inventory & Procurement</title>
  <link rel="stylesheet" href="inventory.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Inventory & Procurement Management</h1>
        <p>Monitor stock, manage suppliers, and track procurement in real-time.</p>
      </header>

      <div class="grid">
        <a href="stock/stock_monitoring.php" class="module">
          <i class="fas fa-boxes"></i>
          <span>Stock Monitoring</span>
        </a>
        <a href="purchases/purchase_orders.php" class="module">
          <i class="fas fa-file-invoice"></i>
          <span>Purchase Orders</span>
        </a>
        <a href="supplier/suppliers.php" class="module">
          <i class="fas fa-truck"></i>
          <span>Supplier Management</span>
        </a>
        <a href="alerts/reorder_alerts.php" class="module">
          <i class="fas fa-exclamation-triangle"></i>
          <span>Reorder Alerts</span>
        </a>
        <a href="usage/stock_usage.php" class="module">
          <i class="fas fa-chart-bar"></i>
          <span>Stock Usage Reports</span>
        </a>
        <a href="grn/grn.php" class="module">
          <i class="fas fa-receipt"></i>
          <span>Goods Received Note (GRN)</span>
        </a>
      </div>

      <a href="/hotel/homepage/index.php" class="module back">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Main Dashboard</span>
      </a>
    </div>
  </div>
</body>
</html>
