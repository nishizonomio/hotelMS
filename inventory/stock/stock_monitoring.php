<?php
require '../db.php';

$search_item = $_GET['item_name'] ?? '';
$search_category = $_GET['category'] ?? '';

$categories = [
  'Hotel Supplies',
  'Foods & Beverages',
  'Cleaning & Sanitation',
  'Utility Products',
  'Office Supplies',
  'Kitchen Equipment',
  'Furniture & Fixtures',
  'Laundry & Linen',
  'Others'
];

$conditions = [];
$params = [];

if ($search_item) {
    $conditions[] = "i.item_name LIKE :item_name";
    $params[':item_name'] = "%$search_item%";
}

if ($search_category) {
    $conditions[] = "i.category = :category";
    $params[':category'] = $search_category;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
    SELECT 
        i.item_id,
        i.item_name,
        i.category,
        SUM(COALESCE(i.quantity_in_stock,0)) AS quantity_in_stock,
        SUM(COALESCE(i.used_qty,0)) AS used_qty,
        SUM(COALESCE(i.wasted_qty,0)) AS wasted_qty,
        AVG(COALESCE(i.unit_price,0)) AS unit_price,
        SUM(COALESCE(i.quantity_in_stock,0) * COALESCE(i.unit_price,0)) AS total_cost,
        GROUP_CONCAT(DISTINCT NULLIF(TRIM(i.inspected_by), '') SEPARATOR ', ') AS inspected_by
    FROM inventory i
    $where
    GROUP BY i.item_id, i.item_name, i.category
    ORDER BY i.item_name ASC
";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalQuery = "
    SELECT 
        SUM(COALESCE(quantity_in_stock,0)) AS total_quantity,
        SUM(COALESCE(used_qty,0)) AS total_used,
        SUM(COALESCE(wasted_qty,0)) AS total_wasted
    FROM inventory
";
$totalStmt = $pdo->query($totalQuery);
$total = $totalStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Monitoring</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body, html {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('/hotel/homepage/hotel_room.jpg') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #fff;
}

.overlay {
    background-color: rgba(0,0,0,0.88);
    min-height: 100vh;
    padding: 40px 20px;
    box-sizing: border-box;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
}

header {
    text-align: center;
    margin-bottom: 30px;
}

h1 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 32px;
    font-weight: 600;
}

p {
    text-align: center;
    margin-bottom: 30px;
    font-size: 16px;
    color: #ccc;
}

.search-container {
    width: 95%;
    margin: 0 auto 20px;
    text-align: center;
}

