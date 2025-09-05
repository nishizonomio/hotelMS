<?php
class Refund {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function createRefund($payment_id, $refund_date, $refund_time, $amount, $reason, $status) {
        $stmt = $this->conn->prepare("INSERT INTO refunds (payment_id, refund_date, refund_time, amount, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", $payment_id, $refund_date, $refund_time, $amount, $reason, $status);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function getRefundById($refund_id) {
        $stmt = $this->conn->prepare("SELECT * FROM refunds WHERE refund_id = ?");
        $stmt->bind_param("i", $refund_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getRefundsByPaymentId($payment_id) {
        $stmt = $this->conn->prepare("SELECT * FROM refunds WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function updateStatus($refund_id, $status) {
        $stmt = $this->conn->prepare("UPDATE refunds SET status = ? WHERE refund_id = ?");
        $stmt->bind_param("si", $status, $refund_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function deleteRefund($refund_id) {
        $stmt = $this->conn->prepare("DELETE FROM refunds WHERE refund_id = ?");
        $stmt->bind_param("i", $refund_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
