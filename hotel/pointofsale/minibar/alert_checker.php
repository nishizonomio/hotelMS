<?php
/**
 * Minibar Alert Checker
 * This script should be run periodically (e.g., via cron job) to check for:
 * - Low stock items
 * - Items nearing expiry
 * - Out of stock items
 */

require __DIR__ . '/db_connect.php';

function checkLowStockAlerts() {
    global $pdo;
    
    // Check for low stock items (threshold: 5 or less)
    $stmt = $pdo->query("
        SELECT item_id, item_name, quantity_in_stock 
        FROM inventory 
        WHERE category = 'Mini Bar' AND quantity_in_stock <= 5 AND quantity_in_stock > 0
    ");
    
    $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lowStockItems as $item) {
        // Check if alert already exists
        $checkStmt = $pdo->prepare("
            SELECT alert_id FROM minibar_inventory_alerts 
            WHERE item_id = ? AND alert_type = 'low_stock' AND is_resolved = 0
        ");
        $checkStmt->execute([$item['item_id']]);
        
        if (!$checkStmt->fetch()) {
            // Create new alert
            $alertMessage = "Item '{$item['item_name']}' is running low (Stock: {$item['quantity_in_stock']})";
            $insertStmt = $pdo->prepare("
                INSERT INTO minibar_inventory_alerts 
                (item_id, alert_type, current_quantity, alert_message, created_at) 
                VALUES (?, 'low_stock', ?, ?, NOW())
            ");
            $insertStmt->execute([$item['item_id'], $item['quantity_in_stock'], $alertMessage]);
            echo "Created low stock alert for: {$item['item_name']}\n";
        }
    }
}

function checkOutOfStockAlerts() {
    global $pdo;
    
    // Check for out of stock items
    $stmt = $pdo->query("
        SELECT item_id, item_name, quantity_in_stock 
        FROM inventory 
        WHERE category = 'Mini Bar' AND quantity_in_stock = 0
    ");
    
    $outOfStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($outOfStockItems as $item) {
        // Check if alert already exists
        $checkStmt = $pdo->prepare("
            SELECT alert_id FROM minibar_inventory_alerts 
            WHERE item_id = ? AND alert_type = 'out_of_stock' AND is_resolved = 0
        ");
        $checkStmt->execute([$item['item_id']]);
        
        if (!$checkStmt->fetch()) {
            // Create new alert
            $alertMessage = "Item '{$item['item_name']}' is out of stock";
            $insertStmt = $pdo->prepare("
                INSERT INTO minibar_inventory_alerts 
                (item_id, alert_type, current_quantity, alert_message, created_at) 
                VALUES (?, 'out_of_stock', 0, ?, NOW())
            ");
            $insertStmt->execute([$item['item_id'], $alertMessage]);
            echo "Created out of stock alert for: {$item['item_name']}\n";
        }
    }
}

function checkExpiryAlerts() {
    global $pdo;
    
    // Check for items expiring within 7 days
    $stmt = $pdo->query("
        SELECT 
            mr.item_id, 
            mr.room_number, 
            mr.expiry_date, 
            i.item_name,
            DATEDIFF(mr.expiry_date, CURDATE()) as days_to_expiry
        FROM minibar_refill_logs mr
        JOIN inventory i ON mr.item_id = i.item_id
        WHERE mr.expiry_date IS NOT NULL 
        AND mr.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND mr.expiry_date >= CURDATE()
    ");
    
    $expiringItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expiringItems as $item) {
        // Check if alert already exists for this specific room and item
        $checkStmt = $pdo->prepare("
            SELECT alert_id FROM minibar_inventory_alerts 
            WHERE item_id = ? AND room_number = ? AND alert_type = 'expiry' 
            AND expiry_date = ? AND is_resolved = 0
        ");
        $checkStmt->execute([$item['item_id'], $item['room_number'], $item['expiry_date']]);
        
        if (!$checkStmt->fetch()) {
            // Create new alert
            $daysText = $item['days_to_expiry'] == 1 ? 'day' : 'days';
            $alertMessage = "Item '{$item['item_name']}' in room {$item['room_number']} expires in {$item['days_to_expiry']} {$daysText}";
            
            $insertStmt = $pdo->prepare("
                INSERT INTO minibar_inventory_alerts 
                (item_id, alert_type, room_number, expiry_date, alert_message, created_at) 
                VALUES (?, 'expiry', ?, ?, ?, NOW())
            ");
            $insertStmt->execute([
                $item['item_id'], 
                $item['room_number'], 
                $item['expiry_date'], 
                $alertMessage
            ]);
            echo "Created expiry alert for: {$item['item_name']} in room {$item['room_number']}\n";
        }
    }
}

// Run all checks
echo "Running minibar alert checks...\n";
echo "Checking low stock items...\n";
checkLowStockAlerts();

echo "Checking out of stock items...\n";
checkOutOfStockAlerts();

echo "Checking expiring items...\n";
checkExpiryAlerts();

echo "Alert check completed.\n";
?>
