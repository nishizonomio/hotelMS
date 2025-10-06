<?php
require_once('../db.php');
session_start();

$order = $_SESSION['order_minibar'] ?? [];
$order_id = $_POST['order_id'] ?? rand(1000,9999);

$guest_id = $_POST['guest_id'] ?? null;
$guest_name = $_POST['guest_name'] ?? null;
$order_type = 'Mini Bar';
$payment_option_input = $_POST['payment_option'] ?? 'bill';
$payment_method = $_POST['payment_method'] ?? null;
$partial_payment = floatval($_POST['partial_payment'] ?? 0);
$order_notes = $_POST['order_notes'] ?? '';

$total = 0;
$item_names = [];
$order_for_receipt = [];

foreach($order as $id => $item){
    if($item['category'] !== 'Mini Bar') continue;
    $qty = intval($item['qty']);
    if($qty <= 0) continue;

    $stmt_stock = $conn->prepare("SELECT quantity_in_stock FROM inventory WHERE item_id = ?");
    $stmt_stock->execute([$item['id'] ?? $id]);
    $stock_row = $stmt_stock->fetch(PDO::FETCH_ASSOC);
    $available_stock = $stock_row['quantity_in_stock'] ?? 0;
    if($available_stock < $qty) $qty = $available_stock;
    if($qty <= 0) continue;

    $price = floatval($item['price']);
    $subtotal = $price * $qty;
    $total += $subtotal;
    $item_names[] = $item['name'];

    $order_for_receipt[] = [
        'id' => $item['id'] ?? $id,
        'name' => $item['name'],
        'qty' => $qty,
        'price' => $price,
        'category' => $item['category']
    ];

    $stmt_update_stock = $conn->prepare("UPDATE inventory SET quantity_in_stock = quantity_in_stock - ? WHERE item_id = ?");
    $stmt_update_stock->execute([$qty, $item['id'] ?? $id]);

    $used_by = $guest_name ?? 'Walk-in Guest';
    $stmt_stock_usage = $conn->prepare("
        INSERT INTO stock_usage
        (order_id, item_name, guest_id, guest_name, quantity_used, used_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt_stock_usage->execute([$order_id, $item['name'], $guest_id, $guest_name, $qty, $used_by]);
}

$item_name_str = implode(", ", $item_names);

if($payment_option_input === 'upfront'){
    $payment_option = 'Paid';
    $paid_amount = min($partial_payment, $total);
    $remaining_amount = $total - $paid_amount;
} else {
    $payment_option = 'To be billed';
    $payment_method = null;
    $paid_amount = 0;
    $remaining_amount = $total;
}

$stmt = $conn->prepare("
    INSERT INTO guest_billing 
    (guest_id, guest_name, order_type, item_name, order_id, total_amount, payment_option, payment_method, partial_payment, remaining_amount, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([$guest_id, $guest_name, $order_type, $item_name_str, $order_id, $total, $payment_option, $payment_method, $paid_amount, $remaining_amount]);

$_SESSION['order_minibar'] = [];
$receipt_date = date('F j, Y, g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt - Hotel La Vista</title>
<link rel="stylesheet" href="minibar_pos.css">

</head>
<body>
<h2>Hotel La Vista</h2>
<p>Date: <?= htmlspecialchars($receipt_date) ?></p>
<p>Guest: <?= htmlspecialchars($guest_name) ?></p>
<p>Order Type: <?= htmlspecialchars($order_type) ?></p>
<p>Payment Method: <?= htmlspecialchars($payment_option === 'Paid' ? ($payment_method ?? '-') : 'To be billed') ?></p>
<?php if(!empty($order_notes)): ?><p>Notes: <?= htmlspecialchars($order_notes) ?></p><?php endif; ?>

<table>
<thead>
<tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
</thead>
<tbody>
<?php foreach($order_for_receipt as $item):
    $qty = $item['qty'];
    $price = $item['price'];
    $subtotal = $qty * $price;
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
<tr><td colspan="3">Total</td><td>₱<?= number_format($total,2) ?></td></tr>
<?php if($payment_option === 'Paid'): ?>
<tr><td colspan="3">Paid</td><td>₱<?= number_format($paid_amount,2) ?></td></tr>
<tr><td colspan="3">Remaining</td><td>₱<?= number_format($remaining_amount,2) ?></td></tr>
<?php else: ?>
<tr><td colspan="3">Remaining</td><td>₱<?= number_format($remaining_amount,2) ?></td></tr>
<?php endif; ?>
</tfoot>
</table>
<div class="print-btn"><button onclick="window.print()">Print Receipt</button></div>
<div class="back-btn"><a href="minibar_pos.php"><button>Back to POS</button></a></div>
</body>
</html>
