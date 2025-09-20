<?php
namespace Housekeeping\Supplies\Services;

class SupplyReportingService {
    private $db;
    
    public function __construct(\PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Get staff supply usage efficiency report
     */
    public function getStaffSupplyEfficiency(
        ?int $staffId = null,
        ?string $dateRange = 'last_30_days'
    ): array {
        $where = [];
        $params = [];
        
        // Date range
        switch ($dateRange) {
            case 'last_7_days':
                $where[] = "su.used_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $where[] = "su.used_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')";
                break;
            default: // last_30_days
                $where[] = "su.used_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
        }
        
        // Staff filter
        if ($staffId) {
            $where[] = "su.used_by = ?";
            $params[] = $staffId;
        }
        
        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "
            SELECT 
                s.staff_id,
                CONCAT(s.first_name, ' ', s.last_name) as staff_name,
                COUNT(DISTINCT su.task_id) as total_tasks,
                COUNT(su.usage_id) as total_supply_uses,
                AVG(su.performance_score) as avg_efficiency,
                SUM(su.quantity_used * COALESCE(su.cost_at_time, 0)) as total_cost,
                -- Efficiency ratings
                COUNT(CASE WHEN su.performance_score >= 90 THEN 1 END) as excellent_uses,
                COUNT(CASE WHEN su.performance_score >= 75 AND su.performance_score < 90 THEN 1 END) as good_uses,
                COUNT(CASE WHEN su.performance_score < 75 THEN 1 END) as needs_improvement
            FROM supply_usage su
            JOIN staff s ON s.staff_id = su.used_by
            $whereClause
            GROUP BY s.staff_id
            ORDER BY avg_efficiency DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get supply consumption trends
     */
    public function getSupplyTrends(string $category = null): array {
        $where = [];
        $params = [];
        
        if ($category) {
            $where[] = "s.category = ?";
            $params[] = $category;
        }
        
        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "
            SELECT 
                s.supply_id,
                s.name as supply_name,
                s.category,
                -- Current month
                SUM(CASE 
                    WHEN su.used_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    THEN su.quantity_used ELSE 0 
                END) as current_month_usage,
                -- Previous month
                SUM(CASE 
                    WHEN su.used_at >= DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH), '%Y-%m-01')
                    AND su.used_at < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    THEN su.quantity_used ELSE 0 
                END) as previous_month_usage,
                -- Costs
                AVG(su.cost_at_time) as avg_unit_cost,
                s.reorder_level,
                s.department_stock as current_stock
            FROM supplies s
            LEFT JOIN supply_usage su ON su.supply_id = s.supply_id
            $whereClause
            GROUP BY s.supply_id
            ORDER BY current_month_usage DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get task-based supply efficiency report
     */
    public function getTaskSupplyEfficiency(): array {
        $query = "
            SELECT 
                ht.task_type,
                s.category,
                COUNT(DISTINCT ht.task_id) as total_tasks,
                AVG(su.performance_score) as avg_efficiency,
                -- Deviation from standards
                AVG(
                    CASE WHEN sts.expected_quantity > 0
                    THEN ((su.quantity_used - sts.expected_quantity) / sts.expected_quantity) * 100
                    ELSE 0 END
                ) as avg_deviation_percent
            FROM housekeeping_tasks ht
            JOIN supply_usage su ON su.task_id = ht.task_id
            JOIN supplies s ON s.supply_id = su.supply_id
            LEFT JOIN supply_task_standards sts 
                ON sts.task_type = ht.task_type 
                AND sts.supply_id = s.supply_id
            GROUP BY ht.task_type, s.category
            ORDER BY avg_efficiency DESC
        ";
        
        return $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get pending supply requests report
     */
    public function getPendingRequestsReport(): array {
        $query = "
            SELECT 
                sr.request_id,
                s.name as supply_name,
                sr.quantity_requested,
                sr.priority,
                sr.workflow_state,
                sr.requested_at,
                CONCAT(staff.first_name, ' ', staff.last_name) as requested_by,
                ht.task_type,
                ht.room_id,
                -- Urgency score (based on stock levels and priority)
                CASE 
                    WHEN s.department_stock <= s.reorder_level AND sr.priority = 'high' THEN 3
                    WHEN s.department_stock <= s.reorder_level THEN 2
                    WHEN sr.priority = 'high' THEN 2
                    ELSE 1
                END as urgency_score
            FROM supply_requests sr
            JOIN supplies s ON s.supply_id = sr.supply_id
            LEFT JOIN staff ON staff.staff_id = sr.requested_by
            LEFT JOIN housekeeping_tasks ht ON ht.task_id = sr.task_id
            WHERE sr.workflow_state = 'pending'
            ORDER BY urgency_score DESC, sr.requested_at ASC
        ";
        
        return $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }
}