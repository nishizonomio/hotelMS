<?php
require __DIR__ . '/../../inventory/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: restaurant.php');
	exit;
}

try {
	$pdo->beginTransaction();

$guest_id = $_POST['guest_id'] ?? '';
$staff_id = $_POST['staff_id'] ?? '';
$table_number = $_POST['table_number'] ?? null;
$order_type = $_POST['order_type'] ?? 'dine_in';
$status = $_POST['status'] ?? 'to_be_billed';
$payment_method = $_POST['payment_method'] ?? 'cash';
$notes = $_POST['notes'] ?? null;
$items = $_POST['items'] ?? [];

	if (empty($guest_id) || empty($staff_id)) {
		throw new Exception('Guest ID and Staff ID are required.');
	}

	// Validate guest exists
	$stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE guest_id = ?");
	$stmt->execute([$guest_id]);
	if (!$stmt->fetch()) {
		throw new Exception('Guest not found.');
	}

	// Validate staff exists
	$stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE staff_id = ?");
	$stmt->execute([$staff_id]);
	if (!$stmt->fetch()) {
		throw new Exception('Staff not found.');
	}

$order_items = [];
$computed_subtotal = 0;

	foreach ($items as $item_name => $item_data) {
		$quantity = intval($item_data['quantity'] ?? 0);
		if ($quantity > 0) {
			$price = floatval($item_data['price'] ?? 0);
			$category = $item_data['category'] ?? 'Main Course';
			$item_total = $quantity * $price;

			$order_items[] = [
				'name' => $item_name,
				'category' => $category,
				'quantity' => $quantity,
				'unit_price' => $price,
				'total_price' => $item_total,
				'special' => $item_data['special_instructions'] ?? null,
			];

    $computed_subtotal += $item_total;
		}
	}

	if (empty($order_items)) {
		throw new Exception('Please select at least one item.');
	}

// Generate Transaction ID
$transaction_id = 'RST-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));

// Totals (prefer posted hidden fields, fallback to compute)
$subtotal_amount = isset($_POST['subtotal_amount']) ? (float)$_POST['subtotal_amount'] : $computed_subtotal;
$tax_amount = isset($_POST['tax_amount']) ? (float)$_POST['tax_amount'] : round($subtotal_amount * 0.12, 2);
$total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : ($subtotal_amount + $tax_amount);

// Normalize status to supported values if DB has legacy enum
$status_map = [
    'to_be_billed' => 'to_be_billed',
    'paid' => 'paid',
    // legacy mappings
    'pending' => 'to_be_billed',
    'in_progress' => 'to_be_billed',
    'served' => 'to_be_billed',
    'cancelled' => 'to_be_billed',
    'refunded' => 'to_be_billed',
    'preparing' => 'to_be_billed',
    'ready' => 'to_be_billed',
];
$status = $status_map[$status] ?? 'to_be_billed';

	// Insert restaurant order
$stmt = $pdo->prepare("
    INSERT INTO restaurant_orders (
        guest_id, table_number, order_type, subtotal_amount, tax_amount, total_amount,
        staff_id, notes, status, payment_method, transaction_id
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $guest_id,
    $table_number,
    $order_type,
    $subtotal_amount,
    $tax_amount,
    $total_amount,
    $staff_id,
    $notes,
    $status,
    $payment_method,
    $transaction_id
]);
	$order_id = $pdo->lastInsertId();

	// Insert order items
	$stmt = $pdo->prepare("
		INSERT INTO restaurant_order_items (order_id, item_name, category, quantity, unit_price, total_price, special_instructions)
		VALUES (?, ?, ?, ?, ?, ?, ?)
	");
	foreach ($order_items as $item) {
		$stmt->execute([
			$order_id,
			$item['name'],
			$item['category'],
			$item['quantity'],
			$item['unit_price'],
			$item['total_price'],
			$item['special']
		]);
	}

	// Push to kitchen queue
	$items_summary = implode(', ', array_map(function($i){ return $i['name']; }, $order_items));
	$stmt = $pdo->prepare("
		INSERT INTO kitchen_orders (order_id, order_type, status, priority, table_number, room_number, assigned_chef, guest_name, item_name, total_amount, notes, estimated_time)
		VALUES (?, 'restaurant', 'pending', 1, ?, NULL, NULL, (SELECT CONCAT(first_name,' ',last_name) FROM guests WHERE guest_id = ?), ?, ?, ?, NULL)
	");
	$stmt->execute([$order_id, $table_number, $guest_id, $items_summary, $total_amount, $notes]);

$pdo->commit();
$_SESSION['success'] = "Restaurant order #{$order_id} placed. TXN: {$transaction_id}. Total: ₱" . number_format($total_amount, 2);

} catch (Exception $e) {
	$pdo->rollBack();
	$_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: restaurant.php');
exit;
