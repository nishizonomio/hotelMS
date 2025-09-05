<?php

require_once __DIR__.'/../config/database.php';

class Payments {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function createPayment($invoice_id, $payment_date, $payment_time, $amount, $payment_method, $group_billing_id) {
    if (!$group_billing_id) {
        $group_billing_id = null;
    }
    $stmt = $this->conn->prepare("
        INSERT INTO payments (invoice_id, payment_date, payment_time, amount_paid, payment_method, group_billing_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $this->conn->error);
    }
    $stmt->bind_param("issdsi", $invoice_id, $payment_date, $payment_time, $amount, $payment_method, $group_billing_id);

    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();
        return [
            "success" => true,
            "message" => "Payment recorded successfully.",
            "payment_id" => $insertId
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return [
            "success" => false,
            "message" => "Failed to record payment.",
            "error" => $error
        ];
    }
}


    public function getPaymentsById($payment_id) {
        $stmt = $this->conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getPaymentsByInvoiceId($invoice_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM payments 
            WHERE invoice_id = ? 
            ORDER BY payment_date DESC, payment_time DESC
        ");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getByGuest($guestId) {
    $stmt = $this->conn->prepare("
        SELECT p.* FROM payments p
        JOIN invoices i ON p.invoice_id = i.invoice_id
        JOIN bookings b ON i.booking_id = b.booking_id
        WHERE b.guest_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $this->conn->error);
    }

    $stmt->bind_param("i", $guestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}


    public function updatePayment($payment_id, $payment_method, $amount) {
        $stmt = $this->conn->prepare("
            UPDATE payments 
            SET payment_method = ?, amount = ? 
            WHERE payment_id = ?
        ");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("sdi", $payment_method, $amount, $payment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deletePayment($payment_id) {
        $stmt = $this->conn->prepare("DELETE FROM payments WHERE payment_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("i", $payment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function handlePayment($method, $amount, $reference, $paymentId) {
        if (!$this->conn) {
            return [
                "success" => false,
                "message" => "Database connection not available.",
                "transaction_id" => null
            ];
        }

        return $this->gateway->processPayment(
            $method,
            $amount,
            $reference,
            $paymentId,
            $this->conn
        );
    }
}
