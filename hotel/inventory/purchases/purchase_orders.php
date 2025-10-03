<?php
require '../db.php';

$search_item = $_GET['item_name'] ?? '';
$search_status = $_GET['status'] ?? '';
$statuses = ['Pending', 'Approved', 'Rejected', 'Received', 'Completed'];

$conditions = [];
$params = [];

if ($search_item) {
    $conditions[] = "po.item_name LIKE :item_name";
    $params[':item_name'] = "%$search_item%";
}

if ($search_status) {
    $conditions[] = "po.status = :status";
    $params[':status'] = $search_status;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
SELECT po.*, s.supplier_name, g.quantity_received, g.condition_status, g.date_received
FROM purchase_orders po
LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
LEFT JOIN grn g ON po.po_number = g.po_number
$where
ORDER BY po.order_date DESC
";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$latestReceived = $pdo->query("
    SELECT po.item_name, s.supplier_name, g.date_received 
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN grn g ON po.po_number = g.po_number
    WHERE g.date_received IS NOT NULL
    ORDER BY g.date_received DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Orders</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="purchase_orders.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Purchase Orders</h1>
      <p>
        Most recent order received: 
        <?php if ($latestReceived): ?>
          <strong><?= htmlspecialchars($latestReceived['item_name']) ?></strong> 
          from <strong><?= htmlspecialchars($latestReceived['supplier_name'] ?? 'Unknown') ?></strong> 
          on <strong><?= date('M j, Y', strtotime($latestReceived['date_received'])) ?></strong>
        <?php else: ?>
          —
        <?php endif; ?>
      </p>
    </header>

    <div class="search-container">
      <a href="/hotel/inventory/inventory.php">
        <button type="button"><i class="fas fa-arrow-left"></i> Back to Inventory</button>
      </a>
      <a href="add_purchase_orders.php">
        <button type="button"><i class="fas fa-plus"></i> Create New Order</button>
      </a>
      <form method="GET" class="search-form">
        <input type="text" name="item_name" placeholder="Search by item name" value="<?= htmlspecialchars($search_item) ?>">
        <select name="status">
          <option value="">All Statuses</option>
          <?php foreach($statuses as $status): ?>
            <option value="<?= htmlspecialchars($status) ?>" <?= ($search_status == $status) ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Search & Filter</button>
      </form>
    </div>

    <?php if (!empty($orders)): ?>
    <table>
      <thead>
        <tr>
          <th>PO #</th>
          <th>Supplier</th>
          <th>Item Name</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Total Amount</th>
          <th>Status</th>
          <th>Condition Status</th>
          <th>Order Date</th>
          <th>Received Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orders as $row): ?>
        <tr>
          <td data-label="PO #"><?= htmlspecialchars($row['po_number']) ?></td>
          <td data-label="Supplier"><?= htmlspecialchars($row['supplier_name'] ?? 'Unknown') ?></td>
          <td data-label="Item Name"><?= htmlspecialchars($row['item_name']) ?></td>
          <td data-label="Category"><?= htmlspecialchars($row['category']) ?></td>
          <td data-label="Quantity"><?= htmlspecialchars($row['quantity']) ?></td>
          <td data-label="Total Amount">₱<?= number_format($row['total_amount'],2) ?></td>
          <td data-label="Status">
            <span class="status-badge <?= strtolower($row['status']) ?>">
              <?= ucfirst(htmlspecialchars($row['status'])) ?>
            </span>
          </td>
          <td data-label="Condition Status">
            <span class="condition-badge <?= strtolower($row['condition_status'] ?? 'unknown') ?>">
              <?= ucfirst(htmlspecialchars($row['condition_status'] ?? 'Unknown')) ?>
            </span>
          </td>
          <td data-label="Order Date"><?= date('M j, Y', strtotime($row['order_date'])) ?></td>
          <td data-label="Received Date"><?= !empty($row['date_received']) ? date('M j, Y', strtotime($row['date_received'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-file-invoice fa-3x"></i>
      <h3>No Purchase Orders Found</h3>
      <p>Start by creating your first purchase order to track inventory procurement.</p>
      <a href="add_purchase_orders.php" class="action-btn primary">
        <i class="fas fa-plus"></i> Create First Order
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
