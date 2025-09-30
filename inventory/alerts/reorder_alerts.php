<?php 
require '../db.php';

if (!isset($pdo)) {
    die("Database connection failed.");
}

$conditions = [];

if (!empty($_POST['item_name'])) {
    $item_name = $_POST['item_name'];
    $conditions[] = "item_name LIKE :item_name";
}

$low_stock_threshold = 10;

$query = "SELECT item_id, item_name, quantity_in_stock, unit_price
          FROM inventory
          WHERE quantity_in_stock <= :threshold"
          . ($conditions ? " AND " . implode(" AND ", $conditions) : "")
          . " ORDER BY item_name ASC";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':threshold', $low_stock_threshold, PDO::PARAM_INT);

if (!empty($_POST['item_name'])) {
    $stmt->bindValue(':item_name', "%$item_name%");
}

$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reorder_items = array_map(fn($i) => $i['item_name'], $items);
$reorder_text = $reorder_items ? implode(", ", $reorder_items) : "No items need to be reordered.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reorder Alerts</title>
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
    max-width: 1200px;
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

.add-form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.add-form input, .add-form select, .add-form button {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    font-size: 14px;
}

.add-form button {
    background-color: #FF9800;
    color: #fff;
    cursor: pointer;
    transition: background 0.3s;
}

.add-form button:hover { 
    background-color: #e67e22; 
}

.edit-btn,
.delete-btn {
    padding: 6px 10px;
    border-radius: 6px;
    border: none;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.3s;
    margin: 2px 0;
    width: 80px;
}

.edit-btn {
    background-color: #FF9800;
    color: #fff;
}

.edit-btn:hover {
    background-color: #e67e22;
}

.delete-btn {
    background-color: #888;
    color: #fff;
}

.delete-btn:hover {
    background-color: #555;
}

td[data-label="Actions"] {
    display: flex;
    justify-content: center;
    gap: 6px;
}
</style>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Reorder Alerts</h1>
      <p>Items that need to be reordered: <?= htmlspecialchars($reorder_text) ?></p>
    </header>

    <div class="search-container">
      <form method="POST" class="search-form">
        <a href="/hotel/inventory/inventory.php">
            <button type="button"><i class="fas fa-arrow-left"></i> Back to Inventory</button>
        </a>
        <input type="text" name="item_name" placeholder="Search by item" value="<?= htmlspecialchars($_POST['item_name'] ?? '') ?>">
        <button type="submit" name="search">🔍 Search</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Item Name</th>
          <th>Stock</th>
          <th>Unit Price</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr class="low-stock">
          <td data-label="ID"><?= htmlspecialchars($item['item_id']) ?></td>
          <td data-label="Item Name"><?= htmlspecialchars($item['item_name']) ?></td>
          <td data-label="Stock"><?= htmlspecialchars($item['quantity_in_stock']) ?></td>
          <td data-label="Unit Price"><?= htmlspecialchars(number_format($item['unit_price'], 2)) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>