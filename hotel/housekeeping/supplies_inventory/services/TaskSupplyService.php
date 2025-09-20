<?php
namespace Housekeeping\Supplies\Services;

class TaskSupplyService {
    private $db;
    private $supplyTrackingService;
    
    public function __construct(\PDO $db, SupplyTrackingService $supplyTrackingService) {
        $this->db = $db;
        $this->supplyTrackingService = $supplyTrackingService;
    }
    
    /**
     * Record supplies used for a specific task
     */
    public function recordTaskSupplyUsage(
        int $taskId,
        int $supplyId,
        int $quantity,
        int $staffId,
        ?string $notes = null
    ): bool {
        try {
            $this->db->beginTransaction();
            
            // Record basic usage
            $usageResult = $this->supplyTrackingService->recordUsage(
                $supplyId,
                $quantity,
                $staffId,
                $notes
            );
            
            if (!$usageResult) {
                throw new \Exception("Failed to record supply usage");
            }
            
            // Get current supply cost for historical tracking
            $stmt = $this->db->prepare("
                SELECT unit_price 
                FROM inventory i
                JOIN supplies s ON s.inventory_item_id = i.item_id
                WHERE s.supply_id = ?
            ");
            $stmt->execute([$supplyId]);
            $cost = $stmt->fetchColumn() ?: 0;
            
            // Update usage record with task context
            $stmt = $this->db->prepare("
                UPDATE supply_usage 
                SET task_id = ?,
                    cost_at_time = ?
                WHERE supply_id = ?
                ORDER BY used_at DESC
                LIMIT 1
            ");
            $stmt->execute([$taskId, $cost, $supplyId]);
            
            // Check against standard usage
            $this->evaluateTaskEfficiency($taskId, $supplyId, $quantity);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Failed to record task supply usage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Evaluate supply usage efficiency against standards
     */
    private function evaluateTaskEfficiency(int $taskId, int $supplyId, int $actualQuantity): void {
        // Get task type
        $stmt = $this->db->prepare("
            SELECT task_type 
            FROM housekeeping_tasks 
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        $taskType = $stmt->fetchColumn();
        
        if (!$taskType) return;
        
        // Compare against standard
        $stmt = $this->db->prepare("
            SELECT expected_quantity 
            FROM supply_task_standards
            WHERE task_type = ? AND supply_id = ?
        ");
        $stmt->execute([$taskType, $supplyId]);
        $expectedQuantity = $stmt->fetchColumn();
        
        if (!$expectedQuantity) return;
        
        // Calculate efficiency score (0-100)
        $efficiency = min(100, (($expectedQuantity / max(1, $actualQuantity)) * 100));
        
        // Update usage record with performance score
        $stmt = $this->db->prepare("
            UPDATE supply_usage 
            SET performance_score = ?
            WHERE task_id = ? AND supply_id = ?
            ORDER BY used_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$efficiency, $taskId, $supplyId]);
    }
    
    /**
     * Get supply usage statistics for a task type
     */
    public function getTaskTypeSupplyStats(string $taskType): array {
        $query = "
            SELECT 
                s.name as supply_name,
                s.category,
                COUNT(su.usage_id) as times_used,
                AVG(su.quantity_used) as avg_quantity,
                AVG(su.performance_score) as avg_efficiency,
                sts.expected_quantity as standard_quantity
            FROM housekeeping_tasks ht
            JOIN supply_usage su ON su.task_id = ht.task_id
            JOIN supplies s ON s.supply_id = su.supply_id
            LEFT JOIN supply_task_standards sts 
                ON sts.task_type = ht.task_type 
                AND sts.supply_id = s.supply_id
            WHERE ht.task_type = ?
            GROUP BY s.supply_id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$taskType]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Request supplies for a specific task
     */
    public function requestTaskSupplies(
        int $taskId,
        int $supplyId,
        int $quantity,
        string $priority = 'medium',
        ?string $notes = null
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO supply_requests (
                supply_id, quantity_requested, task_id,
                priority, notes, workflow_state
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        
        return $stmt->execute([
            $supplyId, $quantity, $taskId,
            $priority, $notes
        ]);
    }
}