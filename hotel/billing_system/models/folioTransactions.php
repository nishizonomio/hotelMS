<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/invoice.php';

class FolioTransactions {
    private $conn;

    public function __construct(){
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function createFolioTransaction($invoice_id, $service_type, $description, $transaction_date, $transaction_time, $amount) {
        $stmt = $this->conn->prepare("INSERT INTO folio_transactions (invoice_id, service_type, description, transaction_date, transaction_time, amount)
        VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issssd", $invoice_id, $service_type, $description, $transaction_date, $transaction_time,$amount);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getFolioTransactionsByInvoiceId($invoice_id) {
        $stmt = $this->conn->prepare("SELECT * FROM folio_transactions WHERE invoice_id = ? ORDER BY transaction_date DESC,
        transaction_time DESC");
        if(!$stmt) {
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getTotalByInvoiceId($invoice_id) {
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM folio_transactions WHERE invoice_id = ?");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        return $result ? $result : 0;
    }

    public function deleteFolioTransaction($transaction_id) {
        $stmt = $this->conn->prepare("DELETE FROM folio_transactions WHERE transaction_id = ?");
        $stmt->bind_param("i", $transaction_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updateFolioTransaction($transaction_id, $service_type, $description, $transaction_date, $transaction_time, $amount) {
        $stmt = $this->conn->prepare("UPDATE folio_transactions SET service_type = ?, description = ?, transaction_date = ?,
        transaction_time = ?, amount = ? WHERE transaction_id = ?");
        if(!$stmt) {
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("ssssdi", $service_type, $description, $transaction_date, $transaction_time, $amount, $transaction_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}