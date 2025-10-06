<?php
require_once('../db.php');
session_start();

$guest = null;
$order = $_SESSION['order_restaurant'] ?? [];
$order_id = rand(1000, 9999);

if (!empty($_GET['clear_cart'])) {
    $order = [];
    $_SESSION['order_restaurant'] = $order;
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
        $qty = $_POST['qty'];
        $priority = $_POST['priority'] ?? 1;
        if(isset($order[$id])) {
            $order[$id]['qty'] += $qty;
        } else {
            $order[$id] = [
                'name' => $name,
                'price' => $price,
                'qty' => $qty,
                'priority' => $priority
            ];
        }
    }
    if (isset($_POST['remove_item'])) {
        unset($order[$_POST['remove_item']]);
    }
    if (isset($_POST['finalize_order'])) {
        $total = 0;
        $item_names = [];
        foreach ($order as $o) {
            $item_names[] = $o['name'];
            $total += $o['price'] * $o['qty'];
        }
        $items_str = implode(', ', $item_names);

        $stmtInsert = $conn->prepare("
            INSERT INTO kitchen_orders 
            (order_id, order_type, status, priority, table_number, guest_name, guest_id, item_name, total_amount, created_at, updated_at, room_number) 
            VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?)
        ");
        $stmtInsert->execute([
            $order_id,
            'Restaurant',
            'preparing',
            $priority,
            1,
            $guest['first_name'].' '.$guest['last_name'],
            $guest['guest_id'] ?? null,
            $items_str,
            $total,
            $guest['room_number'] ?? null
        ]);

        $order = [];
        $_SESSION['order_restaurant'] = $order;
        $order_id = rand(1000, 9999);
    }
    $_SESSION['order_restaurant'] = $order;
}

$food_items = $conn->prepare("SELECT * FROM recipes WHERE is_active = 1 ORDER BY display_order ASC");
$food_items->execute();
$food_items = $food_items->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>POS - Restaurant</title>
<link rel="stylesheet" href="restaurant_pos.css">
<style>
.show { display:block !important; }
#room, #table, #pay-method, #amount-paid-row { display:none; }
.notes-section { margin-top: 10px; }
</style>
</head>
<body>
<header>
  <h1>Hotel La Vista - POS - <span>Restaurant</span></h1>
  <a href="http://localhost/hotel/pointofsale/pos.php"><button type="button">Back</button></a>
</header>

