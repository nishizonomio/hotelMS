<?php
class TaskManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Fetch tasks with pagination
    public function getTasks($limit = 10, $offset = 0) {
                $query = "SELECT 
                                        ht.task_id, ht.room_id, ht.staff_id, ht.task_date, ht.task_type, ht.status, ht.remarks,
                                        COALESCE(CONCAT(s.first_name, ' ', s.last_name), CONCAT('Staff ID: ', ht.staff_id)) as staff_name,
                                        COALESCE(r.room_number, 'Pending') as room_number
                                    FROM housekeeping_tasks ht
                                    LEFT JOIN rooms r ON ht.room_id = r.room_id
                                    LEFT JOIN staff s ON ht.staff_id = s.staff_id
                                    ORDER BY ht.task_date DESC
                                    LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }


    public function addTask($room_id, $task_type, $staff_id) {
    return $this->saveTask([
        "room_id" => $room_id,
        "task_type" => $task_type,
        "staff_id" => $staff_id,
        "task_date" => date("Y-m-d"),
        "status" => "Pending",
        "remarks" => ""
    ]);
}

public function updateTask($task_id, $room_id, $task_type, $staff_id, $status) {
    return $this->saveTask([
        "task_id" => $task_id,
        "room_id" => $room_id,
        "task_type" => $task_type,
        "staff_id" => $staff_id,
        "task_date" => date("Y-m-d"),
        "status" => $status,
        "remarks" => ""
    ]);
}


    // Add or Update Task
    public function saveTask($data) {
        try {
            if (!empty($data['task_id'])) {
                $stmt = $this->conn->prepare("UPDATE housekeeping_tasks 
                    SET room_id=?, task_type=?, staff_id=?, task_date=?, status=?, remarks=? 
                    WHERE task_id=?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->conn->error);
                }
                $stmt->bind_param("isisssi", 
                    $data['room_id'], 
                    $data['task_type'], 
                    $data['staff_id'], 
                    $data['task_date'], 
                    $data['status'], 
                    $data['remarks'], 
                    $data['task_id']
                );
            } else {
                $stmt = $this->conn->prepare("INSERT INTO housekeeping_tasks 
                    (room_id, task_type, staff_id, task_date, status, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->conn->error);
                }
                $stmt->bind_param("isisss", 
                    $data['room_id'], 
                    $data['task_type'], 
                    $data['staff_id'], 
                    $data['task_date'], 
                    $data['status'], 
                    $data['remarks']
                );
            }
            
            $success = $stmt->execute();
            if (!$success) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            return true;
            
        } catch (Exception $e) {
            error_log("TaskManager saveTask error: " . $e->getMessage());
            throw $e;
        }
    }

    // Delete Task
    public function deleteTask($task_id) {
        $stmt = $this->conn->prepare("DELETE FROM housekeeping_tasks WHERE task_id=?");
        $stmt->bind_param("i", $task_id);
        return $stmt->execute();
    }

    // Mark Completed
    public function completeTask($task_id) {
        $stmt = $this->conn->prepare("UPDATE housekeeping_tasks SET status='Completed' WHERE task_id=?");
        $stmt->bind_param("i", $task_id);
        return $stmt->execute();
    }

    // Fetch tasks with pagination



    // Get total task count
    public function getTaskCount() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM housekeeping_tasks");
        return $result->fetch_assoc()['total'] ?? 0;
    }

    // Get task statistics
    public function getTaskStats() {
        $stats = ["Pending" => 0, "In Progress" => 0, "Completed" => 0];
        $query = "SELECT status, COUNT(*) as count FROM housekeeping_tasks GROUP BY status";
        $result = $this->conn->query($query);
        while ($row = $result->fetch_assoc()) {
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = (int)$row['count'];
            }
        }
        return $stats;
    }

    public function markComplete($task_id) {
    $stmt = $this->conn->prepare("UPDATE housekeeping_tasks SET status='Completed' WHERE task_id=?");
        $stmt->bind_param("i", $task_id);
        return $stmt->execute();
    }

    // Dropdown helpers
    public function getRooms() {
        return $this->conn->query("SELECT room_id, room_number FROM rooms ORDER BY room_number ASC");
    }

    public function getStaff() {
    return $this->conn->query("SELECT staff_id, CONCAT(first_name, ' ', last_name) AS staff_name FROM staff ORDER BY first_name, last_name ASC");
    }
}
