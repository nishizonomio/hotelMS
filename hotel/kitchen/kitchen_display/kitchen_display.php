<?php
require_once(__DIR__ . '/../utils/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    if ($new_status === 'ready') {
        $pdo->beginTransaction();

        $stmtOrder = $pdo->prepare("SELECT item_name FROM kitchen_orders WHERE order_id = ?");
        $stmtOrder->execute([$order_id]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $recipes = explode(',', $order['item_name']);
            foreach ($recipes as $item_name) {
                $item_name = trim($item_name);

                $stmtIngredients = $pdo->prepare("
                    SELECT i.item_id, i.quantity_in_stock, i.unit, ig.quantity_needed
                    FROM ingredients ig
                    JOIN inventory i ON i.item_name = ig.ingredient_name
                    JOIN recipes r ON r.id = ig.recipe_id
                    WHERE r.recipe_name = ?
                ");
                $stmtIngredients->execute([$item_name]);
                $ingredients = $stmtIngredients->fetchAll(PDO::FETCH_ASSOC);

                foreach ($ingredients as $ing) {
                    $new_stock = $ing['quantity_in_stock'] - $ing['quantity_needed'];
                    if ($new_stock < 0) $new_stock = 0;
                    $update = $pdo->prepare("UPDATE inventory SET quantity_in_stock = :new_stock, used_qty = used_qty + :used_qty, last_updated = NOW() WHERE item_id = :id");
                    $update->execute([
                        ':new_stock' => $new_stock,
                        ':used_qty' => $ing['quantity_needed'],
                        ':id' => $ing['item_id']
                    ]);
                }
            }
        }

        $stmtUpdate = $pdo->prepare("UPDATE kitchen_orders SET status = :status, updated_at = NOW() WHERE order_id = :order_id");
        $stmtUpdate->execute([':status' => $new_status, ':order_id' => $order_id]);

        $pdo->commit();
    } else {
        $stmt = $pdo->prepare("UPDATE kitchen_orders SET status = :status, updated_at = NOW() WHERE order_id = :order_id");
        $stmt->execute([':status' => $new_status, ':order_id' => $order_id]);
    }
}

$search_table = $_GET['table_number'] ?? '';
$search_status = $_GET['status'] ?? '';
$search_order = $_GET['order_id'] ?? '';
$search_guest = $_GET['guest_name'] ?? '';

$conditions = [];
$params = [];

