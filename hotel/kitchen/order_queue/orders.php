<?php
require_once(__DIR__ . '/../utils/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE kitchen_orders SET status = :status WHERE order_id = :order_id");
    $stmt->execute([':status' => $new_status, ':order_id' => $order_id]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$search_order = $_GET['order_id'] ?? '';
$search_guest = $_GET['guest_name'] ?? '';

$conditions = ["ko.status != 'completed'"];
$params = [];

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
    ko.guest_name,
    ko.status,
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
  SELECT COUNT(*) AS total_orders
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
<title>Order Queue</title>
<link rel="stylesheet" href="orders.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
  setInterval(() => { window.location.reload(); }, 5000);
</script>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header style="margin-top:10px;">
      <h1>Order Queue</h1>
      <p>Total Orders: <?= (int)$total['total_orders'] ?></p>
    </header>

    <div class="search-container" style="display:flex; justify-content:center; flex-wrap:wrap; align-items:center; gap:16px; margin:10px 0 20px 0;">
      <a href="/hotel/kitchen/kitchen.php">
        <button type="button"><i class="fas fa-arrow-left"></i> Back to Kitchen</button>
      </a>
      <form method="GET" class="search-form" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="order_id" placeholder="Search by order ID" value="<?= htmlspecialchars($search_order) ?>" style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">
        <input type="text" name="guest_name" placeholder="Search by guest" value="<?= htmlspecialchars($search_guest) ?>" style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">
        <button type="submit" style="padding:8px 12px; border-radius:6px; border:none; background:#1A237E; color:#fff; cursor:pointer;">🔍 Filter</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Guest</th>
          <th>Status</th>
          <th>Order Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($orders): ?>
        <?php foreach ($orders as $order):
            $isNew = (strtotime($order['order_date']) >= $latestTimestamp - 60) ? 'new-order' : '';
        ?>
          <tr class="<?= $isNew ?>">
            <td><?= htmlspecialchars($order['order_id']) ?></td>
            <td><?= htmlspecialchars($order['guest_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars(ucfirst($order['status'])) ?></td>
            <td><?= htmlspecialchars($order['order_date']) ?></td>
            <td>
              <form method="POST" style="display:flex; gap:5px; align-items:center;">
                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                <select name="status" style="padding:4px 8px; border-radius:4px; min-width:90px;">
                  <option value="preparing" <?= $order['status']=='preparing'?'selected':'' ?>>Preparing</option>
                  <option value="ready" <?= $order['status']=='ready'?'selected':'' ?>>Ready</option>
                  <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>Completed</option>
                </select>
                <button type="submit" name="update_status" style="padding:4px 8px; border-radius:4px; border:none; background:#1A237E; color:#fff; cursor:pointer;"><i class="fas fa-check"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" style="text-align:center;">No orders in the queue.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
