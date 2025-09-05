<?php
class GroupBilling {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($group_name, $total_group_amount, $date, $time) {
        $stmt = $this->conn->prepare("INSERT INTO group_billing (group_name, total_group_amount, date, time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $group_name, $total_group_amount, $date, $time);
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        return $insertId;
    }

    public function getById($group_billing_id) {
        $stmt = $this->conn->prepare("SELECT * FROM group_billing WHERE group_billing_id = ?");
        $stmt->bind_param("i", $group_billing_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM group_billing ORDER BY date DESC, time DESC");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function delete($group_billing_id) {
        $stmt = $this->conn->prepare("DELETE FROM group_billing WHERE group_billing_id = ?");
        $stmt->bind_param("i", $group_billing_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
