<?php
require_once __DIR__ . '/db_connector/config.php';
require_once __DIR__ . '/db/InventoryAdapter.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $required = ['itemId', 'quantity', 'priority'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $itemId = (int)$_POST['itemId'];
    $quantity = (int)$_POST['quantity'];
    $priority = $_POST['priority'];
    $notes = $_POST['notes'] ?? '';

    // Get current item details
    $inventoryAdapter = new InventoryAdapter($pdo);
    $itemDetails = $inventoryAdapter->getStockLevels([$itemId]);

    if (empty($itemDetails)) {
        throw new Exception('Item not found');
    }

    $item = $itemDetails[0];

    // Save reorder request
    $stmt = $pdo->prepare("
        INSERT INTO housekeeping_reorder_requests (
            item_id, 
            quantity_requested, 
            priority_level, 
            notes, 
            current_stock_level,
            status,
            requested_date,
            requested_by
        ) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?)
    ");

    $stmt->execute([
        $itemId,
        $quantity,
        $priority,
        $notes,
        $item['current_stock'],
        $_SESSION['user_id'] ?? 0 // Assuming you have user sessions
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Reorder request submitted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}