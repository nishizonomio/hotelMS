<?php
require_once __DIR__ . '/../config/database.php';

class PaymentGatewayTransactions
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Insert a new gateway transaction log
     */
    public function createTransaction($payment_id, $gateway_name, $gateway_transaction_id, $response_code = null, $response_message = null, $response_payload = null, $status = 'pending')
    {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_gateway_transactions (
                payment_id, gateway_name, gateway_transaction_id, response_code, response_message, response_payload, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }

        $stmt->bind_param(
            "issssss",
            $payment_id,
            $gateway_name,
            $gateway_transaction_id,
            $response_code,
            $response_message,
            $response_payload,
            $status
        );

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return [
                "success" => true,
                "message" => "Gateway transaction recorded successfully.",
                "transaction_id" => $insertId
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                "success" => false,
                "message" => "Failed to record gateway transaction.",
                "error" => $error
            ];
        }
    }

    /**
     * Update the status of a gateway transaction
     */
    public function updateTransactionStatus($transaction_id, $status, $response_payload = null)
    {
        $stmt = $this->conn->prepare("
            UPDATE payment_gateway_transactions
            SET status = ?, response_payload = ?, updated_at = NOW()
            WHERE transaction_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }

        $stmt->bind_param("ssi", $status, $response_payload, $transaction_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Fetch all gateway transactions for a payment
     */
    public function getTransactionsByPaymentId($payment_id)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM payment_gateway_transactions
            WHERE payment_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Fetch a specific transaction by its ID
     */
    public function getTransactionById($transaction_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM payment_gateway_transactions WHERE transaction_id = ?");
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Delete a transaction (useful for cleanup)
     */
    public function deleteTransaction($transaction_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM payment_gateway_transactions WHERE transaction_id = ?");
        $stmt->bind_param("i", $transaction_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
