<?php
require_once('../db.php');
session_start();

$order = $_SESSION['order_restaurant'] ?? [];
$order_id = rand(1000,9999);

$guest_id = $_POST['guest_id'] ?? null;
$guest_name = $_POST['guest_name'] ?? null;
$order_type = 'Restaurant';
$table_number = $_POST['table_number'] ?? null;
$room_number = null;
$payment_option_input = $_POST['payment_option'] ?? 'bill';
$payment_method = $_POST['payment_method'] ?? null;
$partial_payment = floatval($_POST['partial_payment'] ?? 0);
$order_notes = $_POST['order_notes'] ?? '';

$total = 0;
$item_names = [];

foreach($order as $id => $item){
    $qty = intval($item['qty']);
    if($qty <= 0) continue;
    $price = floatval($item['price']);
    $subtotal = $price * $qty;
    $total += $subtotal;
    $item_names[] = $item['name'];
}

$item_name_str = implode(", ", $item_names);

$stmtInsert = $conn->prepare("
    INSERT INTO kitchen_orders
    (order_id, order_type, status, table_number, room_number, guest_name, guest_id, item_name, total_amount, order_notes, created_at, updated_at)
    VALUES (?, ?, 'preparing', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmtInsert->execute([$order_id, $order_type, $table_number, $room_number, $guest_name, $guest_id, $item_name_str, $total, $order_notes]);

if($payment_option_input === 'upfront'){
    $payment_option = 'Paid';
    $paid_amount = $partial_payment > $total ? $total : $partial_payment;
    $remaining_amount = max(0, $total - $paid_amount);
} else {
    $payment_option = 'To be billed';
    $paid_amount = 0;
    $remaining_amount = $total;
}

$stmt = $conn->prepare("
    INSERT INTO guest_billing 
    (guest_id, guest_name, order_type, item_name, order_id, total_amount, payment_option, payment_method, partial_payment, remaining_amount, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([$guest_id, $guest_name, $order_type, $item_name_str, $order_id, $total, $payment_option, $payment_method, $paid_amount, $remaining_amount]);

$receipt_date = date('F j, Y, g:i A');

$_SESSION['order_restaurant'] = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt - Hotel La Vista</title>
<link rel="stylesheet" href="add_order.css">
</head>
<body>
<h2>Hotel La Vista</h2>
<p>Date: <?= htmlspecialchars($receipt_date) ?></p>
<p>Guest: <?= htmlspecialchars($guest_name) ?></p>
<p>
<?php
if($table_number){
    echo "Table: " . htmlspecialchars($table_number);
} else {
    echo "-";
}
?>
</p>
<p>Order Type: <?= htmlspecialchars($order_type) ?></p>
<p>Payment Method: <?= $payment_option === 'Paid' ? htmlspecialchars($payment_method ?? '-') : 'To be billed' ?></p>
<?php if(!empty($order_notes)): ?>
<p>Notes: <?= htmlspecialchars($order_notes) ?></p>
<?php endif; ?>
<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($order as $id => $item):
            $qty = intval($item['qty']);
            if($qty <= 0) continue;
            $price = floatval($item['price']);
            $subtotal = $price * $qty;
        ?>
        <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= $qty ?></td>
            <td>₱<?= number_format($price,2) ?></td>
            <td>₱<?= number_format($subtotal,2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">Total</td>
            <td>₱<?= number_format($total,2) ?></td>
        </tr>
        <?php if($payment_option === 'Paid'): ?>
        <tr>
            <td colspan="3">Paid</td>
            <td>₱<?= number_format($paid_amount,2) ?></td>
        </tr>
        <tr>
            <td colspan="3">Remaining</td>
            <td>₱<?= number_format($remaining_amount,2) ?></td>
        </tr>
        <?php else: ?>
        <tr>
            <td colspan="3">Remaining</td>
            <td>₱<?= number_format($remaining_amount,2) ?></td>
        </tr>
        <?php endif; ?>
    </tfoot>
</table>

<div class="print-btn">
    <button onclick="window.print()">Print Receipt</button>
</div>
<div class="back-btn">
    <a href="http://localhost/hotel/pointofsale/restaurant/restaurant_pos.php"><button>Back to POS</button></a>
</div>
</body>
</html>
