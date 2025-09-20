<?php
namespace Housekeeping\Supplies\Services;

use Housekeeping\Supplies\Adapters\InventoryAdapter;

class SupplyTrackingService {
    private $db;
    private $inventoryAdapter;
    
    public function __construct(\PDO $db, InventoryAdapter $inventoryAdapter) {
        $this->db = $db;
        $this->inventoryAdapter = $inventoryAdapter;
    }
    
    /**
     * Get supplies with both local and inventory stock levels
     */
    public function getSuppliesWithStock(): array {
        $query = "
            SELECT 
                s.*,
                s.department_stock as local_stock,
                COALESCE(s.last_sync_quantity, 0) as last_known_inventory,
                s.last_sync_date,
                s.local_reorder_level
            FROM supplies s
            ORDER BY s.category, s.name
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $supplies = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Enrich with current inventory data where possible
        foreach ($supplies as &$supply) {
            if ($supply['inventory_item_id']) {
                $supply['inventory_stock'] = $this->inventoryAdapter->getInventoryStock($supply['inventory_item_id']);
                $supply['is_low'] = $this->inventoryAdapter->checkLowStock($supply['inventory_item_id']);
            }
        }
        
        return $supplies;
    }
    
    /**
     * Record usage of a supply item
     */
    public function recordUsage(int $supplyId, int $quantity, ?int $staffId = null, string $notes = ''): bool {
        try {
            $this->db->beginTransaction();
            
            // Insert usage record
            $stmt = $this->db->prepare("
                INSERT INTO supply_usage (supply_id, quantity_used, used_by, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$supplyId, $quantity, $staffId, $notes]);
            
            // Update local stock
            $stmt = $this->db->prepare("
                UPDATE supplies 
                SET department_stock = department_stock - ? 
                WHERE supply_id = ?
            ");
            $stmt->execute([$quantity, $supplyId]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Failed to record supply usage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Request more supplies from inventory
     */
    public function createSupplyRequest(int $supplyId, int $quantity, ?int $staffId = null, string $notes = ''): bool {
        $stmt = $this->db->prepare("
            INSERT INTO supply_requests (
                supply_id, quantity_requested, requested_by, notes
            ) VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$supplyId, $quantity, $staffId, $notes]);
    }
    
    /**
     * Sync a supply's local count with inventory
     */
    public function syncWithInventory(int $supplyId): bool {
        $supply = $this->db->query("
            SELECT inventory_item_id FROM supplies WHERE supply_id = $supplyId
        ")->fetch(\PDO::FETCH_ASSOC);
        
        if (!$supply || !$supply['inventory_item_id']) {
            return false;
        }
        
        $inventoryStock = $this->inventoryAdapter->getInventoryStock($supply['inventory_item_id']);
        
        $stmt = $this->db->prepare("
            UPDATE supplies 
            SET last_sync_quantity = ?,
                last_sync_date = CURRENT_TIMESTAMP
            WHERE supply_id = ?
        ");
        
        return $stmt->execute([$inventoryStock, $supplyId]);
    }
}