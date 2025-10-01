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
    public function createRefund($payment_id, $invoice_id, $refund_amount, $refund_method, $refund_reason, $processed_by = null, $status = 'pending')
    {
        // Ensure processed_by is an integer (0 if null)
        $processed_by_val = $processed_by ?? 0;

        // Prepare statement with 7 placeholders
        $stmt = $this->conn->prepare("
        INSERT INTO refunds 
        (payment_id, invoice_id, refund_amount, refund_method, refund_reason, processed_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        // Bind parameters: i=int, d=double, s=string
        $stmt->bind_param(
            "iidssis",
            $payment_id,     // int
            $invoice_id,     // int
            $refund_amount,  // double
            $refund_method,  // string
            $refund_reason,  // string
            $processed_by_val, // int
            $status          // string
        );

        $executeResult = $stmt->execute();

        if (!$executeResult) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

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

    public function getRefundsByStatus($status)
    {
        $stmt = $this->conn->prepare("SELECT * FROM refunds WHERE status = ? ORDER BY refund_date DESC");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getAllRefunds()
    {
        $query = "SELECT * FROM refunds ORDER BY refund_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
    public function getPaymentsByStatus($status)
    {
        $stmt = $this->conn->prepare("SELECT * FROM payments WHERE status = ? ORDER BY payment_datetime DESC");
        $stmt->bind_param("s", $status);
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

    public function updateRefundStatus($refund_id, $status)
    {
        $stmt = $this->conn->prepare("UPDATE refunds SET status = ? WHERE refund_id = ?");
        $stmt->bind_param("si", $status, $refund_id);
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
