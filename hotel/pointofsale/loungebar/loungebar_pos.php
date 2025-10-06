<?php
require_once('../db.php');
session_start();

$order = $_SESSION['order_lounge'] ?? [];
$order_id = $_POST['order_id'] ?? rand(1000,9999);
$guest = null;

if (!empty($_GET['clear_cart'])) {
    $order = [];
    $_SESSION['order_lounge'] = $order;
}

if (!empty($_GET['guest'])) {
    $val = $_GET['guest'];
    if (is_numeric($val)) {
        $stmt = $conn->prepare("
            SELECT g.guest_id, g.first_name, g.last_name, rm.room_number
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id AND r.status='checked_in'
            LEFT JOIN rooms rm ON r.room_id = rm.room_id
            WHERE g.guest_id = ?
            ORDER BY r.check_in DESC
            LIMIT 1
        ");
        $stmt->execute([$val]);
    } else {
        $stmt = $conn->prepare("
            SELECT g.guest_id, g.first_name, g.last_name, rm.room_number
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id AND r.status='checked_in'
            LEFT JOIN rooms rm ON r.room_id = rm.room_id
            WHERE CONCAT(g.first_name,' ',g.last_name) LIKE ?
            ORDER BY r.check_in DESC
            LIMIT 1
        ");
        $stmt->execute(["%$val%"]);
    }
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $guest = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $qty = intval($_POST['qty']);
        if(isset($order[$id])) {
            $order[$id]['qty'] += $qty;
        } else {
            $order[$id] = ['name'=>$name,'price'=>$price,'qty'=>$qty,'category'=>'Lounge Bar'];
        }
    }
    if (isset($_POST['remove_item'])) {
        unset($order[$_POST['remove_item']]);
    }
    $_SESSION['order_lounge'] = $order;
}

$lounge_items = $conn->query("
    SELECT i.*, im.filename
    FROM inventory i
    LEFT JOIN item_images im ON i.item_id = im.item_id
    WHERE i.category='Lounge Bar'
    ORDER BY i.item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hotel La Vista POS - Lounge Bar</title>
<link rel="stylesheet" href="loungebar.css">
</head>
<body>
<header>
   <h1>Hotel La Vista - POS - <span>Lounge Bar</span></h1>
   <a href="http://localhost/hotel/pointofsale/pos.php"><button type="button">Back</button></a>
</header>

<div class="main-grid">
  <div class="menu-items">
    <?php foreach($lounge_items as $l):
        $server_path = __DIR__ . '/../uploads/' . $l['filename'];
        $url_path = '../uploads/' . $l['filename'];
    ?>
    <form method="post" class="menu-item">
      <div class="item-image">
        <?php if(!empty($l['filename']) && file_exists($server_path)): ?>
          <img src="<?= $url_path ?>" alt="<?= htmlspecialchars($l['item_name']) ?>">
        <?php else: ?>
          <img src="https://via.placeholder.com/120x120?text=No+Image" alt="No Image">
        <?php endif; ?>
      </div>
      <div class="item-details">
        <span><?= htmlspecialchars($l['item_name']) ?> - ₱<?= number_format($l['unit_price'],2) ?></span>
        <div class="qty-add-wrapper">
            Qty: <input type="number" name="qty" value="1" min="1">
            <button type="submit" name="add_item">Add</button>
        </div>
        <input type="hidden" name="id" value="<?= $l['item_id'] ?>">
        <input type="hidden" name="name" value="<?= htmlspecialchars($l['item_name']) ?>">
        <input type="hidden" name="price" value="<?= $l['unit_price'] ?>">
      </div>
    </form>
    <?php endforeach; ?>
  </div>

  <div class="order-list">
    <h3>Order Summary</h3>
    <p>Order ID: <?= $order_id ?></p>
    <?php if($guest): ?>
      <p>Guest: <?= htmlspecialchars($guest['first_name'].' '.$guest['last_name']) ?> | <?= $guest['room_number'] ? 'R'.$guest['room_number'] : '-' ?></p>
    <?php endif; ?>

    <div class="guest-bar">
      <form method="get" style="display:flex; gap:5px;">
          <input type="text" name="guest" placeholder="Enter Guest ID or Name" value="<?= htmlspecialchars($_GET['guest'] ?? '') ?>">
          <button type="submit" name="load_guest">Load Guest</button>
          <button type="submit" name="clear_cart">Clear Cart</button>
      </form>
    </div>

    <table>
      <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Remove</th></tr>
      <?php $total=0; foreach ($order as $id=>$item):
        $subtotal=$item['qty']*$item['price']; $total+=$subtotal; ?>
        <tr>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td><?= $item['qty'] ?></td>
          <td>₱<?= number_format($item['price'],2) ?></td>
          <td>₱<?= number_format($subtotal,2) ?></td>
          <td>
            <form method="post" style="display:inline">
              <button type="submit" name="remove_item" value="<?= $id ?>">X</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3"><strong>Total</strong></td><td colspan="2">₱<?= number_format($total,2) ?></td></tr>
    </table>

    <form method="post" action="lounge_add_order.php" class="checkout-form">
      <input type="hidden" name="order_id" value="<?= $order_id ?>">
      <input type="hidden" name="guest_id" value="<?= $guest['guest_id'] ?? '' ?>">
      <input type="hidden" name="guest_name" value="<?= $guest ? htmlspecialchars($guest['first_name'].' '.$guest['last_name']) : '' ?>">
      <input type="hidden" name="order_type" value="Lounge Bar">

      <div class="form-row">
        <label>Payment Option:</label>
        <select name="payment_option" id="payment_option" onchange="togglePaymentFields()">
          <option value="" disabled selected>Select Payment Option</option>
          <option value="upfront">Upfront</option>
          <option value="bill">Bill to Room</option>
        </select>
      </div>

      <div class="form-row" id="payment_method_row">
        <label>Method:</label>
        <select name="payment_method">
          <option>Cash</option>
          <option>Card</option>
          <option>GCash</option>
          <option>Paymaya</option>
          <option>BillEase</option>
        </select>
      </div>

      <div class="form-row" id="amount_paid_row">
        <label>Amount Paid:</label>
        <input type="number" name="partial_payment" step="0.01">
      </div>

      <div class="form-row">
        <button type="submit" class="finalize-btn">Finalize & Print</button>
      </div>
    </form>
  </div>
</div>

<script>
function togglePaymentFields() {
    const option = document.getElementById('payment_option').value;
    const methodRow = document.getElementById('payment_method_row');
    const amountRow = document.getElementById('amount_paid_row');
    if(option === 'bill') {
        methodRow.style.display = 'none';
        amountRow.style.display = 'none';
    } else {
        methodRow.style.display = 'block';
        amountRow.style.display = 'block';
    }
}
togglePaymentFields();
</script>
</body>
</html>
