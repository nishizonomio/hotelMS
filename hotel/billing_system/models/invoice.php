<?php

require_once __DIR__.'/../config/database.php';

class Invoice {
    private $conn;

    public function __construct(){
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function createInvoice($booking_id, $invoice_date, $invoice_time, $total_amount, $status) {
        $stmt = $this->conn->prepare("INSERT INTO invoices (booking_id, invoice_date, invoice_time, total_amount, status) 
        VALUES (?,?,?,?,?)");
        if(!$stmt){
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("issds", $booking_id, $invoice_date, $invoice_time, $total_amount, $status);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getInvoiceById($invoice_id) {
        $stmt = $this->conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
         if(!$stmt){
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getBookingById($booking_id) {
        $stmt =$this->conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
         if(!$stmt){
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    

    public function updateInvoiceStatus($invoice_id, $status) {
        $stmt = $this->conn->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
        if(!$stmt){
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("si", $status, $invoice_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteInvoice($invoice_id) {
        $stmt = $this->conn->prepare("DELETE FROM invoices WHERE invoice_id = ?");
        if(!$stmt){
            throw new Exception("Error preparing statement: ".$this->conn->error);
        }
        $stmt->bind_param("i", $invoice_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
