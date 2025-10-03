<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kitchen & Restaurant Management</title>
  <link rel="stylesheet" href="kitchen.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Kitchen & Restaurant Management</h1>
        <p>Manage recipes, ingredients, orders, kitchen workflow, and customer-reported issues.</p>
      </header>

      <div class="grid">
        <a href="recipe_management/recipes.php" class="module">
          <i class="fas fa-book-open"></i>
          <span>Recipe & Menu Management</span>
        </a>
        <a href="ingredient_tracking/ingredients.php" class="module">
          <i class="fas fa-carrot"></i>
          <span>Ingredient Tracking</span>
        </a>
        <a href="order_queue/orders.php" class="module">
          <i class="fas fa-receipt"></i>
          <span>Order Queue System</span>
        </a>
        <a href="kitchen_display/kitchen_display.php" class="module">
          <i class="fas fa-tv"></i>
          <span>Kitchen Display Integration</span>
        </a>
        <a href="waste_management/waste.php" class="module">
          <i class="fas fa-trash-alt"></i>
          <span>Waste Management</span>
        </a>
        <a href="reports/order_reports.php" class="module">
          <i class="fas fa-exclamation-triangle"></i>
          <span>Order Reports</span>
        </a>
      </div>

      <br>
      <a href="../homepage/index.php" class="module back">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Main Dashboard</span>
      </a>
    </div>
  </div>
</body>
</html>
