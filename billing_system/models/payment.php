<?php
require_once __DIR__ . '/../config/database.php';

class Payments
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function createPayment(
        $invoice_id,
        $amount_paid,
        $payment_method,
        $group_billing_id = null,
        $status = 'succeeded'
    ) {
        $payment_datetime = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("
            INSERT INTO payments (
                invoice_id, group_billing_id, payment_method, amount_paid, 
                payment_datetime, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }

        $stmt->bind_param(
            "iisdss",
            $invoice_id,
            $group_billing_id,
            $payment_method,
            $amount_paid,
            $payment_datetime,
            $status
        );

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

    public function getCancelledPaymentsById()
    {
        $stmt = $this->conn->prepare("
        SELECT * FROM payments WHERE payment_id = ? AND status = 'cancelled'
    ");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function updatePayment($payment_id, $payment_method, $amount_paid, $status = 'succeeded')
    {
        $stmt = $this->conn->prepare("
            UPDATE payments 
            SET payment_method = ?, amount_paid = ?, status = ?
            WHERE payment_id = ?
        ");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("sdsi", $payment_method, $amount_paid, $status, $payment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updatePaymentStatusByInvoice($invoice_id, $status)
    {
        try {
            $stmt = $this->conn->prepare("
            UPDATE payments 
            SET status = ?, payment_datetime = NOW() 
            WHERE invoice_id = ? 
              AND status = 'pending'
            ORDER BY payment_id DESC 
            LIMIT 1
        ");
            $stmt->bind_param("si", $status, $invoice_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                return ["success" => true, "message" => "Payment status updated to $status."];
            } else {
                return ["success" => false, "message" => "No pending payment found for invoice."];
            }
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }


    public function updatePaymentStatus($payment_id, $status)
    {
        $stmt = $this->conn->prepare("
            UPDATE payments
            SET status = ?
            WHERE payment_id = ?
        ");
        $stmt->bind_param("si", $status, $payment_id);
        return $stmt->execute();
    }

    public function getPaymentByStatus($status)
    {
        $stmt = $this->conn->prepare("SELECT p.*, i.invoice_id, CONCAT(g.first_name, ' ', g.last_name) AS guest_name
        FROM payments p
        LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
        LEFT JOIN bookings b ON i.booking_id = b.booking_id
        LEFT JOIN guests g ON b.guest_id = g.guest_id
        WHERE p.status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getPaymentsById($payment_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getPaymentsByInvoiceId($invoice_id)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM payments 
            WHERE invoice_id = ? 
            ORDER BY payment_date DESC, payment_time DESC
        ");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getRecentPayments($limit = 5)
    {
        $stmt = $this->conn->prepare("
        SELECT p.*, 
               i.invoice_id, 
               CONCAT(g.first_name, ' ', g.last_name) AS guest_name
        FROM payments p
        JOIN invoices i ON p.invoice_id = i.invoice_id
        JOIN bookings b ON i.booking_id = b.booking_id
        JOIN guests g ON b.guest_id = g.guest_id
        ORDER BY p.payment_datetime DESC
        LIMIT ?
    ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getAllPayments()
    {
        $stmt = $this->conn->prepare("SELECT * FROM payments ORDER BY payment_date DESC, payment_time DESC");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getByGuest($guestId)
    {
        $stmt = $this->conn->prepare("
            SELECT p.* FROM payments p
            JOIN invoices i ON p.invoice_id = i.invoice_id
            JOIN bookings b ON i.booking_id = b.booking_id
            WHERE b.guest_id = ?
        ");
        $stmt->bind_param("i", $guestId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getTotalPaidByInvoice($invoice_id)
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount_paid), 0) as totalPaid 
        FROM payments WHERE invoice_id = ?");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['totalPaid'] ?? 0;
    }


    public function deletePayment($payment_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
