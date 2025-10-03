<?php
require __DIR__ . '/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: minibar.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'record_consumption':
            recordConsumption();
            break;
        case 'refill_items':
            refillItems();
            break;
        case 'resolve_alert':
            resolveAlert();
            break;
        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: minibar.php');
    exit;
}

function recordConsumption() {
    global $pdo;

    $guest_id = $_POST['guest_id'] ?? null;
    $room_number = $_POST['room_number'] ?? null;
    $staff_id = $_POST['staff_id'] ?? null;
    $items = $_POST['items'] ?? [];
    $notes = $_POST['notes'] ?? null;
    
    // Validation
    if (!$guest_id || !$room_number || !$staff_id) {
        throw new Exception('All required fields must be filled.');
    }
    
    // Validate guest exists
    $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE guest_id = ?");
    $stmt->execute([$guest_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Guest ID not found.');
    }
    
    // Validate staff exists
    $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Staff ID not found.');
    }
    
    // Filter items with quantity > 0
    $consumedItems = [];
    foreach ($items as $item_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            $consumedItems[$item_id] = $quantity;
        }
    }
    
    if (empty($consumedItems)) {
        throw new Exception('No items selected for consumption.');
    }
    
    $pdo->beginTransaction();
    
    try {
        $totalAmount = 0;
        $processedItems = [];
        
        foreach ($consumedItems as $item_id => $quantity) {
            // Get item details and lock for update
            $stmt = $pdo->prepare("
                SELECT item_id, item_name, quantity_in_stock, unit_price 
                FROM inventory 
                WHERE item_id = ? AND category = 'Mini Bar' 
                FOR UPDATE
            ");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new Exception("Item ID $item_id not found or not a minibar item.");
            }

            if ($item['quantity_in_stock'] < $quantity) {
                throw new Exception("Insufficient stock for {$item['item_name']}. Available: {$item['quantity_in_stock']}, requested: $quantity");
            }

            $item_total = $quantity * $item['unit_price'];
            $totalAmount += $item_total;

            // Record consumption
            $stmt = $pdo->prepare("
                INSERT INTO minibar_consumption
                (guest_id, room_number, item_id, quantity, price, total_cost, staff_id, consumed_at, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $guest_id, $room_number, $item_id, $quantity, 
                $item['unit_price'], $item_total, $staff_id, $notes
            ]);

            // Update inventory stock
            $stmt = $pdo->prepare("
                UPDATE inventory 
                SET quantity_in_stock = quantity_in_stock - ?, 
                    used_qty = used_qty + ? 
                WHERE item_id = ?
            ");
            $stmt->execute([$quantity, $quantity, $item_id]);
            
            // Record usage in stock_usage table
            $stmt = $pdo->prepare("
                INSERT INTO stock_usage (item_id, used_qty, used_by, date_used)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$item_id, $quantity, 'Minibar - Room ' . $room_number]);

            // Add to guest folio
            $stmt = $pdo->prepare("
                INSERT INTO folio (guest_id, description, amount, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $description = "Minibar - {$item['item_name']} (Qty: $quantity)";
            $stmt->execute([$guest_id, $description, $item_total]);
            
            $processedItems[] = $item['item_name'] . " (x$quantity)";
            
            // Check for low stock and create alerts
            $newStock = $item['quantity_in_stock'] - $quantity;
            if ($newStock <= 5) {
                $alertType = $newStock == 0 ? 'out_of_stock' : 'low_stock';
                $alertMessage = $newStock == 0 
                    ? "Item '{$item['item_name']}' is out of stock" 
                    : "Item '{$item['item_name']}' is running low (Stock: $newStock)";
                
                // Check if alert already exists
                $stmt = $pdo->prepare("
                    SELECT alert_id FROM minibar_inventory_alerts 
                    WHERE item_id = ? AND alert_type = ? AND is_resolved = 0
                ");
                $stmt->execute([$item_id, $alertType]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO minibar_inventory_alerts 
                        (item_id, alert_type, current_quantity, alert_message, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$item_id, $alertType, $newStock, $alertMessage]);
                }
            }
        }

        $pdo->commit();

        $itemsList = implode(', ', $processedItems);
        $_SESSION['success'] = "Consumption recorded successfully! Items: $itemsList. Total: ₱" . number_format($totalAmount, 2) . ". Added to guest folio.";

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    header('Location: minibar.php');
    exit;
}

function refillItems() {
    global $pdo;
    
    $room_number = $_POST['room_number'] ?? null;
    $staff_id = $_POST['staff_id'] ?? null;
    $refill_items = $_POST['refill_items'] ?? [];
    $expiry_date = $_POST['expiry_date'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    // Validation
    if (!$room_number || !$staff_id) {
        throw new Exception('Room number and staff ID are required.');
    }
    
    // Validate staff exists
    $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Staff ID not found.');
    }
    
    // Filter items with quantity > 0
    $itemsToRefill = [];
    foreach ($refill_items as $item_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            $itemsToRefill[$item_id] = $quantity;
        }
    }
    
    if (empty($itemsToRefill)) {
        throw new Exception('No items specified for refill.');
    }
    
    $pdo->beginTransaction();
    
    try {
        $processedItems = [];
        
        foreach ($itemsToRefill as $item_id => $quantity) {
            // Get item details
            $stmt = $pdo->prepare("
                SELECT item_id, item_name, quantity_in_stock 
                FROM inventory 
                WHERE item_id = ? AND category = 'Mini Bar'
            ");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception("Item ID $item_id not found or not a minibar item.");
            }
            
            // Record refill log
            $stmt = $pdo->prepare("
                INSERT INTO minibar_refill_logs 
                (room_number, item_id, quantity_added, staff_id, refilled_at, expiry_date, notes) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $room_number, $item_id, $quantity, $staff_id, 
                $expiry_date ?: null, $notes
            ]);
            
            // Update inventory stock (Note: This assumes we're adding to the main inventory)
            // In a real scenario, you might want separate room-level inventory tracking
            $stmt = $pdo->prepare("
                UPDATE inventory 
                SET quantity_in_stock = quantity_in_stock + ? 
                WHERE item_id = ?
            ");
            $stmt->execute([$quantity, $item_id]);
            
            $processedItems[] = $item['item_name'] . " (+" . $quantity . ")";
            
            // Check if this resolves any low stock alerts
            $newStock = $item['quantity_in_stock'] + $quantity;
            if ($newStock > 5) {
                $stmt = $pdo->prepare("
                    UPDATE minibar_inventory_alerts 
                    SET is_resolved = 1, resolved_at = NOW() 
                    WHERE item_id = ? AND alert_type IN ('low_stock', 'out_of_stock') AND is_resolved = 0
                ");
                $stmt->execute([$item_id]);
            }
            
            // Create expiry alert if expiry date is set and within 7 days
            if ($expiry_date) {
                $expiryTimestamp = strtotime($expiry_date);
                $sevenDaysFromNow = strtotime('+7 days');
                
                if ($expiryTimestamp <= $sevenDaysFromNow) {
                    $daysToExpiry = ceil(($expiryTimestamp - time()) / (24 * 60 * 60));
                    $alertMessage = "Item '{$item['item_name']}' in room $room_number expires in $daysToExpiry day(s)";
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO minibar_inventory_alerts 
                        (item_id, alert_type, room_number, expiry_date, alert_message, created_at) 
                        VALUES (?, 'expiry', ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$item_id, $room_number, $expiry_date, $alertMessage]);
                }
            }
        }
        
        $pdo->commit();
        
        $itemsList = implode(', ', $processedItems);
        $_SESSION['success'] = "Refill recorded successfully! Room $room_number - Items: $itemsList";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    header('Location: minibar.php');
    exit;
}

function resolveAlert() {
    global $pdo;
    
    $alert_id = $_POST['alert_id'] ?? null;
    
    if (!$alert_id) {
        throw new Exception('Alert ID is required.');
    }
    
    $stmt = $pdo->prepare("
        UPDATE minibar_inventory_alerts 
        SET is_resolved = 1, resolved_at = NOW() 
        WHERE alert_id = ?
    ");
    $stmt->execute([$alert_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Alert resolved successfully.';
    } else {
        $_SESSION['error'] = 'Alert not found or already resolved.';
    }
    
    header('Location: minibar.php');
    exit;
}
?>
