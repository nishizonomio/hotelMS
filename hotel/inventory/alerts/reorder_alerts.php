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

$reorder_items = array_map(fn($i) => $i['item_name'], array_slice($items, 0, 3));
if ($reorder_items) {
    $reorder_text = implode(", ", $reorder_items);
    if (count($items) > 3) {
        $reorder_text .= " and more...";
    }
} else {
    $reorder_text = "No items need to be reordered.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reorder Alerts</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="reorder_alerts.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Reorder Alerts</h1>
      <p>Low Stocks: <?= htmlspecialchars($reorder_text) ?></p>
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