if ($search_table) {
    $conditions[] = "(
        (ko.order_type = 'restaurant' AND ko.table_number = :table_number) OR
        (ko.order_type = 'room_service' AND ko.room_number = :table_number)
    )";
    $params[':table_number'] = $search_table;
}
if ($search_status) {
    $conditions[] = "ko.status = :status";
    $params[':status'] = strtolower($search_status);
}
if ($search_order) {
    $conditions[] = "ko.order_id = :order_id";
    $params[':order_id'] = $search_order;
}
if ($search_guest) {
    $conditions[] = "ko.guest_name LIKE :guest_name";
    $params[':guest_name'] = "%$search_guest%";
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
  SELECT 
    ko.order_id,
    ko.order_type,
    ko.table_number,
    ko.room_number,
    ko.guest_name,
    ko.item_name AS items,
    ko.status,
    ko.notes,
    ko.total_amount,
    ko.created_at AS order_date
  FROM kitchen_orders ko
  $where
  ORDER BY ko.created_at DESC
";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalQuery = "
  SELECT 
    COUNT(DISTINCT ko.order_id) AS total_orders,
    SUM(total_amount) AS total_amount
  FROM kitchen_orders ko
  $where
";
$totalStmt = $pdo->prepare($totalQuery);
foreach ($params as $k => $v) {
    $totalStmt->bindValue($k, $v);
}
$totalStmt->execute();
$total = $totalStmt->fetch(PDO::FETCH_ASSOC);

$latestTimestamp = !empty($orders) ? strtotime(end($orders)['order_date']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kitchen Order Queue</title>
<link rel="stylesheet" href="kitchen_display.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
  setInterval(() => { window.location.reload(); }, 5000);
</script>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header style="margin-top:10px;">
      <h1>Kitchen Display Integration</h1>
      <p>Total Orders: <?= (int)$total['total_orders'] ?> | Total Amount: ₱<?= number_format((float)$total['total_amount'],2) ?></p>
    </header>

    <div class="search-container" style="display:flex; justify-content:center; flex-wrap:wrap; align-items:center; gap:16px; margin:10px 0 20px 0;">
      <a href="/hotel/kitchen/kitchen.php">
        <button type="button"><i class="fas fa-arrow-left"></i> Back to Kitchen</button>
      </a>
      <form method="GET" class="search-form" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="order_id" placeholder="Search by order ID" value="<?= htmlspecialchars($search_order) ?>" style="background-color:#FFFFFF; color:#000000; border:1px solid #ccc; border-radius:6px; padding:8px 12px; font-weight:600;">
        <input type="text" name="guest_name" placeholder="Search by guest" value="<?= htmlspecialchars($search_guest) ?>" style="background-color:#FFFFFF; color:#000000; border:1px solid #ccc; border-radius:6px; padding:8px 12px; font-weight:600;">
        <select name="table_number" style="background-color:#FFFFFF; color:#000000; border:1px solid #ccc; border-radius:6px; padding:8px 12px; font-weight:600;">
          <option value="">All Tables / Rooms</option>
          <?php
          $tablesRooms = $pdo->query("SELECT DISTINCT table_number, room_number FROM kitchen_orders")->fetchAll(PDO::FETCH_ASSOC);
          foreach ($tablesRooms as $tr):
              $display = $tr['table_number'] ? 'T'.$tr['table_number'] : 'R'.$tr['room_number'];
              $value = $tr['table_number'] ?: $tr['room_number'];
          ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= ($search_table == $value ? 'selected' : '') ?>><?= htmlspecialchars($display) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" style="background-color:#FFFFFF; color:#000000; border:1px solid #ccc; border-radius:6px; padding:8px 12px; font-weight:600;">
          <option value="">All Status</option>
          <option value="preparing" <?= strtolower($search_status) === 'preparing' ? 'selected' : '' ?>>Preparing</option>
          <option value="ready" <?= strtolower($search_status) === 'ready' ? 'selected' : '' ?>>Ready</option>
        </select>
        <button type="submit" style="background-color:#1A237E; color:#FFFFFF; border:none; border-radius:6px; padding:8px 12px; font-weight:600; cursor:pointer;">🔍 Filter</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Table / Room</th>
          <th>Guest</th>
          <th>Items</th>
          <th>Status</th>
          <th>Total Amount</th>
          <th>Notes</th>
          <th>Order Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($orders): ?>
        <?php foreach ($orders as $order):
            $isNew = (strtotime($order['order_date']) >= $latestTimestamp - 60) ? 'new-order' : '';
            $tableRoom = $order['order_type'] === 'restaurant' ? 'T'.$order['table_number'] : 'R'.$order['room_number'];
        ?>
          <tr class="<?= $isNew ?>">
            <td><?= htmlspecialchars($order['order_id']) ?></td>
            <td><?= htmlspecialchars($tableRoom) ?></td>
            <td><?= htmlspecialchars($order['guest_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($order['items'] ?? '-') ?></td>
            <td><?= htmlspecialchars(ucfirst($order['status'])) ?></td>
            <td>₱<?= number_format((float)$order['total_amount'],2) ?></td>
            <td><?= htmlspecialchars($order['notes'] ?? '-') ?></td>
            <td><?= htmlspecialchars($order['order_date']) ?></td>
            <td>
              <form method="POST" style="display:flex; gap:5px;">
                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                <select name="status">
                  <option value="preparing" <?= strtolower($order['status'])==='preparing'?'selected':'' ?>>Preparing</option>
                  <option value="ready" <?= strtolower($order['status'])==='ready'?'selected':'' ?>>Ready</option>
                  <option value="completed" <?= strtolower($order['status'])==='completed'?'selected':'' ?>>Completed</option>
                </select>
                <button type="submit" name="update_status">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="9" style="text-align:center;">No orders in the queue.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
