<?php
namespace Housekeeping\Supplies\Adapters;

use Housekeeping\Supplies\DB\InventoryLogger;

class InventoryAdapter {
    private $db;
    private $logger;
    private $housekeepingCategories = [
        'Cleaning & Sanitation',
        'Laundry & Linen',
        'Utility Products'
    ];

    public function __construct(\PDO $db) {
        $this->db = $db;
        $this->logger = new InventoryLogger();
    }

    public function getRelevantInventoryItems(): array {
        try {
            $placeholders = str_repeat('?,', count($this->housekeepingCategories) - 1) . '?';
            $query = "SELECT 
                            s.supply_id as item_id, 
                            s.name as item_name, 
                            s.category,
                            COALESCE(s.department_stock, 0) as quantity_in_stock,
                            s.unit,
                            s.local_reorder_level as reorder_level
                     FROM supplies s 
                     WHERE s.category IN ($placeholders)
                     ORDER BY s.category, s.name";

            $stmt = $this->db->prepare($query);
            $stmt->execute($this->housekeepingCategories);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->logger->logOperation('getRelevantInventoryItems', [
                'categories' => $this->housekeepingCategories,
                'itemCount' => count($result)
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logOperation('getRelevantInventoryItems', [
                'categories' => $this->housekeepingCategories
            ], $e->getMessage());
            throw $e;
        }
    }

    public function getItemsNeedingReorder(): array {
        try {
            $placeholders = str_repeat('?,', count($this->housekeepingCategories) - 1) . '?';
            $query = "SELECT 
                            s.supply_id as item_id, 
                            s.name as item_name, 
                            s.category,
                            s.department_stock as quantity_in_stock,
                            s.local_reorder_level as reorder_level,
                            (s.local_reorder_level - s.department_stock) as units_needed
                     FROM supplies s
                     WHERE s.category IN ($placeholders)
                     AND s.department_stock <= s.local_reorder_level
                     ORDER BY (s.local_reorder_level - s.department_stock) DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($this->housekeepingCategories);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->logger->logOperation('getItemsNeedingReorder', [
                'categories' => $this->housekeepingCategories,
                'itemCount' => count($result)
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->logOperation('getItemsNeedingReorder', [
                'categories' => $this->housekeepingCategories
            ], $e->getMessage());
            throw $e;
        }
    }

    public function getStockLevels(array $itemIds): array {
        try {
            if (empty($itemIds)) {
                return [];
            }

            $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
            $query = "SELECT i.item_id, i.item_name, i.category, i.quantity_in_stock, i.unit_price, i.reorder_level
                     FROM inventory i
                     WHERE i.item_id IN ($placeholders)";

            $stmt = $this->db->prepare($query);
            $stmt->execute($itemIds);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->logger->logOperation('getStockLevels', [
                'itemIds' => $itemIds,
                'itemCount' => count($result)
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->logOperation('getStockLevels', [
                'itemIds' => $itemIds
            ], $e->getMessage());
            throw $e;
        }
    }

    public function createReorderRequest(int $itemId, int $quantity, string $priority, string $notes = ''): bool {
        try {
            $this->db->beginTransaction();

            // First validate the item exists and is a housekeeping item
            $item = $this->getStockLevels([$itemId]);
            if (empty($item) || !in_array($item[0]['category'], $this->housekeepingCategories)) {
                throw new \Exception('Invalid item for housekeeping reorder');
            }

            // Insert the reorder request
            $query = "INSERT INTO reorder_requests 
                        (item_id, requested_quantity, priority, notes, department, status, created_at)
                     VALUES 
                        (?, ?, ?, ?, 'Housekeeping', 'Pending', NOW())";

            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$itemId, $quantity, $priority, $notes]);

            if (!$success) {
                throw new \Exception('Failed to create reorder request');
            }

            $this->db->commit();
            
            $this->logger->logOperation('createReorderRequest', [
                'itemId' => $itemId,
                'quantity' => $quantity,
                'priority' => $priority,
                'requestId' => $this->db->lastInsertId()
            ]);

            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->logger->logOperation('createReorderRequest', [
                'itemId' => $itemId,
                'quantity' => $quantity,
                'priority' => $priority
            ], $e->getMessage());
            
            throw $e;
        }
    }
}