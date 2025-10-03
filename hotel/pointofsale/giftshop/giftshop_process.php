<?php
require __DIR__ . '/../../inventory/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: giftshop.php');
	exit;
}

try {
	// Ensure required tables exist BEFORE starting a transaction (MySQL DDL may auto-commit)
    $pdo->exec("CREATE TABLE IF NOT EXISTS giftshop_orders (
		order_id INT(11) NOT NULL AUTO_INCREMENT,
		guest_id INT(11) NOT NULL,
        item VARCHAR(255) DEFAULT NULL,
		total_amount DECIMAL(10,2) NOT NULL,
		order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		status ENUM('to_be_billed','paid') DEFAULT 'to_be_billed',
		staff_id VARCHAR(20) NOT NULL,
		notes TEXT DEFAULT NULL,
		subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
		tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
		payment_method ENUM('cash','card','gcash','other') DEFAULT 'cash',
		transaction_id VARCHAR(64) DEFAULT NULL,
		PRIMARY KEY (order_id),
		UNIQUE KEY uniq_giftshop_txn (transaction_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // Add item column if missing (MySQL 8+ supports IF NOT EXISTS)
    try { $pdo->exec("ALTER TABLE giftshop_orders ADD COLUMN IF NOT EXISTS item VARCHAR(255) DEFAULT NULL AFTER guest_id"); } catch (Exception $e) { /* ignore */ }

	$pdo->exec("CREATE TABLE IF NOT EXISTS giftshop_order_items (
		id INT(11) NOT NULL AUTO_INCREMENT,
		order_id INT(11) NOT NULL,
		item_name VARCHAR(255) NOT NULL,
		quantity INT(11) NOT NULL,
		unit_price DECIMAL(10,2) NOT NULL,
		total_price DECIMAL(10,2) NOT NULL,
		special_instructions TEXT DEFAULT NULL,
		PRIMARY KEY (id),
		KEY order_id_idx (order_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	$pdo->beginTransaction();

	$guest_id = $_POST['guest_id'] ?? '';
	$staff_id = $_POST['staff_id'] ?? '';
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
			$item_total = $quantity * $price;

			$order_items[] = [
				'name' => $item_name,
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
	$transaction_id = 'GFT-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));

	// Totals (prefer posted hidden fields, fallback to compute)
	$subtotal_amount = isset($_POST['subtotal_amount']) ? (float)$_POST['subtotal_amount'] : $computed_subtotal;
	$tax_amount = isset($_POST['tax_amount']) ? (float)$_POST['tax_amount'] : round($subtotal_amount * 0.12, 2);
	$total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : ($subtotal_amount + $tax_amount);

	// Normalize status similar to restaurant
	$status_map = [
		'to_be_billed' => 'to_be_billed',
		'paid' => 'paid',
		'pending' => 'to_be_billed',
		'cancelled' => 'to_be_billed',
		'refunded' => 'to_be_billed',
	];
	$status = $status_map[$status] ?? 'to_be_billed';

    // Build item summary (comma-separated list of item names)
    $items_summary = implode(', ', array_map(function($i){ return $i['name']; }, $order_items));

    // Insert order (with item summary)
    $stmt = $pdo->prepare("INSERT INTO giftshop_orders (guest_id, item, subtotal_amount, tax_amount, total_amount, staff_id, notes, status, payment_method, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->execute([
        $guest_id,
        mb_strimwidth($items_summary, 0, 255, ''),
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

	// Insert items and deduct inventory atomically
	$insertItem = $pdo->prepare("INSERT INTO giftshop_order_items (order_id, item_name, quantity, unit_price, total_price, special_instructions) VALUES (?, ?, ?, ?, ?, ?)");
	$getInv = $pdo->prepare("SELECT item_id, quantity_in_stock, used_qty FROM inventory WHERE item_name = ? AND category = 'Gift Shop' FOR UPDATE");
	$updInv = $pdo->prepare("UPDATE inventory SET quantity_in_stock = ?, used_qty = COALESCE(used_qty,0) + ? WHERE item_id = ?");
	foreach ($order_items as $item) {
		// Lock inventory row and validate stock
		$getInv->execute([$item['name']]);
		$inv = $getInv->fetch(PDO::FETCH_ASSOC);
		if (!$inv) {
			throw new Exception('Inventory item not found: ' . $item['name']);
		}
		$available = (int)$inv['quantity_in_stock'];
		if ($item['quantity'] > $available) {
			throw new Exception('Insufficient stock for ' . $item['name'] . ' (available: ' . $available . ')');
		}

		// Insert order item
		$insertItem->execute([
			$order_id,
			$item['name'],
			$item['quantity'],
			$item['unit_price'],
			$item['total_price'],
			$item['special']
		]);

		// Deduct inventory
		$newQty = $available - (int)$item['quantity'];
		$updInv->execute([$newQty, (int)$item['quantity'], (int)$inv['item_id']]);
	}

	$pdo->commit();
	$_SESSION['success'] = "Gift shop sale #{$order_id} recorded. TXN: {$transaction_id}. Total: ₱" . number_format($total_amount, 2);

} catch (Exception $e) {
	if ($pdo->inTransaction()) { $pdo->rollBack(); }
	$_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: giftshop.php');
exit;


