<?php
final class MaintenanceRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /** Fetch all maintenance tasks with room info */
    public function getAll(): array
    {
        $sql = "
            SELECT
                hm.request_id AS maintenance_id,
                hm.room_id,
                hm.reported_by,
                hm.issue_description AS issue,
                hm.priority,
                hm.status,
                hm.reported_date,
                hm.completed_date,
                r.room_number
            FROM maintenance_requests hm
                LEFT JOIN rooms r ON hm.room_id = r.room_id
            ORDER BY hm.reported_date DESC
        ";
        $res = $this->db->query($sql);
        if (!$res) throw new RuntimeException("Query failed: ".$this->db->error);

        $tasks = [];
        while ($row = $res->fetch_assoc()) {
            $tasks[] = $row;
        }
        return $tasks;
    }

    /** Add new task */
    public function addTask(int $room_id, string $issue, string $reported_date, ?string $remarks): void
    {
        // reported_by is required in schema; use a default reporter (1) when not provided.
        $reported_by = 1;
        $priority = 'Low';
        $status = 'Pending';
        $stmt = $this->db->prepare(
            "INSERT INTO maintenance_requests (room_id, issue_description, reported_by, priority, status, reported_date, completed_date) VALUES (?, ?, ?, ?, ?, ?, NULL)"
        );
        $stmt->bind_param("isisis", $room_id, $issue, $reported_by, $priority, $status, $reported_date);
        $stmt->execute();
    }

    /** Update task */
    public function updateTask(int $id, int $room_id, string $issue, ?string $remarks): void
    {
        // Schema does not have a 'remarks' column; update the issue_description and room_id
        $stmt = $this->db->prepare(
            "UPDATE maintenance_requests SET room_id=?, issue_description=? WHERE request_id=?"
        );
        $stmt->bind_param("isi", $room_id, $issue, $id);
        $stmt->execute();
    }

    /** Update task status */
    public function updateStatus(int $id, string $status, ?string $completed_date): void
    {
        $stmt = $this->db->prepare(
            "UPDATE maintenance_requests SET status=?, completed_date=? WHERE request_id=?"
        );
        $stmt->bind_param("ssi", $status, $completed_date, $id);
        $stmt->execute();
    }

    /** Delete task */
    public function deleteTask(int $id): void
    {
    $stmt = $this->db->prepare("DELETE FROM maintenance_requests WHERE request_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    }

    /** Stats */
    public function getCounts(): array
    {
        // Map DB statuses to UI keys; DB uses 'Resolved' where UI expects 'Completed'
        $defaults = ['Total'=>0, 'Pending'=>0, 'In Progress'=>0, 'Completed'=>0];
        $res = $this->db->query("SELECT status, COUNT(*) as c FROM maintenance_requests GROUP BY status");
        if ($res) {
            while($row = $res->fetch_assoc()) {
                $key = $row['status'] === 'Resolved' ? 'Completed' : $row['status'];
                if (!isset($defaults[$key])) $defaults[$key] = 0;
                $defaults[$key] = (int)$row['c'];
                $defaults['Total'] += (int)$row['c'];
            }
        }
        return $defaults;
    }

    /** Completed tasks by period */
    public function getCompletedCounts(): array
    {
        // DB marks completed maintenance as 'Resolved'
        $yesterday = $this->db->query(
            "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Resolved' AND completed_date = CURDATE() - INTERVAL 1 DAY"
        )->fetch_assoc()['c'] ?? 0;

        $last3Days = $this->db->query(
            "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Resolved' AND completed_date >= CURDATE() - INTERVAL 3 DAY"
        )->fetch_assoc()['c'] ?? 0;

        $lastMonth = $this->db->query(
            "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Resolved' AND MONTH(completed_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(completed_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)"
        )->fetch_assoc()['c'] ?? 0;

        return [
            'yesterday' => (int)$yesterday,
            'last3Days' => (int)$last3Days,
            'lastMonth' => (int)$lastMonth
        ];
    }

    /** Count for selected month */
    public function getSelectedMonthCount(int $month): int
    {
        $res = $this->db->query("
            SELECT COUNT(*) as c FROM maintenance_requests 
            WHERE status='Resolved' AND MONTH(completed_date) = $month AND YEAR(completed_date) = YEAR(CURDATE())
        ");
        if (!$res) return 0;
        $row = $res->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }

    /** Fetch rooms */
    public function getRooms(): array
    {
        $res = $this->db->query("SELECT room_id, room_number FROM rooms ORDER BY room_number ASC");
        $rooms = [];
        while($r = $res->fetch_assoc()) $rooms[] = $r;
        return $rooms;
    }
}