<div class="main-grid">
  <div class="menu-items">
    <?php foreach ($food_items as $f):
        $img = !empty($f['image_path']) ? "kitchen/uploads/recipes/".$f['image_path'] : "kitchen/uploads/recipes/default.png";
    ?>
      <form method="post" class="menu-item">
        <img src="/hotel/<?= $img ?>" alt="<?= htmlspecialchars($f['recipe_name']) ?>">
        <div>
          <span><?= htmlspecialchars($f['recipe_name']) ?> - ₱<?= number_format($f['price'],2) ?></span><br>
          Qty: <input type="number" name="qty" value="1" min="1">
          Priority:
          <select name="priority">
            <option>1</option><option>2</option><option>3</option>
          </select>
          <input type="hidden" name="id" value="<?= $f['id'] ?>">
          <input type="hidden" name="name" value="<?= htmlspecialchars($f['recipe_name']) ?>">
          <input type="hidden" name="price" value="<?= $f['price'] ?>">
          <button type="submit" name="add_item">Add</button>
        </div>
      </form>
    <?php endforeach; ?>
  </div>

  <div class="order-list">
    <h3>Order Summary</h3>
    <?php if ($guest): ?>
      <p>Guest: <?= htmlspecialchars($guest['first_name'].' '.$guest['last_name']) ?> | <?= !empty($guest['room_number']) ? 'R'.$guest['room_number'] : '-' ?></p>
    <?php elseif (isset($_GET['guest'])): ?>
      <p class="error">Guest not found</p>
    <?php endif; ?>
    <p>Order ID: <?= $order_id ?></p>

    <div class="guest-bar">
      <form method="get" class="guest-form">
        <input type="text" name="guest" class="guest-input" placeholder="Enter Guest ID or Name" value="<?= htmlspecialchars($_GET['guest'] ?? '') ?>">
        <button type="submit" class="guest-btn">Load Guest</button>
      </form>
      <form method="post">
        <button type="submit" name="clear_cart" class="guest-btn clear">Clear Cart</button>
      </form>
    </div>

    <table>
      <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Priority</th><th>Remove</th></tr>
      <?php $total=0; foreach ($order as $id=>$item):
        $subtotal=$item['qty']*$item['price']; $total+=$subtotal; ?>
        <tr>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td><?= $item['qty'] ?></td>
          <td>₱<?= number_format($item['price'],2) ?></td>
          <td>₱<?= number_format($subtotal,2) ?></td>
          <td><?= $item['priority'] ?></td>
          <td>
            <form method="post" style="display:inline">
              <button type="submit" name="remove_item" value="<?= $id ?>">X</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3"><strong>Total</strong></td><td colspan="3">₱<?= number_format($total,2) ?></td></tr>
    </table>

    <form method="post" action="add_order.php" class="checkout-form">
      <input type="hidden" name="order_id" value="<?= $order_id ?>">
      <input type="hidden" name="guest_id" value="<?= $guest['guest_id'] ?? '' ?>">
      <input type="hidden" name="guest_name" value="<?= $guest ? htmlspecialchars($guest['first_name'].' '.$guest['last_name']) : '' ?>">
      <input type="hidden" name="order_type" id="order_type" value="Restaurant">
      <input type="hidden" name="room_number" id="room_number_input" value="<?= !empty($guest['room_number']) ? $guest['room_number'] : '' ?>">

      <div class="notes-section">
        <label for="order_notes">Order Notes:</label>
        <textarea name="order_notes" id="order_notes" rows="3" placeholder="Add any notes for the order here..."></textarea>
      </div>

      <div class="form-row">
        <label>Delivery:</label>
        <select name="delivery_type" id="delivery">
          <option value="" disabled selected>Select Delivery</option>
          <option value="Restaurant">Restaurant</option>
          <option value="Room Service">Room Service</option>
        </select>
      </div>

      <div class="form-row" id="room">
        <label>Room:</label>
        <input type="text" id="room_number_display" value="<?= !empty($guest['room_number']) ? htmlspecialchars($guest['room_number']) : '' ?>" readonly>
      </div>

      <div class="form-row" id="table">
        <label>Table:</label>
        <select name="table_number" id="table_number_select">
          <?php for($i=1;$i<=20;$i++): ?>
            <option value="<?= $i ?>">T<?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="form-row">
        <label>Payment Option:</label>
        <select name="payment_option" id="payment">
          <option value="" disabled selected>Select Payment Option</option>
          <option value="upfront">Upfront</option>
          <option value="bill">Bill to Room</option>
        </select>
      </div>

      <div class="form-row" id="pay-method">
        <label>Method:</label>
        <select name="payment_method">
          <option>Cash</option>
          <option>Card</option>
          <option>GCash</option>
          <option>Paymaya</option>
          <option>BillEase</option>
        </select>
      </div>

      <div class="form-row" id="amount-paid-row">
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
const payment = document.getElementById('payment');
const payMethod = document.getElementById('pay-method');
const amountPaidRow = document.getElementById('amount-paid-row');
const delivery = document.getElementById('delivery');
const room = document.getElementById('room');
const table = document.getElementById('table');

payment.onchange = () => {
    payMethod.classList.toggle('show', payment.value === 'upfront');
    amountPaidRow.style.display = payment.value === 'upfront' ? 'block' : 'none';
};

delivery.onchange = () => {
    if(delivery.value === 'Room Service') {
        room.classList.add('show');
        table.classList.remove('show');
    } else {
        table.classList.add('show');
        room.classList.remove('show');
    }
};

window.addEventListener('DOMContentLoaded', () => {
    if(delivery.value === 'Room Service') {
        room.classList.add('show');
        table.classList.remove('show');
    } else {
        table.classList.add('show');
        room.classList.remove('show');
    }
});
</script>
</body>
</html>
