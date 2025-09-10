<?php
final class SupplyRepository {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function getAll(): array {
    $sql = "SELECT sp.supply_id AS item_id, sp.name AS item_name, sp.category, ss.quantity, sp.unit, sp.reorder_level
        FROM supplies sp
        LEFT JOIN supply_stock ss ON sp.supply_id = ss.supply_id
        ORDER BY sp.supply_id ASC";
    $res = $this->db->query($sql);
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = new Supply($row);
    }
    return $rows;
    }

    public function getCounts(): array {
        // Accept several stored category variations and map them to the UI categories
        $counts = ['total' => 0, 'cleaning' => 0, 'linen' => 0, 'toiletry' => 0];

        $sql = "SELECT category, COUNT(*) AS total FROM supplies GROUP BY category";
        $res = $this->db->query($sql);
        while ($row = $res->fetch_assoc()) {
            $catRaw = strtolower(trim((string)$row['category']));
            $num = (int)$row['total'];
            $counts['total'] += $num;

            // Map possible stored values to canonical keys used by the UI
            if (strpos($catRaw, 'clean') !== false || $catRaw === 'cleaning' || $catRaw === 'cleaning supply') {
                $counts['cleaning'] += $num;
                continue;
            }
            if (strpos($catRaw, 'linen') !== false) {
                $counts['linen'] += $num;
                continue;
            }
            if (strpos($catRaw, 'toiletry') !== false || strpos($catRaw, 'toilet') !== false) {
                $counts['toiletry'] += $num;
                continue;
            }
            // If category doesn't match known ones, keep it counted in total only
        }

        return $counts;
    }

    public function upsert(string $item_name, string $category, int $quantity, string $unit, int $reorder_level): void {
        // Insert into supplies and supply_stock
        $stmt = $this->db->prepare("INSERT INTO supplies (name, description, unit, reorder_level, category) VALUES (?, '', ?, ?, ?)");
        $stmt->bind_param("ssis", $item_name, $unit, $reorder_level, $category);
        $stmt->execute();
        $supply_id = $this->db->insert_id;
        $stmt2 = $this->db->prepare("INSERT INTO supply_stock (supply_id, quantity, last_received) VALUES (?, ?, CURDATE())");
        $stmt2->bind_param("ii", $supply_id, $quantity);
        $stmt2->execute();
    }

    public function update(int $item_id, int $quantity, int $reorder_level): void {
        $stmt = $this->db->prepare("UPDATE supply_stock SET quantity=? WHERE supply_id=?");
        $stmt->bind_param("ii", $quantity, $item_id);
        $stmt->execute();
        $stmt2 = $this->db->prepare("UPDATE supplies SET reorder_level=? WHERE supply_id=?");
        $stmt2->bind_param("ii", $reorder_level, $item_id);
        $stmt2->execute();
    }

    public function delete(int $item_id): void {
        // Delete from supply_stock first due to FK constraint
        $stmt = $this->db->prepare("DELETE FROM supply_stock WHERE supply_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        
        // Then delete from supplies
        $stmt = $this->db->prepare("DELETE FROM supplies WHERE supply_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
    }
}
?>
