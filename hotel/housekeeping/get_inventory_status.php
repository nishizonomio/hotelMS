<?php
require_once __DIR__ . '/db_connector/config.php';
require_once __DIR__ . '/db/InventoryAdapter.php';

header('Content-Type: application/json');

try {
    $inventoryAdapter = new InventoryAdapter($pdo);
    $lowStockItems = $inventoryAdapter->getItemsNeedingReorder();
    
    // Check if the count or status of low stock items has changed
    $currentStatus = json_encode($lowStockItems);
    $lastStatus = $_SESSION['last_inventory_status'] ?? '';
    
    $_SESSION['last_inventory_status'] = $currentStatus;
    
    echo json_encode([
        'success' => true,
        'hasUpdates' => $currentStatus !== $lastStatus,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}