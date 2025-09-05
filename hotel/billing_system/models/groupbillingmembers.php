<?php
class GroupBillingMembers {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function add($group_billing_id, $invoice_id, $share_amount) {
        $stmt = $this->conn->prepare("INSERT INTO group_billing_members (group_billing_id, invoice_id, share_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $group_billing_id, $invoice_id, $share_amount);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function getByGroup($group_billing_id) {
        $stmt = $this->conn->prepare("SELECT * FROM group_billing_members WHERE group_billing_id = ?");
        $stmt->bind_param("i", $group_billing_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function update($group_billing_id, $invoice_id, $share_amount) {
        $stmt = $this->conn->prepare("UPDATE group_billing_members SET share_amount = ? WHERE group_billing_id = ? AND invoice_id = ?");
        $stmt->bind_param("dii", $share_amount, $group_billing_id, $invoice_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function delete($group_billing_id, $invoice_id) {
        $stmt = $this->conn->prepare("DELETE FROM group_billing_members WHERE group_billing_id = ? AND invoice_id = ?");
        $stmt->bind_param("ii", $group_billing_id, $invoice_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