.search-form {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.search-form input,
.search-form select,
.search-form button {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    font-size: 14px;
}

.search-form input {
    background: rgba(255,255,255,0.15);
    color: #fff;
}

.search-form input::placeholder {
    color: #ddd;
}

.search-form select {
    background: rgba(255,255,255,0.9);
    color: #333;
}

.search-form button {
    background: #FF9800;
    color: #fff;
    cursor: pointer;
    transition: background 0.3s;
}

.search-form button:hover { 
    background: #e67e22; 
}

.search-container a button {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    font-size: 14px;
    background: #FF9800;
    color: #fff;
    cursor: pointer;
    transition: background 0.3s;
    margin-right: 10px;
}

.search-container a button:hover {
    background: #e67e22;
}

table {
    margin: 20px auto;
    border-collapse: separate;
    border-spacing: 0;
    width: 95%;
    background: #23272f;
    color: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 18px rgba(0,0,0,0.15);
    opacity: 0.95;
}

th, td {
    padding: 14px 12px;
    text-align: center;
    font-size: 15px;
    border: none;
}

th {
    background: #303642;
    font-weight: 700;
    font-size: 16px;
    color: #FF9800;
}

tr:hover td {
    background: #2e3440;
    transition: background 0.2s;
}

.low-stock {
    color: #e74c3c;
    font-weight: 700;
}

.avg-stock {
    color: #f1c40f;
    font-weight: 700;
}

.high-stock {
    color: #27ae60;
    font-weight: 700;
}

@media (max-width: 1200px) {
    table, thead, tbody, th, td, tr { 
        display: block; 
    }
    thead { 
        display: none; 
    }
    tr { 
        background: #222; 
        margin-bottom: 15px; 
        border-radius: 12px; 
        box-shadow: 0 1px 6px rgba(0,0,0,0.08); 
        padding: 15px;
    }
    td { 
        text-align: right; 
        padding: 8px 15px; 
        position: relative; 
        border-bottom: 1px solid #333;
    }
    td:last-child {
        border-bottom: none;
    }
    td:before { 
        position: absolute; 
        left: 15px; 
        top: 8px; 
        white-space: nowrap; 
        font-weight: bold; 
        color: #FF9800; 
        content: attr(data-label) ": "; 
        font-size: 14px; 
        text-align: left; 
        width: 40%;
    }
}

@media (max-width: 768px) {
    .overlay {
        padding: 20px 10px;
    }
    
    h1 {
        font-size: 28px;
    }
    
    .search-form {
        flex-direction: column;
        gap: 10px;
    }
    
    .search-form input,
    .search-form select,
    .search-form button {
        width: 100%;
        max-width: 300px;
    }
}
</style>
<script>
setInterval(() => {
    window.location.reload();
}, 30000); // Changed to 30 seconds for better user experience
</script>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Stock Monitoring</h1>
      <p>Total quantity in inventory: <?= (int)$total['total_quantity'] ?> | Total used: <?= (int)$total['total_used'] ?> | Total wasted: <?= (int)$total['total_wasted'] ?></p>
    </header>

    <div class="search-container">
      <a href="../inventory.php">
        <button type="button"><i class="fas fa-arrow-left"></i> Back to Inventory</button>
      </a>
      <form method="GET" class="search-form">
        <input type="text" name="item_name" placeholder="Search by item name" value="<?= htmlspecialchars($search_item) ?>">
        <select name="category">
          <option value="">All Categories</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($search_category == $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Search</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Item ID</th>
          <th>Item Name</th>
          <th>Category</th>
          <th>Total Quantity</th>
          <th>Used Quantity</th>
          <th>Wasted Quantity</th>
          <th>Unit Price (Avg)</th>
          <th>Total Cost</th>
          <th>Inspected By</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item):
          $qty = (int)$item['quantity_in_stock'];
          $used_qty = (int)($item['used_qty'] ?? 0);
          $wasted_qty = (int)($item['wasted_qty'] ?? 0);
          $qtyClass = $qty <= 10 ? 'low-stock' : ($qty <= 50 ? 'avg-stock' : 'high-stock');

          $category = $item['category'] ?: 'Uncategorized';
          $rawInspected = $item['inspected_by'] ?? '';
          $inspectedArr = array_values(array_filter(array_map('trim', explode(',', $rawInspected)), fn($v)=>$v !== ''));
          $inspected = $inspectedArr ? implode(', ', $inspectedArr) : '-';
      ?>
        <tr>
          <td data-label="Item ID"><?= htmlspecialchars($item['item_id']) ?></td>
          <td data-label="Item Name"><?= htmlspecialchars($item['item_name']) ?></td>
          <td data-label="Category"><?= htmlspecialchars($category) ?></td>
          <td data-label="Total Quantity" class="<?= $qtyClass ?>"><?= $qty ?></td>
          <td data-label="Used Quantity"><?= $used_qty ?></td>
          <td data-label="Wasted Quantity"><?= $wasted_qty ?></td>
          <td data-label="Unit Price">₱<?= number_format((float)$item['unit_price'],2) ?></td>
          <td data-label="Total Cost">₱<?= number_format((float)$item['total_cost'],2) ?></td>
          <td data-label="Inspected By"><?= htmlspecialchars($inspected) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>