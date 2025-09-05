<?php
class StaffPerformanceRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ================== INSERT NEW PERFORMANCE ==================
    public function insert(array $performance) {
        $stmt = $this->conn->prepare("
            INSERT INTO staff_performance
            (staff_id, task_id, date, tasks_completed, avg_time_minutes, quality_rating, feedback, evaluator_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisisiis",
            $performance['staff_id'],
            $performance['task_id'],
            $performance['date'],
            $performance['tasks_completed'],
            $performance['avg_time_minutes'],
            $performance['quality_rating'],
            $performance['feedback'],
            $performance['evaluator_id']
        );
        $stmt->execute();
        $stmt->close();
    }

    // ================== UPDATE PERFORMANCE ==================
    public function update(array $performance) {
        $stmt = $this->conn->prepare("
            UPDATE staff_performance
            SET staff_id=?, task_id=?, date=?, tasks_completed=?, avg_time_minutes=?, quality_rating=?, feedback=?, evaluator_id=?
            WHERE perf_id=?
        ");
        $stmt->bind_param(
            "iisisiisi",
            $performance['staff_id'],
            $performance['task_id'],
            $performance['date'],
            $performance['tasks_completed'],
            $performance['avg_time_minutes'],
            $performance['quality_rating'],
            $performance['feedback'],
            $performance['evaluator_id'],
            $performance['performance_id']
        );
        $stmt->execute();
        $stmt->close();
    }

    // ================== DELETE PERFORMANCE ==================
    public function delete(int $performance_id) {
    $stmt = $this->conn->prepare("DELETE FROM staff_performance WHERE perf_id=?");
        $stmt->bind_param("i", $performance_id);
        $stmt->execute();
        $stmt->close();
    }

    // ================== GET ALL PERFORMANCE ==================
    public function getAll(): array {
        $sql = "
            SELECT spt.*, CONCAT(s.first_name, ' ', s.last_name) AS staff_name, CONCAT(e.first_name, ' ', e.last_name) AS evaluator_name, ht.task_type AS task_name
            FROM staff_performance spt
            JOIN staff s ON spt.staff_id = s.staff_id
            JOIN staff e ON spt.evaluator_id = e.staff_id
            LEFT JOIN housekeeping_tasks ht ON spt.task_id = ht.task_id
            ORDER BY spt.date DESC
        ";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ================== GET STAFF LIST ==================
    public function getStaffList(): array {
    $sql = "SELECT staff_id, CONCAT(first_name, ' ', last_name) AS full_name FROM staff ORDER BY first_name, last_name ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ================== GET TASKS LIST ==================
    public function getTasksList(): array {
        $sql = "SELECT task_id, task_type FROM housekeeping_tasks ORDER BY task_type ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
