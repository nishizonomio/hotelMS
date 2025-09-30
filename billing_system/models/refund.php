<?php

require_once __DIR__ . '/../config/database.php';

class Refund
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Create a new refund request (default status = pending)
     */
    public function createRefund($payment_id, $invoice_id, $amount, $method, $reason, $processed_by)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO refunds (payment_id, invoice_id, refund_amount, refund_method, refund_reason, processed_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iidssi", $payment_id, $invoice_id, $amount, $method, $reason, $processed_by);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Get refund by ID
     */
    public function getRefundById($refund_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM refunds WHERE refund_id = ?");
        $stmt->bind_param("i", $refund_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Get all refunds for a payment
     */
    public function getRefundsByPaymentId($payment_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM refunds WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Get all pending refund requests
     */
    public function getPendingRefunds()
    {
        $query = "SELECT * FROM refunds WHERE status = 'pending' ORDER BY refund_date DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update refund status (approve/decline)
     */
    public function updateStatus($refund_id, $status, $processed_by = null)
    {
        $stmt = $this->conn->prepare("UPDATE refunds SET status = ?, processed_by = ? WHERE refund_id = ?");
        $stmt->bind_param("sii", $status, $processed_by, $refund_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Delete a refund record
     */
    public function deleteRefund($refund_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM refunds WHERE refund_id = ?");
        $stmt->bind_param("i", $refund_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
