<?php

class InventoryAdapter {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get stock levels for specific items
     * @param array $itemIds Array of item IDs to check
     * @return array Stock information for requested items
     */
    public function getStockLevels($itemIds = []) {
        $query = "SELECT 
            i.item_id,
            i.item_name,
            i.description,
            i.current_stock,
            i.reorder_level,
            i.unit_price,
            i.unit_measure,
            c.category_name
            FROM inventory_items i
            LEFT JOIN item_categories c ON i.category_id = c.category_id";
            
        if (!empty($itemIds)) {
            $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
            $query .= " WHERE i.item_id IN ($placeholders)";
        }
        
        try {
            $stmt = $this->pdo->prepare($query);
            if (!empty($itemIds)) {
                $stmt->execute($itemIds);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching stock levels: " . $e->getMessage());
        }
    }
    
    /**
     * Get items that need reordering
     * @return array Items below reorder level
     */
    public function getItemsNeedingReorder() {
        $query = "SELECT 
            i.item_id,
            i.item_name,
            i.description,
            i.current_stock,
            i.reorder_level,
            i.unit_price,
            i.unit_measure,
            c.category_name
            FROM inventory_items i
            LEFT JOIN item_categories c ON i.category_id = c.category_id
            WHERE i.current_stock <= i.reorder_level";
            
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching reorder items: " . $e->getMessage());
        }
    }
    
    /**
     * Get purchase history for items
     * @param array $itemIds Array of item IDs to check
     * @param string $startDate Optional start date for history
     * @param string $endDate Optional end date for history
     * @return array Purchase history for requested items
     */
    public function getPurchaseHistory($itemIds = [], $startDate = null, $endDate = null) {
        $query = "SELECT 
            p.purchase_id,
            p.item_id,
            i.item_name,
            p.quantity,
            p.unit_price,
            p.purchase_date,
            p.supplier_id,
            s.supplier_name
            FROM purchases p
            JOIN inventory_items i ON p.item_id = i.item_id
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            WHERE 1=1";
            
        $params = [];
        
        if (!empty($itemIds)) {
            $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
            $query .= " AND p.item_id IN ($placeholders)";
            $params = array_merge($params, $itemIds);
        }
        
        if ($startDate) {
            $query .= " AND p.purchase_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND p.purchase_date <= ?";
            $params[] = $endDate;
        }
        
        $query .= " ORDER BY p.purchase_date DESC";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching purchase history: " . $e->getMessage());
        }
    }
}