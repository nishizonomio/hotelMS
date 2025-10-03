<?php
require __DIR__ . '/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $guest_id = $_POST['guest_id'] ?? null;
    $room_number = $_POST['room_number'] ?? null;
    $staff_id = $_POST['staff_id'] ?? null;
    $consumed_items = $_POST['items'] ?? [];

    // Validate staff
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id=?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$staff) {
        $_SESSION['error'] = "❌ Staff ID not found.";
        header("Location:minibar.php"); exit;
    }

    // Validate guest
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE guest_id=?");
    $stmt->execute([$guest_id]);
    $guest = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$guest) {
        $_SESSION['error'] = "❌ Guest ID not found.";
        header("Location:minibar.php"); exit;
    }

    // Validate room
    if (!$room_number) {
        $_SESSION['error'] = "⚠️ Please enter a room number.";
        header("Location:minibar.php"); exit;
    }

    if (empty($consumed_items)) {
        $_SESSION['error'] = "⚠️ No items selected.";
        header("Location:minibar.php"); exit;
    }

    $totalInserted = 0;
    $pdo->beginTransaction(); // Start transaction

    try {
        foreach ($consumed_items as $item_name => $qty) {
            $qty = intval($qty);
            if ($qty <= 0) continue;

            // Fetch inventory item by name
            $stmt = $pdo->prepare("SELECT item_id, quantity_in_stock, unit_price FROM inventory WHERE item_name=? LIMIT 1 FOR UPDATE");
            $stmt->execute([$item_name]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) continue;

            if ($item['quantity_in_stock'] < $qty) {
                throw new Exception("⚠️ Not enough stock for {$item_name}. Available: {$item['quantity_in_stock']}, requested: {$qty}");
            }

            $price = $item['unit_price'];
            $item_id = $item['item_id'];

            // Insert into minibar_consumption
            $stmt = $pdo->prepare("
                INSERT INTO minibar_consumption
                (guest_id, room_number, item_id, quantity, price, staff_id, checked_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$guest_id, $room_number, $item_id, $qty, $price, $staff_id]);

            // Update inventory stock
            $stmt = $pdo->prepare("UPDATE inventory SET quantity_in_stock = quantity_in_stock - ? WHERE item_id=?");
            $stmt->execute([$qty, $item_id]);

            // Record the usage in stock_usage
            $stmt = $pdo->prepare("
                INSERT INTO stock_usage (item_id, used_qty, used_by, date_used)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$item_id, $qty, 'Minibar']);

            // Add to folio instead of billing
            $stmt = $pdo->prepare("
                INSERT INTO folio 
                (guest_id, description, amount, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$guest_id, $item_name, $qty * $price]);

            $totalInserted++;
        }

        $pdo->commit();

        if ($totalInserted > 0) {
            $_SESSION['success'] = "✅ Mini-bar consumption recorded, inventory updated, and added to folio!";
        } else {
            $_SESSION['error'] = "⚠️ No items were recorded. Check quantities and stock.";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "⚠️ Failed to record minibar consumption: " . $e->getMessage();
    }

    header("Location:minibar.php"); exit;
}
