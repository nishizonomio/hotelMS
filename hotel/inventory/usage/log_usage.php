<?php
session_start();
require '../db.php';

$items = $pdo->query("SELECT item_name, SUM(quantity_in_stock) AS total_stock FROM inventory GROUP BY item_name")->fetchAll(PDO::FETCH_ASSOC);
$staffMembers = $pdo->query("SELECT staff_id, CONCAT(first_name, ' ', last_name) AS full_name FROM staff ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name']);
    $used_by = trim($_POST['used_by']);
    $used_qty = max(1, (int)$_POST['used_qty']);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT item_id, quantity_in_stock, used_qty FROM inventory WHERE item_name = :item_name ORDER BY item_id ASC FOR UPDATE");
    $stmt->execute([':item_name' => $item_name]);
    $inventoryRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_stock = array_sum(array_column($inventoryRows, 'quantity_in_stock'));
    if ($used_qty > $total_stock) $used_qty = $total_stock;

    $remaining = $used_qty;
    foreach ($inventoryRows as $row) {
        if ($remaining <= 0) break;
        $deduct = min($row['quantity_in_stock'], $remaining);

        $pdo->prepare("INSERT INTO stock_usage (item_id, used_qty, used_by, date_used) VALUES (:item_id, :used_qty, :used_by, NOW())")
            ->execute([':item_id' => $row['item_id'], ':used_qty' => $deduct, ':used_by' => $used_by]);

        $pdo->prepare("UPDATE inventory SET quantity_in_stock = quantity_in_stock - :deduct, used_qty = used_qty + :deduct WHERE item_id = :item_id")
            ->execute([':deduct' => $deduct, ':item_id' => $row['item_id']]);

        $remaining -= $deduct;
    }

    $pdo->commit();
    header("Location: stock_usage.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Log Stock Usage</title>
<link rel="stylesheet" href="log_usage.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Log Stock Usage</h1>
      <p>Fill out the details below to record a new stock usage.</p>
    </header>

    <form method="POST" class="add-form">
      <select name="item_name" required onchange="updateStock(this)">
        <option value="">Select Item</option>
        <?php foreach($items as $i): ?>
          <option value="<?= htmlspecialchars($i['item_name']) ?>" data-stock="<?= $i['total_stock'] ?>">
            <?= htmlspecialchars($i['item_name']) ?> (In Stock: <?= $i['total_stock'] ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <select name="used_by" required>
        <option value="">Select Staff</option>
        <?php foreach($staffMembers as $staff): ?>
          <option value="<?= htmlspecialchars($staff['full_name']) ?>"><?= htmlspecialchars($staff['full_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="number" name="used_qty" placeholder="Quantity Used" min="1" value="1" required>
      <input type="number" id="quantity_in_stock" readonly placeholder="Current Stock">

      <div class="form-buttons">
        <button type="submit"><i class="fas fa-plus"></i> Submit</button>
        <a href="stock_usage.php">
          <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
        </a>
      </div>
    </form>
  </div>
</div>

<script>
function updateStock(select) {
    const stock = select.options[select.selectedIndex].dataset.stock || 0;
    document.getElementById('quantity_in_stock').value = stock;
}
</script>
</body>
</html>
