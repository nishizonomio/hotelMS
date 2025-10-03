<?php
require __DIR__ . '/../../inventory/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: room_dining.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Validate required fields
    $guest_id = $_POST['guest_id'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $order_type = $_POST['order_type'] ?? 'appetizer';
    $delivery_time = $_POST['delivery_time'] ?? null;
    $special_instructions = $_POST['special_instructions'] ?? null;
    $items = $_POST['items'] ?? [];

    if (empty($guest_id) || empty($staff_id) || empty($room_number)) {
        throw new Exception('Guest ID, Staff ID, and Room Number are required.');
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

    // Validate room exists and is occupied
    $stmt = $pdo->prepare("SELECT room_id FROM rooms WHERE room_number = ? AND status = 'occupied'");
    $stmt->execute([$room_number]);
    if (!$stmt->fetch()) {
        throw new Exception('Room not found or not occupied.');
    }

    // Process order items
    $order_items = [];
    $total_amount = 0;

    foreach ($items as $item_name => $item_data) {
        $quantity = intval($item_data['quantity'] ?? 0);
        if ($quantity > 0) {
            $price = floatval($item_data['price'] ?? 0);
            $category = $item_data['category'] ?? 'Appetizer';
            $item_total = $quantity * $price;
            
            $order_items[] = [
                'name' => $item_name,
                'category' => $category,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_price' => $item_total
            ];
            
            $total_amount += $item_total;
        }
    }

    if (empty($order_items)) {
        throw new Exception('Please select at least one item.');
    }

    // Convert delivery time to proper format
    $delivery_datetime = null;
    if ($delivery_time) {
        $delivery_datetime = date('Y-m-d H:i:s', strtotime($delivery_time));
    }

    // Insert room dining order
    $stmt = $pdo->prepare("
        INSERT INTO room_dining_orders (guest_id, room_number, order_type, total_amount, delivery_time, staff_id, special_instructions)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$guest_id, $room_number, $order_type, $total_amount, $delivery_datetime, $staff_id, $special_instructions]);
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO room_dining_order_items (order_id, item_name, category, quantity, unit_price, total_price)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($order_items as $item) {
        $stmt->execute([
            $order_id,
            $item['name'],
            $item['category'],
            $item['quantity'],
            $item['unit_price'],
            $item['total_price']
        ]);
    }

    // Create billing record
    $stmt = $pdo->prepare("
        INSERT INTO billings (guest_id, billing_type, total_amount, status)
        VALUES (?, 'room_service', ?, 'pending')
    ");
    $stmt->execute([$guest_id, $total_amount]);
    $billing_id = $pdo->lastInsertId();

    // Insert billing items
    $stmt = $pdo->prepare("
        INSERT INTO billing_items (billing_id, item_name, quantity, unit_price, total_price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($order_items as $item) {
        $stmt->execute([
            $billing_id,
            $item['name'],
            $item['quantity'],
            $item['unit_price'],
            $item['total_price']
        ]);
    }

    $pdo->commit();
    
    $delivery_info = $delivery_time ? " (Delivery: " . date('M j, Y H:i', strtotime($delivery_time)) . ")" : "";
    $_SESSION['success'] = "Room service order #{$order_id} placed successfully! Total: ₱" . number_format($total_amount, 2) . $delivery_info;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header('Location: room_dining.php');
exit;
?>

